# Fase 0: higiene y base

**Objetivo:** red de tests, puntuaciones correctas, ranking honesto, fichas pobres ocultas, deuda visual arreglada e infraestructura (scheduler y colas) lista para la Fase 1.

**Lee para toda la fase:** [../backend/arquitectura.md](../backend/arquitectura.md), [../backend/testing.md](../backend/testing.md) y [../infra/entorno-local.md](../infra/entorno-local.md) §3–4.

---

## F0-01 · Tests sobre PostgreSQL

**Depende:** — · **Archivos:** `Backend/phpunit.xml`, `Backend/compose.yml`, `Backend/tests/**`

**Pasos:**
1. `phpunit.xml`: sustituye `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:` por `pgsql` / `testing`, ambos con `force="true"` (bloque completo en testing §1).
2. `compose.yml`, servicio `pgsql`: monta `./docker/pgsql/create-testing-database.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql` (el script ya existe).
3. Crea `tests/TestCase.php` **exactamente** con la protección de testing §3 (en `setUpTraits()`, ni en `setUp()` ni en `beforeRefreshingDatabase()`), y las carpetas `tests/Unit/`, `tests/Fixtures/` y `tests/Concerns/` (`tests/Feature/` ya existe, vacía).
4. Crea los helpers `seedRolesAndPermissions()`, `actingAsUser()` y `actingAsAdmin()` (testing §3).
5. Escribe un *smoke test*: `GET /up` → 200; `GET /api/categories` → 200 con un JSON válido.

**Hecho cuando:**
- `sail test` pasa.
- `Backend/scripts/verify-test-db-guard.sh` termina en `OK`: con otra base de datos forzada (un señuelo con una tabla `items`), los tests abortan con el mensaje de protección **y** el número de filas del señuelo es el mismo antes y después.

**Notas:**
- **Protección en `setUpTraits()`, no en `beforeRefreshingDatabase()`.** El código original de testing §3 no funcionaba: el método de un trait tiene prioridad sobre el heredado, así que el `beforeRefreshingDatabase()` vacío de `RefreshDatabase` tapaba el de `TestCase`. Con `: void` daba un error fatal de firma, y sin él la protección no se habría ejecutado nunca. `setUpTraits()` corre con la configuración cargada y antes de `RefreshDatabase`, `DatabaseMigrations` y `DatabaseTruncation`. testing §3 queda corregido.
- **`DatabaseProtectionTest`:** test extra que comprueba que los tests usan `testing` y que la protección lanza la excepción con otra base de datos (solo cambia la configuración en memoria, sin conectarse a `kudos_db`).
- **Helpers** en `tests/Concerns/InteractsWithUsers.php`. `actingAsUser()` no asigna rol porque el registro tampoco lo hace (solo `UserSeeder` asigna `user`). `seedRolesAndPermissions()` fuerza el guard `web` al sembrar: tras `Sanctum::actingAs()` el guard por defecto es `sanctum` y los roles se creaban con ese guard, así que `assignRole('admin')` fallaba.
- **`APP_SERVICE=kudos-app`:** no estaba en `.env.example`, y sin esa variable `sail test` y `sail artisan` fallan (`service "laravel.test" is not running`). Se añade con valor porque no es un secreto ni se usa en producción. entorno-local §3 queda actualizado.
- **Credenciales:** el `.env` local puede no usar `sail`/`password` (el volumen existente se creó con otro usuario). entorno-local §4 ahora crea `testing` con el script montado y `$POSTGRES_USER`.
- **Prueba de la protección automatizada:** en lugar de forzar `kudos_db` (arriesga los datos de desarrollo y el entorno de permisos del agente lo bloquea), `scripts/verify-test-db-guard.sh` usa una base de datos señuelo desechable. Es equivalente, porque la protección rechaza cualquier nombre distinto de `testing`. Control negativo: con la protección comentada, el señuelo pasa de 3 filas a 0 y el script devuelve `FALLO`.

---

## F0-02 · Batería base de tests

**Depende:** F0-01 · **Objetivo:** fijar el comportamiento **actual** antes de cambiarlo (unos 15–25 tests).

