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
- **Bug encontrado (sin arreglar):** `ShowItemRequest::authorize()` devuelve `$this->user()?->can('view', …) ?? true`, así que un invitado recibe `true` y se salta `ItemPolicy::view()`, que niega los ítems inactivos a los invitados. Un invitado ve el detalle de un ítem inactivo (200 en lugar de 403). El test `ItemDetailTest::test_un_invitado_no_ve_el_detalle_de_un_item_inactivo` queda *incomplete* (`markTestIncomplete` condicional) y pasará solo cuando se arregle. Corregido en F0-14.
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
- **Riesgo conocido (no corregido, R6):** el `INSERT` de `votes` toma un `KEY SHARE` sobre la fila del ítem (por la FK) y después el servicio hace `FOR UPDATE`. Dos votos simultáneos al mismo ítem pueden acabar en *deadlock*: Postgres aborta uno y ese usuario recibe un 500. Ya ocurría con `EmitVoteAction` antes de la F0-03. `ChangeVoteAction` no tiene este riesgo (actualizar el voto no bloquea al padre). Corregido en F0-16.
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

**Notas:**
- **Archivos creados** (`Backend/`):
  - `config/catalog.php` (solo `min_quality_for_discovery` y `template_description_patterns`);
  - `database/migrations/2026_10_27_100000_add_quality_score_to_items_table.php`;
  - `app/Services/ItemQualityScorer.php`, `app/Jobs/RecalculateItemQualityJob.php` y `app/Console/Commands/RecalculateItemQualityCommand.php`;
  - `tests/Feature/Items/{ItemQualityScoreTest,DiscoverableItemsTest,RecalculateItemQualityCommandTest}.php` (19 tests).
- **Archivos modificados:** `app/Models/Item.php` (`$attributes`, `$casts`, hook `saving`, `scopeDiscoverable` y `discoverable` en `applyFilters`), `app/Services/NextCategoryItemService.php`, `app/Http/Requests/Items/ListItemsRequest.php`, `app/Http/Controllers/Item/ItemController.php` (pasa `discoverable` a `$filters`), `routes/console.php`, `database/seeders/DatabaseSeeder.php`, `tests/Concerns/CreatesItems.php`, `Frontend/src/hooks/pages/useCategoryPage.js` y docs (`base-de-datos.md`, `arquitectura.md`, `pipeline-catalogo.md` §6 y §8).
- **Puntos como constantes:** los puntos (40/40/20) y los 60 caracteres son constantes privadas de `ItemQualityScorer`, no `config`: la ficha limita `config/catalog.php` a dos claves.
- **El hook vigila también `released_at` y `attributes`** para que la F1-01 no tenga que tocarlo; hasta entonces `isDirty()` no los ve y el scorer los lee con `getAttribute()` (devuelve `null`, no suma). `quality_score` no está en `$fillable`.
- **Una imagen cuenta** si tiene `path` o `variants` no vacíos (formato nuevo y formato antiguo de `ProposalSeeder`/`StoreItemRequest`). Los patrones de plantilla **no distinguen mayúsculas**.
- **El job escribe con `DB::table`**: sin eventos y sin tocar `updated_at`, porque es un recálculo y no una edición.
- **Programación:** lunes a las 03:30 (`weeklyOn(1, '03:30')`, `withoutOverlapping`). `--chunk=200` por defecto y `--category=slug`. La F0-10 solo tiene que verificarla.
- **`CreatesItems::createItem()`** da por defecto una imagen y una descripción larga (puntuación 80). Motivo: `ItemFactory` crea `images => []` y descripciones de 40–70 caracteres al azar; sin esto el hook puntuaría 0, 40 u 80 al azar y `NextCategoryItemTest` fallaría de forma intermitente. Así sigue en verde sin tocarlo (R8), y no se toca la factory (R6). Los tests de fichas pobres pasan `images => []` y su propia descripción.
- **Caché de la cola:** la cola de 50 ítems se cachea 6 h, así que una ficha que se vuelva pobre puede seguir sirviéndose hasta que caduque la cola. Resuelto en la F0-17: al servir se vuelve a filtrar el lote.
- **Sembrado:** `DatabaseSeeder` llama a `Artisan::call('items:recalculate-quality')` al final de `run()` (con `QUEUE_CONNECTION=sync` se calcula en el momento). Reparto tras `migrate:fresh --seed` contra `testing` (0, 40 y 80):
  ```
  array:3 [
    0 => 349
    40 => 1389
    80 => 2611
  ]
  ```
  Por categoría: álbumes 217×80; artistas 298×0; ciudades 995×40 (sin imágenes, pero su descripción "importada automaticamente" no coincide con los patrones); libros 435×80, 137×40 y 1×0; países 25×80, 219×40 y 1×0; películas 696×80; políticos 49×0; series 688×80 y 3×40; videojuegos 550×80 y 35×40. Artistas y políticos quedan fuera de la cola y de los destacados porque no tienen imagen y su descripción es de plantilla.
- **Pendiente de producción (no se ha tocado, R10):** los ítems existentes tienen `quality_score = 0`, así que en cuanto se despliegue esto **todas las colas quedan vacías y los destacados desaparecen** hasta ejecutar `items:recalculate-quality` con un worker de `catalog` (F0-10).
- **Test de la cola:** el plan decía que la segunda llamada a `next-item` daba 204 sin más, pero la cola no consume el ítem hasta que se vota; el test vota la ficha buena entre las dos llamadas (como `NextCategoryItemTest`) y entonces da 204.
- **Verificación:**
  - `sail test --filter='ItemQualityScoreTest|DiscoverableItemsTest|RecalculateItemQualityCommandTest'` → `Tests: 19 passed`;
  - `sail test` → `Tests: 1 incomplete, 67 passed (414 assertions)` (el incompleto es el de `ShowItemRequest`, anterior);
  - contra `testing`: `migrate:fresh` (OK), `migrate:rollback --step=1` (OK, `hasColumn` = `false`) y `migrate` (OK, `hasColumn` = `true`);
  - `sail artisan schedule:list` muestra `30 3 * * 1  php artisan items:recalculate-quality`;
  - `pnpm build` sin errores; `discoverable` solo aparece en `useCategoryPage.js`.

---

## F0-06 · *Placeholder* generativo

**Depende:** — · **Lee:** frontend §4 · **Archivos:** `Frontend/src/components/ui/{StorageImage,ItemPlaceholder}.jsx`, `Frontend/src/components/items/ItemImage.jsx`, `Frontend/src/lib/constants.js`

**Pasos:**
1. Crea `components/ui/ItemPlaceholder.jsx` con las props `name`, `categorySlug`, `className` y `variant` (`card` | `banner`). El fondo es un degradado HSL de dos tonos derivado de un hash estable del nombre, con saturación y luminosidad que den contraste AA con texto blanco. Encima, el icono de la categoría y hasta dos iniciales.
2. En `lib/constants.js`, un mapa `slug → icono lucide` con un icono por defecto. Usa `category.icon` si existe (llega en la F1).
3. Usa el *placeholder* en `StorageImage.jsx` (en lugar de `ImageOff` cuando es un ítem; ya tiene la prop `fallbackIcon` y `onError`) y en `ItemImage.jsx` cuando no haya imagen o falle su carga.

**Hecho cuando:** las tarjetas y fichas sin imagen o con la imagen rota muestran el *placeholder*, y el mismo nombre da siempre el mismo color.

