# Pipeline del catálogo: diseño del módulo `app/Catalog`

**Responsable:** integraciones y backend. Se implementa en la Fase 1 (TMDB y Wikidata/Wikipedia) y se amplía en la Fase 4 (juegos y destinos).

## 1. Objetivo

Construir y mantener el catálogo de forma automática:
- **importar** ítems nuevos y novedades de cada categoría viva;
- **enriquecer** la descripción en español y los IDs cruzados;
- **refrescar** los datos periódicamente, respetando los 6 meses de TMDB;
- **importar bajo demanda** lo que busca un usuario y no existe aún.

Todo ello sin duplicados y sin llamar a APIs al servir páginas.

## 2. Estructura de archivos

```
app/Catalog/
├── Contracts/
│   └── CatalogProvider.php          # interfaz de proveedor
├── Data/
│   └── CatalogItemData.php          # DTO inmutable y normalizado (readonly class)
├── Providers/
│   ├── TmdbMovieProvider.php        # F1 (películas)
│   ├── TmdbTvProvider.php           # F1 (series); puede compartir un TmdbClient
│   ├── IgdbGameProvider.php         # F4 (o RawgGameProvider)
│   └── WikidataPlaceProvider.php    # F4 (destinos)
├── Clients/
│   ├── TmdbClient.php               # HTTP: token, rate limit, idioma
│   ├── WikidataClient.php           # wbgetentities y SPARQL
│   └── WikipediaClient.php          # resumen ES con respaldo en la Action API
├── Enrichment/
│   └── DescriptionResolver.php      # cadena de prioridades de la descripción
├── CatalogImporter.php              # upsert idempotente: DTO → Item + ItemSource + imágenes
└── CatalogProviderRegistry.php      # devuelve el proveedor de cada categoría según config

# La calidad se calcula con app/Services/ItemQualityScorer.php (creado en la F0-05; la F1-04 añade la regla de +20)

app/Jobs/Catalog/
├── ImportCatalogItemJob.php         # cola "catalog": importa o refresca un único ítem
└── DownloadItemImagesJob.php        # opcional: separa la descarga de imágenes

app/Console/Commands/Catalog/
├── FetchNewCommand.php              # catalog:fetch-new {--category=} {--all} {--limit=}
├── RefreshCommand.php               # catalog:refresh {--older-than=150} {--batch=200}
└── ImportOneCommand.php             # catalog:import {provider} {externalId}

config/catalog.php                   # user_agent, proveedores por categoría, límites, mapas de géneros
```

## 3. Contratos

```php
interface CatalogProvider
{
    /** Clave del proveedor tal como se guarda en item_sources.provider (por ejemplo 'tmdb'). */
    public function key(): string;

    /** Busca candidatos en la fuente para la importación bajo demanda (máximo ~10). */
    public function search(string $query): array;              // CatalogItemData[] (pueden ser parciales)

    /** Ficha completa en español de un ítem. null si la fuente devuelve 404. */
    public function fetch(string $externalId): ?CatalogItemData;

    /** Novedades y populares para la importación periódica. */
    public function fetchNew(int $limit): iterable;            // de CatalogItemData
}
```

```php
final readonly class CatalogItemData
{
    public function __construct(
        public string  $provider,          // 'tmdb'
        public string  $externalId,        // 'movie:603'
        public string  $categorySlug,      // 'peliculas'
        public string  $name,              // título en español (o el original si no hay)
        public ?string $originalTitle,
        public ?string $description,       // ya en español; null si no hay
        public ?string $descriptionSource, // 'tmdb' | 'wikipedia_es' | 'wikidata' | null
        public ?string $descriptionUrl,
        public ?string $releasedAt,        // 'Y-m-d'
        public array   $attributes,        // claves según categories.attribute_schema
        public float   $popularity,
        public ?string $wikidataId,
        public ?string $sourceUrl,         // ficha pública en la fuente
        public array   $images,            // [['url' => …, 'role' => 'poster'|'banner', 'license' => …, 'author' => …, 'source_url' => …]]
    ) {}
}
```

## 4. Flujo de importación (`CatalogImporter::import(CatalogItemData $data): Item`)

1. **Buscar si ya existe:**
   1. por `item_sources (provider, external_id)`;
   2. si no aparece, por `items.wikidata_id`;
   3. si tampoco, se crea.

   **Nunca** se busca por nombre: es lo que causaba duplicados en el seeder antiguo.
2. **Resolver la descripción** con `DescriptionResolver`, en este orden:
   1. `data.description` (el proveedor en español);
   2. Wikipedia ES, usando el *sitelink* `eswiki` del `wikidataId`;
   3. la descripción de Wikidata en `es` más un resumen de los atributos. Por ejemplo: "Película de 1999 dirigida por …". Una plantilla construida con datos reales vale; "Item importado." no;
   4. `null`.
