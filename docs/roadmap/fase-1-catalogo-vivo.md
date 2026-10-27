# Fase 1: catálogo vivo (películas y series)

**Objetivo:**
- Que Películas y Series se importen y refresquen solas desde TMDB, con descripciones en español (de TMDB o, en su defecto, de la Wikipedia ES) y sin duplicados.
- Que los usuarios puedan añadir lo que no encuentren.
- Que se cumplan los términos de TMDB: 6 meses como máximo y atribución.
- Que producción se resetee con el catálogo nuevo.

**Lecturas comunes:**
- [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md)
- [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md)
- [../backend/base-de-datos.md](../backend/base-de-datos.md) (Fase 1)
- [../legal/cumplimiento.md](../legal/cumplimiento.md)

---

## F1-00 · Credenciales (HUMANO)

- **Responsabilidad:** HUMANO
- **Depende de:** —

**Qué hace el humano:**
- Crea una cuenta en themoviedb.org, solicita la API con uso "no comercial / proyecto educativo" y copia el **API Read Access Token (v4)**.
- Lo pone en `Backend/.env` como `TMDB_API_TOKEN` y, más adelante, en `.env.production` en el VPS.
- Define `CATALOG_USER_AGENT` con un correo de contacto real.

**Los agentes** pueden desarrollar todo con fixtures sin el token. Solo lo necesitan para la verificación manual.

---

## F1-01 · Migraciones del catálogo

- **Responsabilidad:** BE
- **Depende de:** F0-05

**Pasos:**
1. **Tres migraciones nuevas,** según [../backend/base-de-datos.md](../backend/base-de-datos.md) (Fase 1):
   - `add_catalog_fields_to_items_table`, que incluye `description` nullable y los índices trigram y `released_at`;
   - `create_item_sources_table`;
   - `add_catalog_fields_to_categories_table`.
2. **Modelos:**
   - `Item`: `fillable` y `casts` (`attributes` como array, `released_at` como date, `last_synced_at` como datetime) y la relación `sources()`;
   - nuevo `ItemSource` con su relación `item()`;
   - `Category`: `attribute_schema` como array e `is_active` como bool.
3. **Generación de `slug`** al crear (`Str::slug(name)` más el año si existe, con sufijo si colisiona) en `Item::booted()` → `creating`. Ojo: `DatabaseSeeder` usa `WithoutModelEvents`, así que el hook no se ejecuta en los seeders.
4. **`ItemQualityScorer`** añade la regla de +20 puntos (`released_at` o `attributes`). El hook `saving` vigila también esos campos.
5. **Búsqueda:** `Item::scopeApplyFilters` y `scopeAdminApplyFilters` buscan en `name` **o** `original_title`. `Vote::scopeApplyFilters` también.
6. **Factories:** `ItemFactory` con *states* para los campos nuevos.

**Criterios de aceptación:**
- `migrate:fresh` funciona y `migrate:rollback` deshace las tres migraciones.
- Los tests existentes siguen en verde.
- Test nuevo: se encuentra un ítem por su `original_title`.
- Test nuevo: crear dos ítems con el mismo nombre y año genera slugs distintos.

---

## F1-02 · Esqueleto del módulo `app/Catalog`

- **Responsabilidad:** INT
- **Depende de:** F1-01

**Pasos:**
1. **Crea** `Contracts/CatalogProvider.php`, `Data/CatalogItemData.php` y `CatalogProviderRegistry.php` según [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md) §2–§3.
2. **Amplía `config/catalog.php`** (§8) y añade las variables a `.env.example` y `.env.production.example`, vacías.
3. **Clientes HTTP:**
   - `TmdbClient`: Bearer, `language=es-ES`, *timeout* de 10 s, `retry(3, …)` solo para 429 y 5xx, y limitación de peticiones con `RateLimiter` de Redis;
   - `WikidataClient`: `getEntity(qid)` y `sparql(query)`;
   - `WikipediaClient`: `summary(title)` con respaldo en la Action API.

   Todos envían `User-Agent: config('catalog.user_agent')`. Para no repetirlo, crea un macro `Http::catalog()` en un *service provider*.

**Criterios de aceptación:**
- Tests unitarios de los clientes con `Http::fake`: envían el User-Agent y el token, reintentan ante 429 y no reintentan ante 404.
- `WikipediaClient` recurre a la Action API cuando REST falla.

---

## F1-03 · Proveedores de TMDB

- **Responsabilidad:** INT
- **Depende de:** F1-02

**Pasos:**
1. **`TmdbMovieProvider` y `TmdbTvProvider`,** que implementan `CatalogProvider`. Los endpoints están en [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md) §4.
2. **`external_id`** con prefijo: `movie:{id}` o `tv:{id}`.
3. **Mapeo de campos:**
   - `name` ← `title` / `name` (es-ES);
   - `originalTitle` ← `original_title` / `original_name`;
   - `description` ← `overview` si no está vacío;
   - `releasedAt` ← `release_date` / `first_air_date`;
   - `popularity` ← `popularity`;
   - `wikidataId` ← `external_ids.wikidata_id`;
   - `sourceUrl` ← `https://www.themoviedb.org/movie/{id}`.