**Notas:**
- **Colores:** degradado `linear-gradient(135deg, …)` entre dos tonos HSL con saturación 60 % y luminosidad 28 % → 20 %; el segundo tono se desplaza +40° del primero. El contraste mínimo con texto blanco, calculado sobre los 360 tonos y las dos luminosidades, es **5,06:1** (peor caso, amarillo a L = 28 %), por encima del 4,5:1 de AA. Se usa `text-white` y no `text-text-btn` para que el contraste no dependa del tema.
- **Hash:** FNV-1a de 32 bits sobre el nombre en minúsculas (`toLocaleLowerCase('es-ES')`) y recortado; tono = `hash % 360`. Estable entre sesiones y navegadores, e insensible a mayúsculas y espacios exteriores.
- **Iniciales:** primera letra o cifra de las dos primeras palabras (se ignoran signos iniciales como `¿`), en mayúsculas. `"El señor de los anillos"` → `ES`; `"¿Quién es quién?"` → `QE`.
- **Desviación: `lib/placeholder.js`.** La ficha solo preveía `ItemPlaceholder.jsx` y `constants.js`; la lógica pura (hash, colores, iniciales) va en un módulo sin React ni lucide para poder probarla con `node` (no hay tests de frontend).
- **Iconos:** `CATEGORY_SLUG_ICONS` (por slug, con `DEFAULT_CATEGORY_ICON = LayoutGrid`) es nuevo y convive con `categoryIcons` (por nombre normalizado, lo usa `getCategoryIcon`/`CategoryCard`); no se unifican (R6), es candidato para la F1. `CATEGORY_ICONS_BY_NAME` es una lista cerrada `nombre lucide en kebab-case → componente` para `category.icon`, para no meter todo lucide en el *bundle*: la F1 tendrá que añadir aquí los iconos nuevos que use.
- **Discrepancia (R1): `onError` en `StorageImage`.** La ficha y `arquitectura.md` decían que la prop "ya existe", pero no: `onError` caía en `...props`, que va después del manejador interno, y un `onError` externo lo sustituiría (`hasError` no pasaría a `true`). Ahora se desestructura y se llama tras marcar el estado interno. Corregido el documento.
- **Prop nueva `fallback`** en `StorageImage`: si hay error y se pasa `fallback`, se pinta ese nodo; si no, sigue el `div` con `fallbackIcon` (`ImageOff`) para lo que no son ítems.
- **`ItemCard` y `MyVoteItemCard` tocados** aunque no estaban en *Archivos*: sin ello las tarjetas no mostrarían el *placeholder*. `ItemImage` (ficha y votación) pinta el *placeholder* si no hay variante de imagen o si falla la carga del principal (`imageFailed`, que se reinicia al cambiar de imagen).
- **Variantes:** `card` (icono 40 px, `text-3xl`), `banner` (64 px, `text-5xl md:text-6xl`) y `thumb` (20 px, `text-sm`), esta última para la miniatura de 56×64 de `MyVoteItemCard`.
- **Sin cambios de JSON (R7)** ni de backend; `item.category.slug` ya llegaba a todos los consumidores.
- **Verificación:**
  - `docker compose run --rm kudos-frontend pnpm build` → `✓ 2366 modules transformed … ✓ built in 3.02s`, sin errores;
  - comprobación en Node (`hashString`, `getPlaceholderColors`, `getInitials`, contraste en 360 tonos) ejecutada dos veces con la misma salida: `OK placeholder; contraste mínimo 5.06 ; Matrix -> hsl(204, 60%, 28%)`.
- **Sin prueba manual en el navegador:** el entorno del agente no la permite; la comprobación visual (tarjetas, ficha, votación y "Mis votos" sin imagen o con la imagen rota) queda para la revisión humana.

---

## F0-07 · Ocultar secciones vacías

**Depende:** — · **Archivos:** `Frontend/src/components/items/ItemDetailColumn.jsx`, `ItemCard`

**Pasos:** no renderices "Acerca de…" si `description` es nulo o queda vacío tras `trim()`. Aplica el mismo criterio a cualquier otra sección vacía de la ficha o la tarjeta (imágenes, etc.). El creador ya se protege con `item.creator &&`; mantenlo, porque los ítems importados no tienen creador.

**Hecho cuando:** una ficha sin descripción no muestra una caja vacía.

**Notas:**
- **Criterio:** el bloque "Acerca de…" solo se pinta si `hasText(item.description)` (string no vacío tras `trim()`); se muestra el texto recortado, para que `whitespace-pre-line` no deje líneas en blanco al principio y al final.
- **Desviación: `lib/text.js`.** Es nuevo y no estaba en *Archivos*. Es lógica pura, sin React ni lucide, para poder probarla con `node` (no hay tests de frontend), como `lib/placeholder.js` en la F0-06.
- **Revisión del resto de secciones:** la imagen nunca queda vacía (el *placeholder* de la F0-06 en `ItemImage`); el creador sigue protegido con `item.creator &&`; la insignia de nota siempre tiene contenido (`0.0 (0 votos)`); `ItemCard` no tiene secciones que puedan quedar vacías (imagen con *placeholder* como `fallback`, nota con `-.-`, nombre obligatorio en base de datos), así que **no se ha tocado**.
- **Fuera de alcance (R6):** `AdminItemCard` pinta `item.description` sin comprobarlo, pero es del panel de administración.
- **Sin cambios de JSON (R7)** ni de backend.
- **Verificación:**
  - `docker compose run --rm kudos-frontend pnpm build` → `✓ built in 2.99s`, sin errores;
  - comprobación en Node de `hasText` (nulo, `undefined`, `''`, espacios, tabuladores y saltos de línea, número, objeto, texto con y sin espacios exteriores): `OK hasText 9`;
  - `grep` estático: el import, la constante `description`, `{description && (` y `{item.creator && (` aparecen en `ItemDetailColumn.jsx`, y `{item.description}` ya no.
- **Sin prueba manual en el navegador:** el entorno del agente no la permite; la comprobación visual de una ficha sin descripción queda para la revisión humana.

---

## F0-08 · Clases CSS sin definir

**Depende:** — · **Lee:** frontend §3 · **Archivos:** `Frontend/tailwind.config.js`, `Frontend/src/index.css` y los archivos que devuelva el `grep`

**Pasos:** aplica la solución de la tabla de frontend §3 a cada clase y revisa visualmente cada página afectada.

**Hecho cuando:** el `grep` de frontend §3 no devuelve nada y no se ha añadido ningún plugin de Tailwind.

**Notas:**
- **Valores elegidos y origen:** `lg:h-page-content` = `calc(100dvh - 9rem)` y `min-h-state` = `60vh`, tomados del commit `b54835a`, que sustituyó `lg:h-[calc(100dvh-9rem)]` y `min-h-[60vh]` por estas clases. `9rem` cuadra con la maqueta actual: cabecera `h-[80px]` + borde (≈ 5rem) y `py-8` de `<main>` (4rem). `animate-fade-in` = `fadeIn 0.5s ease-out` (0 → 1 de opacidad), definida en `theme.extend` (`keyframes` + `animation`); `plugins: []` sigue igual.
- **Discrepancia (R1): 60vh y no 50vh.** frontend §3 decía `min-h-state` ≈ `50vh`, pero el código original y `CategoryPage.jsx:20` (`min-h-[60vh]`, que no se toca, R6) usan 60vh.
- **`scrollbar-thin` y `scrollbar-thumb-slate-200` quitadas** de `Ranking`, `ItemDetailColumn` y `CommentBox`. No cambia nada a la vista (hoy no se aplicaban; la segunda es sintaxis del plugin `tailwind-scrollbar`, que no está instalado y la tabla no mencionaba). Definirlas en `index.css` dejaría usos que encuentra el `grep` del "Hecho cuando".
- **`prose` sustituido sin añadir clases:** los hijos de `ItemDetailColumn` (`h3` y `p`) ya llevan tipografía explícita; basta con quitar `prose prose-slate max-w-none`.
- **`duration-500` quitado** de los dos usos de `CategoryPage` (`animate-in fade-in duration-500` → `animate-fade-in`): sin el plugin solo afectaba a `transition-duration` y esos elementos no tienen transición; la duración va en `animation['fade-in']`. El `transition-opacity duration-500` de la línea 35 no se toca.
- **Discrepancia del `grep` del "Hecho cuando" con la tabla:** la tabla manda *definir* `h-page-content` y `min-h-state`, pero el `grep` las incluye, así que tras definirlas seguirá encontrando sus usos (`ItemDetailPage.jsx:21,25,33` y `VotePage.jsx:21`, ahora válidos). `animate-fade-in` se dejó fuera del `grep` por el mismo motivo. Se sigue la tabla y se corrige la comprobación en frontend §3. El "Hecho cuando" no se ha reescrito: lo decide el humano.
- **Hallazgo: `scrollbar-hide`** (`ProfileTabs.jsx:6`, `MyVotesHeader.jsx:38`) tampoco está definida (sin plugin ni `index.css`). No estaba en la tabla ni en el `grep` de la ficha, así que no se toca (R6). Por eso la comprobación de frontend §3 usa `scrollbar-thin\|scrollbar-thumb` y no `scrollbar-`. Candidata a una tarea posterior.
- **Efecto visual nuevo:** la animación de entrada (`animate-fade-in`) pasa a verse de verdad en Explorar, ficha, categoría y páginas de admin (antes la clase no existía y no hacía nada).
- **Sin cambios de JSON (R7)** ni de backend; `package.json`, `pnpm-lock.yaml` e `index.css` sin tocar.
- **Verificación:**
  - `grep -rn "animate-in\|prose\|scrollbar-thin\|scrollbar-thumb" Frontend/src` → sin salida;
  - `grep -rn "h-page-content\|min-h-state\|animate-in\|prose\|scrollbar-thin" Frontend/src` (el de la ficha) → solo:
    ```
    Frontend/src/pages/VotePage.jsx:21:    ... lg:h-page-content
    Frontend/src/pages/ItemDetailPage.jsx:21:      ... animate-fade-in ... lg:h-page-content
    Frontend/src/pages/ItemDetailPage.jsx:25:        ... min-h-state ...
    Frontend/src/pages/ItemDetailPage.jsx:33:          ... min-h-state ...
    ```
  - `docker compose run --rm kudos-frontend pnpm build` → `✓ 2367 modules transformed … ✓ built in 2.96s`, sin errores;
  - CSS generado (`dist/assets/*.css`): `@keyframes fadeIn{0%{opacity:0}to{opacity:1}}`, `.animate-fade-in{animation:fadeIn .5s ease-out}`, `h-page-content{height:calc(100dvh - 9rem)}` (en la media query `lg`), `.min-h-state{min-height:60vh}`; `grep -c "scrollbar-thin\|prose"` → `0`;
  - `grep -n plugins Frontend/tailwind.config.js` → `65:  plugins: [],`; `git diff --stat rediseno-fase-0 -- package.json pnpm-lock.yaml src/index.css` → sin salida.
