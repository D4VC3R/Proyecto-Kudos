# Fase 0: higiene y base

**Objetivo:** tener una red de tests, corregir los bugs de puntuación, ofrecer un ranking honesto, ocultar las fichas pobres, arreglar la deuda visual evidente y preparar la infraestructura (scheduler y colas) para la Fase 1.

**Lecturas comunes:**
- [../guia-agentes.md](../guia-agentes.md)
- [../backend/arquitectura.md](../backend/arquitectura.md)
- [../backend/testing.md](../backend/testing.md)
- [../infra/entorno-local.md](../infra/entorno-local.md)

---

## F0-01 · Tests sobre PostgreSQL

- **Responsabilidad:** INFRA, QA
- **Depende de:** —
- **Objetivo:** que `sail test` ejecute PHPUnit contra la base de datos `testing` de PostgreSQL de forma segura.

**Pasos:**
1. En `Backend/phpunit.xml`, sustituye `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:` por `pgsql` / `testing`, ambos con `force="true"`. Ver [../backend/testing.md](../backend/testing.md) §1.
2. En `Backend/compose.yml`, servicio `pgsql`, añade el volumen (el script `docker/pgsql/create-testing-database.sql` ya existe) `./docker/pgsql/create-testing-database.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql`.
3. Crea `tests/TestCase.php` con la protección de nombre de base de datos ([../backend/testing.md](../backend/testing.md) §3) y `tests/Feature/`, `tests/Unit/` y `tests/Fixtures/`.
4. Crea los helpers `actingAsUser()`, `actingAsAdmin()` y `seedRolesAndPermissions()`.
5. Escribe un *smoke test*: `GET /up` devuelve 200 y `GET /api/categories` devuelve 200 con un JSON válido.

**Criterios de aceptación:**
- `sail test` pasa, con al menos el *smoke test*.
- Si se fuerza `DB_DATABASE=kudos_db`, los tests abortan con el mensaje de protección **antes** de borrar nada (la protección va en `beforeRefreshingDatabase()`, ver testing §3). Pruébalo a mano y no lo dejes así.
- La base de datos de desarrollo `kudos_db` no se toca al ejecutar los tests: comprueba que el número de filas de `items` es el mismo antes y después.

**Archivos:** `Backend/phpunit.xml`, `Backend/compose.yml`, `Backend/tests/**`

---

## F0-02 · Batería base de tests

- **Responsabilidad:** QA, BE
- **Depende de:** F0-01
- **Objetivo:** cubrir el comportamiento **actual** antes de cambiarlo, con unos 15–25 tests.

**Casos mínimos:**

| Área | Casos |
|---|---|
| Votos | Emitir un voto (201/200, actualiza `vote_count`); voto duplicado (devuelve el existente y no suma); *skip* (no cambia la media); un ítem inactivo no se puede votar; un usuario baneado o sin verificar recibe 403; cambiar la nota; borrar el voto |
| Kudos | El primer voto da `vote_first_time_item`; repetir no da más (idempotencia); el rendimiento decreciente por encima de 20 votos diarios; el tope diario de 50; `kudos:audit-consistency` sin desajustes después de los flujos anteriores |
| Ranking | `GET /api/categories/{slug}/ranking` ordena por media y excluye los inactivos |
| Cola | `GET /api/categories/{slug}/next-item` no devuelve ítems ya votados ni inactivos; devuelve null o vacío cuando no queda nada |
| Ítems | `GET /api/items?search=` encuentra por nombre parcial sin distinguir mayúsculas; el detalle devuelve la estructura esperada |

**Notas:**
- Los tests de las medias con medios puntos se escriben en la F0-03. Aquí se documenta el comportamiento actual de los votos con notas enteras.
- **Si encuentras otros bugs, no los arregles aquí.** Márcalos con `markTestIncomplete` y anótalos en esta ficha para crear una tarea.

**Criterios de aceptación:** todos los casos implementados y en verde.

---

## F0-03 · Medias exactas (bug del truncado)

- **Responsabilidad:** BE
- **Depende de:** F0-01
- **Bug:** ver [../backend/arquitectura.md](../backend/arquitectura.md) §3, puntos 1 y 2.

