# Fase 1: catálogo vivo (películas y series)

**Objetivo:** que Películas y Series se importen y refresquen solas desde TMDB, en español y sin duplicados; que los usuarios puedan añadir lo que no encuentren; cumplir los términos de TMDB; resetear producción con el catálogo nuevo.

**Lee para toda la fase:** [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md), [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md) §1 y §4, y [../backend/base-de-datos.md](../backend/base-de-datos.md) §3 (Fase 1).

---

## F1-00 · Credenciales (HUMANO)

**Depende:** —

El humano crea una cuenta en themoviedb.org (uso "no comercial / proyecto educativo") y pone el **API Read Access Token (v4)** en `Backend/.env` como `TMDB_API_TOKEN` (y más adelante en `.env.production` del VPS). También define `CATALOG_USER_AGENT` con un correo real.

Los agentes desarrollan con fixtures; el token solo hace falta para la verificación manual.

---

## F1-01 · Migraciones del catálogo

**Depende:** F0-05 · **Archivos:** `Backend/database/migrations/`, `Backend/app/Models/{Item,Category,ItemSource,Vote}.php`, `Backend/app/Services/ItemQualityScorer.php`, `Backend/database/factories/ItemFactory.php`

**Pasos:**
1. Tres migraciones según base-de-datos §3 (Fase 1): `add_catalog_fields_to_items_table` (incluye `description` nullable y los índices trigram y `released_at`), `create_item_sources_table` y `add_catalog_fields_to_categories_table`.
2. Modelos:
   - `Item`: `fillable`, `casts` (`attributes` como array, `released_at` como date, `last_synced_at` como datetime) y `sources()`;
   - nuevo `ItemSource` con `item()`;
   - `Category`: `attribute_schema` como array, `is_active` como bool.
3. `slug` al crear, en `Item::booted()` → `creating`: `Str::slug(name)` + el año si existe, con sufijo si colisiona. `WithoutModelEvents` impide que se ejecute en los seeders.
4. `ItemQualityScorer`: añade la regla de +20; el hook `saving` vigila también `attributes` y `released_at`.
5. Búsqueda en `name` **o** `original_title` en `Item::scopeApplyFilters`, `Item::scopeAdminApplyFilters` y `Vote::scopeApplyFilters`.
6. `ItemFactory`: *states* para los campos nuevos.

**Hecho cuando:**
- `migrate:fresh` funciona y `migrate:rollback` deshace las tres migraciones.
- Los tests existentes siguen en verde.
- Tests nuevos: un ítem se encuentra por su `original_title`; dos ítems con el mismo nombre y año tienen slugs distintos.

**Notas:**
- **Archivos creados** (`Backend/`): migraciones `2026_10_28_000000_add_catalog_fields_to_items_table.php`, `2026_10_28_000100_create_item_sources_table.php` y `2026_10_28_000200_add_catalog_fields_to_categories_table.php`; `app/Models/ItemSource.php`; tests `Feature/Items/ItemOriginalTitleSearchTest.php` (5), `ItemSlugTest.php` (6) y `ItemCatalogFieldsTest.php` (8).
- **Archivos modificados:** `app/Models/{Item,Category,Vote}.php`, `app/Services/ItemQualityScorer.php` (solo un comentario) y `database/factories/ItemFactory.php` (*states* `released`, `withOriginalTitle`, `withCatalogAttributes`, `withoutDescription`, `syncedDaysAgo` e `imported`; `definition()` sin cambios).
- **Desviación R1 (paso 4 ya hecho):** la regla de +20 de `ItemQualityScorer` y el hook `saving` que vigila `released_at` y `attributes` ya los dejó hechos la F0-05 (con su test en `ItemQualityScoreTest`). En la F1-01 solo se actualizaron los comentarios "llegan en la F1-01"; la lógica no se tocó.
- **Slug:** se genera en `creating` solo si viene vacío (`Item::generateUniqueSlug()`), cuenta también los ítems borrados, no cambia al renombrar, y los ítems sembrados con `WithoutModelEvents` quedan con `slug = null` (lo resolverá la F1-10; los ítems existentes tampoco se rellenan en la migración). El slug no se expone aún en la API (F1-06).
- **`attributes` choca con `Model::$attributes`:** dentro del modelo se usa `getAttribute('attributes')`; la *state* de la factory se llama `withCatalogAttributes()` por lo mismo.
- **`down()` de items** convierte las descripciones `null` en `''` para poder restaurar `NOT NULL`.
- **Índice GIN de `attributes`** con nombre `items_attributes_gin_idx`; el trigram de `original_title` es `items_original_title_trgm_idx`. `attributes` y `attribute_schema` se declaran con `default('{}')` / `default('[]')` y PostgreSQL los guarda como `'{}'::jsonb` y `'[]'::jsonb` (comprobado con `\d`).
- **`Category::$fillable` ampliado** (`attribute_schema`, `is_active`, `sort_order`, `icon`) sin exponerlo aún en Form Requests ni Resources (R7: contrato JSON sin cambios).
- **Búsqueda:** `Item::scopeApplyFilters`, `Item::scopeAdminApplyFilters` y `Vote::scopeApplyFilters` buscan en `name` u `original_title` con el OR agrupado y `Like::contains()`.
- **Tests:** jsonb no conserva el orden de las claves de un objeto, así que los tests de `attributes` y `attribute_schema` comparan con `assertEquals`.
- **Verificación:** `sail test --filter=...` 19 passed (48 assertions); `sail test` 119 passed (604 assertions), también tras el ciclo `migrate:fresh` / `migrate:rollback --step=3` / `migrate:status` (las tres en `Pending`) / `migrate` contra `testing`.

---

## F1-02 · Esqueleto de `app/Catalog`

**Depende:** F1-01 · **Lee:** pipeline §2, §3 y §8

**Pasos:**
1. Crea `Contracts/CatalogProvider.php`, `Data/CatalogItemData.php` y `CatalogProviderRegistry.php`.
2. Amplía `config/catalog.php` (pipeline §8) y añade las variables, vacías, a los `.env*.example` (R9).
3. Clientes HTTP, todos con `User-Agent: config('catalog.user_agent')` a través de un macro `Http::catalog()` registrado en un *service provider*:
   - `TmdbClient`: Bearer, `language=es-ES`, *timeout* de 10 s, `retry(3, …)` solo ante 429 y 5xx, y limitación con `RateLimiter`;
   - `WikidataClient`: `getEntity(qid)` y `sparql(query)`;
   - `WikipediaClient`: `summary(title)`, con respaldo en la Action API.

