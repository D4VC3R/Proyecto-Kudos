<?php

namespace App\Catalog\Providers;

use App\Catalog\CatalogImporter;
use App\Catalog\Clients\CommonsClient;
use App\Catalog\Clients\WikidataClient;
use App\Catalog\Contracts\CatalogProvider;
use App\Catalog\Contracts\RecognizesExternalIds;
use App\Catalog\Data\CatalogItemData;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Proveedor de destinos (Wikidata y Commons, F4-02). Alcance: estados soberanos, ciudades de más de
 * catalog.places.min_city_population habitantes y capitales de provincia españolas, todos con sitelink eswiki
 * y foto (P18). Wikidata es el proveedor principal (external_id = QID, su fuente es la única y principal).
 * La foto es la de Commons y solo se guarda si tiene licencia y autor. La descripción la pone el
 * DescriptionResolver (Wikipedia ES por eswiki o, si no, Wikidata). Es una lista cerrada: sin búsqueda bajo demanda.
 * Se recorren todos los estados soberanos vigentes: un país sin foto o sin eswiki no entra, pero sus ciudades sí (y las de
 * Países Bajos y Dinamarca, que usan otro P17).
 */
final class WikidataPlaceProvider implements CatalogProvider, RecognizesExternalIds
{
    public const PROVIDER_KEY = CatalogImporter::WIKIDATA_PROVIDER;

    public const CATEGORY_SLUG = 'destinos';

    public const SITE_URL = 'https://www.wikidata.org/wiki/';

    public const SPAIN = 'Q29';

    /** P31 que hacen de un lugar un país: estado soberano y país. */
    public const COUNTRY_CLASSES = ['Q3624078', 'Q6256'];

    public const KIND_COUNTRY = 'país';

    public const KIND_CITY = 'ciudad';

    public const EARTH = 'http://www.wikidata.org/entity/Q2';

    public const IMAGE_ROLE = 'poster';

    /**
     * Estados soberanos cuyas ciudades usan otro ítem en P17 (F4-02): el Reino de los Países Bajos → Países Bajos (Q55)
     * y el Reino de Dinamarca → Dinamarca (Q35). Sus ciudades se piden también con ese P17.
     */
    public const CITY_COUNTRY_ALIASES = ['Q29999' => ['Q55'], 'Q756617' => ['Q35']];

    /** attribute_schema de `destinos` (lo siembran CategorySeeder y la migración de activación, F4-02). */
    public const ATTRIBUTE_SCHEMA = [
        ['key' => 'kind', 'label' => 'Tipo', 'type' => 'string', 'filterable' => true, 'show_in_card' => true],
        ['key' => 'country', 'label' => 'País', 'type' => 'string', 'filterable' => true, 'show_in_card' => true],
        ['key' => 'population', 'label' => 'Población', 'type' => 'integer', 'filterable' => false, 'show_in_card' => false],
        ['key' => 'coordinates', 'label' => 'Coordenadas', 'type' => 'coordinates', 'filterable' => false, 'show_in_card' => false],
    ];

    public function __construct(
        private readonly WikidataClient $wikidata,
        private readonly CommonsClient $commons,
    ) {}

    public function key(): string
    {
        return self::PROVIDER_KEY;
    }

    /** true si el external_id es un QID de Wikidata. No hace peticiones. */
    public function recognizes(string $externalId): bool
    {
        return preg_match('/^Q[1-9]\d*$/', $externalId) === 1;
    }

    /**
     * Destinos es una lista cerrada que se carga con fetchNew (F4-02): no hay búsqueda bajo demanda
     * (catalog.categories.destinos.on_demand = false, la API responde 422 antes de llegar aquí).
     */
    public function search(string $query): array
    {
        return [];
    }