**Pasos:**
1. **Primero el test que falla:** votar 7.5 y 8.0 sobre un ítem da una media de 7.75. Cambiar 7.5 por 6.5 guarda `score = 6.5` y la media pasa a 7.25. Borrar recalcula de forma exacta.
2. **Crea `app/Services/ItemStatsService.php`** con `recalculate(string $itemId): Item`:
   - se ejecuta dentro de la transacción del llamante, con `Item::lockForUpdate()`;
   - calcula `COUNT(*)` y `AVG(score)` de `votes` donde `item_id = ?` y `type = 'vote'`;
   - guarda `vote_count` y `vote_avg` (`round(…, 2)`; 0 si no hay votos).
3. **Sustituye los cálculos incrementales** de `EmitVoteAction`, `ChangeVoteAction` y `DeleteVoteAction` por llamadas al servicio:
   - elimina todos los casts `(int)`;
   - `ChangeVoteAction` guarda la nota como float.
4. **En `StoreVoteRequest` y `UpdateVoteRequest`,** añade la regla `multiple_of:0.5` a `score`, con un mensaje en español.
5. **Crea el comando `items:recalculate-stats {--category=}`:**
   - recorre los ítems por bloques (`chunkById`) y encola un job por bloque en la cola `catalog` que llama al servicio (ver F0-10: el scheduler tiene poca memoria);
   - se registra en `routes/console.php` con una frecuencia diaria a las 03:00 (`withoutOverlapping`).

**Criterios de aceptación:**
- Los tests del paso 1 pasan.
- Los tests de la F0-02 siguen en verde.
- Ningún cast `(int)` sobre notas en `app/Actions`.

---

## F0-04 · Ranking bayesiano

- **Responsabilidad:** BE
- **Depende de:** F0-03

**Fórmula:**

```
bayes = (v·R + m·C) / (v + m)     si v > 0
bayes = 0                          si v = 0   (los ítems sin votos van al final)
```

- `v` es `vote_count` y `R` es `vote_avg`.
- `C` es la media de todos los votos de tipo `vote` de la categoría. Se cachea 1 h en `category-mean:{id}`; si no hay votos, se usa 5.0.
- `m` es `config('kudos.ranking.bayes_min_votes')`, 5 por defecto.

**Pasos:**
1. **Migración** con la columna `bayes_score` y el índice ([../backend/base-de-datos.md](../backend/base-de-datos.md), Fase 0).
2. **`config/kudos.php`:** sección `ranking` con `bayes_min_votes` y `category_mean_fallback` (5.0), cada una comentada.
3. **`ItemStatsService::recalculate`** también calcula `bayes_score`. El comando `items:recalculate-stats` invalida y recalcula `C` primero.
4. **Ordenar por `bayes_score`:**
   - `Category::getRankingPaginator()`: `bayes_score desc`, luego `vote_count desc`, luego `name`;
   - `Item::scopeApplySorting`: la opción `vote_avg` (la que usan los destacados y "Mejor valorados" en Explorar) ordena por `bayes_score desc` y después por `vote_count desc`.

   **No cambies el nombre del parámetro** (`sort_by=vote_avg`) ni los campos del JSON, para no romper el frontend.

**Criterios de aceptación:**
- Test: con m=5 y C≈6, un ítem con un único 10 queda **por debajo** de uno con 20 votos y media 8.
- Test: los ítems sin votos quedan al final.
- El JSON del ranking sigue teniendo `vote_avg` y `vote_count`.

---

## F0-05 · `quality_score` y exclusión de las fichas pobres

- **Responsabilidad:** BE
- **Depende de:** F0-01

**Reglas iniciales** (en la Fase 1 se amplían a 100 puntos):

| Condición | Puntos |
|---|---|
| Al menos una imagen en `images` | +40 |
| `description` no vacía, de 60 caracteres o más, y que no contiene ningún patrón de plantilla | +40 |
| (F1) `released_at` no nulo o `attributes` no vacío | +20 |

- **Patrones de plantilla:** se configuran en `config/catalog.php` → `template_description_patterns`, empezando con `Item importado` e `importado desde`.
- **Umbral de descubrimiento:** `config('catalog.min_quality_for_discovery')`, 40 por defecto.