**Hecho cuando:** hay tests unitarios con `Http::fake` que comprueban que se envían el User-Agent y el token, que se reintenta ante 429 y no ante 404, y que `WikipediaClient` recurre a la Action API si REST falla.

**Notas:**
- **Archivos creados** (`Backend/`): `app/Catalog/Contracts/CatalogProvider.php`, `app/Catalog/Data/CatalogItemData.php`, `app/Catalog/CatalogProviderRegistry.php`, `app/Catalog/Clients/{TmdbClient,WikidataClient,WikipediaClient,RetryPolicy}.php`, `app/Catalog/Clients/Concerns/ThrottlesRequests.php` y `app/Providers/CatalogServiceProvider.php`; tests en `tests/Unit/Catalog/`: `Clients/TmdbClientTest.php` (9), `Clients/WikidataClientTest.php` (6), `Clients/WikipediaClientTest.php` (6), `Clients/RetryPolicyTest.php` (3), `CatalogProviderRegistryTest.php` (4), `CatalogConfigTest.php` (1) y `Data/CatalogItemDataTest.php` (1).
- **Archivos modificados:** `config/catalog.php` (claves previas intactas), `bootstrap/providers.php`, `.env.example` y `.env.production.example` (las tres variables, vacías, R9; `docs/infra/produccion.md` §3 ya las documentaba).
- **`Http::catalog()`** centraliza User-Agent, `acceptJson`, *timeout* de 10 s y `retry(3, …, throw: false)` para **todos** los clientes (no solo TMDB, por fuentes-externas §1.5); la política está en `App\Catalog\Clients\RetryPolicy`.
- **Qué devuelve cada cliente:** `Response`/`?array`. Un 404 nunca lanza (lo interpreta el proveedor; en la F1-03 `fetch()` → `null`); 429/5xx persistentes devuelven la última respuesta (TMDB) o lanzan `RequestException` (Wikidata/Wikipedia tras `throw()`); un fallo de conexión persistente lanza `ConnectionException`.
- **Limitación:** `ThrottlesRequests` va en `beforeSending()`, así que cuenta también los reintentos. Wikidata y Wikipedia comparten la clave `catalog:wikimedia` (150/min), también para SPARQL, cuyo *timeout* sube a 60 s.
- **`WikidataClient::getEntity()`** usa `wbgetentities` (con `props`, `languages=es|mul|en` y `sitefilter=eswiki`) en lugar de `Special:EntityData`, porque el payload es mucho menor.
- **`WikipediaClient::summary()`** recurre a la Action API ante cualquier respuesta REST no 2xx (incluido 404) o fallo de conexión; usa `formatversion=2` e `inprop=url`.
- **`TmdbClient::get()`** fuerza `language=es-ES` y lanza `RuntimeException` si falta `TMDB_API_TOKEN` (F1-00 pendiente). Los métodos de alto nivel son de la F1-03.
- **Desviación R1:** `env('X') ?: defecto` en `config/catalog.php` en lugar de `env('X', defecto)` (pipeline §8): las variables van vacías en los `.env*.example` y `env()` devuelve `''` si existen vacías (User-Agent vacío y límite 0). Lo cubre `CatalogConfigTest`.
- **`CatalogServiceProvider`** registrado en `bootstrap/providers.php` (no en `withProviders()` de `bootstrap/app.php`, donde está `MediaServiceProvider`).
- **Verificación:** `sail test --filter=Catalog` 40 passed (101 assertions; incluye los 8 de `ItemCatalogFieldsTest`); `sail test` 149 passed (669 assertions; antes 119).

---

## F1-03 · Proveedores de TMDB

**Depende:** F1-02 · **Lee:** fuentes-externas §4 (TMDB)

**Pasos:**
1. `TmdbMovieProvider` y `TmdbTvProvider` implementan `CatalogProvider`. El `external_id` lleva prefijo: `movie:{id}` o `tv:{id}`.
2. Mapeo:
   - `name` ← `title`/`name` (es-ES);
   - `originalTitle` ← `original_title`/`original_name`;
   - `description` ← `overview` si no está vacío (nunca la sinopsis en inglés);
   - `releasedAt` ← `release_date`/`first_air_date`;
   - `popularity` ← `popularity`;
   - `wikidataId` ← `external_ids.wikidata_id`;
   - `sourceUrl` ← `https://www.themoviedb.org/{movie|tv}/{id}`.
3. `attributes`:
   - películas: `year`, `genres` (nombres es-ES de la lista de géneros, cacheada 7 días), `runtime`, `directors` (`credits.crew` con `job=Director`, máximo 3), `countries`, `original_language`;
   - series: `year`, `genres`, `seasons`, `episodes`, `creators`, `networks`, `status`.
4. Imágenes: póster `w780` (rol `poster`) y fondo `w1280` (rol `banner`), con `license = "© titular de derechos"` y `source_url = sourceUrl`.
5. `fetchNew(limit)` combina, sin duplicados y paginando hasta `limit`: películas `now_playing`, `upcoming` y `popular`; series `on_the_air` y `popular`.
6. Acuerda aquí el `attribute_schema` de `peliculas` y `series` (lo siembra la F1-10).

**Hecho cuando:** hay tests con fixtures recortadas (película completa, película con `overview` vacío, serie, `search` y 404) y `fetch()` de un 404 devuelve `null`.

**Notas:**
- **Archivos creados:** `Backend/app/Catalog/Providers/{AbstractTmdbProvider,TmdbMovieProvider,TmdbTvProvider}.php`, `Backend/tests/Concerns/LoadsFixtures.php`, `Backend/tests/Fixtures/catalog/tmdb/*.json` (7 fixtures) y `Backend/tests/Unit/Catalog/Providers/{TmdbMovieProviderTest,TmdbTvProviderTest}.php`. **Modificados:** `Backend/config/catalog.php` (comentario de `categories` y `providers.tmdb.genres_cache_days = 7`), el docblock de `fetchNew()` en `CatalogProvider.php` y `CatalogProviderRegistryTest.php` (un test nuevo).
- **Base común:** `AbstractTmdbProvider` concentra el mapeo al DTO, las imágenes, la caché de géneros y la paginación de `fetchNew()`; los dos proveedores solo declaran sus campos, rutas y `detailAttributes()`.
- **Discrepancias con la documentación (R1):**
  1. La ficha no lista **Archivos**: salen de pipeline §2, más la base abstracta.
  2. Las series piden solo `append_to_response=external_ids` (fuentes-externas §4 pedía también `credits`): creadores y cadenas vienen en el propio detalle.
  3. Los géneros del detalle (`genres[].name`) ya vienen en es-ES; la lista cacheada 7 días (`/genre/{movie|tv}/list`) solo traduce los `genre_ids` de los listados (`search`, `fetchNew`).
  4. `fetchNew()` devuelve DTO **parciales** (los de los listados, como `search()`) e intercala los listados por página para que las novedades no tapen a las populares. La ficha completa la da `fetch()`: la F1-05 debe encolar un `ImportCatalogItemJob` por `externalId`.
  5. Las fixtures están escritas a mano con la forma documentada de la API (ningún agente puede llamar a TMDB), no capturadas con `curl`. El humano puede sustituirlas por capturas reales recortadas manteniendo los valores que comprueban los tests.
