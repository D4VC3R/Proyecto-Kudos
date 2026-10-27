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
- **Caché de la cola:** la cola de 50 ítems se cachea 6 h, así que una ficha que se vuelva pobre puede seguir sirviéndose hasta que caduque la cola.
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