**Pasos:**
1. **Migración** con `quality_score` y su índice. Crea `config/catalog.php` solo con las claves anteriores (la F1-02 lo amplía).
2. **Crea `app/Services/ItemQualityScorer.php`** con `score(Item $item): int`.
3. **Hook en `Item::booted()`:** en el evento `saving`, si cambian `images` o `description` (o, desde la F1, `attributes`/`released_at`), se recalcula `quality_score`.
4. **`DatabaseSeeder` usa `WithoutModelEvents`,** así que el hook no se ejecuta al sembrar. Al final de `DatabaseSeeder::run()`, ejecuta `Artisan::call('items:recalculate-quality')`.
5. **Crea el comando `items:recalculate-quality`** (por bloques) y prográmalo semanalmente.
6. **Excluir los ítems con `quality_score < umbral`:**
   - de `NextCategoryItemService::generateNewBatch()`;
   - de `GET /api/items` cuando se pase el parámetro nuevo `discoverable=1`, validado en `ListItemsRequest`.
7. **Frontend:** `useCategoryPage.js` (destacados) envía `discoverable: 1`.

**Criterios de aceptación:**
- Tests:
  - un ítem sin imagen y con "Item importado." tiene una puntuación menor de 40;
  - no aparece en la cola;
  - no aparece en `?discoverable=1`;
  - **sí** aparece en Explorar y en la búsqueda, que no llevan el filtro.
- Después de `migrate:fresh --seed`, `quality_score` está calculado (no queda todo a 0).

---

## F0-06 · *Placeholder* generativo

- **Responsabilidad:** FE
- **Depende de:** —
- **Lectura:** [../frontend/arquitectura.md](../frontend/arquitectura.md) §4

**Pasos:**
1. **Crea `components/ui/ItemPlaceholder.jsx`** con las props `name`, `categorySlug`, `className` y `variant` (`card` o `banner`):
   - el fondo es un degradado de dos tonos HSL derivado de un hash estable del nombre. Usa saturación y luminosidad controladas para mantener el contraste AA con texto blanco;
   - encima, el icono de la categoría y hasta dos iniciales.
2. **Mapa de iconos `slug → icono lucide`** en `lib/constants.js`, con un icono por defecto. En la F1 vendrá de `categories.icon`; deja preparada la lectura de `category.icon` si existe.
3. **Usa el *placeholder*** en `StorageImage.jsx` (en lugar de `ImageOff` cuando el contexto es un ítem) y en `ItemImage.jsx` cuando no haya imagen o falle su carga (`onError`).

**Criterios de aceptación:**
- `pnpm build` termina sin errores.
- Las tarjetas y las fichas sin imagen muestran el *placeholder*: el mismo nombre da siempre el mismo color.
- Las imágenes rotas también caen al *placeholder*.

---

## F0-07 · Ocultar secciones vacías

- **Responsabilidad:** FE
- **Depende de:** —

**Pasos:**
- En `components/items/ItemDetailColumn.jsx`, no se renderiza el bloque "Acerca de…" si `description` es nulo o está vacío tras `trim()`.
- Revisa la ficha y la tarjeta por si hay otras secciones con el mismo problema (creador vacío, imágenes vacías) y aplica el mismo criterio.

**Criterios de aceptación:**
- Una ficha sin descripción no muestra una caja vacía.
- `pnpm build` termina sin errores.

---

## F0-08 · Clases CSS sin definir

- **Responsabilidad:** FE
- **Depende de:** —
- **Lectura:** [../frontend/arquitectura.md](../frontend/arquitectura.md) §3 (tabla "Deuda de CSS conocida")

**Pasos:**
- Aplica la solución decidida para cada clase.
- Comprueba visualmente las 13 páginas o componentes afectados, entre ellos `CategoryPage`, `ExplorePage`, `ItemDetailPage`, `VotePage`, `CommentBox`, `Ranking` y `admin/*`.

**Criterios de aceptación:**
- El `grep` de comprobación no devuelve ninguna coincidencia.
- `pnpm build` termina sin errores.
- No se ha añadido ningún plugin de Tailwind.

---

## F0-09 · Búsqueda global

- **Responsabilidad:** FE, BE
- **Depende de:** F0-01

**Backend:**
1. Verifica que `GET /api/items?search=…&per_page=…` sin categoría funciona y que `ItemListResource` incluye la categoría (`slug`, `name`). Si no la incluye, añádela con *eager loading*, sin consultas N+1.
2. Exige un mínimo de 2 caracteres en `search` (regla `min:2`). `ListItemsRequest` también lo usa Explorar: comprueba que `useFilters` no envía búsquedas de 1 carácter (darían 422).
3. Añade `reserved_slugs` a `config/catalog.php` (créalo si la F0-05 aún no lo ha hecho) y valídalo en `StoreCategoryRequest` y `UpdateCategoryRequest` (ver [../frontend/arquitectura.md](../frontend/arquitectura.md) §2).
4. Escribe los tests.