- **Errores:** `fetch()` de un 404 devuelve `null` (una sola petición); cualquier otro error (429/5xx tras los reintentos, 401…) lanza `RequestException`, para que un fallo temporal no se confunda con "ya no existe" (lo que desactivaría el ítem en la F1-04). El `external_id` se valida con su prefijo (`movie:{id}` / `tv:{id}`): uno ajeno lanza `InvalidArgumentException` sin hacer peticiones.
- **Aviso para F1-04/F1-05:** la clave `tmdb` es común a los dos proveedores, así que hay que resolver el proveedor por categoría (o por el prefijo `movie:`/`tv:` del `external_id`), no solo por `item_sources.provider`.
- **Textos en español:** países e idioma original traducidos con `intl` (si intl no conoce el código, se omite; nunca el nombre en inglés de TMDB); el estado de las series se traduce con `TmdbTvProvider::STATUS_LABELS` (uno desconocido no se guarda). Máximo de 3 para dirección, creadores y cadenas. Los géneros de series de TMDB es-ES vienen a veces en inglés ("Sci-Fi & Fantasy") y se guardan tal cual.
- **`attribute_schema`:** el acordado vive en `TmdbMovieProvider::ATTRIBUTE_SCHEMA` y `TmdbTvProvider::ATTRIBUTE_SCHEMA` (en tarjeta solo `year` y `genres`); la F1-10 lo siembra desde ahí. Documentado en base-de-datos §3.
- **Verificación:** `sail test --filter=Tmdb` 36 passed (145 assertions; 9 de `TmdbClientTest`, 26 de los proveedores y el del registro); `sail test --filter=Catalog` 67 passed (226 assertions); `sail test` 176 passed (794 assertions; antes 149). Pint sin cambios pendientes en los archivos nuevos; sin tokens en las fixtures; ninguna petición real.

---

## F1-04 · `DescriptionResolver` y `CatalogImporter`

**Depende:** F1-02 (en paralelo con la F1-03) · **Lee:** pipeline §4 · **Archivos:** `Backend/app/Catalog/**`, `Backend/app/Jobs/Catalog/**`, `Backend/app/Services/Media/RemoteImageDownloader.php`

**Pasos:**
1. `DescriptionResolver::resolve(CatalogItemData): CatalogItemData` aplica la cadena de pipeline §4, paso 2: rellena `description`, `descriptionSource` y `descriptionUrl`, y cachea en Redis durante 7 días las respuestas de Wikidata y Wikipedia (por QID y por título).
2. `CatalogImporter::import(CatalogItemData): Item` hace los pasos 1–6 de pipeline §4 en una transacción, excepto la descarga de imágenes. Reutiliza `RemoteImageDownloader::downloadToTemp()` y `MediaManager::processItemVariants()`.
3. Jobs en la cola `catalog`:
   - `ImportCatalogItemJob(providerKey, externalId)`: `provider->fetch()` → *resolver* → *importer*. Si `fetch()` devuelve `null`, desactiva el ítem existente;
   - `DownloadItemImagesJob(itemId)`: la descarga de imágenes.
4. **Testabilidad:** `RemoteImageDownloader::assertSafeUrl()` llama a `gethostbyname()`. Extrae esa resolución a un colaborador inyectable y falséalo en los tests, manteniendo intacta la protección anti-SSRF.

**Hecho cuando:**
- Importar dos veces el mismo ítem no duplica nada: ni ítems, ni fuentes, ni descargas.
- Una película sin sinopsis es-ES y con `eswiki` recibe la descripción de Wikipedia, con `description_source = wikipedia_es` y `description_url`.
- Sin `eswiki` ni descripción, `description` es `null` y `quality_score` queda por debajo del umbral.
- Si falla una imagen, la importación no se aborta.
- Un QID que ya existe en otra categoría no fusiona los ítems.
- Los tests pasan sin red.

**Notas:**
- **Archivos creados:** `Backend/app/Catalog/Enrichment/DescriptionResolver.php`, `Backend/app/Catalog/CatalogImporter.php`, `Backend/app/Jobs/Catalog/{ImportCatalogItemJob,DownloadItemImagesJob}.php`, `Backend/app/Contracts/Media/HostResolverInterface.php`, `Backend/app/Services/Media/DnsHostResolver.php`, `Backend/tests/Concerns/FakesRemoteImages.php`, las fixtures `Backend/tests/Fixtures/catalog/wikidata/{Q190050-eswiki,Q190050-sin-eswiki,Q190050-sin-eswiki-ni-descripcion-es}.json` y `wikipedia/summary-el-club-de-la-lucha.json`, y los tests `tests/Unit/Catalog/Enrichment/DescriptionResolverTest.php`, `tests/Unit/Services/Media/RemoteImageDownloaderTest.php` y `tests/Feature/Catalog/{CatalogImporterTest,ImportCatalogItemJobTest,DownloadItemImagesJobTest}.php`. **Modificados:** `RemoteImageDownloader.php` (recibe el `HostResolverInterface` por constructor; el filtro anti-SSRF no cambia), `MediaServiceProvider.php`, `CatalogItemData.php` (`withDescription()`), `config/catalog.php` (`providers.wikimedia.cache_days = 7`), `ItemQualityScorer.php` y los tests `ItemQualityScoreTest` y `CatalogItemDataTest` (casos nuevos; los existentes, intactos).
- **Discrepancias con la documentación (R1):**
  1. La ficha pedía `ImportCatalogItemJob(providerKey, externalId)`, pero la clave `tmdb` es común a películas y series (F1-03) y con ella no se sabe qué proveedor llamar. Firma real: **`ImportCatalogItemJob(categorySlug, externalId)`**; resuelve con `CatalogProviderRegistry::forCategory()` y la clave del proveedor sale de `$provider->key()`. El contrato `CatalogProvider` no cambia.
  2. La ficha pedía `DownloadItemImagesJob(itemId)`, pero el job necesita las URL y el DTO no se persiste. Firma real: **`DownloadItemImagesJob(itemId, images)`** (con `CatalogItemData::$images`), que encaja con la F1-07 (guardar en síncrono y encolar siempre la descarga).
  3. Con las reglas de la F0-05, un ítem sin descripción pero con póster y atributos puntuaba 40 + 20 = 60 y era *discoverable*, contra fuentes-externas §5. Ver "Decisiones del humano".
  4. `items.images` gana la clave `role` (`poster`/`banner`), además de `license`, `author` y `source_url`. Solo se añade: ningún Resource cambia (R7).
  5. Pipeline §4 paso 3 pedía `status = active` en cada importación, lo que reactivaría ítems desactivados a mano. Ver "Decisiones del humano".