| Área | Casos |
|---|---|
| Votos | Emitir (201/200, actualiza `vote_count`); voto duplicado (devuelve el existente y no suma); `skip` (no cambia la media); un ítem inactivo no se puede votar; usuario baneado o sin verificar → 403; cambiar la nota; borrar el voto |
| Kudos | El primer voto da `vote_first_time_item`; repetir no da más; rendimiento decreciente por encima de 20 votos diarios; tope diario de 50; `kudos:audit-consistency` sin desajustes tras esos flujos |
| Ranking | `GET /api/categories/{slug}/ranking` ordena por media y excluye los inactivos |
| Cola | `GET /api/categories/{slug}/next-item` no devuelve ítems votados ni inactivos; devuelve null o vacío cuando no queda nada (`Cache::flush()` antes) |
| Ítems | `GET /api/items?search=` encuentra por nombre parcial sin distinguir mayúsculas; el detalle devuelve la estructura esperada |

- Usa solo notas enteras: los medios puntos van en la F0-03.
- **Si encuentras otros bugs, no los arregles:** márcalos con `markTestIncomplete` y anótalos en esta ficha.

**Hecho cuando:** todos los casos están implementados y en verde.

**Notas:**
- **Archivos creados** (todos en `Backend/tests/`, 24 tests nuevos: 23 en verde y 1 *incomplete*):
  - `Feature/Votes/EmitVoteTest.php` (6), `ChangeVoteTest.php` (2), `DeleteVoteTest.php` (2) y `NextCategoryItemTest.php` (2): 12 tests de votos y cola;
  - `Feature/Kudos/VoteKudosTest.php` (6);
  - `Feature/Ranking/CategoryRankingTest.php` (2);
  - `Feature/Items/ItemSearchTest.php` (1) y `ItemDetailTest.php` (3);
  - `Concerns/CreatesItems.php`: trait con `createCategory()` y `createItem()`. No se añadió a `TestCase`: cada clase lo usa con `use CreatesItems;`. Existe porque `ItemFactory` y `VoteFactory` eligen usuario, categoría o ítem al azar de la base de datos en `definition()` (que se ejecuta aunque haya *overrides*), así que antes de crear un ítem tiene que haber un usuario y una categoría; y porque `CategoryFactory` puede repetir `name`/`slug` (únicos) entre dos categorías del mismo test.
- **Desviación R1 (cola):** cuando no queda nada por votar, `GET /api/categories/{slug}/next-item` responde **204 No Content** sin cuerpo, no "null o vacío".
- **Desviación R1 (ítem inactivo):** votar un ítem inactivo responde **403** (`VotePolicy::create()` en `StoreVoteRequest::authorize()`, que va antes de la validación), no 422. El 422 "No se puede votar un item inactivo." de `StoreVoteRequest::withValidator()` es inalcanzable por `POST /api/votes`. No se ha tocado.
- **Bug encontrado (sin arreglar):** `ShowItemRequest::authorize()` devuelve `$this->user()?->can('view', …) ?? true`, así que un invitado recibe `true` y se salta `ItemPolicy::view()`, que niega los ítems inactivos a los invitados. Un invitado ve el detalle de un ítem inactivo (200 en lugar de 403). El test `ItemDetailTest::test_un_invitado_no_ve_el_detalle_de_un_item_inactivo` queda *incomplete* (`markTestIncomplete` condicional) y pasará solo cuando se arregle.
- **Ranking:** los datos (A 9/10, B 9/4, C 7/10, D sin votos) se eligieron para que el orden no cambie con el ranking bayesiano de la F0-04 (m=5, C=5 de *fallback*).
- **Kudos:** los importes salen de `config('kudos.voting')`, no están escritos a mano, porque la F2-03 los reajustará. El tiempo se congela con `travelTo()` para que el TTL `endOfDay()` del contador diario no caduque a mitad de los bucles de 21 y 51 votos.
- `can_edit` del detalle es `true` para el creador del ítem (no solo para admins): el test del detalle crea el ítem antes de autenticar al usuario para que el creador sea otro.