    /**
     * Ficha completa. Las excepciones de Wikidata y de Commons no se capturan: el job falla y se reintenta,
     * y no se pisan los datos ni las fotos actuales con datos parciales.
     */
    public function fetch(string $externalId): ?CatalogItemData
    {
        if (! $this->recognizes($externalId)) {
            throw new InvalidArgumentException("El external_id '{$externalId}' no es un QID de Wikidata.");
        }

        $entity = $this->wikidata->getEntity($externalId);

        if ($entity === null) {
            return null;
        }

        $name = WikidataClient::labelOf($entity) ?? $this->text(data_get($entity, 'sitelinks.eswiki.title'));

        if ($name === null) {
            throw new UnexpectedValueException("Wikidata devolvió un lugar sin nombre para '{$externalId}'.");
        }

        $kind = array_intersect($this->entityIds($entity, 'P31', truthyOnly: false), self::COUNTRY_CLASSES) !== []
            ? self::KIND_COUNTRY
            : self::KIND_CITY;

        // Un país no repite su propio nombre.
        $countryId = $this->entityIds($entity, 'P17')[0] ?? null;
        $country = $countryId !== null && $countryId !== $externalId ? $this->countryLabel($countryId) : null;

        $population = $this->population($entity);

        $attributes = array_filter([
            'kind' => $kind,
            'country' => $country,
            'population' => $population,
            'coordinates' => $this->coordinates($entity),
        ], fn ($value) => $value !== null);

        return new CatalogItemData(
            provider: self::PROVIDER_KEY,
            externalId: $externalId,
            categorySlug: self::CATEGORY_SLUG,
            name: $name,
            originalTitle: null,
            description: null,              // la pone el DescriptionResolver (Wikipedia ES por eswiki, o Wikidata)
            descriptionSource: null,
            descriptionUrl: null,
            releasedAt: null,
            attributes: $attributes,
            popularity: (float) ($population ?? 0),
            wikidataId: $externalId,
            sourceUrl: self::SITE_URL.$externalId,
            images: $this->images($externalId, $this->stringValue($entity, 'P18')),
        );
    }

    /**
     * España (el país, sus capitales de provincia y sus ciudades) y después el resto de países por población,
     * cada uno con sus ciudades. Los DTO son parciales (QID, nombre y población): la ficha completa la da fetch().
     * Si la consulta de un país falla, se repite una vez al final (plan B).
     *
     * @return \Generator<int, CatalogItemData>
     */
    public function fetchNew(int $limit): iterable
    {
        if ($limit <= 0) {
            return;
        }

        $seen = [];

        foreach ($this->listing() as $row) {
            if (isset($seen[$row['qid']])) {
                continue;
            }

            $seen[$row['qid']] = true;

            yield $this->partial($row);

            if (count($seen) >= $limit) {
                return;
            }
        }
    }