**Frontend:**
1. **Componente `GlobalSearch`** en `MainHeader` y `NavBarMobile`:
   - *debounce* de 300 ms (reutiliza `useFilters` si encaja);
   - al pulsar Enter, navega a `/buscar?q=`.
2. **Ruta `/buscar`,** declarada **antes** de `/:categorySlug` en `routes.jsx`:
   - página `SearchPage` con su hook `useSearchPage`;
   - consulta infinita;
   - resultados agrupados por categoría con `ItemCard`;
   - estados de vacío y de carga con los *skeletons* existentes.

**Criterios de aceptación:**
- Buscar "mat" encuentra "Matrix" desde cualquier página.
- `/buscar` no se confunde con un slug de categoría.
- `pnpm build` termina sin errores y los tests del backend están en verde.

---

## F0-10 · Scheduler, colas y memoria en producción

- **Responsabilidad:** INFRA
- **Depende de:** —
- **Lectura:** [../infra/produccion.md](../infra/produccion.md) §2

**Pasos:**
1. **En `compose.prod.yml`:**
   - servicio `scheduler`;
   - worker con `--queue=default,catalog` y 512M;
   - comentario de presupuesto de memoria actualizado.
2. **En `deploy/README.md`:**
   - explica el servicio nuevo;
   - explica cómo comprobarlo: `kudos-dc logs scheduler`, `kudos-dc exec app php artisan schedule:list`.
3. **En `routes/console.php`,** deja programadas las tareas que ya existen en esta fase: `items:recalculate-stats` e `items:recalculate-quality`. El scheduler ejecuta los comandos **dentro de su contenedor de 128M**: los comandos programados solo recorren IDs y **encolan jobs** en la cola `catalog`; el trabajo pesado lo hace el worker (512M).
4. **Valida la sintaxis** con `docker compose -f compose.prod.yml --env-file .env.production.example config > /dev/null`. Usa un `.env` temporal si hace falta y **no lo subas**.

**Criterios de aceptación:**
- `docker compose … config` es válido.
- `schedule:list` muestra las tareas en local.
- **No se ha desplegado nada.** El despliegue lo hace el humano.

---

## F0-11 · Copia de seguridad de imágenes

- **Responsabilidad:** INFRA
- **Depende de:** —
- **Prioridad:** baja
- **Lectura:** [../infra/produccion.md](../infra/produccion.md) §4

**Criterios de aceptación:**
- Existe un script con rotación, sin errores en `shellcheck` si está disponible.
- Está documentado en `deploy/README.md` junto con la línea de cron.

---

## F0-12 · Corregir el README

- **Responsabilidad:** DOCS
- **Depende de:** F0-01

**Cambios en el `README.md` raíz y en `Backend/README.md`:**
- MySQL → **PostgreSQL**;
- quitar "Spatie Media Library" y "Telescope" si no están en `composer.json`;
- explicar cómo ejecutar los tests;
- enlazar a `docs/`.

**Criterios de aceptación:** las afirmaciones del README coinciden con `composer.json`, `package.json` y la estructura real del repositorio.

---

## F0-13 · CI con GitHub Actions

- **Responsabilidad:** INFRA
- **Depende de:** F0-02

**Pasos:**
1. **Crea `.github/workflows/tests.yml`:**
   - se lanza con `push` y `pull_request`;
   - servicio `postgres:18-alpine` con la base de datos `testing`;
   - `shivammathur/setup-php@v2` con PHP 8.5 y las extensiones `pdo_pgsql`, `pgsql`, `gd`, `intl`, `mbstring` y `redis`;
   - `composer install`, `cp .env.example .env`, `php artisan key:generate`;
   - variables `DB_HOST=127.0.0.1`, `DB_USERNAME` y `DB_PASSWORD`. No hace falta servicio Redis: los tests usan caché y sesión en array (no uses `predis`, no está en `composer.json`);
   - `php artisan test`.
2. **Job adicional** con Node 22 y pnpm que ejecute `pnpm install --frozen-lockfile && pnpm build` en `Frontend/`.

**Criterios de aceptación:**
- El workflow es válido y pasa en una rama de prueba.
- **Pide permiso al humano antes del push.**
