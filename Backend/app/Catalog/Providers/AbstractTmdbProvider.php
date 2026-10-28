<?php

namespace App\Catalog\Providers;

use App\Catalog\Clients\TmdbClient;
use App\Catalog\Contracts\CatalogProvider;
use App\Catalog\Data\CatalogItemData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Locale;
use UnexpectedValueException;

/**
 * Base común de los proveedores de TMDB (películas y series): mapeo al DTO, imágenes, géneros en
 * español cacheados y paginación de los listados. Nunca devuelve el payload en bruto (términos de TMDB)
 * ni la sinopsis en inglés: TmdbClient pide siempre es-ES y un overview vacío se queda en null.
 */
abstract class AbstractTmdbProvider implements CatalogProvider
{
    public const PROVIDER_KEY = 'tmdb';

    public const SITE_URL = 'https://www.themoviedb.org';

    public const IMAGE_BASE_URL = 'https://image.tmdb.org/t/p';

    public const POSTER_SIZE = 'w780';

    public const BANNER_SIZE = 'w1280';

    /** Licencia de las imágenes de TMDB (base-de-datos §3, items.images). */
    public const IMAGE_LICENSE = '© titular de derechos';

    /** Máximo de candidatos de search() (pipeline §3: ~10). */
    public const SEARCH_LIMIT = 10;

    /** Región de los listados de novedades (fuentes-externas §4). */
    public const REGION = 'ES';

    /** TMDB no sirve páginas por encima de la 500. */
    protected const MAX_PAGES = 500;

    public function __construct(protected readonly TmdbClient $client) {}

    /** 'movie' o 'tv': prefijo del external_id y segmento de las rutas de TMDB. */
    abstract protected function mediaType(): string;

    /** Slug de la categoría de Kudos ('peliculas' o 'series'). */
    abstract protected function categorySlug(): string;

    /** Campo del título en español ('title' o 'name'). */
    abstract protected function titleField(): string;

    /** Campo del título original ('original_title' u 'original_name'). */
    abstract protected function originalTitleField(): string;

    /** Campo de la fecha de estreno ('release_date' o 'first_air_date'). */
    abstract protected function releaseDateField(): string;

    /** append_to_response del detalle. */
    abstract protected function detailAppends(): string;

    /** @return list<string> listados de fetchNew() en orden de prioridad, p. ej. 'movie/now_playing' */
    abstract protected function newListPaths(): array;

    /**
     * Atributos que solo trae el detalle (sin year ni genres, que pone toData()).
     * Los valores null, '' o [] se descartan después.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    abstract protected function detailAttributes(array $payload): array;

    public function key(): string
    {
        return self::PROVIDER_KEY;
    }

    /**
     * Candidatos de la búsqueda (parciales: los del listado de TMDB, sin wikidataId ni detalle).
     *
     * @return list<CatalogItemData>
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $results = $this->client
            ->get("search/{$this->mediaType()}", ['query' => $query, 'include_adult' => 'false'])
            ->throw()
            ->json('results');

        if (! is_array($results)) {
            return [];
        }

        return array_slice($this->mapListResults($results), 0, self::SEARCH_LIMIT);
    }

    /**
     * Ficha completa en español. 404 → null; cualquier otro error lanza RequestException, para que
     * un fallo temporal nunca se confunda con "ya no existe".
     */
    public function fetch(string $externalId): ?CatalogItemData
    {
        $id = $this->tmdbId($externalId);

        $response = $this->client->get("{$this->mediaType()}/{$id}", ['append_to_response' => $this->detailAppends()]);

        if ($response->notFound()) {
            return null;
        }

        $payload = $response->throw()->json();

        if (! is_array($payload) || ! isset($payload['id'])) {
            throw new UnexpectedValueException("TMDB devolvió un detalle sin 'id' para '{$externalId}'.");
        }

        $data = $this->toData(
            $payload,
            $this->names($payload['genres'] ?? []),
            $this->detailAttributes($payload),
            $this->wikidataId($payload),
        );

        if ($data === null) {
            throw new UnexpectedValueException("TMDB devolvió un detalle sin título para '{$externalId}'.");
        }

        return $data;
    }