---

## F0-03 · Medias exactas (bug del truncado)

**Depende:** F0-01 · **Lee:** arquitectura §3 (trampas 1 y 2) · **Archivos:** `Backend/app/Actions/{Emit,Change,Delete}VoteAction.php`, `Backend/app/Http/Requests/Votes/{Store,Update}VoteRequest.php`, `Backend/routes/console.php`

**Pasos:**
1. **Primero, el test que falla:** votar 7.5 y 8.0 da una media de 7.75; cambiar 7.5 por 6.5 guarda `score = 6.5` y la media pasa a 7.25; borrar recalcula de forma exacta.
2. Crea `app/Services/ItemStatsService.php` con `recalculate(string $itemId): Item`. Se ejecuta dentro de la transacción del llamante, con `Item::lockForUpdate()`, y calcula `COUNT(*)` y `AVG(score)` de `votes` con `type = 'vote'`. Guarda `vote_count` y `vote_avg` (`round(…, 2)`, o 0 si no hay votos).
3. Sustituye los cálculos incrementales de las tres Actions por llamadas al servicio. Elimina todos los casts `(int)`. `ChangeVoteAction` guarda la nota como float.
4. Añade `multiple_of:0.5` a `score` en `StoreVoteRequest` y `UpdateVoteRequest`, con mensaje en español.
5. Crea el comando `items:recalculate-stats {--category=}`: recorre los ítems con `chunkById` y **encola un job por bloque** en la cola `catalog`, que llama al servicio (motivo en produccion §2). Prográmalo a diario a las 03:00 con `withoutOverlapping()`.

**Hecho cuando:** el test del paso 1 pasa, la F0-02 sigue en verde y `grep -rn "(int)" Backend/app/Actions` no devuelve ningún cast sobre notas.

**Notas:**
- **Archivos creados** (`Backend/`):
  - `app/Services/ItemStatsService.php`, `app/Jobs/RecalculateItemStatsJob.php` y `app/Console/Commands/RecalculateItemStatsCommand.php` (la carpeta `Commands` es nueva y Laravel la descubre sola);
  - tests: `tests/Feature/Votes/ExactAverageTest.php` (4), `tests/Feature/Votes/VoteScoreValidationTest.php` (3) y `tests/Feature/Items/RecalculateItemStatsCommandTest.php` (5).
- **Archivos modificados:**
  - `app/Actions/{Emit,Change,Delete}VoteAction.php`: usan `ItemStatsService`; desaparecen `updateItemAverages` y los `(int)`;
  - `app/Http/Requests/Votes/{Store,Update}VoteRequest.php`: `multiple_of:0.5` con mensaje en español;
  - `routes/console.php`: `Schedule::command('items:recalculate-stats')->dailyAt('03:00')->withoutOverlapping()`;
  - `database/factories/VoteFactory.php`: *state* `rated(float)`.
- **Test fallando antes del arreglo** (`sail test --filter=ExactAverageTest`, `Tests: 4 failed (11 assertions)`):
  - `Failed asserting that 7.5 matches expected 7.75` (votar 8.0 y 7.5: la media incremental truncaba 7.5 a 7);
  - `Failed asserting that 6 matches expected 6.5` (`PUT` con 6.5 guardaba `score = 6`);
  - `Failed asserting that 7.0 matches expected 7.33` (tres votos 7.5, 8.0 y 6.5);
  - el cuarto fallaba porque aún no existía `VoteFactory::rated()` (`BadMethodCallException`).
  - Después del arreglo: `Tests: 4 passed (23 assertions)`.
- **Decisiones:**
  - `--category` recibe el **slug** (la clave de ruta de `Category`);
  - `--chunk` (200 por defecto) se añade a la ficha para poder probar "un job por bloque" sin crear 200 ítems;
  - el job abre una transacción corta por ítem, para que el bloqueo de la fila dure solo su recálculo, y ignora los ítems borrados (soft delete) entre el encolado y la ejecución;
  - `EmitVoteAction` solo recalcula cuando `type === 'vote'`: un `skip` no cambia la media ni el contador (lo exige `EmitVoteTest`);
  - el recálculo se hace **después** de escribir o borrar el voto, para que `COUNT`/`AVG` lo incluyan o excluyan;
  - `ChangeVoteAction` ya no hace `lockForUpdate` por su cuenta: lo hace el servicio;
  - `VoteFactory::rated(float)` es el *state* que recomendaba `testing.md` §4; `withScore(int)` se deja igual.