- **Decisiones del humano:**
  1. **Calidad sin descripción:** una ficha sin descripción (null, vacía o solo espacios) nunca llega al umbral, tenga lo que tenga. Se implementa en `ItemQualityScorer` con un **tope** (`min_quality_for_discovery` − 1), sin tocar los pesos 40/40/20. La ficha de la F0-05 no se reescribe. `items:recalculate-quality` (semanal) lo reaplica si cambia el umbral.
  2. **Estado al refrescar:** `status = active` solo al **crear**. Al reimportar un ítem existente se actualizan los datos y su `status` no se toca, así que un ítem desactivado a mano o por un 404 **tampoco se reactiva solo** si TMDB lo vuelve a servir: lo reactiva un admin. Los ítems borrados (soft delete) nunca vuelven ni se actualizan.
  3. Las firmas de los jobs de arriba.
- **`DescriptionResolver`:** la cadena es la del pipeline §4 paso 2. El título de Wikipedia sale siempre de `sitelinks.eswiki.title`. Solo se usa la descripción `es` de Wikidata (nunca `mul` ni `en`) más los atributos `Géneros`, `Dirección`, `Creación`, `Cadenas`, `Países`, `Duración` y `Temporadas`. **No aborta ante fallos de Wikimedia:** deja un aviso en el log y pasa al siguiente paso; el fallo no se cachea, así que el siguiente refresco lo reintenta. Caché de 7 días (`providers.wikimedia.cache_days`) por QID y por título, incluido "no existe" (se envuelve el valor porque `Cache::remember()` no guarda `null`); solo se cachean `eswiki` y `description_es`, no la entidad entera.
- **`CatalogImporter`:** orden de búsqueda fuente → QID en la misma categoría → crear; nunca por nombre. Un QID que pertenece a otra categoría, a un ítem borrado o a un ítem con otra ficha primaria del mismo proveedor deja el ítem **sin `wikidata_id` ni fuente `wikidata`** (aviso en el log); nunca se fusiona ni se le quita el QID al otro. Si el proveedor ya no trae descripción, se conserva la de otra fuente (Wikipedia, Wikidata, manual); la del propio proveedor sí se borra (términos de TMDB). Una sola fuente primaria por ítem. `deactivate(provider, externalId)` pone `inactive` (no borra, puede tener votos).
- **Imágenes:** `DownloadItemImagesJob` guarda en `items/{categoría}/{id}/{md5(url)}-{thumb|banner}.webp`; si la entrada ya existe y los archivos están en disco, no descarga; si una falla, sigue con las demás; si fallan todas, conserva las actuales; borra del disco las variantes de `items/{categoría}/{id}/` que ya no están. Cada entrada guarda `role`, `license`, `author` y `source_url`.
- **Testabilidad:** `HostResolverInterface` (por defecto `DnsHostResolver`, con `gethostbyname()`, registrado en `MediaServiceProvider`) sustituye a la llamada directa de `RemoteImageDownloader`; en los tests, `FakesRemoteImages::fakeHostResolver()`. Con ese trait, `fakePng()` y los `Http::fake()` de imágenes devuelven la respuesta en un *closure* (una por petición): el cuerpo en streaming de una respuesta reutilizada ya está consumido en la segunda descarga.
- **Aviso para la F1-05:** encolar `ImportCatalogItemJob($categorySlug, $externalId)`. En `catalog:refresh` la categoría sale del ítem; `catalog:import {provider} {externalId}` tendrá que traducir el proveedor a una categoría. Usar `CatalogImporter::deactivate()` para los 404 fuera del job. El worker de producción ya reintenta (`--tries=3`), por eso los jobs no declaran `$tries`.
- **Las fixtures** de Wikidata y Wikipedia están escritas a mano con la forma documentada de las APIs (ningún agente puede llamar a Wikimedia), no capturadas con `curl`.
- **Verificación:** `sail test --filter=ItemQualityScoreTest` 12 passed; `--filter=Items` 68 passed; `--filter=RemoteImageDownloaderTest` 6 passed; `--filter=DescriptionResolverTest` 11 passed; `--filter=CatalogImporterTest` 17 passed; `--filter=ImportCatalogItemJobTest` 9 passed; `--filter=DownloadItemImagesJobTest` 7 passed; `--filter=Catalog` 112 passed (395 assertions); `sail test` **231 passed** (988 assertions; antes 176). `gethostbyname` solo en `DnsHostResolver`; sin tokens en las fixtures; ninguna petición real.

---

## F1-05 · Comandos y programación

**Depende:** F1-03, F1-04, F0-10 · **Lee:** pipeline §5–6 · **Archivos:** `Backend/app/Console/Commands/Catalog/`, `Backend/routes/console.php`

**Comandos:**
- `catalog:fetch-new {--category=} {--all} {--limit=}`
- `catalog:refresh {--older-than=150} {--batch=200}`
- `catalog:purge-stale`
- `catalog:import {provider} {externalId}`

Prográmalos según pipeline §6. Los comandos programados solo encolan jobs.

**Hecho cuando:**
- Tests: `fetch-new` crea ítems a partir de fixtures; `refresh` solo procesa los ítems con un `last_synced_at` antiguo; `purge-stale` desactiva los ítems de TMDB con más de 180 días y borra sus imágenes.
- `schedule:list` muestra las tareas.
- Si hay token: `sail artisan catalog:fetch-new --category=peliculas --limit=20` importa 20 películas en español. Pega en el resumen cuántos ítems hay, cuántos tienen descripción y cuántos superan el umbral.