    /**
     * Novedades y populares, sin duplicados, hasta $limit. Los DTO son parciales (los de los listados,
     * como search()): la ficha completa la da fetch().
     *
     * @return \Generator<int, CatalogItemData>
     */
    public function fetchNew(int $limit): iterable
    {
        if ($limit <= 0) {
            return;
        }

        $seen = [];
        $pending = $this->newListPaths(); // listados que aún tienen páginas

        for ($page = 1; $pending !== [] && $page <= self::MAX_PAGES; $page++) {
            foreach ($pending as $index => $path) {
                $response = $this->client->get($path, ['page' => $page, 'region' => self::REGION])->throw();
                $results = $response->json('results');
                $results = is_array($results) ? $results : [];

                foreach ($this->mapListResults($results) as $data) {
                    if (isset($seen[$data->externalId])) {
                        continue;
                    }

                    $seen[$data->externalId] = true;

                    yield $data;

                    if (count($seen) >= $limit) {
                        return;
                    }
                }

                if ($results === [] || $page >= (int) $response->json('total_pages', 1)) {
                    unset($pending[$index]);
                }
            }
        }
    }

    /**
     * @param  array<mixed>  $results
     * @return list<CatalogItemData>
     */
    protected function mapListResults(array $results): array
    {
        $results = array_values(array_filter($results, 'is_array'));

        $needsGenres = false;
        foreach ($results as $result) {
            if (! empty($result['genre_ids'])) {
                $needsGenres = true;
                break;
            }
        }

        $genreMap = $needsGenres ? $this->genreNamesById() : [];

        $items = [];
        foreach ($results as $result) {
            $data = $this->fromListResult($result, $genreMap);

            if ($data !== null) {
                $items[] = $data;
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<int, string>  $genreMap
     */
    protected function fromListResult(array $result, array $genreMap): ?CatalogItemData
    {
        $genres = [];
        $ids = $result['genre_ids'] ?? [];

        if (is_array($ids)) {
            foreach ($ids as $genreId) {
                $name = is_int($genreId) || is_string($genreId) ? ($genreMap[$genreId] ?? null) : null;

                if ($name !== null && ! in_array($name, $genres, true)) {
                    $genres[] = $name;
                }
            }
        }

        return $this->toData($result, $genres, [], null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $genres
     * @param  array<string, mixed>  $extraAttributes
     */
    protected function toData(array $payload, array $genres, array $extraAttributes, ?string $wikidataId): ?CatalogItemData
    {
        $id = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $originalTitle = $this->text($payload[$this->originalTitleField()] ?? null);
        $name = $this->text($payload[$this->titleField()] ?? null) ?? $originalTitle;

        if ($id === false || $name === null) {
            return null;
        }

        $sourceUrl = self::SITE_URL."/{$this->mediaType()}/{$id}";
        $description = $this->text($payload['overview'] ?? null);
        $releasedAt = $this->date($payload[$this->releaseDateField()] ?? null);

        $attributes = $this->withoutEmpty(array_merge([
            'year' => $releasedAt !== null ? (int) substr($releasedAt, 0, 4) : null,
            'genres' => $genres,
        ], $extraAttributes));

        return new CatalogItemData(
            provider: self::PROVIDER_KEY,
            externalId: "{$this->mediaType()}:{$id}",
            categorySlug: $this->categorySlug(),
            name: $name,
            originalTitle: $originalTitle,
            description: $description,
            descriptionSource: $description !== null ? 'tmdb' : null,
            descriptionUrl: $description !== null ? $sourceUrl : null,
            releasedAt: $releasedAt,
            attributes: $attributes,
            popularity: is_numeric($payload['popularity'] ?? null) ? (float) $payload['popularity'] : 0.0,
            wikidataId: $wikidataId,
            sourceUrl: $sourceUrl,
            images: $this->images($payload, $sourceUrl),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{url: string, role: string, license: string, author: null, source_url: string}>
     */
    protected function images(array $payload, string $sourceUrl): array
    {
        $images = [];

        foreach ([['poster_path', self::POSTER_SIZE, 'poster'], ['backdrop_path', self::BANNER_SIZE, 'banner']] as [$field, $size, $role]) {
            $path = $payload[$field] ?? null;

            if (is_string($path) && $path !== '' && str_starts_with($path, '/')) {
                $images[] = [
                    'url' => self::IMAGE_BASE_URL."/{$size}{$path}",
                    'role' => $role,
                    'license' => self::IMAGE_LICENSE,
                    'author' => null,
                    'source_url' => $sourceUrl,
                ];
            }
        }

        return $images;
    }

    /**
     * Mapa id => nombre de los géneros en español, cacheado (catalog.providers.tmdb.genres_cache_days).
     *
     * @return array<int, string>
     */
    protected function genreNamesById(): array
    {
        return Cache::remember(
            "catalog:tmdb:genres:{$this->mediaType()}:".TmdbClient::LANGUAGE,
            now()->addDays((int) config('catalog.providers.tmdb.genres_cache_days', 7)),
            function () {
                $genres = $this->client->get("genre/{$this->mediaType()}/list")->throw()->json('genres');
                $map = [];

                foreach (is_array($genres) ? $genres : [] as $genre) {
                    if (is_array($genre) && isset($genre['id']) && is_int($genre['id'])) {
                        $name = $this->text($genre['name'] ?? null);

                        if ($name !== null) {
                            $map[$genre['id']] = $name;
                        }
                    }
                }

                return $map;
            },
        );
    }

    /** Valida el prefijo del external_id ('movie:603') y devuelve el id numérico de TMDB. */
    protected function tmdbId(string $externalId): int
    {
        if (preg_match('/^'.preg_quote($this->mediaType(), '/').':([1-9]\d*)$/', $externalId, $matches) !== 1) {
            throw new InvalidArgumentException(
                "El external_id '{$externalId}' no es de TMDB {$this->mediaType()} (se esperaba '{$this->mediaType()}:{id}')."
            );
        }

        return (int) $matches[1];
    }

    /** @param  array<string, mixed>  $payload */
    protected function wikidataId(array $payload): ?string
    {
        $qid = data_get($payload, 'external_ids.wikidata_id');

        return is_string($qid) && preg_match('/^Q[1-9]\d*$/', $qid) === 1 ? $qid : null;
    }

    protected function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /** Fecha 'Y-m-d' válida; TMDB manda "" cuando no hay fecha. */
    protected function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1) {
            return null;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
    }

    /** TMDB manda 0 o null cuando no conoce el valor (p. ej. runtime). */
    protected function positiveInt(mixed $value): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $int === false ? null : $int;
    }

    /**
     * Nombres no vacíos y sin repetir (en orden) de una lista de objetos de TMDB.
     *
     * @return list<string>
     */
    protected function names(mixed $list, ?int $max = null): array
    {
        $names = [];

        foreach (is_array($list) ? $list : [] as $entry) {
            $name = is_array($entry) ? $this->text($entry['name'] ?? null) : null;

            if ($name !== null && ! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $max !== null ? array_slice($names, 0, $max) : $names;
    }

    /**
     * Países en español con intl; si intl no conoce el código se omite (nunca el nombre en inglés de TMDB).
     *
     * @return list<string>
     */
    protected function countryNames(mixed $countries): array
    {
        $names = [];

        foreach (is_array($countries) ? $countries : [] as $country) {
            $code = is_array($country) ? ($country['iso_3166_1'] ?? null) : null;

            if (! is_string($code) || preg_match('/^[A-Za-z]{2}$/', $code) !== 1) {
                continue;
            }

            $display = Locale::getDisplayRegion('-'.strtoupper($code), 'es');

            if ($display !== '' && $display !== strtoupper($code) && ! in_array($display, $names, true)) {
                $names[] = $display;
            }
        }

        return $names;
    }

    /** Idioma original en español con intl ('en' → 'Inglés'); null si intl no lo conoce. */
    protected function languageName(mixed $code): ?string
    {
        if (! is_string($code) || preg_match('/^[a-z]{2,3}$/', $code) !== 1) {
            return null;
        }

        $name = Locale::getDisplayLanguage($code, 'es');

        return $name !== '' && $name !== $code ? Str::ucfirst($name) : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function withoutEmpty(array $attributes): array
    {
        return array_filter($attributes, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }
}