- **Sin prueba manual en el navegador:** el entorno del agente no la permite; la revisión visual de ficha, votación, categoría, Explorar y admin queda para la revisión humana.

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

**Notas:**
- **`reserved_slugs`** (`Backend/config/catalog.php`): `buscar`, `ranking`, `admin`, `profile`, `my-proposals`, `forbidden`, `404`, `login`, `register`, `verify-email`, `forgot-password`, `password-reset` (rutas de primer nivel actuales, `404` incluido) y `wishlist`, `u`, `listas`, `privacidad`, `aviso-legal` (previstas en frontend §2). La comparación no distingue mayúsculas (React Router tampoco las distingue). Al añadir una ruta de primer nivel hay que añadirla a la lista.
- **Desviación: `App\Rules\NotReservedSlug`.** Archivo nuevo, no previsto en *Archivos*: una regla reutilizable (patrón de `FileOrUrlRule`) que usan `StoreCategoryRequest` y `UpdateCategoryRequest`. Como `prepareForValidation` genera el slug a partir del nombre, un nombre como "Buscar" o "Admin" también da 422 con `error.details.slug`. Los requests conservan su indentación (tabulador + 8 espacios en `Store`, tabuladores en `Update`).
- **`search` con `min:2`** en `ListItemsRequest`, con el mensaje "La búsqueda debe tener al menos 2 caracteres." No cambia el JSON (R7): solo se añade una validación.
- **`useFilters`:** parámetro nuevo `minSearchLength` (por defecto 1) y `setSearchInput` añadido al objeto devuelto. Con el valor por defecto, las páginas de admin se comportan igual (salvo que el término se recorta; Laravel ya lo recortaba en el servidor con `TrimStrings`). Explorar y la búsqueda global usan `MIN_SEARCH_LENGTH` (2), así que no envían búsquedas de 1 carácter (que ahora darían 422 y mostrarían "Problemas de conexión").
- **Desviación: `lib/search.js`.** Archivo nuevo. Lógica pura, sin React, para probarla con `node` (no hay tests de frontend), como `lib/placeholder.js` y `lib/text.js`: `MIN_SEARCH_LENGTH`, `normalizeSearchTerm` (cuenta puntos de código, como `mb_strlen`), `buildSearchUrl`, `buildItemUrl` y `groupItemsByCategory`.
- **`GlobalSearch`** (`components/layout/`, lógica en `hooks/common/useGlobalSearch.js`): *debounce* de 300 ms, vista previa de 6 resultados (`useItems` con `per_page: 6`, `keepPreviousData`, `staleTime` de 1 min), Enter → `/buscar?q=` con el texto actual (sin esperar al *debounce*) y "Ver todos los resultados" al final del desplegable. Escape y el `blur` lo cierran; `onMouseDown` con `preventDefault` evita que el `blur` lo cierre antes del clic. Posición: en `MainHeader` a la derecha de la navegación, solo desde `md` (`hidden md:block`, `md:max-w-[14rem] lg:max-w-xs`, `min-w-0` para que encoja antes que la navegación); en móvil, como primer elemento del menú de `NavBarMobile` (al navegar lo cierra).
- **`SearchPage` / `useSearchPage`:** consulta infinita de 24 por página, agrupada por categoría **en el orden en que aparece cada una por primera vez**. El orden dentro de `/api/items` es el de siempre (`bayes_score`), sin relevancia por coincidencia. Estados: sin término (o menor de 2 caracteres), carga (`Skeleton`), error, sin resultados y resultados. Ruta pública `/buscar` entre `/ranking` y `/:categorySlug`.
- **Tests que fallaban antes del cambio** (7 de 14): 6 de los 8 de slug reservado (los dos que ya pasaban son "un slug no reservado se sigue aceptando" y "actualizar sin tocar el slug") y `test_una_busqueda_de_un_caracter_da_422` (200 en vez de 422). Los de búsqueda global sin categoría, `per_page` y *eager loading* (1 sola consulta a `categories`) ya pasaban antes: la ficha solo pedía verificarlo.
- **Hallazgo fuera de alcance (R6):** `scopeApplyFilters` (`Item.php`) no escapa `%` ni `_` en el `ilike`, así que buscar `%` o `_` devuelve todos los ítems. Candidato a una tarea posterior.
- **Sin migraciones (R5), sin variables de entorno (R9), sin dependencias nuevas** (`package.json` y `pnpm-lock.yaml` sin tocar) y sin cambios de JSON (R7).
- **Sin prueba manual en el navegador:** el entorno del agente no la permite. Queda para la revisión humana: cabecera en `md` y `lg` (invitado y con sesión iniciada, por si el buscador aprieta a `UserInfo`), menú móvil, desplegable de resultados y `/buscar` (con resultados, sin resultados y sin término).
- **Verificación:**
  - antes del arreglo, `sail test --filter='ItemSearchTest|ReservedCategorySlugTest'` → `Tests: 7 failed, 7 passed (27 assertions)`;
  - después, `Tests: 14 passed (66 assertions)`;
  - `sail test` → `Tests: 1 incomplete, 80 passed (476 assertions)` (antes: `1 incomplete, 67 passed`; el *incomplete* es el de `ItemDetailTest` anotado en la F0-02);
  - `docker compose run --rm kudos-frontend pnpm build` → `✓ 2372 modules transformed … ✓ built in 2.97s`, sin errores;
  - comprobación en Node de `lib/search.js` → `OK search`; `matchRoutes` con `/:categorySlug` antes de `/buscar` → `OK ruta /buscar {}`;
  - `grep`: `GlobalSearch` en `MainHeader.jsx` y `NavBarMobile.jsx`; `MainHeader` montado en `App.jsx` (todas las páginas); `minSearchLength` en `useExplorePage.js`; `git diff --stat rediseno-fase-0 -- Frontend/package.json Frontend/pnpm-lock.yaml` → sin salida.

---

## F0-10 · Scheduler, colas y memoria en producción

**Depende:** — · **Lee:** produccion §2 · **Archivos:** `Backend/compose.prod.yml`, `deploy/README.md`, `Backend/routes/console.php`

**Pasos:**
1. En `compose.prod.yml`, aplica produccion §2: servicio `scheduler`, worker con `--queue=default,catalog` y 512M, y el comentario de presupuesto de memoria.
2. En `deploy/README.md`, documenta el servicio y cómo comprobarlo: `kudos-dc logs scheduler` y `kudos-dc exec app php artisan schedule:list`.
3. En `routes/console.php`, programa las tareas ya existentes en esta fase (`items:recalculate-stats` ya se programa en la F0-03, solo hay que verificarlo; `items:recalculate-quality`). Esos comandos solo encolan.
4. Valida con `docker compose -f compose.prod.yml --env-file .env.production.example config > /dev/null`. Si hace falta un `.env` temporal, no lo subas.

**Hecho cuando:** `config` es válido, `sail artisan schedule:list` muestra las tareas y **no se ha desplegado nada**.