**Notas:**
- **Archivos creados:** `Backend/app/Console/Commands/Catalog/{FetchNewCommand,RefreshCommand,PurgeStaleCommand,ImportOneCommand}.php`, `Backend/app/Jobs/Catalog/{FetchNewCatalogItemsJob,PurgeStaleCatalogItemsJob}.php`, `Backend/app/Catalog/Contracts/RecognizesExternalIds.php` y los tests `tests/Feature/Catalog/{FetchNewCommandTest,RefreshCommandTest,PurgeStaleCommandTest,ImportOneCommandTest,CatalogScheduleTest}.php`. **Modificados:** `routes/console.php`, `app/Catalog/CatalogProviderRegistry.php` (`categoriesFor()`), `app/Catalog/Providers/AbstractTmdbProvider.php` (`recognizes()`) y, solo con casos nuevos, `CatalogProviderRegistryTest`, `TmdbMovieProviderTest` y `TmdbTvProviderTest`. Sin migraciones ni variables de entorno nuevas.
- **Discrepancias con la documentación (R1):**
  1. La ficha solo listaba `app/Console/Commands/Catalog/` y `routes/console.php`. Para que los comandos programados **solo encolen** (el scheduler tiene 128M), `fetch-new` y `purge-stale` encolan dos jobs nuevos en `app/Jobs/Catalog/` (`FetchNewCatalogItemsJob(categorySlug, limit)` y `PurgeStaleCatalogItemsJob(itemIds)`): el comando no llama a TMDB ni procesa ítems.
  2. `--older-than` no lleva `150` fijo en la firma: su valor por defecto sale de `config('catalog.refresh_older_than_days')` (guía §5). `--batch=200` sí va en la firma.
  3. Pipeline §6 decía "Semanal" para `catalog:purge-stale` sin día ni hora: se programa el **domingo a las 05:30** (después de `catalog:refresh`, y lejos del recálculo de calidad del lunes 03:30).
- **`catalog:import {provider} {externalId}`:** la clave `tmdb` es común a películas y series, así que se añade la interfaz opcional `RecognizesExternalIds` (`recognizes($externalId)`, sin peticiones; la implementa `AbstractTmdbProvider` con `tmdbId()`) y `CatalogProviderRegistry::categoriesFor($providerKey, $externalId)`. No se amplía `CatalogProvider` (R8: el proveedor falso de los tests no la implementa; un proveedor sin ella acepta cualquier `external_id`). `tmdb movie:603` va a `peliculas` y `tmdb tv:1399` a `series`; si ninguna o más de una categoría lo reconoce, o la categoría no existe, el comando falla. No se programa.
- **`fetch-new`:** `--category` o `--all` (exactamente una). `--all` salta con aviso las categorías sin proveedor, inexistentes o inactivas; `--category` sobre una categoría inactiva avisa pero encola (útil para prepararla antes de activarla). Encola **también los ítems que ya existen** (se refrescan sin duplicar) y `--limit` cuenta candidatos de `fetchNew()`, no ítems nuevos. Si `fetchNew()` falla a mitad, el worker reintenta el job y vuelve a encolar los ya encolados (es idempotente).
- **`refresh`:** incluye los ítems inactivos (siguen inactivos: el importador no toca el `status`, decisión del humano en la F1-04) y excluye los borrados. No filtra por `categories.is_active` (los términos de TMDB obligan a refrescar también lo que no se muestra). Solo procesa ítems con fuente primaria; los más antiguos primero (`last_synced_at` nulo antes); si quedan pendientes tras el lote, avisa en el log y en la consola.
- **`purge-stale`:** para los ítems con fuente `tmdb` y `last_synced_at` nulo o anterior a `catalog.tmdb_max_age_days` (180), **incluidos los borrados**: `status = inactive`; se vacían `attributes` y `popularity`, y la sinopsis solo si `description_source = 'tmdb'` (se conserva la de Wikipedia/Wikidata o manual); se quitan de `images` y se borran del disco las imágenes con `source_url` de TMDB (solo archivos de `items/{categoría}/{id}/`) y se renumera `order` de las demás. **Se conservan** `name`, `original_title`, `released_at`, `slug`, `wikidata_id`, `last_synced_at` y las fuentes (identifican un ítem que puede tener votos). El hook `saving` recalcula `quality_score` (sin descripción ni imágenes queda bajo el umbral). El job vuelve a comprobar la fecha (puede haberse refrescado entre el comando y el job) y es idempotente: un ítem ya purgado no se guarda ni se registra. Un ítem purgado vuelve a refrescarse con `catalog:refresh`, pero sigue `inactive` hasta que lo reactive un admin.
- **Comprobación con token (2026-10-28, local, con permiso explícito del humano, una sola ejecución):** `kudos_db` tenía pendientes las 3 migraciones de la F1-01; se aplicaron con `sail artisan migrate` (solo añaden columnas, `item_sources` e índices; los 4.349 ítems antiguos siguen intactos). Después, `sail artisan catalog:fetch-new --category=peliculas --limit=20` (con `QUEUE_CONNECTION=sync`, unos 27 s; `queue:work --queue=catalog --stop-when-empty` no tenía nada pendiente) creó **20 ítems** en `peliculas` y **40 fuentes** (20 `tmdb` y 20 `wikidata`). Los 20 tienen **sinopsis en español** (`description_source = tmdb`), `wikidata_id`, `slug` y `last_synced_at`, y **2 imágenes** cada uno (póster y fondo, con `source_url` de TMDB y licencia "© titular de derechos"); las 80 variantes existen en el disco `public`. Los 20 están `active` y con `quality_score` 100: **20 de 20 superan el umbral** (`min_quality_for_discovery` = 40). 0 jobs fallidos.
- **Verificación:** `sail test --filter=Catalog` 150 passed (556 assertions); `--filter=CatalogProviderRegistryTest` 7 passed; `--filter=TmdbMovieProviderTest` 19 passed; `--filter=TmdbTvProviderTest` 9 passed; `sail test` **269 passed** (1149 assertions; antes 231). `sail artisan schedule:list` muestra las cinco tareas: `items:recalculate-stats` (0 3 * * *), `items:recalculate-quality` (30 3 * * 1), `catalog:fetch-new --all` (0 4 * * *), `catalog:refresh` (0 5 * * *) y `catalog:purge-stale` (30 5 * * 0). `sail artisan list catalog` muestra los cuatro comandos; `catalog:fetch-new` sin opciones falla con "Indica --category=<slug> o --all". Ninguna petición real.

---

## F1-06 · La API expone los campos nuevos

**Depende:** F1-01 · **Archivos:** `Backend/app/Http/Resources/{ItemDetail,ItemList,Category}Resource.php`, `Backend/app/Http/Controllers/Category/`

**Pasos:**
1. `ItemDetailResource` añade `original_title`, `released_at`, `attributes`, `slug` y `attribution: { data_source: {name, url}, description_source: {name, url, license} }`.
2. `ItemListResource` añade `original_title`, `released_at` y los `attributes` con `show_in_card` (o todos, si es más sencillo y no pesa).
3. `CategoryResource` añade `attribute_schema`, `icon` y `sort_order`.
4. `GET /api/categories` devuelve solo las activas, ordenadas por `sort_order`; las rutas públicas de una categoría inactiva dan 404.

