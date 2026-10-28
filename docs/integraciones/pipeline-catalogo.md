# Pipeline del catálogo (`app/Catalog`)

Se implementa en la F1 (TMDB, Wikidata y Wikipedia) y se amplía en la F4 (juegos y destinos). Reglas de las APIs: [fuentes-externas.md](fuentes-externas.md) §1.

## 1. Objetivo

Importar novedades, enriquecer la descripción en español y los IDs cruzados, refrescar dentro de los 6 meses de TMDB e importar bajo demanda lo que no existe. Todo sin duplicados y sin llamar a APIs al servir páginas.

## 2. Archivos

```
Backend/app/Catalog/
├── Contracts/CatalogProvider.php
├── Data/CatalogItemData.php           # DTO readonly normalizado
├── Providers/
│   ├── AbstractTmdbProvider.php       # F1: base común (mapeo, imágenes, géneros, paginación)
│   ├── TmdbMovieProvider.php          # F1
│   ├── TmdbTvProvider.php             # F1 (comparte TmdbClient)
│   ├── IgdbGameProvider.php           # F4 (o RawgGameProvider)
│   └── WikidataPlaceProvider.php      # F4
├── Clients/
│   ├── TmdbClient.php                 # token, rate limit, idioma
│   ├── WikidataClient.php             # wbgetentities y SPARQL
│   ├── WikipediaClient.php            # resumen ES con respaldo en la Action API
│   ├── RetryPolicy.php                # cuándo reintentar (429, 5xx, conexión) y la espera
│   └── Concerns/ThrottlesRequests.php # RateLimiter por proveedor
├── Enrichment/DescriptionResolver.php
├── CatalogImporter.php                # upsert idempotente: DTO → Item + ItemSource
└── CatalogProviderRegistry.php        # proveedor por categoría, según config

Backend/app/Jobs/Catalog/
├── ImportCatalogItemJob.php           # cola "catalog": ImportCatalogItemJob(categorySlug, externalId); importa o refresca un ítem
└── DownloadItemImagesJob.php          # cola "catalog": DownloadItemImagesJob(itemId, images); descarga las imágenes de un ítem

Backend/app/Console/Commands/Catalog/
├── FetchNewCommand.php                # catalog:fetch-new {--category=} {--all} {--limit=}
├── RefreshCommand.php                 # catalog:refresh {--older-than=150} {--batch=200}
├── PurgeStaleCommand.php              # catalog:purge-stale
└── ImportOneCommand.php               # catalog:import {provider} {externalId}
```

El macro `Http::catalog()` (User-Agent, JSON, *timeout* y reintentos) se registra en `app/Providers/CatalogServiceProvider.php`.

La calidad la calcula `app/Services/ItemQualityScorer.php` (F0-05; la regla de +20 por `released_at` o `attributes` ya existía desde la F0-05 y en la F1-01 entran las columnas). Desde la F1-04, una ficha sin descripción nunca llega al umbral (tope de `min_quality_for_discovery` − 1).

El job de importación recibe la **categoría** y no la clave del proveedor: películas y series comparten la clave `tmdb`, y es la categoría (`CatalogProviderRegistry::forCategory()`) la que elige el proveedor. `DownloadItemImagesJob` recibe las URL de las imágenes porque el DTO no se persiste.

## 3. Contratos

```php
interface CatalogProvider
{
    /** Clave tal como se guarda en item_sources.provider (p. ej. 'tmdb'). */
    public function key(): string;

    /** Candidatos para la importación bajo demanda (~10 como máximo; pueden ser parciales). */
    public function search(string $query): array;              // CatalogItemData[]

    /** Ficha completa en español. null si la fuente devuelve 404. */
    public function fetch(string $externalId): ?CatalogItemData;

    /** Novedades y populares para la importación periódica (pueden ser parciales, como los de search(); la ficha completa la da fetch()). */
    public function fetchNew(int $limit): iterable;            // de CatalogItemData
}

final readonly class CatalogItemData
{
    public function __construct(
        public string  $provider,          // 'tmdb'
        public string  $externalId,        // 'movie:603'
        public string  $categorySlug,      // 'peliculas'
        public string  $name,              // en español (o el original si no hay)
        public ?string $originalTitle,
        public ?string $description,       // ya en español; null si no hay
        public ?string $descriptionSource, // 'tmdb' | 'wikipedia_es' | 'wikidata' | null
        public ?string $descriptionUrl,
        public ?string $releasedAt,        // 'Y-m-d'
        public array   $attributes,        // claves según attribute_schema
        public float   $popularity,
        public ?string $wikidataId,
        public ?string $sourceUrl,         // ficha pública en la fuente
        public array   $images,            // [['url', 'role' => 'poster'|'banner', 'license', 'author', 'source_url']]
    ) {}
}
```