**Notas:**
- **Archivos modificados:** `Backend/compose.prod.yml` (servicio `scheduler`, worker con `--queue=default,catalog` y 512M, comentario de presupuesto), `Backend/docker/prod/entrypoint.sh` (solo comentarios), `deploy/README.md` (diagrama, tabla de ficheros, operación diaria y sección "Tareas programadas y colas"), `docs/infra/produccion.md` §1 y `docs/backend/arquitectura.md` (trampa 8, resuelta).
- **`routes/console.php` sin cambios (discrepancia con el paso 3, R1):** las dos tareas ya las programaron F0-03 (`items:recalculate-stats`, `dailyAt('03:00')`) y F0-05 (`items:recalculate-quality`, `weeklyOn(1, '03:30')`). Solo se ha verificado con `schedule:list`. Las horas son UTC (`config/app.php`).
- **Validación de la ficha:** el comando literal falla con la plantilla porque `DB_PASSWORD` está vacío y `${DB_PASSWORD:?}` lo rechaza (`required variable DB_PASSWORD is missing a value`). Se valida con `DB_PASSWORD=solo-para-validar` delante, sin crear ningún `.env` en el repo. Además, `env_file: .env.production` exige que ese archivo exista: en un checkout limpio hay que validar en una copia temporal fuera del repo. Desde `Backend/` usa siempre `> /dev/null`, porque `config` imprime las variables del `env_file` real.
- **Memoria del scheduler:** hereda `memory_limit = 512M` de `docker/prod/php.ini` aunque el contenedor tiene 128M. Si una tarea lo superase, Docker mataría el proceso (OOM) en vez de dar un error de PHP. Hoy las tareas solo encolan.
- **Presupuesto:** ~2,3 GB de límites (postgres 512M + app 1G + worker 512M + scheduler 128M + redis 128M + nginx 64M). Pendiente de que el humano confirme que cabe; si no, la propuesta es bajar `app` a 768M y `pm.max_children`.
- **No se ha desplegado nada (R10):** solo `docker compose config`; ni `up`, `run`, `build`, `down`, `restart`, `deploy.sh` ni conexión al VPS. `deploy.sh` hace `up -d --remove-orphans`, así que levantará el servicio nuevo sin cambios.
- **Verificación:**
  - `DB_PASSWORD=solo-para-validar docker compose -f compose.prod.yml --env-file .env.production.example config > /dev/null && echo CONFIG_OK` → `CONFIG_OK`.
  - `config --services` → `postgres, redis, app, nginx, scheduler, worker`.
  - Inspección JSON en copia fuera del repo: scheduler `command` = `["php","artisan","schedule:work"]`, `mem` = `134217728`, `stop_grace_period` = `30s`, `depends_on` = `["postgres","redis"]`; worker con `--queue=default,catalog` y `mem` = `536870912`.
  - `sail artisan schedule:list` → `0 3 * * *  php artisan items:recalculate-stats` y `30 3 * * 1  php artisan items:recalculate-quality`.
  - `sail test` → `Tests: 1 incomplete, 80 passed (476 assertions)` (el incompleto es el de `ShowItemRequest`, anterior a la tarea).

---

## F0-11 · Copia de seguridad de imágenes (prioridad baja)

**Depende:** — · **Lee:** produccion §4

**Hecho cuando:** existe un script con rotación, sin avisos de `shellcheck` (si está disponible), documentado en `deploy/README.md` con su línea de cron.

**Notas:**
- **Archivos:** `deploy/backup-storage.sh` (nuevo, modo 755), `deploy/README.md` (tabla de ficheros, §8 con la prueba manual, las dos líneas de cron, el espacio y la restauración de imágenes, y el párrafo "Ojo con las horas") y `docs/infra/produccion.md` (§1 tachado, §4 describe lo implementado, §5 con la copia de imágenes antes del vaciado).
- **Script nuevo en vez de ampliar `backup-db.sh` (R6):** `backup-db.sh` no se ha tocado. Cada script rota solo lo suyo: `kudos-storage-*.tar.gz` frente a `kudos-*.dump` (los patrones no se solapan).
- **`docker run` con `kudos-api:latest` en vez de `compose exec`:** no depende de que el stack esté levantado, monta el volumen en solo lectura y sin red, usa el mismo uid (`www-data`, 82) para copiar y para restaurar, y se puede probar con volúmenes temporales sin tocar los reales. `--entrypoint tar` salta el entrypoint de Laravel (que necesita el `.env`).
- **`--pull never`:** la imagen `kudos-api` se construye en local con `deploy.sh`; nunca debe intentar descargarse de un registro.
- **Comprobación del volumen:** sin ella, `docker run -v` crearía un volumen vacío con ese nombre y la copia saldría "bien" sin imágenes. Verificado: con un volumen inexistente sale con `exit=1` y no lo crea.
- **Hora del cron:** 03:45, después de `backup-db.sh` (03:30). La instalación en el VPS la hace el humano.
- **Espacio:** unos 200 MB de imágenes (medidos tras la siembra) dan unos 1,6 GB para ~8 copias, porque gzip apenas reduce. Se puede bajar la retención solo para esta línea con `RETENTION_DAYS=3`.
- **R9 no aplica:** `STORAGE_VOLUME` y `BACKUP_IMAGE` son variables del shell del cron, no de la aplicación, así que no van a `.env.example` ni a `.env.production.example`. R4 tampoco aplica (no hay PHP ni tests de PHPUnit).
- **shellcheck:** no está instalado en el host; se ha pasado con la imagen `koalaman/shellcheck:v0.10.0` (sin avisos).
- **No se ha desplegado nada (R10):** ni `deploy.sh`, ni `compose.prod.yml` con `up`, `run`, `build`, `down` o `restart`, ni conexión al VPS. El script solo se ha ejecutado contra volúmenes temporales `f011-prueba-*` (borrados al final); `kudos_storage-public` y el resto de `kudos_*` siguen intactos y no se ha creado ningún `kudos_*` nuevo.
- **Verificación** (bloque literal del plan, en una sola llamada):
  - `bash -n` → `SINTAXIS_OK`; permisos `-rwxr-xr-x`; shellcheck → `SHELLCHECK_OK` (sin avisos).
  - Caso normal: `OK .../kudos-storage-20261027-153613.tar.gz (4,0K)`, `exit=0`; directorio `700`, copia `600`; 1 copia (la de hace 10 días borrada); `tar -tzf` → `./items/1/a.webp` y `./avatars/b.webp`; `DUMP_INTACTO`.
  - Volumen inexistente: `ERROR no existe el volumen f011-prueba-no-existe`, `exit=1`, y el volumen no se crea (`0`).
  - Imagen inexistente: `docker: Error response from daemon: No such image: f011-imagen-inexistente:0`, `exit=125`; 0 ficheros `.tmp` y 1 `.tar.gz`.
  - Restauración en volumen temporal: `imagen`, `avatar` y `-rw-r--r-- 1 82 82 ... a.webp` (uid 82).
  - `git status --short` → `deploy/README.md`, `docs/infra/produccion.md`, `docs/roadmap/README.md` (tablero, lo cambia el orquestador) y `deploy/backup-storage.sh` (nuevo); esta ficha se suma al guardar estas notas.

---

## F0-12 · Corregir el README

**Depende:** F0-01 · **Archivos:** `README.md`, `Backend/README.md`

**Pasos:** MySQL → PostgreSQL; quita "Spatie Media Library" y "Telescope" si no están en `composer.json`; explica cómo ejecutar los tests; enlaza a `docs/`.

**Hecho cuando:** cada afirmación coincide con `composer.json`, `package.json` y la estructura real.

**Notas:**
- **Archivos tocados:** `README.md`, `Backend/README.md` y esta ficha. No se ha tocado `docs/infra/entorno-local.md` ni `docs/backend/testing.md` (ya estaban al día), ni código, ni variables (R9).
- **`README.md` (raíz), afirmaciones corregidas:**
  - Pila: Laravel 12 (`composer.json` `^12.0`), PHP 8.5 (imagen `docker/8.5`), PostgreSQL 18 (`postgres:18-alpine` en `compose.yml` y `compose.prod.yml`), Redis, React 18, Vite 6, Tailwind 3 y pnpm con Node 22 (`Frontend/package.json` y `Frontend/Dockerfile`). El icono `mysql` pasa a `postgres`.
  - Librerías: se quitan "Spatie Media Library" y "Laravel Telescope", que no están en `composer.json`; se añaden Spatie Laravel Permission, las herramientas de desarrollo (Breeze, Sail, Pint, PHPUnit 11) y las dependencias del frontend que faltaban (TanStack Query, clsx, tailwind-merge).
  - Árbol: se quita `storage/media-library` (no existe) y se añaden `.github/workflows/`, `docs/`, `deploy/`, `deploy.sh`, `Backend/scripts/`, `Backend/docker/prod`, `Backend/routes`, `app/Console`, `app/Jobs`, `app/Notifications`, `tests/Concerns`, `tests/Unit` y `tests/Fixtures`.
  - Secciones nuevas: aviso de Kudos 2.0, "Puesta en marcha", "Tests" (Sail contra PostgreSQL `testing`) y "Documentación" (enlaces a `docs/`, `Backend/docs/`, `Frontend/README.md` y `deploy/README.md`).