    /**
     * Filas del listado, por país: el país y, después, sus ciudades (una consulta SPARQL cada vez; al ser un
     * generador, solo se hacen si el límite no se ha alcanzado). Plan B: las consultas que fallan (un timeout de
     * WDQS por carga) se repiten una vez al final; si vuelven a fallar, queda un error en el log con los países.
     * Se recorren todos los estados soberanos vigentes: un país sin foto o sin eswiki no entra, pero sus ciudades sí
     * (y las de Países Bajos y Dinamarca, que usan otro P17).
     *
     * @return \Generator<int, array{qid: string, name: ?string, population: ?int, in_scope: bool}>
     */
    private function listing(): \Generator
    {
        // Una consulta para todos los países; un fallo aquí aborta (sin países no hay nada que paginar).
        $countries = $this->rows($this->countriesQuery());
        // España primero (público español); el resto, por población, como viene de la consulta.
        $countries = array_merge(
            array_values(array_filter($countries, fn (array $row) => $row['qid'] === self::SPAIN)),
            array_values(array_filter($countries, fn (array $row) => $row['qid'] !== self::SPAIN)),
        );
        $failed = [];

        foreach ($countries as $country) {
            // El país solo entra con foto y eswiki (alcance de la ficha); sus ciudades se piden igualmente.
            if ($country['in_scope']) {
                yield $country;
            }

            $queries = $country['qid'] === self::SPAIN ? [$this->spanishCapitalsQuery()] : [];

            foreach ([$country['qid'], ...(self::CITY_COUNTRY_ALIASES[$country['qid']] ?? [])] as $cityCountry) {
                $queries[] = $this->citiesQuery($cityCountry);
            }

            foreach ($queries as $query) {
                try {
                    yield from $this->pacedRows($query);
                } catch (StrayRequestException $e) {
                    throw $e;
                } catch (HttpClientException|RuntimeException $e) {
                    Log::warning('Catálogo: falló una consulta SPARQL de destinos; se repetirá al final.', [
                        'country' => $country['qid'],
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = ['country' => $country['qid'], 'query' => $query];
                }
            }
        }

        $stillFailing = [];

        if ($failed !== []) {
            // Un minuto: la ventana de la cuota de WDQS (un 429 o un pico de carga suelen pasar en ese tiempo).
            Sleep::for(60)->seconds();
        }

        foreach ($failed as $retry) {
            try {
                yield from $this->pacedRows($retry['query']);
            } catch (StrayRequestException $e) {
                throw $e;
            } catch (HttpClientException|RuntimeException $e) {
                $stillFailing[] = $retry['country'];
            }
        }

        if ($stillFailing !== []) {
            Log::error('Catálogo: no se pudieron listar las ciudades de algunos países de destinos; vuelve a lanzar catalog:fetch-new --category=destinos.', [
                'countries' => array_values(array_unique($stillFailing)),
            ]);
        }
    }

    /**
     * Filas de una consulta de ciudades y, después, una pausa igual a lo que ha tardado (F4-02): WDQS da a cada
     * cliente 60 s de proceso por minuto y el listado encadena unas 200 consultas; sin la pausa llegan los 429.
     * También se espera si la consulta falla (los errores cuentan en la cuota).
     *
     * @return list<array{qid: string, name: ?string, population: ?int, in_scope: bool}>
     */
    private function pacedRows(string $query): array
    {
        $start = hrtime(true);

        try {
            return $this->rows($query);
        } finally {
            Sleep::for((int) ceil((hrtime(true) - $start) / 1_000_000))->milliseconds();
        }
    }

    /**
     * @return list<array{qid: string, name: ?string, population: ?int, in_scope: bool}>
     */
    private function rows(string $query): array
    {
        $rows = [];

        foreach ($this->wikidata->sparql($query) as $binding) {
            $uri = $binding['item']['value'] ?? null;

            if (! is_string($uri) || preg_match('~/entity/(Q[1-9]\d*)$~', $uri, $match) !== 1) {
                continue;
            }

            $qid = $match[1];
            $label = $binding['itemLabel']['value'] ?? null;
            $name = is_string($label) && trim($label) !== '' && trim($label) !== $qid ? trim($label) : null;
            $value = $binding['maxPopulation']['value'] ?? null;

            $rows[] = [
                'qid' => $qid,
                'name' => $name,
                'population' => is_numeric($value) ? (int) round((float) $value) : null,
                // Países (F4-02): se recorren todos para listar sus ciudades, pero solo entran con foto y eswiki.
                'in_scope' => ($binding['hasImage']['value'] ?? 'true') !== 'false'
                    && ($binding['hasEswiki']['value'] ?? 'true') !== 'false',
            ];
        }

        return $rows;
    }

    /** @param  array{qid: string, name: ?string, population: ?int, in_scope: bool}  $row */
    private function partial(array $row): CatalogItemData
    {
        return new CatalogItemData(
            provider: self::PROVIDER_KEY,
            externalId: $row['qid'],
            categorySlug: self::CATEGORY_SLUG,
            name: $row['name'] ?? $row['qid'],
            originalTitle: null,
            description: null,
            descriptionSource: null,
            descriptionUrl: null,
            releasedAt: null,
            attributes: [],
            popularity: (float) ($row['population'] ?? 0),
            wikidataId: $row['qid'],
            sourceUrl: self::SITE_URL.$row['qid'],
            images: [],
        );
    }

    private function countriesQuery(): string
    {
        return <<<'SPARQL'
SELECT ?item ?itemLabel ?hasImage ?hasEswiki (MAX(?population) AS ?maxPopulation) WHERE {
  ?item wdt:P31 wd:Q3624078 .
  FILTER NOT EXISTS { ?item wdt:P31 wd:Q3024240 }
  FILTER NOT EXISTS { ?item wdt:P576 [] }
  BIND(EXISTS { ?item wdt:P18 [] } AS ?hasImage)
  BIND(EXISTS { ?article schema:about ?item ; schema:isPartOf <https://es.wikipedia.org/> } AS ?hasEswiki)
  OPTIONAL { ?item wdt:P1082 ?population }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "es,mul,en". }
}
GROUP BY ?item ?itemLabel ?hasImage ?hasEswiki
ORDER BY DESC(?maxPopulation)
SPARQL;
    }

    /**
     * $countryQid ya está validado (sale de rows()). Ciudades y municipios (P31/P279*: en Wikidata muchas ciudades,
     * como Madrid, son "municipio" y no "ciudad"). El orden de las condiciones es deliberado (optimizer "None"):
     * primero la población con rangeSafe (el filtro usa el índice y quedan unos miles de candidatos en todo el mundo)
     * y después el país. Empezar por wdt:P17 agota los 60 s de WDQS en EE. UU. o Alemania (millones de ítems).
     * El umbral va como decimal (300000.0): P1082 es xsd:decimal y, con un entero, Blazegraph no resuelve el rango con
     * el índice (Andorra, sin ciudades, tardaba 25 s; con el decimal, 1,4 s).
     * Las etiquetas se piden fuera de la agregación.
     */
    private function citiesQuery(string $countryQid): string
    {
        $min = (int) config('catalog.places.min_city_population', 300000);

        return <<<SPARQL
SELECT ?item ?itemLabel ?maxPopulation WHERE {
  {
    SELECT ?item (MAX(?population) AS ?maxPopulation) WHERE {
      hint:Query hint:optimizer "None" .
      ?item wdt:P1082 ?population .
      hint:Prior hint:rangeSafe true .
      FILTER(?population > {$min}.0)
      ?item wdt:P17 wd:{$countryQid} .
      FILTER NOT EXISTS { ?item wdt:P576 [] }
      ?item wdt:P18 [] .
      ?article schema:about ?item ; schema:isPartOf <https://es.wikipedia.org/> .
      ?item wdt:P31/wdt:P279* ?class .
      VALUES ?class { wd:Q515 wd:Q15284 }
    }
    GROUP BY ?item
  }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "es,mul,en". }
}
ORDER BY DESC(?maxPopulation)
SPARQL;
    }

    private function spanishCapitalsQuery(): string
    {
        return <<<'SPARQL'
SELECT ?item ?itemLabel (MAX(?population) AS ?maxPopulation) WHERE {
  ?province wdt:P31 wd:Q162620 ;
            wdt:P36 ?item .
  ?item wdt:P17 wd:Q29 .
  FILTER EXISTS { ?item wdt:P18 [] }
  FILTER EXISTS { ?article schema:about ?item ; schema:isPartOf <https://es.wikipedia.org/> }
  OPTIONAL { ?item wdt:P1082 ?population }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "es,mul,en". }
}
GROUP BY ?item ?itemLabel
ORDER BY DESC(?maxPopulation)
SPARQL;
    }

    /**
     * Declaraciones de una propiedad sin las deprecated y con valor. Con $truthyOnly, si hay alguna preferred,
     * solo esas (como wdt: en SPARQL).
     *
     * @param  array<string, mixed>  $entity
     * @return list<array<string, mixed>>
     */
    private function statements(array $entity, string $property, bool $truthyOnly = true): array
    {
        $statements = [];

        foreach ((array) ($entity['claims'][$property] ?? []) as $statement) {
            if (! is_array($statement) || ($statement['rank'] ?? 'normal') === 'deprecated') {
                continue;
            }

            if (($statement['mainsnak']['snaktype'] ?? null) !== 'value') {
                continue;
            }

            $statements[] = $statement;
        }

        if ($truthyOnly) {
            $preferred = array_values(array_filter($statements, fn (array $s) => ($s['rank'] ?? 'normal') === 'preferred'));

            if ($preferred !== []) {
                return $preferred;
            }
        }

        return $statements;
    }

    /**
     * @param  array<string, mixed>  $entity
     * @return list<string>
     */
    private function entityIds(array $entity, string $property, bool $truthyOnly = true): array
    {
        $ids = [];

        foreach ($this->statements($entity, $property, $truthyOnly) as $statement) {
            $id = $statement['mainsnak']['datavalue']['value']['id'] ?? null;

            if (is_string($id) && preg_match('/^Q[1-9]\d*$/', $id) === 1 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /** @param  array<string, mixed>  $entity */
    private function stringValue(array $entity, string $property): ?string
    {
        foreach ($this->statements($entity, $property) as $statement) {
            $value = $statement['mainsnak']['datavalue']['value'] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /** Población (P1082): la declaración con la fecha P585 más reciente; si ninguna tiene fecha, la primera. */
    private function population(array $entity): ?int
    {
        $best = null;
        $bestTime = null;

        foreach ($this->statements($entity, 'P1082') as $statement) {
            $amount = $statement['mainsnak']['datavalue']['value']['amount'] ?? null;

            if (! is_string($amount) || ! is_numeric(ltrim($amount, '+'))) {
                continue;
            }

            $value = (int) round((float) ltrim($amount, '+'));

            if ($value <= 0) {
                continue;
            }

            $time = $statement['qualifiers']['P585'][0]['datavalue']['value']['time'] ?? null;
            $time = is_string($time) ? $time : null;

            if ($best === null) {
                $best = $value;
                $bestTime = $time;

                continue;
            }

            if ($time !== null && ($bestTime === null || strcmp($time, $bestTime) > 0)) {
                $best = $value;
                $bestTime = $time;
            }
        }

        return $best;
    }

    /**
     * @param  array<string, mixed>  $entity
     * @return array{lat: float, lng: float}|null
     */
    private function coordinates(array $entity): ?array
    {
        foreach ($this->statements($entity, 'P625') as $statement) {
            $value = $statement['mainsnak']['datavalue']['value'] ?? null;

            if (! is_array($value)) {
                continue;
            }

            $globe = $value['globe'] ?? null;

            if ($globe !== null && $globe !== self::EARTH) {
                continue;
            }

            $lat = $value['latitude'] ?? null;
            $lng = $value['longitude'] ?? null;

            if (! is_numeric($lat) || ! is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
                continue;
            }

            return ['lat' => round((float) $lat, 6), 'lng' => round((float) $lng, 6)];
        }

        return null;
    }

    private function countryLabel(string $qid): ?string
    {
        // Cache::remember() no guarda null: se envuelve en ['value' => …] para cachear también "sin etiqueta".
        return Cache::remember(
            "catalog:wikidata:label:{$qid}",
            now()->addDays((int) config('catalog.providers.wikimedia.cache_days', 7)),
            fn () => ['value' => $this->wikidata->label($qid)],
        )['value'];
    }

    /**
     * La foto de Commons solo se guarda con licencia y autor. Las excepciones de Commons se propagan.
     *
     * @return list<array{url: string, role: string, license: string, author: string, source_url: string}>
     */
    private function images(string $qid, ?string $fileName): array
    {
        if ($fileName === null) {
            return [];
        }

        $info = $this->commons->imageInfo($fileName);

        if ($info === null) {
            Log::info('Catálogo: la foto de Commons no existe; el destino se importa sin foto.', ['qid' => $qid, 'file' => $fileName]);

            return [];
        }

        if ($info['license'] === null || $info['author'] === null) {
            Log::warning('Catálogo: la foto de Commons no tiene licencia o autor; no se guarda.', ['qid' => $qid, 'file' => $fileName]);

            return [];
        }

        return [[
            'url' => CommonsClient::filePathUrl($fileName, (int) config('catalog.places.image_width', 1280)),
            'role' => self::IMAGE_ROLE,
            'license' => $info['license'],
            'author' => $info['author'],
            'source_url' => $info['source_url'],
        ]];
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
