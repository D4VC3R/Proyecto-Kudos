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
│   ├── TmdbMovieProvider.php          # F1
│   ├── TmdbTvProvider.php             # F1 (comparte TmdbClient)
│   ├── IgdbGameProvider.php           # F4 (o RawgGameProvider)
│   └── WikidataPlaceProvider.php      # F4
├── Clients/
│   ├── TmdbClient.php                 # token, rate limit, idioma
│   ├── WikidataClient.php             # wbgetentities y SPARQL
│   └── WikipediaClient.php            # resumen ES con respaldo en la Action API
├── Enrichment/DescriptionResolver.php
├── CatalogImporter.php                # upsert idempotente: DTO → Item + ItemSource
└── CatalogProviderRegistry.php        # proveedor por categoría, según config

Backend/app/Jobs/Catalog/
├── ImportCatalogItemJob.php           # cola "catalog": importa o refresca un ítem
└── DownloadItemImagesJob.php          # cola "catalog": descarga las imágenes de un ítem

Backend/app/Console/Commands/Catalog/
├── FetchNewCommand.php                # catalog:fetch-new {--category=} {--all} {--limit=}
├── RefreshCommand.php                 # catalog:refresh {--older-than=150} {--batch=200}
├── PurgeStaleCommand.php              # catalog:purge-stale
└── ImportOneCommand.php               # catalog:import {provider} {externalId}
```

La calidad la calcula `app/Services/ItemQualityScorer.php` (F0-05; la regla de +20 por `released_at` o `attributes` ya existía desde la F0-05 y en la F1-01 entran las columnas).

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

    /** Novedades y populares para la importación periódica. */
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
   2. si no, por `items.wikidata_id` **dentro de la misma categoría**. Si el QID existe en otra categoría, registra un aviso en el log e importa sin `wikidata_id`;
   3. si tampoco, se crea.

   **Nunca** se busca por nombre: es lo que causaba duplicados en el seeder antiguo.
2. **Descripción** (`DescriptionResolver`), por este orden:
   1. `data.description` (del proveedor, en español);
   2. Wikipedia ES a través del *sitelink* `eswiki` del `wikidataId`;
   3. la descripción `es` de Wikidata más un resumen de atributos ("Película de 1999 dirigida por …"). Una plantilla con datos reales vale; "Item importado." no;
   4. `null`.
3. **Guardar en una transacción:**
   - `items`: `name`, `original_title`, `description` + fuente + URL, `released_at`, `attributes`, `popularity`, `wikidata_id`, `slug` (solo al crear), `last_synced_at = now()` y `status = active`;
   - `item_sources`: el proveedor principal (`is_primary = true`) y, si lo hay, Wikidata.
4. **Imágenes**, fuera de la transacción:
   - solo si cambian o faltan;
   - con `RemoteImageDownloader::downloadToTemp()` + `MediaManager::processItemVariants()` (patrón de `database/seeders/Trait/DownloadsSeedImages.php`);
   - cada entrada guarda `license`, `author` y `source_url`;
   - si una falla, se registra un aviso y se sigue.
5. **`quality_score`** lo recalcula el hook `saving` del modelo.
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
  - de forma síncrona, `provider->fetch()` (*timeout* corto) + `DescriptionResolver` + guardar la ficha, y **se encola siempre** `DownloadItemImagesJob`;
  - responde 201 con `ItemDetailResource` (el frontend muestra el *placeholder* hasta que llegan las imágenes). Si ya existía, responde 200 con su `item_id`, sin duplicar;
  - `creator_id` queda `null` (lo crea el sistema); en el log se registra quién lo pidió.

## 8. `config/catalog.php`

> La F0-05 creó el archivo con `min_quality_for_discovery` y `template_description_patterns`, y la F0-09 añadió `reserved_slugs`; la F1-02 lo amplía hasta llegar a lo que sigue.

```php
return [
    'user_agent' => env('CATALOG_USER_AGENT', 'KudosBot/1.0 (+https://kudos.dcerdan.es)'),
    'import_limit' => (int) env('CATALOG_IMPORT_LIMIT', 50),
    'refresh_older_than_days' => 150,
    'tmdb_max_age_days' => 180,
    'min_quality_for_discovery' => 40,          // F0-05: por debajo, fuera de la portada y la cola
    'template_description_patterns' => ['Item importado', 'importado desde'],   // F0-05
    'reserved_slugs' => ['buscar', 'ranking', 'admin', /* … lista completa en config/catalog.php */],   // F0-09
    'categories' => [
        'peliculas' => ['provider' => \App\Catalog\Providers\TmdbMovieProvider::class],
        'series'    => ['provider' => \App\Catalog\Providers\TmdbTvProvider::class],
        // F4: 'videojuegos' => [...], 'destinos' => [...]
    ],
    'providers' => [
        'tmdb' => ['token' => env('TMDB_API_TOKEN'), 'rate_per_second' => 20],
        'wikimedia' => ['rate_per_minute' => 150],
    ],
    // F2-06: 'queue_weights'; F4-01: 'genre_translations'
];
```

## 9. Pruebas

Siempre con `Http::fake()` y fixtures ([../backend/testing.md](../backend/testing.md) §5). Para la verificación manual hace falta un `TMDB_API_TOKEN` real en `Backend/.env`, y **solo** lo pone el humano.