- **Programación:** `items:recalculate-stats` ya queda programado aquí. La F0-10 solo tiene que verificarlo con `schedule:list` (`0 3 * * *`).
- **Cola `catalog`:** hasta la F0-10 el worker de producción (`compose.prod.yml`) solo escucha la cola `default`, así que los jobs de `catalog` no se procesarían allí. No se ha desplegado nada.
- **Riesgo conocido (no corregido, R6):** el `INSERT` de `votes` toma un `KEY SHARE` sobre la fila del ítem (por la FK) y después el servicio hace `FOR UPDATE`. Dos votos simultáneos al mismo ítem pueden acabar en *deadlock*: Postgres aborta uno y ese usuario recibe un 500. Ya ocurría con `EmitVoteAction` antes de la F0-03. `ChangeVoteAction` no tiene este riesgo (actualizar el voto no bloquea al padre).
- El frontend ya envía múltiplos de 0.5 entre 0.5 y 10, así que `multiple_of:0.5` no cambia la interfaz.

---

## F0-04 · Ranking bayesiano

**Depende:** F0-03 · **Lee:** base-de-datos §3 (Fase 0) · **Archivos:** `Backend/app/Services/ItemStatsService.php`, `Backend/app/Models/{Category,Item}.php`, `Backend/config/kudos.php`

**Fórmula:** `bayes = (v·R + m·C) / (v + m)` si `v > 0`; `bayes = 0` si `v = 0` (los ítems sin votos van al final).
- `v` = `vote_count`; `R` = `vote_avg`.
- `C` = media de todos los votos `type = 'vote'` de la categoría, cacheada 1 h en `category-mean:{id}`. Si no hay votos, `ranking.category_mean_fallback` (5.0).
- `m` = `config('kudos.ranking.bayes_min_votes')`, 5 por defecto.

**Pasos:**
1. Migración con `bayes_score` y su índice (base-de-datos §3, Fase 0).
2. `config/kudos.php` → sección `ranking` con `bayes_min_votes` y `category_mean_fallback`, cada una comentada.
3. `ItemStatsService::recalculate` también calcula `bayes_score`. `items:recalculate-stats` invalida y recalcula `C` antes de encolar nada.
4. Ordena por `bayes_score`:
   - `Category::getRankingPaginator()`: `bayes_score desc`, `vote_count desc`, `name`;
   - `Item::scopeApplySorting`, opción `vote_avg` (la usan los destacados y "Mejor valorados"): `bayes_score desc`, `vote_count desc`.
   - **No cambies** el parámetro `sort_by=vote_avg` ni los campos del JSON (R7).

**Hecho cuando:**
- Test: con m=5 y C≈6, un ítem con un único 10 queda **por debajo** de uno con 20 votos y media 8.
- Test: los ítems sin votos quedan al final.
- El JSON del ranking sigue incluyendo `vote_avg` y `vote_count`.

**Notas:**
- **Archivos creados** (`Backend/`):
  - `database/migrations/2026_10_27_000000_add_bayes_score_to_items_table.php`;
  - `tests/Feature/Ranking/BayesianRankingTest.php` (8 tests).
- **Archivos modificados:**
  - `app/Services/ItemStatsService.php`: `recalculate()` guarda `bayes_score`; nuevos `bayesScore()`, `categoryMean()`, `refreshCategoryMean()`, `categoryMeanCacheKey()` y la constante `CATEGORY_MEAN_TTL`;
  - `app/Console/Commands/RecalculateItemStatsCommand.php` y `app/Jobs/RecalculateItemStatsJob.php` (solo el texto);
  - `app/Models/Item.php` (`$attributes`, `$fillable`, `$casts` y rama `vote_avg` de `applySorting`) y `app/Models/Category.php` (`getRankingPaginator`);
  - `config/kudos.php`: sección `ranking`;
  - `tests/Concerns/CreatesItems.php` y `database/seeders/VoteSeeder.php`;
  - docs: `base-de-datos.md` (§2 y §3) y `arquitectura.md` (§2).
