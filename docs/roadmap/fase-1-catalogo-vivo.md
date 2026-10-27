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

---

## F1-06 · La API expone los campos nuevos

**Depende:** F1-01 · **Archivos:** `Backend/app/Http/Resources/{ItemDetail,ItemList,Category}Resource.php`, `Backend/app/Http/Controllers/Category/`

**Pasos:**
1. `ItemDetailResource` añade `original_title`, `released_at`, `attributes`, `slug` y `attribution: { data_source: {name, url}, description_source: {name, url, license} }`.
2. `ItemListResource` añade `original_title`, `released_at` y los `attributes` con `show_in_card` (o todos, si es más sencillo y no pesa).
3. `CategoryResource` añade `attribute_schema`, `icon` y `sort_order`.
4. `GET /api/categories` devuelve solo las activas, ordenadas por `sort_order`; las rutas públicas de una categoría inactiva dan 404.

**Hecho cuando:** hay tests de estructura JSON, y una categoría inactiva no sale en el listado y su slug da 404. No se renombra nada (R7).

---

## F1-07 · Importación bajo demanda

**Depende:** F1-05, F0-09 · **Lee:** pipeline §7

**Backend:** `GET /api/catalog/search` y `POST /api/catalog/import`, con `auth:sanctum`, `verified` y `not_banned`, y límites con nombre en `AppServiceProvider` (`RateLimiter::for('catalog-search', …)`, etc.).

**Frontend:** en `/buscar`, con menos de 5 resultados locales, se muestra "¿No lo encuentras? Búscalo en TMDB": un selector entre las categorías con proveedor y, en cada resultado, "Añadir a Kudos", que lleva a la ficha nueva. Sin sesión: "Inicia sesión para añadir".

**Hecho cuando:**
- Tests de autorización, de *throttle*, de "ya existe" (200 con `item_id`, sin duplicar) y de importación (201, con `DownloadItemImagesJob` encolado).
- Flujo manual completo con el token.

---

## F1-08 · Atribución en la interfaz

**Depende:** F1-06 · **Lee:** cumplimiento §1

**Pasos:**
- Pie de página: logo oficial de TMDB (descargado de su página de logos, en `Frontend/public/`) y el texto de no respaldo.
- Ficha: una línea de atribución con enlaces a partir de `attribution`.

**Hecho cuando:** se ve en escritorio y en móvil, y los enlaces llevan `target="_blank" rel="noopener noreferrer"`.

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