- **Insignias quitadas, por qué:** "build passing" era falsa (el único workflow es `docs.yml`; el CI de tests es la F0-13); "version 1.0.0" no coincide con `Frontend/package.json` (0.1.0); "license MIT" no tiene respaldo (no hay archivo `LICENSE`). En `Backend/README.md` se quita el bloque de insignias de `laravel/framework` (el "Build Status" era el CI del framework, no el de Kudos); se conserva el logo de Laravel.
- **Postman quitado:** la referencia a la colección (`docs/postman/Proyecto-Kudos.postman_collection.json`) y el icono de Postman, porque la colección no existe.
- **`Backend/README.md`, afirmaciones corregidas:**
  - Icono `mysql` → `postgres` y la introducción cita PHP 8.5, PostgreSQL 18 y Redis.
  - "Estructura principal" con el árbol real (`Console/Commands`, `Contracts/Media`, `Jobs`, `Notifications`, `Rules`, `Services/Media` y `routes/console.php`).
  - Módulo 0: "Metadatos por categoria y comentarios" → "Comentarios" (`Category` solo tiene `name`, `description`, `slug` e `image`).
  - `app/Services/ProposalService.php` **no existe**: se quita de las piezas clave del módulo 3.
  - Rutas de controladores corregidas: `Item/ItemCommentController.php`, `app/Actions/ReviewProposalAction.php`, `Admin/AdminUserController.php`, `Admin/AdminItemController.php` y `Proposal/ProposalController.php`.
  - "Entorno local": los comandos pasan de `php artisan` en el host a `./vendor/bin/sail artisan …` (R2); se añaden `sail up -d` y `kudos:audit-consistency`, y la nota de que `migrate:fresh --seed` tarda.
  - Sección nueva "Tests" (con `scripts/verify-test-db-guard.sh`) y enlace a `../docs/README.md` en "Documentación de contrato API".
- **Fuera de alcance (R6), sin tocar:** `Frontend/README.md` dice `VITE_STORAGE_URL=http://localhost:8095/api/storage`, pero `Frontend/.env.example` tiene `http://localhost:8095/storage/`. Conviene corregirlo en otra tarea.
- **Sin CI de tests:** el README no afirma que exista; lo trae la F0-13.
- **Verificación:**
  - `grep -niE "mysql|media library|media-library|telescope|postman|ProposalService|Actions/Admin/Proposals|badge/build|version-1\.0\.0|php artisan" README.md Backend/README.md` → sin salida (exit 1).
  - Enlaces relativos de ambos README → 18 líneas, todas `OK`.
  - Rutas citadas en `Backend/README.md` (15) → sin `FALTA`.
  - Manifiestos: `composer.json` → 3 (sanctum, permission, intervention); `package.json` → 11.
  - Directorios del árbol raíz (incluido `deploy.sh`, comprobado con `git ls-files`) → todos existen.
  - `sail test` → `Tests: 1 incomplete, 80 passed (476 assertions)` (el incompleto es el de `ShowItemRequest`, anterior a la tarea).
  - `git status --short` → `README.md`, `Backend/README.md`, `docs/roadmap/fase-0-higiene.md` y el tablero `docs/roadmap/README.md`.

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

**Notas:**
- **Archivos tocados:** `.github/workflows/tests.yml` (nuevo), `docs/backend/testing.md` (§7 nueva), `README.md` (sección "Tests") y esta ficha. `docs.yml` queda intacto. Sin tests nuevos (R8), sin secretos, sin variables nuevas en la app (R9) y sin peticiones reales (R4).
- **El workflow NO se ha ejecutado en GitHub.** El humano decidió cerrar la tarea con validación local; la ejecución real en GitHub queda pendiente de su `push` (R11). Hasta entonces el "Hecho cuando" ("el workflow pasa en una rama de prueba") solo está cubierto por la reproducción local.
- **Decisiones:**
  - Tags de las acciones: `actions/checkout@v7`, `actions/setup-node@v7` y `shivammathur/setup-php@v2` (comprobados con `git ls-remote --tags`; actionlint 1.7.12 los acepta, así que no hizo falta bajar a `@v6`).
  - pnpm: `package.json` no tiene `packageManager` y no se toca. El workflow fija pnpm **12.15.0** (variable `PNPM_VERSION` del job), la versión que trae la imagen local del frontend, instalada con `npm install -g`.
  - Sin `mkdir` de `storage/framework`: aunque `Backend/storage/framework/` no está versionado, `AppServiceProvider::ensureFrameworkDirectoriesExist()` (llamado en `boot()`) crea `views`, `cache/data` y `sessions` al arrancar la app, y ya lo hace el `package:discover` de `composer install`. El verificador lo comprobó borrando la carpeta en la copia limpia y ejecutando solo `php artisan test` (`1 incomplete, 80 passed`). No se añaden `.gitignore` en `storage/framework` (R6). (Una primera versión del workflow llevaba ese `mkdir` con una justificación errónea; se quitó.)
  - `pg_isready -h 127.0.0.1` en el *healthcheck*: durante la inicialización de la imagen el servidor temporal solo escucha en el socket, y así no se da por listo antes de tiempo.
  - `concurrency` por referencia con `cancel-in-progress` (cancela ejecuciones obsoletas) y `permissions: contents: read` (mínimo privilegio).
  - Sin servicio Redis: `phpunit.xml` pone caché y sesión en array y `predis` no está instalado. La extensión `redis` de PHP se instala igualmente porque `.env.example` usa `REDIS_CLIENT=phpredis`.
- **Discrepancias (R1):**
  1. `Backend/storage/framework/` no está versionado, pero no afecta al CI porque la app lo crea al arrancar.
  2. **Las imágenes locales de Sail llevan PHP 8.4.26, no 8.5.12** (`sail-8.5/app` y `kudos-api` no se han reconstruido aunque los Dockerfiles piden 8.5.12). El CI con PHP 8.5 es la primera ejecución real en 8.5; la reproducción local construyó una imagen 8.5.12 aparte (y temporal). El humano debería reconstruir Sail (`sail build --no-cache`) fuera de esta tarea.
  3. pnpm no está fijado en el repositorio (`Frontend/Dockerfile` usa `corepack enable` sin versión); el CI lo fija a 12.15.0.
  4. `docs/backend/testing.md` §1 dice que `DB_HOST`, `DB_USERNAME` y `DB_PASSWORD` se heredan de `.env`; en CI vienen del `env` del job. Se documenta en la §7 nueva.
- **Validación local** (script en el *scratchpad*, no en el repo; los pasos `run` y las variables se extraen del propio YAML, en una copia limpia del repo sin `vendor`, `node_modules`, `.env` ni `storage/framework`):
  - `python3 -c "import yaml; …"` → `YAML OK`.
  - `docker run --rm -v "$PWD":/repo:ro -w /repo rhysd/actionlint:1.7.12 -color .github/workflows/tests.yml` → sin avisos, `actionlint OK` (la imagen trae shellcheck 0.11.0).
  - `git ls-remote --tags` → `refs/tags/v7` de `actions/checkout` y `actions/setup-node`, y `refs/tags/v2` de `shivammathur/setup-php`.
  - Backend (imagen `php:8.5.12-cli-bookworm` con las extensiones de Sail, contra `postgres:18-alpine` efímero):
    ```
    Checkout limpio: storage/framework no existe
    PHP 8.5.12 (cli) (built: Oct 21 2026 15:49:43) (NTS)
    Tests:    1 incomplete, 80 passed (476 assertions)
    Duration: 3.71s
    ```
    El incompleto es `ItemDetailTest::test_un_invitado_no_ve_el_detalle_de_un_item_inactivo`, anterior a la tarea.
  - Frontend (`node:22-alpine`, pnpm 12.15.0):
    ```
    Done in 1.6s using pnpm v12.15.0
    ✓ built in 3.00s
    ```
  - Limpieza: contenedor `kudos-ci-f0-13-pg`, imagen `kudos-ci-f0-13-php:8.5.12` y copia temporal eliminados; los contenedores `kudos-*` y `backend-*` siguen igual.
- **Aviso al instalar con Composer (anterior a la tarea, sin tocar):** `composer install` avisa de que "the lock file is not up to date with the latest changes in composer.json" (el `content-hash`). `composer.json` cambió por última vez en `5f4a40d` (PHP 8.5.12) sin regenerar `composer.lock`. No rompe la instalación ni los tests; conviene un `composer update --lock` en otra tarea.

---

## Correcciones de la Fase 0