- **Migración:** se llama `add_bayes_score_to_items_table` y no `add_ranking_and_quality_to_items_table` (como decía `base-de-datos.md` §3), porque la F0-05 crea su propia migración con `quality_score` y R5 impide editar migraciones existentes. El documento ya está corregido (R1).
- **`CreatesItems::createItem()` calcula `bayes_score`** a partir de `vote_avg`/`vote_count` con C igual al *fallback* (5.0), salvo que el test lo pase. Motivo: `CategoryRankingTest` escribe `vote_avg`/`vote_count` sin votos reales; con `bayes_score = 0` el orden saldría [A, C, B, D] en vez de [A, B, C, D]. Con C = 5 salen A = 7.667, B = 6.778, C = 6.333 y D = 0, y el test pasa sin tocarlo (R8).
- **`VoteSeeder` calcula `bayes_score`:** escribe `vote_avg`/`vote_count` con `update()` directo, sin pasar por el servicio; sin este cambio, `migrate:fresh --seed` dejaría todos los `bayes_score` a 0. Lo cubre `test_el_seeder_de_votos_calcula_bayes_score`.
- **C** excluye los votos de ítems borrados (soft delete) e incluye los de ítems inactivos. `bayes_score` se redondea a 4 decimales y el TTL de la caché es una constante del servicio (`CATEGORY_MEAN_TTL`, 3600 s), no una opción de `config`.
- **Retraso de C:** como C se cachea 1 h, los votos en vivo usan una C con hasta 1 h de retraso; `items:recalculate-stats` la refresca antes de encolar.
- **`sort_order=asc`** sigue respetándose: en ese caso los ítems sin votos (`bayes_score = 0`) salen primero.
- **Pendiente de producción (no se ha tocado, R10):** los ítems existentes tienen `bayes_score = 0` hasta ejecutar `items:recalculate-stats` al desplegar, y eso necesita el worker de la cola `catalog` (F0-10).
- **Test antes del arreglo:** `sail test --filter=BayesianRankingTest` daba `Tests: 8 failed (13 assertions)`; los fallos eran `Call to undefined method ItemStatsService::bayesScore()` / `categoryMeanCacheKey()` (los métodos aún no existían), así que no llegó a mostrar el orden [X, Y, Z] antiguo.
- **Verificación:**
  - `sail test --filter=BayesianRankingTest` → `Tests: 8 passed (53 assertions)`;
  - `sail test` → `Tests: 1 incomplete, 48 passed (362 assertions)` (el incompleto es el `markTestIncomplete` de `ShowItemRequest`, anterior a esta tarea);
  - contra `testing`: `migrate:fresh` (OK), `migrate:rollback --step=1` (OK, `hasColumn` = `false`) y `migrate` (OK, `hasColumn` = `true`);
  - `grep -rn "orderByDesc('vote_avg')\|orderBy('vote_avg'" app/Models` → sin resultados.

---

## F0-05 · `quality_score` y exclusión de fichas pobres

**Depende:** F0-01 · **Lee:** base-de-datos §3 (Fase 0) · **Archivos:** `Backend/app/Models/Item.php`, `Backend/app/Services/NextCategoryItemService.php`, `Backend/app/Http/Requests/Items/ListItemsRequest.php`, `Backend/database/seeders/DatabaseSeeder.php`, `Frontend/src/hooks/pages/useCategoryPage.js`

**Reglas** (0–100):

| Condición | Puntos |
|---|---|
| Al menos una imagen en `images` | +40 |
| `description` de 60 caracteres o más, sin patrones de plantilla | +40 |
| `released_at` no nulo o `attributes` no vacío (se añade en la F1-01) | +20 |