**Hecho cuando:** hay tests de estructura JSON, y una categoría inactiva no sale en el listado y su slug da 404. No se renombra nada (R7).

**Notas:**
- **Archivos creados** (`Backend/tests/Feature/`): `Categories/CategoryVisibilityTest.php` (8), `Categories/CategoryResourceTest.php` (5) e `Items/ItemCatalogApiTest.php` (12). Sin migraciones ni variables de entorno.
- **Archivos modificados** (`Backend/app/Http/`): `Resources/{CategoryResource,CategoryWithItemsResource,ItemDetailResource,ItemListResource}.php`, `Controllers/Category/CategoryController.php` y `Controllers/Item/ItemController.php`.
- **Ampliaciones respecto a la ficha (R1):**
  1. Se tocan además `ItemController.php` (los dos `select` de la categoría en `index` y `myItems` incluyen `attribute_schema,icon,sort_order`; sin ello la `category` anidada en los listados salía con `null` y no se podían filtrar los atributos de tarjeta) y `CategoryWithItemsResource.php` (lo devuelve `GET /categories/{slug}`).
  2. `attribution.description_source` añade `license_url` además de `{name, url, license}` (cumplimiento §1 exige enlazar la licencia CC BY-SA). Solo se añade.
  3. `ItemListResource.attributes` contiene solo las claves con `show_in_card` del `attribute_schema` de la categoría, en el orden del esquema; si la categoría no está cargada o no tiene esquema, `{}`.
  4. El 404 de la categoría inactiva se aplica a `show`, `ranking` y `next-item`, para todos (también admin). `PUT`/`DELETE` siguen funcionando con categorías inactivas (comparten el *binding* por slug, por eso se comprueba en el controlador con `ensureActive()` y no en el *binding*). `store()` devuelve `$category->fresh()` para incluir los valores por defecto de la BD (`attribute_schema = []`, `sort_order = 0`).
- **Forma de `attribution`:** `data_source: {name, url} | null` desde la fuente con `is_primary = true` de `item_sources` (null si no hay, como en ítems manuales o heredados); `description_source: {name, url, license, license_url} | null`, null si no hay descripción, no hay `description_source` o es `manual`. Nombres, webs y licencias en `ItemDetailResource::SOURCES` (`tmdb`, `wikipedia_es` con CC BY-SA 4.0, `wikidata` con CC0 1.0); F4 añadirá `igdb` y `rawg`. Una clave desconocida se devuelve tal cual como `name`.
- **Formato:** `attributes` es siempre un objeto JSON (`{}` cuando está vacío, nunca `[]`); `released_at` sale como `YYYY-MM-DD`; `slug` puede ser `null` en ítems sembrados con `WithoutModelEvents`.
- **Desviación R1 (código de error del 404):** el plan y `bootstrap/app.php` suponían `error.code = not_found` para una categoría inexistente, pero el `Handler` de Laravel convierte `ModelNotFoundException` en `NotFoundHttpException` *antes* de los `render()` personalizados, así que el bloque `ModelNotFoundException` de `bootstrap/app.php` nunca se ejecuta y un slug inexistente (y una categoría inactiva) responde `{"error":{"code":"route_not_found","message":"La ruta solicitada no existe."}}`. Se lanza `ModelNotFoundException` para que la respuesta de una inactiva sea idéntica a la de un slug inexistente (hay un test que lo comprueba) y los tests aseguran `route_not_found`. No se ha tocado `bootstrap/app.php` (fuera de alcance).
- **Decisiones para validar:** (1) el listado público y el detalle ocultan las inactivas también a los admin, así que el panel de admin (que usa `GET /api/categories`) no las ve; ningún Form Request acepta `is_active`, la activación es por seeder o consola; (2) `GET /api/items` y `GET /api/items/{id}` **no** filtran por `categories.is_active` (hoy las categorías inactivas no tendrán ítems hasta la F4); (3) una descripción `manual` no se atribuye y TMDB no lleva licencia (F1-08 pone el aviso de no respaldo en el pie).
- **Verificación:** el test que justifica `store()` (`test_crear_una_categoria_devuelve_los_valores_por_defecto`) y el del `select` (`test_el_listado_incluye_el_esquema_de_la_categoria_anidada`) fallan sin esos cambios y pasan con ellos. `sail test` completo: `Tests: 294 passed (1314 assertions)` (antes: 269). Sin los cambios de `app/`, solo pasan `una categoria activa sigue respondiendo` y `un admin puede editar y borrar una categoria inactiva` (guardas de regresión); los otros 23 tests nuevos fallan.

---

## F1-07 · Importación bajo demanda

**Depende:** F1-05, F0-09 · **Lee:** pipeline §7

**Backend:** `GET /api/catalog/search` y `POST /api/catalog/import`, con `auth:sanctum`, `verified` y `not_banned`, y límites con nombre en `AppServiceProvider` (`RateLimiter::for('catalog-search', …)`, etc.).

**Frontend:** en `/buscar`, con menos de 5 resultados locales, se muestra "¿No lo encuentras? Búscalo en TMDB": un selector entre las categorías con proveedor y, en cada resultado, "Añadir a Kudos", que lleva a la ficha nueva. Sin sesión: "Inicia sesión para añadir".

**Hecho cuando:**
- Tests de autorización, de *throttle*, de "ya existe" (200 con `item_id`, sin duplicar) y de importación (201, con `DownloadItemImagesJob` encolado).
- Flujo manual completo con el token.

