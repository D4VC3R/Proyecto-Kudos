<?php

namespace App\Catalog\Providers;

use App\Catalog\Clients\IgdbClient;
use App\Catalog\Clients\SteamStoreClient;
use App\Catalog\Clients\WikidataClient;
use App\Catalog\Contracts\CatalogProvider;
use App\Catalog\Contracts\RecognizesExternalIds;
use App\Catalog\Data\CatalogItemData;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Proveedor de videojuegos (IGDB, F4-01): ficha, imágenes, géneros y plataformas traducidos. La descripción
 * no viene de IGDB (su summary/storyline solo está en inglés y no se pide ni se guarda, fuentes-externas §1.8):
 * se toma de la tienda de Steam si el juego tiene appid y la tienda está en español; si solo está en inglés,
 * queda como descripción de respaldo (último recurso de la cadena del DescriptionResolver). El QID de Wikidata
 * se busca aquí por el slug de IGDB (P5794) o por el appid de Steam (P1733), porque el importador también lo necesita.
 */
final class IgdbGameProvider implements CatalogProvider, RecognizesExternalIds
{
    public const PROVIDER_KEY = 'igdb';

    public const CATEGORY_SLUG = 'videojuegos';

    public const SITE_URL = 'https://www.igdb.com/games/';

    public const IMAGE_BASE_URL = 'https://images.igdb.com/igdb/image/upload';

    public const POSTER_SIZE = 't_cover_big';

    public const BANNER_SIZE = 't_screenshot_big';

    public const IMAGE_LICENSE = '© titular de derechos';

    public const SEARCH_LIMIT = 10;

    public const MAX_DEVELOPERS = 3;

    public const STEAM_PROVIDER = 'steam';

    /** ID de Steam en /external_game_sources (el campo category de external_games está deprecado). */
    public const STEAM_EXTERNAL_GAME_SOURCE = 1;

    /** game_type que entran: juego principal, expansión independiente, remake, remaster y juego ampliado (fuera DLC, mods, bundles, packs, episodios, ports…). */
    public const GAME_TYPES = [0, 4, 8, 9, 10];

    /** Ventana de novedades de fetchNew(): lanzamientos de los últimos y de los próximos N días. */
    public const NEW_WINDOW_DAYS = 90;

    /** Propiedades de Wikidata para encontrar el QID (fuentes-externas §4). */
    public const WIKIDATA_IGDB_PROPERTY = 'P5794';

    public const WIKIDATA_STEAM_PROPERTY = 'P1733';

    /** Nunca se piden summary ni storyline (solo en inglés, fuentes-externas §1.8). */
    public const FIELDS = 'name,slug,first_release_date,cover.image_id,screenshots.image_id,genres.name,platforms.name,'
        .'involved_companies.company.name,involved_companies.developer,total_rating_count,'
        .'external_games.external_game_source,external_games.uid';

    /** attribute_schema de `videojuegos` (lo siembra CategorySeeder y la migración de activación, F4-01). */
    public const ATTRIBUTE_SCHEMA = [
        ['key' => 'year', 'label' => 'Año', 'type' => 'integer', 'filterable' => true, 'show_in_card' => true],
        ['key' => 'genres', 'label' => 'Géneros', 'type' => 'tags', 'filterable' => true, 'show_in_card' => true],
        ['key' => 'platforms', 'label' => 'Plataformas', 'type' => 'tags', 'filterable' => true, 'show_in_card' => false],
        ['key' => 'developers', 'label' => 'Desarrollo', 'type' => 'tags', 'filterable' => false, 'show_in_card' => false],
    ];

    protected const PAGE_SIZE = 50;

    protected const MAX_PAGES = 20;

    public function __construct(
        private readonly IgdbClient $client,
        private readonly SteamStoreClient $steam,
        private readonly WikidataClient $wikidata,
    ) {}

    public function key(): string
    {
        return self::PROVIDER_KEY;
    }

    /** true si el external_id es un ID numérico de IGDB. No hace peticiones. */
    public function recognizes(string $externalId): bool
    {
        return preg_match('/^[1-9]\d*$/', $externalId) === 1;
    }

