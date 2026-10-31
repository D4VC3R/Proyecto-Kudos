<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Ítems similares por contenido (F2-08): misma categoría y coincidencia de géneros, dirección/creación y década.
 * La lista de IDs de cada ítem se calcula al vuelo en PostgreSQL y se cachea (no hay tabla item_similarities).
 */
class SimilarItemsService
{
    /**
     * Década del candidato: año de released_at o, si no hay, attributes.year
     * (mismo criterio que el slug, Item::slugYear()), dividido entre 10.
     */
    private const DECADE_SQL = "(COALESCE(EXTRACT(YEAR FROM items.released_at)::int, CASE WHEN items.attributes->>'year' ~ '^[0-9]{1,4}$' THEN (items.attributes->>'year')::int END) / 10)";

    /**
     * Similares de un ítem para mostrar en su ficha: hasta catalog.similar.limit ítems elegibles, del más al
     * menos parecido, con la categoría cargada y, si hay sesión, el voto del usuario en userVote.
     * Una categoría inactiva no tiene similares (no sale en la API pública).
     *
     * @param Item $item Ítem de la ficha.
     * @param User|null $viewer Usuario con sesión, si lo hay.
     * @param int|null $limit Máximo de ítems (por defecto catalog.similar.limit).
     * @return Collection<int, Item>
     */
    public function similarTo(Item $item, ?User $viewer = null, ?int $limit = null): Collection
    {
        $limit ??= (int) config('catalog.similar.limit', 12);

        if ($limit <= 0) {
            return collect();
        }

        $item->loadMissing('category');

        if (! $item->category?->is_active) {
            return collect();
        }

        return $this->eligibleItems($item, $limit, $viewer);
    }