- Los patrones de plantilla están en `config/catalog.php` → `template_description_patterns` (al principio: `Item importado`, `importado desde`).
- El umbral es `config('catalog.min_quality_for_discovery')`, 40 por defecto.

**Pasos:**
1. Migración con `quality_score` y su índice. Crea `config/catalog.php` solo con estas claves (la F1-02 lo amplía).
2. Crea `app/Services/ItemQualityScorer.php` → `score(Item $item): int`.
3. En `Item::booted()` → `saving`: si cambian `images` o `description`, recalcula `quality_score`.
4. `DatabaseSeeder` usa `WithoutModelEvents`, así que el hook no se ejecuta al sembrar: al final de `run()`, llama a `Artisan::call('items:recalculate-quality')`.
5. Crea el comando `items:recalculate-quality`, que funciona por bloques y encola igual que en la F0-03. Prográmalo semanalmente.
6. Excluye los ítems con `quality_score < umbral` de `NextCategoryItemService::generateNewBatch()` y de `GET /api/items` cuando llegue el parámetro nuevo `discoverable=1` (validado en `ListItemsRequest`).
7. Frontend: `useCategoryPage.js` (destacados) envía `discoverable: 1`.

**Hecho cuando:**
- Tests: un ítem sin imagen y con "Item importado." puntúa menos de 40; no aparece en la cola ni con `?discoverable=1`; **sí** aparece en Explorar y en la búsqueda.
- Tras `migrate:fresh --seed`, `quality_score` está calculado (no todo a 0).

---

## F0-06 · *Placeholder* generativo

**Depende:** — · **Lee:** frontend §4 · **Archivos:** `Frontend/src/components/ui/{StorageImage,ItemPlaceholder}.jsx`, `Frontend/src/components/items/ItemImage.jsx`, `Frontend/src/lib/constants.js`

**Pasos:**
1. Crea `components/ui/ItemPlaceholder.jsx` con las props `name`, `categorySlug`, `className` y `variant` (`card` | `banner`). El fondo es un degradado HSL de dos tonos derivado de un hash estable del nombre, con saturación y luminosidad que den contraste AA con texto blanco. Encima, el icono de la categoría y hasta dos iniciales.
2. En `lib/constants.js`, un mapa `slug → icono lucide` con un icono por defecto. Usa `category.icon` si existe (llega en la F1).
3. Usa el *placeholder* en `StorageImage.jsx` (en lugar de `ImageOff` cuando es un ítem; ya tiene la prop `fallbackIcon` y `onError`) y en `ItemImage.jsx` cuando no haya imagen o falle su carga.

**Hecho cuando:** las tarjetas y fichas sin imagen o con la imagen rota muestran el *placeholder*, y el mismo nombre da siempre el mismo color.

---

## F0-07 · Ocultar secciones vacías

**Depende:** — · **Archivos:** `Frontend/src/components/items/ItemDetailColumn.jsx`, `ItemCard`

**Pasos:** no renderices "Acerca de…" si `description` es nulo o queda vacío tras `trim()`. Aplica el mismo criterio a cualquier otra sección vacía de la ficha o la tarjeta (imágenes, etc.). El creador ya se protege con `item.creator &&`; mantenlo, porque los ítems importados no tienen creador.

**Hecho cuando:** una ficha sin descripción no muestra una caja vacía.

---

## F0-08 · Clases CSS sin definir

**Depende:** — · **Lee:** frontend §3 · **Archivos:** `Frontend/tailwind.config.js`, `Frontend/src/index.css` y los archivos que devuelva el `grep`

**Pasos:** aplica la solución de la tabla de frontend §3 a cada clase y revisa visualmente cada página afectada.

**Hecho cuando:** el `grep` de frontend §3 no devuelve nada y no se ha añadido ningún plugin de Tailwind.

---

## F0-09 · Búsqueda global y slugs reservados

**Depende:** F0-01 · **Lee:** frontend §2 y §4 · **Archivos:** `Backend/app/Http/Requests/Items/ListItemsRequest.php`, `Backend/app/Http/Requests/Category/{Store,Update}CategoryRequest.php`, `Frontend/src/components/layout/{MainHeader,NavBarMobile}.jsx`, `Frontend/src/routes/routes.jsx`