    /**
     * Candidatos de la búsqueda (parciales: sin Steam, sin Wikidata y sin descripción).
     *
     * @return list<CatalogItemData>
     */
    public function search(string $query): array
    {
        $query = trim(str_replace(['"', '\\'], ' ', $query));

        if ($query === '') {
            return [];
        }

        $rows = $this->client
            ->query('games', 'search "'.$query.'"; fields '.self::FIELDS.'; where '.$this->baseFilter().'; limit '.self::SEARCH_LIMIT.';')
            ->throw()
            ->json();

        if (! is_array($rows)) {
            return [];
        }

        return array_slice($this->mapGames($rows), 0, self::SEARCH_LIMIT);
    }

    /**
     * Ficha completa. IGDB responde [] cuando el ID no existe (su "404") → null; cualquier otro error lanza
     * RequestException, para que un fallo temporal nunca se confunda con "ya no existe". Hace una sola petición
     * a Steam (la descripción) y, como mucho, dos búsquedas del QID en Wikidata.
     */
    public function fetch(string $externalId): ?CatalogItemData
    {
        if (! $this->recognizes($externalId)) {
            throw new InvalidArgumentException("El external_id '{$externalId}' no es de IGDB (se esperaba el ID numérico).");
        }

        $id = (int) $externalId;

        $response = $this->client->query('games', 'fields '.self::FIELDS."; where id = {$id}; limit 1;");

        if ($response->notFound()) {
            return null;
        }

        $rows = $response->throw()->json();

        if (! is_array($rows)) {
            throw new UnexpectedValueException("IGDB devolvió una respuesta inesperada para '{$externalId}'.");
        }

        if ($rows === [] || ! is_array($rows[0] ?? null)) {
            return null;
        }

        $game = $rows[0];
        $appId = $this->steamAppId($game);

        [$description, $fallback] = $this->steamDescription($appId);
        $wikidataId = $this->findWikidataId($this->text($game['slug'] ?? null), $appId);

        $data = $this->toData($game, $description, $fallback, $wikidataId);

        if ($data === null) {
            throw new UnexpectedValueException("IGDB devolvió un juego sin nombre para '{$externalId}'.");
        }

        return $data;
    }

    /**
     * Novedades (últimos y próximos 90 días) y populares, sin duplicados, hasta $limit. Los DTO son parciales
     * (sin Steam ni Wikidata): la ficha completa la da fetch().
     *
     * @return \Generator<int, CatalogItemData>
     */
    public function fetchNew(int $limit): iterable
    {
        if ($limit <= 0) {
            return;
        }

        $from = now()->subDays(self::NEW_WINDOW_DAYS)->getTimestamp();
        $to = now()->addDays(self::NEW_WINDOW_DAYS)->getTimestamp();
        $base = $this->baseFilter();

        $pending = [
            "where {$base} & first_release_date >= {$from} & first_release_date <= {$to} & (hypes > 0 | total_rating_count > 0); sort hypes desc;",
            "where {$base} & total_rating_count > 0; sort total_rating_count desc;",
        ];
        $seen = [];

        for ($page = 0; $pending !== [] && $page < self::MAX_PAGES; $page++) {
            foreach ($pending as $index => $clause) {
                $offset = $page * self::PAGE_SIZE;

                $rows = $this->client
                    ->query('games', 'fields '.self::FIELDS."; {$clause} limit ".self::PAGE_SIZE."; offset {$offset};")
                    ->throw()
                    ->json();
                $rows = is_array($rows) ? $rows : [];

                foreach ($this->mapGames($rows) as $data) {
                    if (isset($seen[$data->externalId])) {
                        continue;
                    }

                    $seen[$data->externalId] = true;

                    yield $data;

                    if (count($seen) >= $limit) {
                        return;
                    }
                }

                if (count($rows) < self::PAGE_SIZE) {
                    unset($pending[$index]);
                }
            }
        }
    }

    private function baseFilter(): string
    {
        return 'game_type = ('.implode(',', self::GAME_TYPES).') & version_parent = null';
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<CatalogItemData>
     */
    private function mapGames(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $data = is_array($row) ? $this->toData($row, null, null, null) : null;

            if ($data !== null) {
                $items[] = $data;
            }
        }

        return $items;
    }