**Notas:**
- **Archivos creados** (`Backend/`): `app/Actions/ImportCatalogItemOnDemandAction.php`; `app/Catalog/{CatalogSearch.php, Concerns/CallsProvidersOnDemand.php, Data/OnDemandImportResult.php, Exceptions/CatalogUnavailableException.php}`; `app/Http/Controllers/Catalog/CatalogController.php`; `app/Http/Requests/Catalog/{SearchCatalogRequest.php, ImportCatalogItemRequest.php, Concerns/ValidatesCatalogCategory.php}`; `app/Http/Resources/CatalogSearchResultResource.php`; `tests/Feature/Catalog/{CatalogSearchApiTest.php (13), CatalogImportApiTest.php (13)}`. Y en `Frontend/src/`: `hooks/catalog/{useCatalogQueries.js, useCatalogMutations.js}` y `components/catalog/CatalogImportPanel.jsx`.
- **Archivos modificados:** `config/catalog.php` (bloque `on_demand`), `routes/api.php`, `app/Providers/AppServiceProvider.php` (`RateLimiter::for('catalog-search')` y `('catalog-import')`), `app/Http/Resources/CategoryResource.php` (`has_catalog_provider`), `tests/Feature/Categories/CategoryResourceTest.php` (+1 test); `Frontend/src/{lib/search.js, hooks/pages/useSearchPage.js, pages/SearchPage.jsx}`. Sin migraciones ni variables de entorno nuevas (R9).
- **Contrato final** (pipeline §7): `GET /api/catalog/search` devuelve `data` (candidatos con `external_id`, `name`, `original_title`, `released_at`, `year`, `description`, `source_url`, `exists`, `item_id`) y `meta {category, query}`. `POST /api/catalog/import`: **201** `ItemDetailResource`; **200** `{item_id}` si ya existía y está activo (sin llamar al proveedor); **409** `conflict` si existe pero está inactivo o borrado; **404** `not_found` si la fuente no lo tiene; **503** `catalog_unavailable` si la fuente falla, no hay token o hay otra importación del mismo título en curso; **422** `validation_error`; **429** `too_many_requests` con `Retry-After` y `X-RateLimit-*`.
- **Discrepancias con la documentación (R1):** (1) la ficha no lista **Archivos** ni el pipeline §7 dice dónde va el código: se sigue `backend/arquitectura.md` §1 (Action en `app/Actions/`, servicios en `app/Catalog/`); (2) el pipeline §7 solo preveía 201 y 200: se añaden 409, 404 y 503; (3) el pipeline decía "verificado" y la ficha "`auth:sanctum`, `verified` y `not_banned`": se usa el grupo de rutas que ya aplica los tres.
- **Timeout corto:** `catalog.on_demand.timeout_seconds` (5) y `retry_times` (1) sustituyen a `catalog.http.*` durante la petición y se restauran en un `finally` (la macro `Http::catalog()` los lee en cada llamada, así que afecta a TMDB, Wikidata y Wikipedia). Solo se envuelven las llamadas al proveedor y al resolver, nunca `CatalogImporter::import()`. `StrayRequestException` se relanza para que en los tests una petición sin falsear no acabe en 503 (R4).
- **Candado por título:** `Cache::lock('catalog:on-demand-import:{provider}:{external_id}', 60)->block(10 s)`; dos peticiones simultáneas no duplican y la segunda responde 200. Si se agota la espera, 503. En el log queda `user_id`, categoría, `external_id`, resultado e `item_id`; el ítem nace con `creator_id = null`.
- **Los 429** llevan su propio `->response()` porque `bootstrap/app.php` no da formato a los 429 (devolvería `{"message": "Too Many Attempts."}`).
- **`has_catalog_provider`:** campo nuevo en `CategoryResource` (solo se añade, R7), para que el selector del frontend sepa qué categorías ofrecer; se propaga a la categoría anidada de los listados.
- **Búsqueda:** los candidatos (arrays, no DTO) se cachean 10 minutos por `(categoría, texto en minúsculas y sin espacios sobrantes)`; `exists` e `item_id` se calculan en cada petición con dos consultas. Un fallo de la fuente no se cachea.
- **Test de 503 sin cachear:** un segundo `Http::fake()` no sustituye al primero, así que se usa `Http::sequence()` (500 y luego la fixture) en lugar de "cambiar el fake".
- **Verificación:** `--filter=CatalogSearchApiTest` 13 passed (89 assertions); `--filter=CatalogImportApiTest` 13 passed (68); `--filter=CategoryResourceTest` 6 passed (50); `sail test` completo **321 passed** (1474 assertions; antes 294). `route:list --path=catalog` muestra `GET api/catalog/search` con `throttle:catalog-search` y `POST api/catalog/import` con `throttle:catalog-import`. `pnpm build` sin errores. Ninguna petición real (R4). Pint `--test` pasa en los archivos nuevos; en los archivos preexistentes tocados (`config/catalog.php`, `routes/api.php`, `AppServiceProvider.php`, `CategoryResource.php`, `CategoryResourceTest.php`) ya fallaba antes de los cambios por estilo ajeno y no se ha reformateado (R6). `CatalogController` y `CatalogSearchResultResource` usan tabuladores, como `VoteController` y los demás Resources.
- **Comprobación real con el token (hecha por el verificador, con permiso explícito del humano, contra la base local `kudos_db`; nunca producción):** a través de la API (`curl` con un token de Sanctum temporal de un usuario verificado, borrado al terminar). `GET /api/catalog/search?category=peliculas&q=paprika` → 200 en 0,48 s, 10 candidatos (`movie:4977` "Paprika, detective de los sueños", 2006, `exists: false`). `POST /api/catalog/import` `movie:4977` → **201** en 7,5 s (incluye la descarga de las 2 imágenes, porque en local `QUEUE_CONNECTION=sync`), `status active`, `creator_id` null, `wikidata_id Q578595`, sinopsis de TMDB; repetido → **200** en 0,04 s con el mismo `item_id` y sin llamar a TMDB; la búsqueda siguiente lo marca `exists: true` con su `item_id`. En series, `q=mushishi` → `tv:26867` "Mushi-Shi" → **201** en 1,3 s con 2 imágenes. Sin sesión → 401. En el log quedan las dos líneas `Catálogo: importación bajo demanda.` con el `user_id`. Los dos ítems se quedan en la base local. **No** se ha probado la interfaz en un navegador: el panel de `/buscar` (incluido "Inicia sesión para añadir" y la vuelta a `/buscar?q=…`) solo está comprobado con el código y `pnpm build`. Observación ajena a esta tarea: TMDB devuelve algún título explícito sin la marca `adult` aunque se pida `include_adult=false` (en `q=paprika` sale uno).
- **Decisiones para validar:** (1) el campo `has_catalog_provider` (alternativa: lista fija `['peliculas', 'series']` en el frontend); (2) las respuestas 409/404/503 no previstas en el pipeline, y que un ítem inactivo o borrado salga en la búsqueda con `exists: true` e `item_id: null` ("No disponible"); (3) timeout de 5 s y un solo intento para TMDB, Wikidata y Wikipedia (en el peor caso, una importación tarda unos 20 s); (4) la búsqueda se lanza con un botón y los resultados son solo texto, sin pósteres (enlazar `image.tmdb.org` desde el navegador podría chocar con fuentes-externas §1.2); (5) el panel es nuevo, hecho solo con componentes y clases existentes; (6) el flujo manual con el token real.

---

## F1-08 · Atribución en la interfaz

**Depende:** F1-06 · **Lee:** cumplimiento §1

**Pasos:**
- Pie de página: logo oficial de TMDB (descargado de su página de logos, en `Frontend/public/`) y el texto de no respaldo.
- Ficha: una línea de atribución con enlaces a partir de `attribution`.

**Hecho cuando:** se ve en escritorio y en móvil, y los enlaces llevan `target="_blank" rel="noopener noreferrer"`.