**Backend:**
1. `GET /api/items?search=…&per_page=…` sin categoría ya funciona, y `ItemListResource` ya incluye `category` con *eager loading*. Verifícalo con un test.
2. `search` con `min:2`. Explorar usa la misma petición: comprueba que `useFilters` no envía búsquedas de 1 carácter.
3. `reserved_slugs` en `config/catalog.php` (créalo si la F0-05 no lo ha hecho) y valídalo en `StoreCategoryRequest` y `UpdateCategoryRequest`.

**Frontend:**
1. Componente `GlobalSearch` en `MainHeader` y `NavBarMobile`: *debounce* de 300 ms (reutiliza `useFilters` si encaja); con Enter, navega a `/buscar?q=`.
2. Ruta `/buscar` → `SearchPage` + `useSearchPage`: consulta infinita, resultados agrupados por categoría con `ItemCard`, y estados de vacío y de carga con los *skeletons* existentes.

**Hecho cuando:** buscar "mat" encuentra "Matrix" desde cualquier página; `/buscar` no se trata como categoría; una categoría con slug `buscar` da 422.

---

## F0-10 · Scheduler, colas y memoria en producción

**Depende:** — · **Lee:** produccion §2 · **Archivos:** `Backend/compose.prod.yml`, `deploy/README.md`, `Backend/routes/console.php`

**Pasos:**
1. En `compose.prod.yml`, aplica produccion §2: servicio `scheduler`, worker con `--queue=default,catalog` y 512M, y el comentario de presupuesto de memoria.
2. En `deploy/README.md`, documenta el servicio y cómo comprobarlo: `kudos-dc logs scheduler` y `kudos-dc exec app php artisan schedule:list`.
3. En `routes/console.php`, programa las tareas ya existentes en esta fase (`items:recalculate-stats` ya se programa en la F0-03, solo hay que verificarlo; `items:recalculate-quality`). Esos comandos solo encolan.
4. Valida con `docker compose -f compose.prod.yml --env-file .env.production.example config > /dev/null`. Si hace falta un `.env` temporal, no lo subas.

**Hecho cuando:** `config` es válido, `sail artisan schedule:list` muestra las tareas y **no se ha desplegado nada**.

---

## F0-11 · Copia de seguridad de imágenes (prioridad baja)

**Depende:** — · **Lee:** produccion §4

**Hecho cuando:** existe un script con rotación, sin avisos de `shellcheck` (si está disponible), documentado en `deploy/README.md` con su línea de cron.

---

## F0-12 · Corregir el README

**Depende:** F0-01 · **Archivos:** `README.md`, `Backend/README.md`

**Pasos:** MySQL → PostgreSQL; quita "Spatie Media Library" y "Telescope" si no están en `composer.json`; explica cómo ejecutar los tests; enlaza a `docs/`.

**Hecho cuando:** cada afirmación coincide con `composer.json`, `package.json` y la estructura real.

---

## F0-13 · CI con GitHub Actions

**Depende:** F0-02 · **Archivos:** `.github/workflows/tests.yml` (no toques `docs.yml`)

**Pasos:**
1. Job de backend, en `push` y `pull_request`:
   - servicio `postgres:18-alpine` con la base de datos `testing`;
   - `shivammathur/setup-php@v2` con PHP 8.5 y las extensiones `pdo_pgsql`, `pgsql`, `gd`, `intl`, `mbstring` y `redis`;
   - `composer install`, `cp .env.example .env`, `php artisan key:generate`;
   - `DB_HOST=127.0.0.1`, `DB_USERNAME` y `DB_PASSWORD`;
   - sin servicio Redis (los tests usan caché y sesión en array; `predis` no está instalado);
   - `php artisan test`.
2. Job de frontend: Node 22 y pnpm, `pnpm install --frozen-lockfile && pnpm build` en `Frontend/`.

**Hecho cuando:** el workflow pasa en una rama de prueba. **Pide permiso antes del push** (R11).