4. **`attributes`:**
   - películas: `year`, `genres` (nombres en español de la lista de géneros es-ES, cacheada 7 días), `runtime`, `directors` (de `credits.crew` con `job=Director`, máximo 3), `countries`, `original_language`;
   - series: `year`, `genres`, `seasons`, `episodes`, `creators`, `networks`, `status`.
5. **Imágenes:** póster en `w780` (rol `poster`) y fondo en `w1280` (rol `banner`), con `license = "© titular de derechos"` y `source_url = sourceUrl`.
6. **`fetchNew(limit)`** combina, sin duplicados:
   - películas: `now_playing`, `upcoming` y `popular`;
   - series: `on_the_air` y `popular`.

   Pagina hasta alcanzar `limit`.
7. **Define el `attribute_schema`** de las categorías `peliculas` y `series` en `CategorySeeder`. Se hace en la F1-10, pero se acuerda aquí.

**Criterios de aceptación:**
- Tests con fixtures reales recortadas: una película completa, una con `overview` vacío, una serie, `search` y un 404.
- `fetch()` de un 404 devuelve `null`.

---

## F1-04 · `DescriptionResolver` y `CatalogImporter`

- **Responsabilidad:** INT, BE
- **Depende de:** F1-02 (se puede hacer en paralelo con la F1-03)

**Pasos:**
1. **`DescriptionResolver::resolve(CatalogItemData): CatalogItemData`** aplica la cadena de prioridades de [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md) §4, paso 2:
   - rellena `description`, `descriptionSource` y `descriptionUrl`;
   - **cachea** en Redis 7 días las respuestas de Wikidata y Wikipedia por QID y título.
2. **`CatalogImporter::import(CatalogItemData): Item`:** los pasos 1–6 del §4, todo en una transacción excepto la descarga de imágenes. Reutiliza `RemoteImageDownloader` y `MediaManager::processItemVariants`. El cruce por `wikidata_id` se limita a la misma categoría.
   - **Testabilidad:** extrae la resolución DNS de `RemoteImageDownloader::assertSafeUrl()` (hoy `gethostbyname()`) a un colaborador inyectable y falséalo en los tests (ver [../backend/testing.md](../backend/testing.md) §5). Mantén la protección anti-SSRF intacta.
3. **`ImportCatalogItemJob`** en la cola `catalog`:
   - recibe `(providerKey, externalId)`, llama a `provider->fetch()`, luego al *resolver* y luego al *importer*;
   - si `fetch()` devuelve `null`, desactiva el ítem existente.

**Criterios de aceptación:**
- Importar dos veces el mismo ítem no crea duplicados (ni ítems, ni fuentes, ni imágenes descargadas otra vez).
- Una película sin sinopsis en español con un `wikidata_id` que tiene `eswiki` recibe la descripción de la Wikipedia, con `description_source = wikipedia_es` y `description_url`.
- Sin `eswiki` ni descripción, `description` queda en `null` y `quality_score` por debajo del umbral.
- Si falla la descarga de una imagen, la importación no se aborta.

---

## F1-05 · Comandos y programación

- **Responsabilidad:** BE
- **Depende de:** F1-03, F1-04, F0-10

**Comandos** (en `app/Console/Commands/Catalog/`):
- `catalog:fetch-new {--category=} {--all} {--limit=}`
- `catalog:refresh {--older-than=150} {--batch=200}`
- `catalog:purge-stale`
- `catalog:import {provider} {externalId}`

Están descritos en [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md) §5–§6.

**Programación:** en `routes/console.php`, con las frecuencias de la tabla del §6 y `withoutOverlapping()`. Los comandos solo encolan `ImportCatalogItemJob`; no importan en el propio proceso (el scheduler tiene 128M).

`purge-stale` también borra las imágenes de TMDB descargadas.

**Criterios de aceptación:**
- **Tests:**
  - `fetch-new` crea ítems a partir de las fixtures;
  - `refresh` solo procesa los ítems con un `last_synced_at` antiguo;
  - `purge-stale` desactiva los ítems de TMDB con más de 180 días.
- **`schedule:list`** muestra las tareas.
- **Verificación manual,** si el humano ha puesto el token: `sail artisan catalog:fetch-new --category=peliculas --limit=20` importa 20 películas con fichas en español. Pega en el resumen el número de ítems, cuántos tienen descripción y cuántos superan el umbral de calidad.

---

## F1-06 · La API expone los campos nuevos

- **Responsabilidad:** BE
- **Depende de:** F1-01

**Pasos:**
1. **Resources de ítems:**
   - `ItemDetailResource` añade `original_title`, `released_at`, `attributes` y `slug`;
   - añade también `attribution`: `{ data_source: {name, url}, description_source: {name, url, license} }`;
   - `ItemListResource` añade `original_title`, `released_at` y los `attributes` marcados con `show_in_card` (o todos, si es más sencillo y no pesa).