**Notas:**
- **Archivos creados:** `Frontend/public/tmdb-logo.svg`, `Frontend/src/lib/attribution.js` (`hasAttribution`) y `Frontend/src/components/items/ItemAttribution.jsx` (solo pinta). **Modificados:** `Frontend/src/components/layout/Footer.jsx` y `Frontend/src/components/items/ItemDetailColumn.jsx`. Sin cambios en `Backend/` (R7), sin variables ni dependencias nuevas.
- **Logo:** variante "Alt short (blue)" de https://www.themoviedb.org/about/logos-attribution, descargada de `https://www.themoviedb.org/assets/v4/logos/v2/blue_short-8e7b30f73a4020692ccca9c88bafe5dcb6f8a62a4c6bc55cd9ba82bb2cd95f6c.svg` **sin modificar** (2065 bytes, SHA-256 `8e7b30f73a4020692ccca9c88bafe5dcb6f8a62a4c6bc55cd9ba82bb2cd95f6c`). Se pinta a 16 px de alto (`h-4`), menos prominente que el logo de Kudos de la cabecera, y enlaza a `https://www.themoviedb.org`.
- **Desviación R1 (texto de no respaldo):** `legal/cumplimiento.md` §1 decía "This product uses the TMDB API but is not endorsed or certified by TMDB.", que no es lo que exigen los términos de la API de TMDB (https://www.themoviedb.org/api-terms-of-use, §3 *Attribution*, última actualización 20-10-2023). Se usa el literal de los términos con "website": "This website uses TMDB and the TMDB APIs but is not endorsed, certified, or otherwise approved by TMDB.", más la traducción propia "Esta web usa TMDB y sus API, pero TMDB no la respalda, certifica ni aprueba." Los términos no han cambiado desde 2023. Se ha corregido cumplimiento §1.
- **Pie:** segunda fila dentro del `<footer>` (separada por un borde), con logo y aviso en inglés + traducción; se ve en todas las páginas. La fila anterior no se ha tocado.
- **Línea de la ficha:** "Datos: TMDB · Descripción: Wikipedia, licencia CC BY-SA 4.0", en texto pequeño gris justo debajo de la caja "Acerca de…" (o al pie de la columna si no hay descripción). Cada fuente enlaza a su `url` y la licencia a `license_url`; sin URL se pinta solo el nombre. Sin `attribution` no se pinta nada ni queda hueco. Con sinopsis de TMDB sale "Datos: TMDB · Descripción: TMDB".
- **Verificación:** `docker compose run --rm kudos-frontend pnpm build` sin errores (`✓ built in 3.00s`); `dist/tmdb-logo.svg` existe (2065 bytes) y el SHA-256 de `public/tmdb-logo.svg` coincide; `grep`: 2 `target="_blank"` y 2 `rel="noopener noreferrer"` en `Footer.jsx`, 1 y 1 en `ItemAttribution.jsx`. Capturas con Chrome sin interfaz (`zenika/alpine-chrome:124`) en `.claude/ciclo/F1-08/capturas/` (ignorada por git): `ficha-movil.png` (360 px; "Datos: TMDB · Descripción: TMDB" bajo la descripción y, al final, logo y textos del pie apilados y centrados, sin desbordar), `ficha-escritorio.png` (1280x900; la ficha de escritorio tiene la altura de la ventana y el pie queda debajo del pliegue), `pie-escritorio.png` (`/buscar?q=zzzz`; pie con logo y texto en una fila), y el caso Wikipedia: `ficha-wikipedia-movil.png` ("Datos: TMDB · Descripción: Wikipedia, licencia CC BY-SA 4.0", con la coma pegada a "Wikipedia") y `ficha-wikipedia-escritorio-alta.png` (1280x2200, la línea bajo la caja "Acerca de…"). El caso Wikipedia se simuló cambiando `description_source`/`description_url` de Paprika en la base **local** (valores originales `tmdb` y `https://www.themoviedb.org/movie/4977`, restaurados y confirmados con la API). No se ha probado en un navegador real ni se han abierto los enlaces.
- **Decisiones para validar:** (1) el texto de no respaldo (literal de los términos) y su traducción; (2) logo "Alt short (blue)" a 16 px en una segunda fila del pie; (3) formato de la línea de la ficha, que repite la fuente con sinopsis de TMDB; (4) la comprobación visual es con capturas de Chrome sin interfaz, no en un navegador real.

---

## F1-09 · Chips de atributos

**Depende:** F1-06 · **Lee:** base-de-datos §3 (`attribute_schema`)

**Pasos:**
1. Componente `AttributeChips(schema, attributes)`:
   - `tags` → varios chips;
   - `minutes` → "2 h 16 min";
   - `date` → formato local español;
   - `integer` → tal cual.
2. En la ficha, todos los atributos con valor; en `ItemCard`, solo los que tienen `show_in_card`.
3. Los filtros por `filterable` quedan **fuera** de esta tarea.

**Hecho cuando:** una película muestra año, géneros, duración y dirección, y un ítem sin atributos no muestra nada roto.

---

## F1-10 · Sembrado nuevo y *runbook* del reset

**Depende:** F1-05 · **Lee:** produccion §5 · **Archivos:** `Backend/database/seeders/`, `docs/infra/entorno-local.md`, `docs/infra/produccion.md`

**Pasos:**
1. `CategorySeeder`:
   - `peliculas` y `series`: activas, con `attribute_schema`, `icon` y `sort_order`;
   - `videojuegos` y `destinos`: creadas pero inactivas hasta la F4;
   - quita libros, álbumes, artistas, países, ciudades y políticos. **Los JSON de `seed-data/` se conservan.**
2. `DatabaseSeeder`: quita `ItemSeeder` y `ProposalSeeder`; deja `VoteSeeder` solo si `app()->isLocal()`. **No borres `ItemSeeder.php`**: márcalo como heredado en su docblock.
3. Documenta en entorno-local §3 la secuencia de desarrollo:
   ```bash
   sail artisan migrate:fresh --seed
   sail artisan catalog:fetch-new --all --limit=50
   sail artisan db:seed --class=VoteSeeder
   ```
4. Revisa el *runbook* de produccion §5 y confirma la ruta real de las imágenes de ítems en el disco `public`.

**Hecho cuando:** la secuencia funciona en local (con token o con un proveedor falso) y el *runbook* está revisado. **No se ejecuta nada en producción.**

---

## F1-11 · Reset de producción (HUMANO)

**Depende:** F1-10

El humano fusiona en `main`, ejecuta el *runbook* y anota aquí la fecha y el resultado (ítems importados e incidencias).