    /** @param  array<string, mixed>  $game */
    private function toData(array $game, ?string $description, ?string $fallback, ?string $wikidataId): ?CatalogItemData
    {
        $id = filter_var($game['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $name = $this->text($game['name'] ?? null);

        if ($id === false || $name === null) {
            return null;
        }

        $slug = $this->text($game['slug'] ?? null);
        $sourceUrl = $slug !== null ? self::SITE_URL.$slug : null;

        $timestamp = $game['first_release_date'] ?? null;
        $releasedAt = is_int($timestamp) ? gmdate('Y-m-d', $timestamp) : null;

        $attributes = array_filter([
            'year' => $releasedAt !== null ? (int) substr($releasedAt, 0, 4) : null,
            'genres' => $this->translate($this->names($game['genres'] ?? []), 'genre_translations'),
            'platforms' => $this->translate($this->names($game['platforms'] ?? []), 'platform_translations'),
            'developers' => $this->developers($game['involved_companies'] ?? []),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        $appId = $this->steamAppId($game);
        $storeUrl = $appId !== null ? SteamStoreClient::storeUrl($appId) : null;

        return new CatalogItemData(
            provider: self::PROVIDER_KEY,
            externalId: (string) $id,
            categorySlug: self::CATEGORY_SLUG,
            name: $name,
            originalTitle: null,
            description: $description,
            descriptionSource: $description !== null ? self::STEAM_PROVIDER : null,
            descriptionUrl: $description !== null ? $storeUrl : null,
            releasedAt: $releasedAt,
            attributes: $attributes,
            popularity: is_numeric($game['total_rating_count'] ?? null) ? (float) $game['total_rating_count'] : 0.0,
            wikidataId: $wikidataId,
            sourceUrl: $sourceUrl,
            images: $this->images($game, $sourceUrl),
            secondarySources: $appId !== null
                ? [['provider' => self::STEAM_PROVIDER, 'external_id' => $appId, 'url' => $storeUrl]]
                : [],
            fallbackDescription: $fallback,
            fallbackDescriptionSource: $fallback !== null ? self::STEAM_PROVIDER : null,
            // La página de la tienda en inglés, donde está ese texto; permite distinguirlo del español en la base.
            fallbackDescriptionUrl: $fallback !== null && $storeUrl !== null ? $storeUrl.'?l=english' : null,
        );
    }

    /**
     * @param  array<string, mixed>  $game
     * @return list<array{url: string, role: string, license: string, author: null, source_url: ?string}>
     */
    private function images(array $game, ?string $sourceUrl): array
    {
        $images = [];

        $cover = $this->imageId($game['cover'] ?? null);

        if ($cover !== null) {
            $images[] = $this->image(self::POSTER_SIZE, $cover, 'poster', $sourceUrl);
        }

        $screenshots = $game['screenshots'] ?? null;
        $screenshot = is_array($screenshots) ? $this->imageId($screenshots[0] ?? null) : null;

        if ($screenshot !== null) {
            $images[] = $this->image(self::BANNER_SIZE, $screenshot, 'banner', $sourceUrl);
        }

        return $images;
    }

    /** @return array{url: string, role: string, license: string, author: null, source_url: ?string} */
    private function image(string $size, string $imageId, string $role, ?string $sourceUrl): array
    {
        return [
            'url' => self::IMAGE_BASE_URL."/{$size}/{$imageId}.jpg",
            'role' => $role,
            'license' => self::IMAGE_LICENSE,
            'author' => null,
            'source_url' => $sourceUrl,
        ];
    }

    private function imageId(mixed $image): ?string
    {
        $imageId = is_array($image) ? ($image['image_id'] ?? null) : null;

        return is_string($imageId) && preg_match('/^[A-Za-z0-9_]+$/', $imageId) === 1 ? $imageId : null;
    }

    /**
     * @param  mixed  $companies  involved_companies de IGDB
     * @return list<string>
     */
    private function developers(mixed $companies): array
    {
        $developers = [];

        foreach (is_array($companies) ? $companies : [] as $entry) {
            if (! is_array($entry) || ($entry['developer'] ?? false) !== true) {
                continue;
            }

            $name = is_array($entry['company'] ?? null) ? $this->text($entry['company']['name'] ?? null) : null;

            if ($name !== null && ! in_array($name, $developers, true)) {
                $developers[] = $name;
            }
        }

        return array_slice($developers, 0, self::MAX_DEVELOPERS);
    }

    /** Appid de Steam: el uid de la primera external_game cuya fuente es Steam. */
    private function steamAppId(array $game): ?string
    {
        $externals = $game['external_games'] ?? null;

        foreach (is_array($externals) ? $externals : [] as $external) {
            if (! is_array($external) || ($external['external_game_source'] ?? null) !== self::STEAM_EXTERNAL_GAME_SOURCE) {
                continue;
            }

            $uid = $external['uid'] ?? null;

            if ((is_string($uid) || is_int($uid)) && preg_match('/^[1-9]\d*$/', (string) $uid) === 1) {
                return (string) $uid;
            }
        }

        return null;
    }

    /**
     * Descripción de la tienda de Steam: [en español, de respaldo en inglés]. Una sola petición por juego.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function steamDescription(?string $appId): array
    {
        if ($appId === null) {
            return [null, null];
        }

        try {
            $details = $this->steam->appDetails($appId);
        } catch (StrayRequestException $e) {
            throw $e; // en los tests, una petición sin Http::fake() no se oculta
        } catch (HttpClientException|RuntimeException|InvalidArgumentException $e) {
            Log::warning('Catálogo: no se pudo consultar Steam; se sigue sin su descripción.', ['appid' => $appId, 'error' => $e->getMessage()]);

            return [null, null];
        }

        $text = $details['short_description'] ?? null;

        if ($text === null) {
            return [null, null]; // success: false (como un 404) o sin descripción corta
        }

        // Con l=spanish Steam devuelve el inglés sin avisar si no hay traducción: se detecta con supported_languages.
        return SteamStoreClient::supportsSpanish($details['supported_languages'] ?? null) ? [$text, null] : [null, $text];
    }

    /** QID de Wikidata: primero por el slug de IGDB (P5794) y, si no, por el appid de Steam (P1733). */
    private function findWikidataId(?string $slug, ?string $appId): ?string
    {
        return $this->wikidataByStatement(self::WIKIDATA_IGDB_PROPERTY, $slug)
            ?? $this->wikidataByStatement(self::WIKIDATA_STEAM_PROPERTY, $appId);
    }

    private function wikidataByStatement(string $property, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            // Cache::remember() no guarda null: se envuelve en ['value' => …] para cachear también "no existe".
            return Cache::remember(
                "catalog:wikidata:statement:{$property}:".sha1($value),
                now()->addDays((int) config('catalog.providers.wikimedia.cache_days', 7)),
                fn () => ['value' => $this->wikidata->findByStatement($property, $value)],
            )['value'];
        } catch (StrayRequestException $e) {
            throw $e;
        } catch (HttpClientException|RuntimeException|InvalidArgumentException $e) {
            Log::warning('Catálogo: no se pudo buscar el QID en Wikidata; se sigue sin él.', ['property' => $property, 'value' => $value, 'error' => $e->getMessage()]);

            return null; // el fallo no se cachea: el siguiente refresco lo reintenta
        }
    }

    /**
     * Traduce los nombres con el mapa de config/catalog.php; lo que no está conserva su nombre original.
     *
     * @param  list<string>  $names
     * @return list<string>
     */
    private function translate(array $names, string $mapKey): array
    {
        $map = (array) config("catalog.{$mapKey}.igdb", []);
        $translated = [];

        foreach ($names as $name) {
            $value = $map[$name] ?? $name;

            if (! in_array($value, $translated, true)) {
                $translated[] = $value;
            }
        }

        return $translated;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Nombres no vacíos y sin repetir (en orden) de una lista de objetos de IGDB.
     *
     * @return list<string>
     */
    private function names(mixed $list): array
    {
        $names = [];

        foreach (is_array($list) ? $list : [] as $entry) {
            $name = is_array($entry) ? $this->text($entry['name'] ?? null) : null;

            if ($name !== null && ! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