Fallos y deuda detectados durante F0-01…F0-13 (ver sus **Notas**). Se corrigen antes de empezar la Fase 1. Decisiones del humano: artistas y políticos sin cola se dejan así; `scrollbar-hide` se quita; licencia MIT a nombre de David Cerdán; el backend no se despliega hasta terminar todas las fases.

---

## F0-14 · Los invitados no ven el detalle de los ítems inactivos

**Depende:** F0-02 · **Archivos:** `Backend/app/Http/Requests/Items/ShowItemRequest.php`, `Backend/tests/Feature/Items/ItemDetailTest.php`

**Pasos:**
1. `ShowItemRequest::authorize()` (l.16) devuelve `$this->user()?->can('view', …) ?? true`: un invitado recibe `true` y se salta `ItemPolicy::view()`, que niega los ítems inactivos (Notas de F0-02). Haz que la *policy* se evalúe también sin usuario (p. ej. `Gate::forUser($this->user())->allows('view', $this->route('item'))`, comprobando que `ItemPolicy::view` acepta `?User`).
2. Quita el `markTestIncomplete` condicional de `test_un_invitado_no_ve_el_detalle_de_un_item_inactivo` (deja el `assertForbidden()`).

**Hecho cuando:** ese test pasa en verde (falla antes del arreglo), un invitado sigue viendo los ítems activos y `sail test` no tiene ningún *incomplete*.

**Notas:**
- **Desviación de la ficha (R1):** la ficha proponía `Gate::forUser($this->user())`, pero `GET /api/items/{item}` es una ruta pública sin `auth:sanctum` (`routes/api.php:27`), el guard por defecto es `web` (`config/auth.php:17`) y el frontend envía `Authorization: Bearer`. Con `$this->user()` el usuario sería `null` en producción aunque llegue el token y un admin recibiría 403 en los ítems inactivos (en los tests no se nota porque `Sanctum::actingAs()` cambia el guard por defecto). Se usa `Gate::forUser($this->user('sanctum'))->allows('view', $this->route('item'))`, igual que `ItemController::show()`. `ItemPolicy::view()` ya aceptaba `?User` y no se ha tocado; el JSON del 403 es el mismo de `bootstrap/app.php`.
- **Test nuevo:** `ItemDetailTest::test_un_admin_con_token_ve_el_detalle_de_un_item_inactivo` usa un token Bearer real (`createToken()` + `withToken()`, sin `Sanctum::actingAs`). Comprobación: con `$this->user()` en lugar de `$this->user('sanctum')` este test falla; se revirtió después.
- **Salida antes del arreglo** (`sail test --filter=test_un_invitado_no_ve_el_detalle_de_un_item_inactivo`, sin el `markTestIncomplete`): `Expected response status code [403] but received 200.` -> `Tests: 1 failed (1 assertions)`.
- **Salida después** (`sail test`): `Tests: 82 passed (479 assertions)`, sin *incomplete*.

---

## F0-15 · Escapar `%`, `_` y `\` en las búsquedas `ilike`

**Depende:** F0-09 · **Archivos:** `Backend/app/Models/{Item,Vote,Proposal,User}.php`, un helper nuevo (p. ej. `Backend/app/Support/`), tests