## 4. Importación (`CatalogImporter::import(CatalogItemData): Item`)

1. **Buscar si existe:**
   1. por `item_sources (provider, external_id)`;
   2. si no, por `items.wikidata_id` **dentro de la misma categoría**. Si el QID existe en otra categoría, o ya lo tiene un ítem borrado o un ítem con otra ficha primaria del mismo proveedor, registra un aviso en el log e importa sin `wikidata_id` ni fuente `wikidata`: nunca se fusiona ni se quita el QID al otro ítem;
   3. si tampoco, se crea.

   **Nunca** se busca por nombre: es lo que causaba duplicados en el seeder antiguo. Un ítem borrado (soft delete) cuya fuente se vuelve a importar **no se resucita ni se actualiza**.
2. **Descripción** (`DescriptionResolver`), por este orden:
   1. `data.description` (del proveedor, en español);
   2. Wikipedia ES a través del *sitelink* `eswiki` del `wikidataId`;
   3. la descripción `es` de Wikidata (nunca `mul` ni `en`) más un resumen de atributos: la frase con mayúscula inicial y punto final, y después `Géneros`, `Dirección`, `Creación`, `Cadenas`, `Países`, `Duración` (`139 min`) y `Temporadas`, en ese orden y solo los que existan. Por ejemplo: "Película de 1999 dirigida por David Fincher. Géneros: Drama. Dirección: David Fincher. Duración: 139 min.". Una plantilla con datos reales vale; "Item importado." no;
   4. `null`.

   Las respuestas de Wikidata (por QID) y de Wikipedia (por título) se cachean 7 días (`providers.wikimedia.cache_days`) en el *store* por defecto, también "no existe". Si Wikimedia falla, no se aborta la importación: se deja un aviso en el log y se pasa al siguiente paso (el fallo no se cachea, así que el siguiente refresco lo reintenta).
3. **Guardar en una transacción:**
   - `items`: `name`, `original_title`, `description` + fuente + URL, `released_at`, `attributes`, `popularity`, `wikidata_id`, `slug` (solo al crear), `last_synced_at = now()` y `creator_id = null`;
   - `status = active` **solo al crear**. Al refrescar un ítem existente se actualizan los datos pero su `status` no se toca: un ítem desactivado a mano o por un 404 sigue inactivo (lo reactiva un admin) y los borrados nunca vuelven;
   - si el proveedor ya no trae descripción, se conserva la de otra fuente (Wikipedia, Wikidata, manual) en lugar de borrarla; la del propio proveedor sí se borra (términos de TMDB);
   - `item_sources`: el proveedor principal (`is_primary = true`, y es la única primaria del ítem) y, si lo hay, Wikidata (`is_primary = false`).
4. **Imágenes**, fuera de la transacción:
   - las hace `DownloadItemImagesJob(itemId, images)`; solo se descargan si cambian o faltan (la ruta lleva el hash de la URL: `items/{categoría}/{id}/{md5(url)}-{thumb|banner}.webp`);
   - con `RemoteImageDownloader::downloadToTemp()` + `MediaManager::processItemVariants()` (patrón de `database/seeders/Trait/DownloadsSeedImages.php`);
   - cada entrada guarda `role` (`poster`/`banner`), `license`, `author` y `source_url`;
   - si una falla, se registra un aviso y se sigue; si fallan todas, se conservan las actuales;
   - las imágenes que ya no están en la lista se borran del disco (solo las de `items/{categoría}/{id}/`).
5. **`quality_score`** lo recalcula el hook `saving` del modelo; sin descripción nunca llega al umbral.
6. **Idempotente:** el mismo DTO dos veces no crea filas ni vuelve a descargar.

## 5. Refresco y cumplimiento de TMDB

