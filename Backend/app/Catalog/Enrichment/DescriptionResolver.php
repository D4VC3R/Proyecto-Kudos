<?php

namespace App\Catalog\Enrichment;

use App\Catalog\Clients\WikidataClient;
use App\Catalog\Clients\WikipediaClient;
use App\Catalog\Data\CatalogItemData;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Rellena la descripción en español de una ficha que el proveedor no trae (pipeline-catalogo §4, paso 2).
 *
 * Cadena, en este orden:
 * 1. La descripción del proveedor, si la hay (no se hace ninguna petición).
 * 2. El resumen de Wikipedia ES, a partir de `sitelinks.eswiki` del QID de Wikidata (el título nunca se adivina).
 * 3. La descripción corta en español de Wikidata más un resumen de los atributos de la ficha.
 * 4. Nada: `description = null`. Nunca se inventa una plantilla.
 *
 * Un fallo de Wikimedia no aborta la importación: se deja un aviso en el log y se pasa al siguiente paso.
 * Como el fallo no se cachea, el siguiente refresco lo reintenta. Las respuestas correctas se cachean
 * (por QID y por título) en el store por defecto (Redis en producción) durante `cache_days` días.
 */
final class DescriptionResolver
{
    public const SOURCE_WIKIPEDIA = 'wikipedia_es';

    public const SOURCE_WIKIDATA = 'wikidata';

    public const WIKIDATA_PAGE_URL = 'https://www.wikidata.org/wiki/';

    /** Atributos que resumen la plantilla de Wikidata, en este orden (pipeline §4, paso 2.3). */
    private const SUMMARY_FIELDS = [
        'genres' => 'Géneros',
        'directors' => 'Dirección',
        'creators' => 'Creación',
        'networks' => 'Cadenas',
        'countries' => 'Países',
        'runtime' => 'Duración',
        'seasons' => 'Temporadas',
    ];

    public function __construct(
        private readonly WikidataClient $wikidata,
        private readonly WikipediaClient $wikipedia,
    ) {}

    /** Devuelve el DTO con la mejor descripción disponible (o con null si no hay ninguna). */
    public function resolve(CatalogItemData $data): CatalogItemData
    {
        if (filled($data->description)) {
            return $data;
        }

        if ($data->wikidataId === null) {
            return $data->withDescription(null, null, null);
        }

        $entity = $this->entity($data->wikidataId);

        if ($entity === null) {
            return $data->withDescription(null, null, null);
        }

        if ($entity['eswiki'] !== null) {
            $summary = $this->summary($entity['eswiki']);

            if ($summary !== null) {
                return $data->withDescription($summary['extract'], self::SOURCE_WIKIPEDIA, $summary['url']);
            }
        }

        if ($entity['description_es'] !== null) {
            return $data->withDescription(
                $this->wikidataText($entity['description_es'], $data->attributes),
                self::SOURCE_WIKIDATA,
                self::WIKIDATA_PAGE_URL.$data->wikidataId,
            );
        }

        return $data->withDescription(null, null, null);
    }

    /**
     * Solo lo necesario de la entidad de Wikidata (título de eswiki y descripción en español), o null si no existe
     * o falla la consulta.
     *
     * @return array{eswiki: ?string, description_es: ?string}|null
     */
    private function entity(string $qid): ?array
    {
        try {
            // Cache::remember() no guarda null: se envuelve en ['value' => …] para cachear también "no existe".
            return Cache::remember('catalog:wikidata:entity:'.$qid, $this->ttl(), function () use ($qid) {
                $entity = $this->wikidata->getEntity($qid);

                return ['value' => $entity === null ? null : [
                    'eswiki' => $this->text(data_get($entity, 'sitelinks.eswiki.title')),
                    'description_es' => $this->text(data_get($entity, 'descriptions.es.value')),
                ]];
            })['value'];
        } catch (StrayRequestException $e) {
            throw $e; // en los tests, una petición sin Http::fake() no se oculta
        } catch (HttpClientException|RuntimeException|InvalidArgumentException $e) {
            Log::warning('Catálogo: no se pudo consultar Wikidata; se sigue sin su descripción.', ['qid' => $qid, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Resumen de Wikipedia ES para un título, o null si no existe o falla la consulta.
     *
     * @return array{title: string, extract: string, url: string}|null
     */
    private function summary(string $title): ?array
    {
        try {
            return Cache::remember('catalog:wikipedia:es:summary:'.sha1($title), $this->ttl(), function () use ($title) {
                return ['value' => $this->wikipedia->summary($title)];
            })['value'];
        } catch (StrayRequestException $e) {
            throw $e; // en los tests, una petición sin Http::fake() no se oculta
        } catch (HttpClientException|RuntimeException|InvalidArgumentException $e) {
            Log::warning('Catálogo: no se pudo consultar Wikipedia ES; se prueba con Wikidata.', ['title' => $title, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function ttl(): \DateTimeInterface
    {
        return now()->addDays((int) config('catalog.providers.wikimedia.cache_days', 7));
    }

    /**
     * Descripción corta de Wikidata en frase (mayúscula inicial y punto final) seguida de los atributos que
     * resumen la ficha: "Géneros: Drama. Dirección: David Fincher. Duración: 139 min."
     *
     * @param  array<string, mixed>  $attributes
     */
    private function wikidataText(string $description, array $attributes): string
    {
        $sentence = Str::ucfirst(trim($description));

        if (! preg_match('/[.!?…]$/u', $sentence)) {
            $sentence .= '.';
        }

        $parts = [$sentence];

        foreach (self::SUMMARY_FIELDS as $key => $label) {
            $value = $attributes[$key] ?? null;

            if (is_array($value)) {
                $value = implode(', ', array_filter($value, fn ($v) => is_string($v) && trim($v) !== ''));
            } elseif (is_int($value) && $value > 0) {
                $value = $key === 'runtime' ? "{$value} min" : (string) $value;
            } else {
                $value = '';
            }

            if ($value !== '') {
                $parts[] = "{$label}: {$value}.";
            }
        }

        return implode(' ', $parts);
    }

    /** String recortado no vacío, o null. */
    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