**Pasos:**
1. Las búsquedas usan `ilike "%{$search}%"` sin escapar los comodines (Notas de F0-09): buscar `%` devuelve todo. Aparecen en `Item.php` (l.83 y l.113), `Vote.php` (l.48), `Proposal.php` (l.61-62) y `User.php` (l.130-131).
2. Crea un helper reutilizable que escape `\`, `%` y `_` (PostgreSQL usa `\` como escape por defecto en `LIKE`/`ILIKE`) y úsalo en todas.

**Hecho cuando:** hay tests de que `?search=%` y `?search=_` en `/api/items` y en al menos un listado de admin solo devuelven ítems que contienen ese carácter literal, y la batería sigue en verde.

**Notas:**
- **Helper:** `App\Support\Like` (`Backend/app/Support/Like.php`, carpeta nueva; el autoload PSR-4 `App\` la cubre). `escape()` sustituye `\`, `%` y `_` por `\\`, `\%` y `\_` (la barra invertida primero, para no duplicar los escapes añadidos) y `contains()` devuelve `'%' . escape($valor) . '%'`. No hace falta `ESCAPE` en la consulta: PostgreSQL toma `\` como escape por defecto en `LIKE`/`ILIKE` y las consultas usan parámetros enlazados.
- **Dónde se usa** (`Like::contains()`): `Item::scopeApplyFilters` y `Item::scopeAdminApplyFilters` (`name`), `Vote::scopeApplyFilters` (`name` del ítem), `Proposal::scopeAdminApplyFilters` (`name` y `description`) y `User::scopeAdminApplyFilters` (`name` y `email`). `grep -rn "ilike" Backend/app` solo muestra esas llamadas (con `Like::contains(...)` o con `$pattern` calculado con él).
- **Desviación de la ficha (R1):** `ListItemsRequest` valida `search` con `min:2` (F0-09), así que `GET /api/items?search=%` y `?search=_` devuelven 422. En `/api/items` se prueba con `0%` y `_1`; los comodines sueltos (`%`, `_` y `\`) se prueban en `GET /api/admin/items`, cuyo Form Request no tiene `min`. La validación no se toca (R8).
- **Tests nuevos:** `tests/Unit/Support/LikeTest.php` (3 tests, PHPUnit puro) y `tests/Feature/Items/ItemSearchWildcardsTest.php` (5 tests: `0%` y `_1` en `/api/items`; `%`, `_` y `\` en `/api/admin/items`).
- **Salida antes del arreglo** (`sail test --filter=ItemSearchWildcardsTest`): `Tests: 5 failed (10 assertions)` (p. ej. buscar `\` en admin devolvía `Rebajas 100%` en lugar de `C:\Juegos`).
- **Salida después:** `--filter=ItemSearchWildcardsTest` -> `Tests: 5 passed (10 assertions)`; `--filter=LikeTest` -> `Tests: 3 passed (6 assertions)`; `sail test` -> `Tests: 90 passed (495 assertions)`, sin *incomplete*.

---

## F0-16 · Evitar el *deadlock* al votar el mismo ítem

**Depende:** F0-03 · **Archivos:** `Backend/app/Actions/EmitVoteAction.php`, tests

**Pasos:**
1. `EmitVoteAction` inserta el voto (el `INSERT` en `votes` toma `KEY SHARE` sobre el ítem por la FK) y después `ItemStatsService::recalculate()` hace `lockForUpdate` del ítem: dos votos simultáneos al mismo ítem pueden acabar en *deadlock* y en un 500 (Notas de F0-03).
2. Bloquea el ítem (`Item::lockForUpdate()`) al principio de la transacción, antes de comprobar el voto existente y de `Vote::create`. El `lockForUpdate` posterior de `recalculate()` es inocuo dentro de la misma transacción.

**Hecho cuando:** un test comprueba en el *query log* que el `select … for update` sobre `items` va antes del `insert into "votes"`, el orden queda documentado en `docs/backend/arquitectura.md` y la batería sigue en verde.

**Notas:**
- **Cambio:** `EmitVoteAction::execute()` hace ahora `Item::withTrashed()->lockForUpdate()->findOrFail($voteData['item_id'])` como primera consulta de la transacción. La comprobación del voto existente, que estaba fuera de la transacción, pasa dentro, después del bloqueo. El bloqueo se hace también en `skip` (aunque no recalcule), porque su `INSERT` en `votes` toma el mismo `KEY SHARE`. El `try/catch QueryException` de la carrera se mantiene sin cambios (R6/R8).
- **`withTrashed()`:** hueco preexistente. `StoreVoteRequest` valida `item_id` con `Rule::exists('items','id')`, que no mira `deleted_at`, así que un `skip` sobre un ítem borrado con *soft delete* ya daba 201 (y un `vote` daba 404 por `authorize()`/`withValidator()`). Para no cambiar qué ítems se pueden votar (R6), el bloqueo usa `withTrashed()` y solo bloquea la fila.
- **Test:** `EmitVoteTest` suma `test_el_voto_bloquea_el_item_antes_de_comprobar_e_insertar_el_voto` y `test_un_skip_tambien_bloquea_el_item_antes_de_insertar`. Comprueban el orden en el *query log* (`select … from "items" … for update` antes de la consulta del voto existente y del `insert into "votes"`), no la concurrencia real: con `RefreshDatabase` (una sola transacción) no se puede reproducir el *deadlock*.
- **Salida antes del arreglo** (`sail test --filter=bloquea_el_item`): `Tests: 2 failed (7 assertions)`.
- **Salida después:** `--filter=bloquea_el_item` -> `Tests: 2 passed (14 assertions)`; `--filter=EmitVoteTest` -> `Tests: 8 passed (49 assertions)`; `sail test` -> `Tests: 92 passed (509 assertions)`, sin *incomplete*.

---

## F0-17 · La cola de votación no ofrece ítems ya votados

**Depende:** F0-05 · **Archivos:** `Backend/app/Services/NextCategoryItemService.php` (y, si se elige invalidar, las Actions de voto), tests

**Pasos:**
1. La cola se cachea 6 h en `next-item-queue:{user}:{category}`. Si el usuario vota un ítem del lote por otra vía (desde la ficha), la cola puede volver a ofrecérselo (Notas de F0-02).
2. Al servir el siguiente ítem, descarta los que el usuario ya ha votado (o invalida la clave al votar). Elige la opción más simple y anótala.

**Hecho cuando:** hay un test en el que, con el lote ya cacheado, el usuario vota por `POST /api/votes` un ítem del lote y `GET /api/categories/{slug}/next-item` ya no lo devuelve; la batería sigue en verde.

**Notas:**
- **Opción elegida:** volver a filtrar la cola al servir (`stillEligibleItemIds()` en `NextCategoryItemService`), con los mismos criterios que `generateNewBatch()` (misma categoría, `status = active`, `discoverable()`, sin voto ni *skip* del usuario) y en una sola consulta que conserva el orden del lote. No se invalida la caché al votar: tiraría el lote en cada voto desde la cola, forzaría otro `ORDER BY random()`, acoplaría la Action a la clave del servicio y no cubriría los cambios de estado o calidad hechos por un admin.
- **Ampliación pedida por el humano:** además de votos y *skips*, la cola deja de ofrecer ítems que pasan a inactivos, a ficha pobre (`quality_score` bajo el umbral), borrados o movidos de categoría. Resuelve la nota "Caché de la cola" de la F0-05.
- `meta.remaining` queda exacto (cuenta solo ítems elegibles). Si el filtro y la regeneración salen vacíos, se hace `Cache::forget` del lote antiguo. El `find()` y la recursión por ítem borrado se mantienen como red de seguridad. Contrato JSON, Actions y controlador sin tocar (R7).
- **Tests nuevos** (`NextCategoryItemTest`): votado desde la ficha, omitido (*skip*) desde la ficha, pasa a inactivo y pasa a ficha pobre, con el helper `cachedQueueAfterFirstItem()`.
- **Salida antes del arreglo** (`sail test --filter=no_ofrece_un_item_del_lote`, sin tocar el servicio): `Tests: 4 failed (23 assertions)`.
- **Salida después:** `--filter=no_ofrece_un_item_del_lote` -> `Tests: 4 passed (27 assertions)`; `--filter=NextCategoryItemTest` -> `Tests: 6 passed (41 assertions)`; `--filter=DiscoverableItemsTest` -> `Tests: 5 passed (19 assertions)`; `sail test` -> `Tests: 96 passed (536 assertions)`, sin *incomplete*.
- **Desviaciones (R1):** `DiscoverableItemsTest.php` está en `tests/Feature/Items/`, no se ha tocado. La nota de la F0-05 y el comentario de `DiscoverableItemsTest.php:41` dicen que "la cola no consume el ítem hasta que se vota", pero en realidad cada GET hace `array_shift`: es la regeneración la que lo vuelve a ofrecer. Se anota aquí y no se toca ese comentario (R6).

---

## F0-18 · `Http::preventStrayRequests()` para todos los tests

**Depende:** F0-02 · **Archivos:** `Backend/tests/TestCase.php`, `docs/backend/testing.md`, `README.md`

**Pasos:**
1. Hoy `Http::preventStrayRequests()` solo está en el `setUp()` de los tests nuevos; R4 debe valer para todos. Llámalo desde `TestCase` sin tocar la protección de base de datos de `setUpTraits()`.
2. Quitar las llamadas redundantes de cada clase es opcional; no se borra ningún test (R8).
3. Actualiza `docs/backend/testing.md` y, si hace falta, la frase del `README.md` raíz sobre APIs externas.

**Hecho cuando:** está en `TestCase`, `Backend/scripts/verify-test-db-guard.sh` pasa (se toca `TestCase`), un test demuestra que una petición HTTP no simulada falla en cualquier clase de test y `sail test` sigue en verde.

**Notas:**
- `Backend/tests/TestCase.php` tiene ahora un `setUp()` que llama a `parent::setUp()` y después a `Http::preventStrayRequests()`. Va en `setUp()` y no en `setUpTraits()` porque cada test arranca una aplicación nueva. `setUpTraits()` y `ensureTestingDatabase()` no se han tocado: la protección de la base de datos sigue corriendo antes (dentro de `parent::setUp()`).
- No se han quitado las llamadas redundantes a `Http::preventStrayRequests()` del `setUp()` de las 17 clases que ya la tenían (R6; era opcional en la ficha). Son inocuas.
- Test nuevo: `tests/Feature/StrayRequestsTest.php`, sin `setUp()` propio ni `RefreshDatabase`. Comprueba que `Http::preventingStrayRequests()` es verdadero y que `Http::get('http://kudos.invalid/')` lanza `StrayRequestException` (dominio `.invalid`, sin red, R4); y que una petición con `Http::fake()` sigue funcionando.
- Antes del arreglo (`sail test --filter=StrayRequestsTest`):
  ```
  ⨯ una peticion http no simulada falla en cualquier clase de test
  ✓ una peticion simulada con http fake sigue funcionando
  Failed asserting that false is true.  (tests/Feature/StrayRequestsTest.php:18)
  Tests:    1 failed, 1 passed (4 assertions)
  ```
  Después: `Tests:    2 passed (6 assertions)`. Batería completa: `Tests:    98 passed (542 assertions)` (antes, 96), sin incomplete.
- `Backend/scripts/verify-test-db-guard.sh`:
  ```
  Filas en kudos_guard_canary.items: antes=3 después=3
  OK: los tests abortan con "Los tests deben ejecutarse contra la base de datos 'testing', no 'kudos_guard_canary'." y el señuelo sigue intacto.
  ```
- Los tests `Unit` que extienden `PHPUnit\Framework\TestCase` (p. ej. `LikeTest`) no arrancan la aplicación y no heredan la protección; tampoco pueden usar `Http`. Si alguno lo necesita, tiene que extender `Tests\TestCase`.
- Documentación: `docs/backend/testing.md` §2, §3 y §5, y `README.md` (frase sobre APIs externas).

---

## F0-19 · Herramientas: `composer.lock`, Sail en PHP 8.5.12 y pnpm fijado

**Depende:** F0-13 · **Archivos:** `Backend/composer.lock`, `Frontend/package.json`, `.github/workflows/tests.yml` (si hace falta), docs

**Pasos:**
1. `composer install` avisa de que `composer.lock` no está al día con `composer.json` (Notas de F0-13). Regenera solo el hash con `sail composer update --lock` (sin actualizar paquetes; revisa el diff).
2. La imagen local de Sail tiene PHP 8.4.26 aunque `docker/8.5/Dockerfile` pide 8.5.12. Reconstrúyela (`sail build --no-cache`, `sail up -d`) hasta que `sail php -v` dé 8.5.12. **No toques** la imagen `kudos-api` ni el stack `kudos` (producción local).
3. Añade `"packageManager": "pnpm@12.15.0"` a `Frontend/package.json` y deja `tests.yml` coherente (misma versión, o que la lea de ahí).

**Hecho cuando:** `composer install` no avisa del lock, `sail test` en verde con PHP 8.5.12, `pnpm build` pasa, actionlint sigue sin avisos y los docs (entorno local, testing §7) están al día.

**Notas:**
- **Sail con PHP 8.5.12.** `sail php -v` antes: `PHP 8.4.26 (cli) (built: Sep 24 2026 19:13:27)`; después de `sail build --no-cache && sail up -d`: `PHP 8.5.12 (cli) (built: Oct 21 2026 15:49:43)` con Zend OPcache v8.5.12. `sail php -m` incluye `bcmath, exif, gd, intl, pcntl, pdo_pgsql, pgsql, redis, zip, Zend OPcache`. `sail up -d` solo recreó `backend-kudos-app-1` (pgsql, redis y mailpit seguían en marcha). La imagen antigua de `sail-8.5/app` (`4698f8af8e16`) no aparece después ni como `<none>` (`docker images -a --filter dangling=true` sale vacío): Docker la descartó al reetiquetar `sail-8.5/app:latest` (nuevo id `417713043999`); no se ha borrado ninguna imagen a mano.
- **R10 (producción local intacta).** `docker image inspect kudos-api:latest` da `sha256:d1b5587027cd...` antes y después. Siguen *Up* `kudos-app-1`, `kudos-worker-1`, `kudos-nginx-1`, `kudos-postgres-1` y `kudos-redis-1`, con los mismos tiempos de actividad.
- **`composer.lock`.** `sail composer update --lock --no-interaction` (ya en 8.5.12). Diff (`git diff --stat`: `4 insertions(+), 4 deletions(-)`): `content-hash` `991713f0...` → `9775fd0f...`, `platform-overrides.php` `8.4.0` → `8.5.12` y, además, la URL de `vlucas/phpdotenv` (`github.com/vlucas/phpdotenv` → `github.com/phpdotenv/phpdotenv`, en `source.url` y `dist.url`): es el repositorio renombrado por upstream, con el mismo *commit* (`301c0793...`) y la misma versión. `git diff -U0 -- composer.lock | grep -E '^[+-] +"version"'` sale vacío: ningún paquete cambia de versión. `composer.json` no se toca.
- `sail composer validate --no-check-publish` → `./composer.json is valid` (sin "Lock file errors"). `sail composer install --no-interaction` → `Nothing to install, update or remove`, sin aviso del lock.
- **Aviso de seguridad (fuera de alcance, sin tocar).** `sail composer audit --locked` avisa de `symfony/yaml` (CVE-2026-109413, recursión sin límite en el *lexer* inline). Hay que actualizarlo en otra tarea.
- **`sail test`** con PHP 8.5.12: `Tests:    98 passed (542 assertions)`, sin incomplete ni *deprecations* nuevas. `Backend/scripts/verify-test-db-guard.sh` sigue pasando (`antes=3 después=3`).
- **pnpm.** `Frontend/package.json` lleva `"packageManager": "pnpm@12.15.0"`. `docker compose run --rm kudos-frontend pnpm --version` → `12.15.0`; `pnpm build` → `✓ built in 3.03s`. Se ha comprobado que la imagen del frontend se construye (`docker build`, con `pnpm install --frozen-lockfile` dentro) y que `pnpm --version` da 12.15.0.
- **`Frontend/pnpm-lock.yaml` cambia (no previsto en la ficha).** Con `packageManager` fijado, pnpm 12 añade al principio del lock un primer documento YAML (`lockfileVersion: '9.0'`, `importers: . : packageManagerDependencies: pnpm 12.15.0` y los paquetes `pnpm@12.15.0` / `@pnpm/exe.*`; +158 líneas, sin tocar el resto). Sin ese bloque, `pnpm install --frozen-lockfile` falla con `ERR_PNPM_FROZEN_LOCKFILE_WITH_OUTDATED_LOCKFILE` (comprobado en la simulación del CI con el lock original), así que el cambio es necesario y debe ir en el mismo commit que `package.json`. Con el lock nuevo, la instalación congelada no vuelve a modificarlo.
- **CI.** `tests.yml` (job `frontend`) ya no define `PNPM_VERSION` en `env`: el paso "Instalar pnpm" lo calcula con `node -p` a partir de `packageManager` y ejecuta `npm install -g "pnpm@$PNPM_VERSION"`. Decisión: así hay una sola fuente de verdad (`package.json`) para la imagen local y para el CI. actionlint 1.7.12: sin avisos (`actionlint OK`); `YAML OK`. Simulación del job (Node 22 alpine, sin `node_modules`): `pnpm 12.15.0`, `Done in 1.4s using pnpm v12.15.0`, `✓ built in 3.16s`.
- **Discrepancias con los documentos (R1).** (1) `laravelsail/php85-composer` no existe: `docs/infra/entorno-local.md` §3 sigue usando `laravelsail/php84-composer ... --ignore-platform-reqs` para el primer `composer install`, que funciona; no se cambia. (2) El frontend en producción se despliega en Vercel, que no usa `Frontend/Dockerfile` ni corepack: su versión de pnpm sale de `packageManager` (o de sus ajustes del proyecto); no se ha podido comprobar desde aquí y queda para el humano. (3) El contenedor antiguo `ecstatic_rhodes` (frontend de desarrollo) no se ha tocado.

---

## F0-20 · Accesibilidad del buscador global

**Depende:** F0-09 · **Archivos:** `Frontend/src/components/layout/GlobalSearch.jsx`, `Frontend/src/hooks/common/useGlobalSearch.js`, `Frontend/src/lib/`

**Pasos:**
1. El desplegable se cierra al perder el foco, así que con Tab no se llega a los resultados y no hay navegación con flechas (Notas de F0-09).
2. Aplica el patrón *combobox* ARIA: `role="combobox"`, `aria-expanded`, `aria-controls`, `role="listbox"`/`option`, `aria-activedescendant`; ↑/↓ mueven la opción activa, Enter abre la activa (o `/buscar` si no hay), Escape cierra. Que el desplegable no se cierre al mover el foco dentro de él.
3. La lógica de la opción activa va en una función pura en `lib/`, probada con Node (como `lib/search.js`).

**Hecho cuando:** `pnpm build` pasa, la comprobación con Node de la navegación pasa y la revisión manual en el navegador queda anotada como pendiente para el humano.

---

## F0-21 · Limpieza menor de la Fase 0

**Depende:** F0-08 · **Archivos:** `Frontend/src/components/profile/ProfileTabs.jsx`, `Frontend/src/components/votes/MyVotesHeader.jsx`, `Frontend/src/components/admin/AdminItemCard.jsx`, `Frontend/README.md`, `Backend/app/Http/Requests/Votes/StoreVoteRequest.php`, docs

**Pasos:**
1. Quita `scrollbar-hide` (sin definir, no tiene efecto) de `ProfileTabs.jsx` (l.6) y `MyVotesHeader.jsx` (l.38), y su nota de pendiente en `docs/frontend/arquitectura.md` §3 (Notas de F0-08).
2. `AdminItemCard.jsx` (l.18) pinta `item.description` sin comprobarlo: usa `hasText` de `lib/text.js` (Notas de F0-07).
3. `Frontend/README.md` dice `VITE_STORAGE_URL=http://localhost:8095/api/storage`; corrígelo según `Frontend/.env.example` (Notas de F0-12).
4. Elimina el 422 inalcanzable de `StoreVoteRequest::withValidator` ("No se puede votar un item inactivo."): la *policy* en `authorize()` ya responde 403 (Notas de F0-02). `EmitVoteTest::test_no_se_puede_votar_un_item_inactivo` debe seguir en verde.
5. `StoreVoteRequest` valida `item_id` con `Rule::exists('items','id')`, que no mira `deleted_at`: un `skip` sobre un ítem borrado con *soft delete* responde 201 (Notas de F0-16). Haz que un ítem borrado se rechace igual para `vote` y `skip` (p. ej. `->withoutTrashed()` en la regla), con test.