2. **Resources de categorías:** `CategoryResource` añade `attribute_schema`, `icon` y `sort_order`.
3. **Categorías inactivas:**
   - `GET /api/categories` devuelve solo las activas, ordenadas por `sort_order`;
   - las rutas públicas de una categoría inactiva devuelven 404.
4. **Contrato:** no se renombra ningún campo existente.

**Criterios de aceptación:** tests de estructura JSON; una categoría inactiva no aparece en el listado y su slug da 404.

---

## F1-07 · Importación bajo demanda

- **Responsabilidad:** BE, FE
- **Depende de:** F1-05, F0-09

**Backend:** los endpoints `GET /api/catalog/search` y `POST /api/catalog/import`, según [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md) §7:
- middleware `auth:sanctum`, `verified` y `not_banned`;
- límites de peticiones con nombre en `AppServiceProvider` (`RateLimiter::for('catalog-search', …)`).

**Frontend:** en `/buscar`, si hay menos de 5 resultados locales, se muestra la sección "¿No lo encuentras? Búscalo en TMDB":
- tiene un selector de categoría entre las que tienen proveedor;
- cada resultado lleva un botón "Añadir a Kudos" que, al terminar, lleva a la ficha nueva;
- los usuarios sin sesión ven "Inicia sesión para añadir".

**Criterios de aceptación:**
- Tests de autorización, de límite de peticiones, de "ya existe" (devuelve `item_id` y no duplica) y de importación correcta (201, con la descarga de imágenes encolada).
- Flujo manual completo con el token.
- `pnpm build` termina sin errores.

---

## F1-08 · Atribución en la interfaz

- **Responsabilidad:** FE, LEGAL
- **Depende de:** F1-06
- **Lectura:** [../legal/cumplimiento.md](../legal/cumplimiento.md) §2

**Pasos:**
- **Pie de página:** logo de TMDB (descárgalo de su página oficial de logos y súbelo como asset estático en `Frontend/public/`) y el texto de no respaldo.
- **Ficha:** una línea de atribución con enlaces a partir de `attribution`.

**Criterios de aceptación:** visible en escritorio y en móvil; los enlaces se abren en una pestaña nueva con `rel="noopener noreferrer"`.

---

## F1-09 · Chips de atributos genéricos

- **Responsabilidad:** FE
- **Depende de:** F1-06

**Pasos:**
1. **Componente `AttributeChips`** que recibe `schema` y `attributes` y renderiza cada tipo:
   - `tags` como varios chips;
   - `minutes` como "2 h 16 min";
   - `date` en formato local español;
   - `integer` tal cual.
2. **Dónde se usa:**
   - en la ficha: todos los atributos con valor;
   - en `ItemCard`: solo los marcados con `show_in_card`.
3. **Filtros:** si `filterable`, Explorar puede filtrar por ellos más adelante. **No es parte de esta tarea**; basta con dejarlo preparado.

**Criterios de aceptación:** una película muestra año, géneros, duración y dirección; un ítem sin atributos no muestra nada roto.

---

## F1-10 · Sembrado nuevo y *runbook* del reset

- **Responsabilidad:** BE, INFRA
- **Depende de:** F1-05

**Pasos:**
1. **`CategorySeeder`:**
   - crea solo `peliculas` y `series` (activas, con `attribute_schema`, `icon` y `sort_order`);
   - deja `videojuegos` y `destinos` creadas pero **inactivas** hasta la F4.

   Elimina del seeder las demás categorías (libros, álbumes, artistas, países, ciudades, políticos). Los JSON de `seed-data/` **se conservan**.
2. **`DatabaseSeeder`:**
   - quita `ItemSeeder` y `ProposalSeeder` de la llamada por defecto;
   - deja `VoteSeeder` **solo** si `app()->isLocal()`, para tener datos de desarrollo después de importar.

   **No borres `ItemSeeder.php`:** márcalo como heredado en su docblock.
3. **Orden de desarrollo** documentado en [../infra/entorno-local.md](../infra/entorno-local.md):

   ```bash
   sail artisan migrate:fresh --seed
   sail artisan catalog:fetch-new --all --limit=50
   sail artisan db:seed --class=VoteSeeder
   ```

4. **Revisa y completa el *runbook*** de [../infra/produccion.md](../infra/produccion.md) §5. Confirma la ruta real de las imágenes de ítems en el disco `public`.

**Criterios de aceptación:**
- La secuencia del paso 3 funciona en local (con el token o con un proveedor falso).
- El *runbook* está revisado.
- **No se ejecuta nada en producción.**

---

## F1-11 · Reset de producción (HUMANO)

- **Responsabilidad:** HUMANO
- **Depende de:** F1-10

**Qué hace el humano:**
- Fusiona en `main` y ejecuta el *runbook*.
- Anota aquí la fecha y el resultado (número de ítems importados, incidencias).