    /**
     * Ítems similares todavía elegibles, en el orden de similitud de la caché. Como la caché dura horas,
     * se vuelve a comprobar que sigan activos, descubribles y de la misma categoría.
     *
     * @param Item $item Ítem de origen.
     * @param int $limit Máximo de ítems.
     * @param User|null $viewer Usuario con sesión: se carga su voto en userVote.
     * @param array<int, string> $excludeIds IDs que no deben salir.
     * @param bool $excludeVotedByViewer Si es true, quita los ítems con voto (de cualquier tipo) del usuario.
     * @return Collection<int, Item>
     */
    public function eligibleItems(Item $item, int $limit, ?User $viewer = null, array $excludeIds = [], bool $excludeVotedByViewer = false): Collection
    {
        $ids = array_values(array_diff($this->similarIds($item), $excludeIds));

        if ($ids === [] || $limit <= 0) {
            return collect();
        }

        $byId = Item::query()
            ->whereIn('id', $ids)
            ->where('category_id', $item->category_id)
            ->active()
            ->discoverable()
            ->when($excludeVotedByViewer && $viewer, fn ($query) => $query->whereDoesntHave('votes', fn ($votes) => $votes->where('user_id', $viewer->id)))
            ->with(['category'])
            ->when($viewer, fn ($query) => $query->with(['userVote' => fn ($vote) => $vote->where('user_id', $viewer->id)]))
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn (string $id) => $byId->get($id))->filter()->take($limit)->values();
    }

    /**
     * IDs de los ítems más parecidos (hasta catalog.similar.cache_size), del más al menos parecido,
     * cacheados catalog.similar.cache_hours horas. No se invalida al votar, importar o editar ítems.
     *
     * @return array<int, string>
     */
    public function similarIds(Item $item): array
    {
        return Cache::remember(
            self::cacheKey($item->id),
            now()->addHours((int) config('catalog.similar.cache_hours', 24)),
            fn () => $this->computeSimilarIds($item, (int) config('catalog.similar.cache_size', 60))
        );
    }

    /**
     * Clave de caché de la lista de similares de un ítem.
     */
    public static function cacheKey(string $itemId): string
    {
        return "items:{$itemId}:similar";
    }

    /**
     * Calcula los IDs similares en una sola consulta. Puntuación del candidato (misma categoría, activo,
     * descubrible y distinto del origen):
     *   géneros en común (sin repetir) × peso genre
     *   + peso people si comparte alguna persona de attributes.directors o attributes.creators
     *   + peso decade si es de la misma década.
     * Cada término solo se añade si el origen tiene ese dato. Solo cuentan los candidatos con puntuación > 0;
     * desempate por bayes_score, popularity e id. Los pesos son enteros de config incrustados en el SQL
     * (evita problemas de inferencia de tipos en PostgreSQL); los valores del ítem van siempre como bindings.
     * No se usa el operador jsonb ?| porque PDO lo confunde con un placeholder: se usa jsonb_exists_any.
     * Con whereRaw + orderByRaw (y no selectRaw + pluck) cada grupo lleva sus propios bindings.
     *
     * @return array<int, string>
     */
    private function computeSimilarIds(Item $item, int $limit): array
    {
        $attributes = $item->getAttribute('attributes');
        $attributes = is_array($attributes) ? $attributes : [];

        $genres = $this->stringList($attributes['genres'] ?? null);
        $people = array_values(array_unique(array_merge(
            $this->stringList($attributes['directors'] ?? null),
            $this->stringList($attributes['creators'] ?? null)
        )));
        $decade = $this->decade($item);

        $weights = config('catalog.similar.weights');
        $genreWeight = (int) ($weights['genre'] ?? 1);
        $peopleWeight = (int) ($weights['people'] ?? 2);
        $decadeWeight = (int) ($weights['decade'] ?? 1);

        $terms = [];
        $bindings = [];

        if ($genres !== []) {
            // Géneros del candidato que están en los del origen (sin repetir)
            $terms[] = '(SELECT count(DISTINCT g.name) FROM jsonb_array_elements_text(' . self::jsonArraySql("items.attributes->'genres'") . ') AS g(name)'
                . ' WHERE g.name IN (SELECT jsonb_array_elements_text(CAST(? AS jsonb)))) * ' . $genreWeight;
            $bindings[] = json_encode($genres, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        if ($people !== []) {
            // jsonb_exists_any en lugar del operador ?| (PDO lo confunde con un placeholder)
            $terms[] = 'CASE WHEN jsonb_exists_any(' . self::jsonArraySql("items.attributes->'directors'") . ' || ' . self::jsonArraySql("items.attributes->'creators'")
                . ', ARRAY(SELECT jsonb_array_elements_text(CAST(? AS jsonb)))) THEN ' . $peopleWeight . ' ELSE 0 END';
            $bindings[] = json_encode($people, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        if ($decade !== null) {
            $terms[] = 'CASE WHEN ' . self::DECADE_SQL . ' = CAST(? AS integer) THEN ' . $decadeWeight . ' ELSE 0 END';
            $bindings[] = $decade;
        }

        if ($terms === []) {
            return [];
        }

        $score = '(' . implode(' + ', $terms) . ')';

        return Item::query()
            ->where('category_id', $item->category_id)
            ->whereKeyNot($item->getKey())
            ->active()
            ->discoverable()
            ->whereRaw("{$score} > 0", $bindings)
            ->orderByRaw("{$score} DESC", $bindings)
            ->orderByDesc('bayes_score')
            ->orderByDesc('popularity')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    /**
     * SQL de la expresión si es un array JSON; si no (null, texto, objeto), un array vacío.
     */
    private static function jsonArraySql(string $expression): string
    {
        return "(CASE WHEN jsonb_typeof({$expression}) = 'array' THEN {$expression} ELSE '[]'::jsonb END)";
    }

    /**
     * Lista de textos de un valor: solo los elementos string, recortados, sin vacíos y sin repetir.
     *
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $strings = [];

        foreach ($value as $element) {
            if (is_string($element) && trim($element) !== '') {
                $strings[] = trim($element);
            }
        }

        return array_values(array_unique($strings));
    }

    /**
     * Década del ítem (año / 10): la de released_at o, si no hay, la de attributes.year. Null si no tiene año.
     */
    private function decade(Item $item): ?int
    {
        $year = $item->released_at?->year;

        if ($year === null) {
            $attributes = $item->getAttribute('attributes');
            $attributeYear = is_array($attributes) ? ($attributes['year'] ?? null) : null;

            if (is_int($attributeYear)) {
                $year = $attributeYear;
            } elseif (is_string($attributeYear) && ctype_digit($attributeYear)) {
                $year = (int) $attributeYear;
            }
        }

        return $year === null ? null : intdiv($year, 10);
    }
}