**Hecho cuando:** `grep -rn "scrollbar-" Frontend/src` sale vacío, votar o saltar un ítem borrado da error (test), `sail test` y `pnpm build` en verde, y los docs están al día.

---

## F0-22 · Licencia MIT

**Depende:** — · **Archivos:** `LICENSE`, `README.md`, `Frontend/package.json`

**Pasos:**
1. Crea `LICENSE` en la raíz con el texto MIT estándar y `Copyright (c) 2026 David Cerdán`.
2. Añade una sección "📄 Licencia" al `README.md` raíz que enlace a `LICENSE` (sin insignia).
3. Añade `"license": "MIT"` a `Frontend/package.json` (`Backend/composer.json` ya lo tiene).

**Hecho cuando:** el archivo existe, los dos manifiestos declaran MIT y los enlaces del README son válidos.

---

## F0-23 · Vulnerabilidad de `symfony/yaml` y versión mínima de PHP

**Depende:** F0-19 · **Archivos:** `Backend/composer.json`, `Backend/composer.lock`, docs

**Pasos:**
1. `sail composer audit` avisa de una vulnerabilidad en `symfony/yaml` (CVE-2026-109413; Notas de F0-19). Actualiza solo ese paquete y lo mínimo que arrastre (`sail composer update symfony/yaml --with-dependencies`), revisando el diff del lock.
2. `composer.json` declara `"php": "^8.4"` aunque la plataforma y la imagen son 8.5.12: súbelo a `^8.5` y regenera el `content-hash` (`sail composer update --lock`), sin cambiar otras versiones.

**Hecho cuando:** `sail composer audit` no da avisos, `sail composer validate` es válido, `sail test` en verde y la simulación del job `backend` del CI (o `composer install` limpio con PHP 8.5.12) pasa.