3. **Guardar el ítem dentro de una transacción:**
   - en `items`: `name`, `original_title`, `description` y su fuente y URL, `released_at`, `attributes`, `popularity`, `wikidata_id`, `slug` (solo al crear), `last_synced_at = now()`, `status = active`;
   - en `item_sources`: el proveedor principal con `is_primary = true` y, si existe, Wikidata.
4. **Descargar las imágenes:**
   - solo si cambian o no existen;
   - se usa `RemoteImageDownloader::downloadToTemp()` y `MediaManager::processItemVariants()`, siguiendo el patrón de `database/seeders/Trait/DownloadsSeedImages.php`;
   - en cada entrada se guardan `license`, `author` y `source_url`;
   - si falla una imagen, se registra un aviso en el log y se continúa.
5. **Recalcular `quality_score`** con `ItemQualityScorer`, que se ejecuta solo desde el hook `saving` del modelo.
6. **Idempotencia:** importar dos veces el mismo DTO no crea filas nuevas ni descarga de nuevo las imágenes.

## 5. Refresco y cumplimiento de TMDB

- **`catalog:refresh` diario:**
  - toma hasta `--batch` ítems cuyo `last_synced_at` sea más antiguo que `--older-than` días (150 por defecto, lo que deja margen frente a los 180 de TMDB);
  - encola un `ImportCatalogItemJob` por cada uno.
- **El tamaño del lote** debe garantizar que todo el catálogo de TMDB se recorra en menos de 150 días. Con unos 3.000 ítems, 200 al día bastan. El comando avisa en el log si el número de pendientes crece.
- **Si el proveedor devuelve 404,** el ítem pasa a `status = inactive` y se registra en el log. No se borra, porque puede tener votos.
- **Ítems de TMDB sin refrescar en más de 180 días:** el comando `catalog:purge-stale` los marca como inactivos y vacía sus campos procedentes de TMDB. Se ejecuta cada semana como red de seguridad.

## 6. Tareas programadas (`routes/console.php`)

| Comando | Frecuencia | Notas |
|---|---|---|
| `catalog:fetch-new --all` | Diaria, 04:00 | Límite de `CATALOG_IMPORT_LIMIT` por categoría |
| `catalog:refresh` | Diaria, 05:00 | `withoutOverlapping()` |
| `catalog:purge-stale` | Semanal | Red de seguridad para TMDB |
| `items:recalculate-stats` | Diaria, 03:00 | Medias y `bayes_score` de todos los ítems (F0-03 y F0-04) |
| `items:recalculate-quality` | Semanal | Por si cambian las reglas de calidad |

## 7. Importación bajo demanda (F1-07)

- **`GET /api/catalog/search?category={slug}&q={texto}`**
  - Requiere estar autenticado y verificado, con un límite de 10 peticiones por minuto por usuario.
  - Llama a `provider->search()` y marca cada resultado con `exists: true/false` y `item_id` cuando ya está en la base de datos.
  - Cachea la respuesta en Redis 10 minutos por `(categoría, q)`.
- **`POST /api/catalog/import`** con `{category, external_id}`
  - Mismos requisitos, con un límite de 5 peticiones por minuto.
  - Importa **de forma síncrona** con un *timeout* corto y devuelve el ítem con `ItemDetailResource`.
  - Si tarda, encola el job y devuelve 202 con `{status: "queued"}`.
  - El `creator_id` del ítem queda vacío, porque lo crea el sistema. Se registra en el log quién lo pidió.

## 8. Configuración (`config/catalog.php`)

```php
return [
    'user_agent' => env('CATALOG_USER_AGENT', 'KudosBot/1.0 (+https://kudos.dcerdan.es)'),
    'import_limit' => (int) env('CATALOG_IMPORT_LIMIT', 50),
    'refresh_older_than_days' => 150,
    'tmdb_max_age_days' => 180,
    'min_quality_for_discovery' => 40,        // por debajo: fuera de la portada y de la cola (F0-05)
    'categories' => [
        'peliculas' => ['provider' => \App\Catalog\Providers\TmdbMovieProvider::class],
        'series'    => ['provider' => \App\Catalog\Providers\TmdbTvProvider::class],
        // F4: 'videojuegos' => [...], 'destinos' => [...]
    ],
    'providers' => [
        'tmdb' => ['token' => env('TMDB_API_TOKEN'), 'rate_per_second' => 20],
        'wikimedia' => ['rate_per_minute' => 150],
    ],
];
```

## 9. Pruebas

Todo se prueba con `Http::fake()` y fixtures. Ver [../backend/testing.md](../backend/testing.md) §5. Para verificar a mano en local se necesita un `TMDB_API_TOKEN` real en `Backend/.env`, que **solo** pone el humano.