- **`catalog:refresh`** (diario): toma hasta `--batch` ítems con `last_synced_at` anterior a `--older-than` días (150 por defecto, con margen frente a los 180 de TMDB) y encola un `ImportCatalogItemJob` por cada uno. El lote debe recorrer todo el catálogo de TMDB en menos de 150 días (con ~3.000 ítems, 200 al día bastan). Registra un aviso si crecen los pendientes.
- **404 del proveedor:** `status = inactive` y aviso en el log. No se borra, porque puede tener votos.
- **`catalog:purge-stale`** (semanal, red de seguridad): los ítems de TMDB sin refrescar en más de 180 días pasan a inactivos, se vacían sus campos procedentes de TMDB y **se borran sus imágenes de TMDB** del disco y de `images`.
- **Memoria:** los comandos programados solo encolan. El scheduler tiene 128M ([../infra/produccion.md](../infra/produccion.md) §2).

## 6. Tareas programadas (`routes/console.php`)

| Comando | Frecuencia | Notas |
|---|---|---|
| `items:recalculate-stats` | Diaria, 03:00 | Medias y `bayes_score` (F0-03, F0-04) |
| `items:recalculate-quality` | Semanal, lunes 03:30 | Por si cambian las reglas (F0-05) |
| `catalog:fetch-new --all` | Diaria, 04:00 | `CATALOG_IMPORT_LIMIT` por categoría |
| `catalog:refresh` | Diaria, 05:00 | |
| `catalog:purge-stale` | Semanal | |

Todas llevan `withoutOverlapping()`.

## 7. Importación bajo demanda (F1-07)

- **`GET /api/catalog/search?category={slug}&q={texto}`**:
  - con sesión y verificado; límite de 10 por minuto y usuario;
  - llama a `provider->search()` y marca cada resultado con `exists` (y `item_id` si ya está);
  - caché en Redis de 10 minutos por `(categoría, q)`.
- **`POST /api/catalog/import`** con `{category, external_id}`:
  - mismos requisitos; límite de 5 por minuto;
  - de forma síncrona, `provider->fetch()` (*timeout* corto) + `DescriptionResolver` + guardar la ficha, y **se encola siempre** `DownloadItemImagesJob($itemId, $images)`;
  - responde 201 con `ItemDetailResource` (el frontend muestra el *placeholder* hasta que llegan las imágenes). Si ya existía, responde 200 con su `item_id`, sin duplicar;
  - `creator_id` queda `null` (lo crea el sistema); en el log se registra quién lo pidió.

## 8. `config/catalog.php`

> La F0-05 creó el archivo con `min_quality_for_discovery` y `template_description_patterns`, y la F0-09 añadió `reserved_slugs`; la F1-02 lo amplió hasta lo que sigue. Se usa `env('X') ?: defecto` y no `env('X', defecto)`: las variables van vacías en los `.env*.example` (R9) y `env()` devuelve `''`, no el valor por defecto, cuando la variable existe vacía.

```php
return [
    'user_agent' => env('CATALOG_USER_AGENT') ?: 'KudosBot/1.0 (+https://kudos.dcerdan.es)',
    'import_limit' => (int) (env('CATALOG_IMPORT_LIMIT') ?: 50),
    'refresh_older_than_days' => 150,
    'tmdb_max_age_days' => 180,
    'min_quality_for_discovery' => 40,          // F0-05: por debajo, fuera de la portada y la cola
    'template_description_patterns' => ['Item importado', 'importado desde'],   // F0-05
    'reserved_slugs' => ['buscar', 'ranking', 'admin', /* … lista completa en config/catalog.php */],   // F0-09
    'http' => [                                 // macro Http::catalog()
        'timeout_seconds' => 10,
        'retry_times' => 3,                     // intentos en total; solo ante 429, 5xx o fallo de conexión
        'retry_sleep_ms' => 500,                // espera del reintento N: N × este valor
    ],
    'categories' => [
        'peliculas' => ['provider' => \App\Catalog\Providers\TmdbMovieProvider::class],
        'series'    => ['provider' => \App\Catalog\Providers\TmdbTvProvider::class],
        // F4: 'videojuegos' => [...], 'destinos' => [...]
    ],
    'providers' => [
        'tmdb' => ['token' => env('TMDB_API_TOKEN') ?: null, 'rate_per_second' => 20, 'genres_cache_days' => 7],
        'wikimedia' => ['rate_per_minute' => 150, 'sparql_timeout_seconds' => 60, 'cache_days' => 7],
    ],
    // F2-06: 'queue_weights'; F4-01: 'genre_translations'
];
```

## 9. Pruebas

Siempre con `Http::fake()` y fixtures ([../backend/testing.md](../backend/testing.md) §5). Para la verificación manual hace falta un `TMDB_API_TOKEN` real en `Backend/.env`, y **solo** lo pone el humano.
