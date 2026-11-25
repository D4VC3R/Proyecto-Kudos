# Fase 5: refactor y preparación para `main`

**Objetivo:** dejar `rediseno-fase-0` lista para fusionarse en `main` y desplegarse: red de tests sobre el código heredado, correcciones puntuales, datos de terceros fuera, *runbook* único, documentación para personas y formato con Pint al final.

**Por qué:** la auditoría del 02-11-2026 (sobre `bdb7adc`, 209 commits por delante de `main`; cuatro informes de sección y una síntesis en `.claude/ciclo/auditoria/`, que git ignora) concluye que hace falta un refactor **puntual, no estructural**. Los fallos se concentran en tres zonas:
1. el **código heredado** de antes de Kudos 2.0, sin tests y con fallos reales (moderación sin aviso DSA, barrera de errores rota, tarjetas inaccesibles, ruta inalcanzable);
2. los **bordes del contrato** (formato de error incumplido en el backend y descartado por el frontend, N+1, reglas duplicadas);
3. la **preparación de la fusión** (datos de terceros en el repo, *runbook* desfasado, documentación orientada a agentes, Pint).

Las fichas copian los hallazgos que necesitan, así que no hace falta leer los informes. Los IDs llevan el prefijo de su informe: `BE` (núcleo del backend), `CAT` (catálogo), `FE` (frontend) y `TR` (transversal). Las rutas `ruta:línea` son de `bdb7adc` y se han comprobado al escribir esta fase; si una línea se ha movido, busca por el contenido citado.

**Orden:** F5-00 (hecho) → F5-01 → correcciones (F5-02…F5-12) → F5-13 → F5-14 → F5-15 (**la última**) → F5-16 (humano). F5-02, F5-07 y F5-09 no dependen de nada y pueden ir en paralelo con F5-01.

**Reglas durante la fase:** siguen R1–R13. R6 sigue vigente hasta F5-15 (estilo del archivo, sin Pint). Las únicas excepciones son las de F5-00 (D4 y D6), y los cambios de contrato son solo los aprobados en D8.

**Lee para toda la fase:** [../guia-agentes.md](../guia-agentes.md), [../backend/arquitectura.md](../backend/arquitectura.md), [../backend/testing.md](../backend/testing.md) y [../frontend/arquitectura.md](../frontend/arquitectura.md).

### Lo que NO se toca

- **Arquitectura del backend:** Controller → FormRequest/Policy → Action/Service → Resource. Las Actions de F2–F3 (`DeleteAccountAction`, `ResolveReportAction`, `LikeListAction`, `AddItemToListAction`) son el patrón que hay que imitar, no que cambiar.
- **Kudos y medias:** `KudosService::awardIfFirst` con `action_key` único, `kudos:audit-consistency`, `ItemStatsService` con `AVG` y la media bayesiana cacheada.
- **Catálogo:** contrato `CatalogProvider` + DTO inmutable + registro por config; `CatalogImporter` (fuente → QID, nunca por nombre); cumplimiento de [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md) §1; R4. F5-21 solo quita duplicación interna.
- **Tests e infraestructura:** la protección de `TestCase` y `preventStrayRequests` global; el Dockerfile multietapa, `compose.prod.yml`, `deploy.sh` y las copias de seguridad; el CI con PostgreSQL real.
- **Frontend:** la capa de datos (`hooks/<dominio>/`, factorías de claves, invalidación precisa), `lib/` puro, Leaflet aislado con `lazy` y los estados de carga, vacío y error.
- **No hacer:**
  - reescribir los ~450 comentarios que citan IDs del roadmap;
  - renombrar campos heredados del contrato (`total_skips`, `categorySlug`; R7);
  - unificar las rutas en inglés y en español (FE-C3);
  - factorizar duplicación de tests con variaciones legítimas (`rate()`, `fakeWikidata()`);
  - migrar a TypeScript u Octane;
  - pasar Pint antes de F5-15.

---

## F5-00 · Decisiones previas a la fase (HUMANO)

**Depende:** —

**Decisión (02-11-2026, del humano):** se aprueban las nueve tal como las recomienda la auditoría ("All as recommended").

| # | Decisión aprobada | Se aplica en |
|---|---|---|
| D1 | **Portadas de `CategorySeeder` a `null`**, como `destinos` (el frontend pinta el icono o el *placeholder* de la categoría). El *runbook* borra además `categories/*/seed/` del volumen de producción. Las fotos de Commons o propias quedan para después. | F5-08, F5-13 |
| D2 | **Borrar `Backend/database/seed-data/` del árbol**, sin purgar el historial (nada de `--force`, R11). La purga solo se haría si lo pide un titular. | F5-08 |
| D3 | **Se conservan `.claude/`, `AGENTS.md`, `CLAUDE.md` y el roadmap.** El roadmap queda como histórico (aviso de "plan cerrado") y `docs/README.md` se reescribe como índice para personas. | F5-14 |
| D4 | **Excepción a R6:** un único commit de Pint sobre `Backend/` al final de la fase, más `pint --test` en el CI y el hash en `.git-blame-ignore-revs`. Hasta entonces, R6 sigue igual. | F5-15 |
| D5 | **Inter autoalojada** (sin Google Fonts) y **OpenStreetMap citado en `/privacidad`**. El texto legal nuevo lo aprueba el humano al verificar la tarea. | F5-08 |
| D6 | **Excepción a R5:** esquema literal en las dos migraciones de activación (`2026_11_04_000000_activate_videojuegos_category.php` y `2026_11_05_000000_activate_destinos_category.php`). Se permite porque aún no están en `main` ni se han ejecutado en producción. No vale para ninguna otra migración. | F5-06 |
| D7 | **Fusionar F0–F4 de una vez**, con un *runbook* único que absorbe la F1-11. El despliegue automático de Vercel **se pausa** durante la ventana. | F5-13, F5-16 |
| D8 | **Cambios de contrato aprobados** (guía §6). F5-04: el 404 de modelo pasa de `route_not_found` a `not_found`; 429, 405 y 500 con el formato `{"error"}`; `forgot-password` responde siempre 200. F5-06: se quitan la ruta `GET /api/items/my-items` y el campo `images` de las tres peticiones de escritura. | F5-04, F5-06 |
| D9 | **Versionar `Backend/docs/api-contract.md` revisado** (`Backend/.gitignore` pasa a ignorar solo `/docs/api`), retirar `api-reference.md` si está desfasado y **corregir** `.github/workflows/docs.yml`, sin borrarlo. | F5-14 |

Decisiones menores, que se toman después de `main`: la popularidad de IGDB (F5-22), los tests que fijan valores de configuración (F5-23) y las rutas mezcladas en inglés y español (se dejan como están).

---

## F5-01 · Tests de caracterización de la API heredada (QA, BE)

**Depende:** — · **Cierra:** TR-H1 · **Lee:** [../backend/testing.md](../backend/testing.md) §3–§6

**Objetivo:** tener una red de seguridad antes de refactorizar el código heredado. Solo se añaden tests del comportamiento **correcto** actual.

**Hallazgo (TR-H1):** hay **0 tests** de estas rutas (comprobado con `grep` en `Backend/tests/`):
- `Backend/routes/auth.php:15-43`: `forgot-password`, `reset-password`, `verify-email/{id}/{hash}`, `email/verification-notification`, `logout` y `logout-all`. El login solo se prueba de pasada (`ProfileInterestsTest:108`) y `not_banned`, solo en 3 tests de votos y catálogo.
- `Backend/routes/api.php`:
  - `:77` `POST /profile/daily-claim`;
  - `:114-119` `proposals` (alta, `my-proposals`, ver, editar y borrar);
  - `:128-129` `PUT/DELETE /comments/{comment}`;
  - `:153-154` `POST/DELETE /items`;
  - `:156-159` `admin/items` (`update` y `moderate`);
  - `:162-167` `admin/users` (listar, ver, `ban`, `unban` y `sessions/revoke`);
  - `:170-173` `admin/proposals` (listar, `pending` y `review`);
  - `:176-178` `admin/comments` (`hide` y `unhide`).

**Archivos (nuevos, en `Backend/tests/Feature/`):** `Auth/LoginLogoutTest.php`, `Auth/PasswordResetTest.php`, `Auth/EmailVerificationTest.php`, `Admin/AdminUserBanTest.php`, `Admin/AdminItemModerationTest.php`, `Proposals/ProposalFlowTest.php`, `Kudos/DailyClaimTest.php`, `Items/CommentModerationTest.php` e `Items/ItemCrudTest.php`. Si hace falta, estados nuevos en `database/factories/ProposalFactory.php` (`pending()`…), sin cambiar los existentes.

**Pasos:**
1. **Login y logout:** login correcto, credenciales malas (422), cuenta baneada; `logout` revoca el token actual y `logout-all` todos.
2. **Recuperación:** con `Notification::fake()`, un correo **existente** recibe el enlace, y `reset-password` con un token válido cambia la contraseña. El caso del correo inexistente **no** se fija: hoy responde 422 y enumera cuentas (BE-R2), y eso lo corrige F5-04.
3. **Verificación:** `verify-email` con una firma válida marca `email_verified_at` y con un *hash* malo responde 403. `email/verification-notification` envía la notificación.
4. **Baneos:** ban temporal y permanente; el baneado recibe 403 en una ruta `not_banned`; `unban`; `sessions/revoke` revoca los tokens; un usuario que no es admin recibe 403 y un invitado, 401.
5. **Propuestas:** CRUD del dueño; una propuesta ajena da 403; una revisión aceptada crea el ítem y da la recompensa `proposal_accepted` **una sola vez** (repetir la revisión no la duplica); una rechazada no tiene efectos.
6. **Recompensa diaria:** racha, idempotencia en el mismo día (con `travelTo`) y reinicio de la racha tras un día sin reclamar.
7. **Comentarios:** editar el propio; uno ajeno da 403; `DELETE` según lo que decida hoy `ItemCommentPolicy::delete` (`app/Policies/ItemCommentPolicy.php:62`); `hide` y `unhide` como admin cambian el estado. **No** se fija la ausencia de notificación (BE-B2, la corrige F5-03).
8. **Ítems y panel:** `POST/DELETE /items` y `admin/items` (`update`, `moderate`) como admin, y 403 como usuario normal.
9. Cada ruta lleva su caso 401 (invitado) y 403 (rol insuficiente o baneado) cuando aplique.

**Restricciones:**
- No cambia `app/`. Los bugs ya conocidos (BE-B2, BE-R2, BE-R7, BE-R9, BE-R12, BE-R16) no se fijan en un test, porque los corrige su tarea.
- Un bug **nuevo** se anota en **Notas** y solo se arregla aquí si es trivial, con su test.
- `GET /items/my-items` no se prueba: es inalcanzable (BE-R9) y F5-06 la quita.

**Riesgo:** medio (puede destapar bugs).

**Hecho cuando:**
- `sail test` en verde, con los tests nuevos y ningún test existente tocado.
- Un `grep` de cada URI de las rutas listadas arriba en `Backend/tests/` da al menos un resultado por ruta (salvo `my-items`), con su 401 o 403.
- `git diff --stat` no toca nada fuera de `Backend/tests/` y `database/factories/`, salvo los bugs triviales anotados.

**Notas (F5-01):**
- **Archivos nuevos** (`Backend/tests/Feature/`, 85 tests): `Auth/LoginLogoutTest` (10), `Auth/PasswordResetTest` (6), `Auth/EmailVerificationTest` (8), `Admin/AdminUserBanTest` (12), `Admin/AdminItemModerationTest` (8), `Items/ItemCrudTest` (5), `Items/CommentModerationTest` (11), `Proposals/ProposalFlowTest` (17) y `Kudos/DailyClaimTest` (8). No se ha tocado `app/`, ni ningún test existente, ni ninguna factory.
- **Discrepancias con la ficha (R1, se sigue el código):**
  - El login con credenciales malas responde **401 `unauthenticated`** (`AuthenticatedSessionController.php:29-35`), no 422 como decía el paso 1; el 422 solo sale con campos vacíos o mal formados. Los tests fijan las dos cosas.
  - `ProposalFactory` queda sin cambios: `pending` ya es su estado por defecto.
  - Repetir una revisión aceptada por HTTP da **422** (`ReviewProposalRequest.php:57-59`, solo se revisan las `pending`) y no llega a la Action. El test fija ese 422 y que no se duplican ni el ítem ni los kudos; la carrera de dos aceptaciones simultáneas dentro de la Action (BE-R16) solo se puede probar allí, en F5-03.
  - `forgot-password`, `reset-password`, `login` y `verify-email` son rutas `guest` o firmadas: no tienen caso 401. Su 403 es el baneo en `login` y la firma o el *hash* malos en `verify-email`.
  - La recompensa diaria tiene un bonus aleatorio (`random_int`, no se puede sembrar): los tests comprueban que la cuantía sea la base o la base con el multiplicador de la racha efectiva (limitada al tope de 5).
- **Lo que no se fija a propósito:** BE-R1 (código de los 404 de modelo y `invalid-hash`), BE-R2 (correo inexistente en `forgot-password`) y BE-R3 (textos en inglés de los mensajes) → F5-04; BE-B2 (ausencia de aviso o registro al ocultar o borrar), BE-R8 (`reviewer` en `my-proposals`) y BE-R16 (carrera de la revisión) → F5-03; BE-R13 (`can_edit`/`can_delete`) → F5-05; BE-R9 (`my-items`) y BE-R10 (`images` en las altas y ediciones de ítems) → F5-06.
- **Bugs nuevos encontrados:** ninguno. No se ha arreglado nada en `app/`.

---

## F5-02 · Rendimiento de los listados: N+1 y límites (BE)

**Depende:** — · **Cierra:** BE-B1, BE-R5, BE-R6

**Hallazgos:**
- **BE-B1:** `Backend/app/Models/Category.php:65-73`. El *accessor* `itemsCount()` hace `activeItems()->count()` si la categoría no viene con `withCount`, y `CategoryResource.php:33` lo lee en **cada ítem** de los listados (categoría anidada). Medido: `GET /api/home` hace 56 consultas, 36 de ellas `select count(*) from items`. Afecta a `/api/items`, la portada, las listas, los similares, las recomendaciones y el onboarding.
- **BE-R6:** `app/Http/Controllers/Item/ItemCommentController.php:47,51,87,102,119,133` carga `user:id,name`, pero `ItemCommentResource.php:37` lee `user->profile->avatar`: una consulta por comentario y por respuesta.
- **BE-R5:** `app/Http/Controllers/Category/CategoryController.php:86` hace `(int) $request->query('per_page', 10)` sin validar en una ruta pública: con `per_page=100000` o `-1` devuelve la categoría entera.

**Pasos:**
1. El *accessor* (o el Resource) solo lee el valor cargado: `$this->attributes['items_count'] ?? null`, sin consultas implícitas. La clave `items_count` se mantiene en `CategoryResource` (R7). `index` y `show` de categorías siguen con `withItemCount()` y `loadCount('items')` (`CategoryController.php:37,55`), y `HomeFeedService.php:70` usa `withItemCount()`. En el frontend solo la lee `components/category/CategoryCard.jsx:30`, que es código muerto.
2. Revisa los demás usos (`grep -rn "itemsCount\|items_count" Backend/app`). `CategoryController.php:70` (el mensaje del 409) leería `null`: si el cambio lo rompe, corrígelo aquí con una cuenta explícita y anótalo (es BE-C5, previsto en F5-04).
3. Comentarios: carga `user.profile:id,user_id,avatar` en los seis puntos.
4. `app/Http/Requests/Category/ListCategoryRankingRequest.php` (nuevo): `per_page` `integer|min:1|max:100`, por defecto 10. El controlador lo usa en lugar de `$request->query`.
5. `Model::preventLazyLoading(! app()->isProduction())` en `AppServiceProvider::boot()`. Arregla lo que destape la suite con la carga mínima y anótalo. Si salta en `my-proposals` (`ProposalController.php:74`, `fresh()`), quita el `fresh()` aquí y anótalo (BE-R8, previsto en F5-03).

**Riesgo:** bajo.

**Hecho cuando:**
- Hay tests de consultas con `DB::enableQueryLog()` en `/api/home`, `/api/items` y `/api/items/{id}/comments`: el número de consultas **no crece** con el número de ítems o comentarios (p. ej., 3 frente a 12). **Fallan antes del cambio.**
- `GET /api/categories/{slug}/ranking` responde 422 con `per_page` igual a `-1`, `0`, `101` y `abc`, y 200 con `100`.
- `sail test` en verde con `preventLazyLoading` activo.

**Notas (F5-02):**
- **Archivos tocados** (`Backend/`): `app/Models/Category.php` (el *accessor* `itemsCount()` solo lee `items_count` y da `null` si no viene; nuevo `loadItemCount()` con el mismo recuento de activos que `withItemCount()`), `app/Http/Controllers/Category/CategoryController.php`, `app/Http/Requests/Category/ListCategoryRankingRequest.php` (nuevo), `app/Http/Controllers/Item/ItemCommentController.php` (`user.profile:id,user_id,avatar` en los seis puntos), `app/Http/Controllers/Proposal/ProposalController.php` (sin `fresh()` en `my-proposals`) y `app/Providers/AppServiceProvider.php` (`Model::preventLazyLoading(! $this->app->isProduction())`).
- **Tests nuevos (15):** `Performance/ListingQueryCountTest` (3), `Performance/CommentQueryCountTest` (2), `Performance/LazyLoadingTest` (2), `Categories/CategoryItemsCountTest` (4) y 4 más al final de `Ranking/CategoryRankingTest`. Antes del cambio fallaban 8 (las tres mediciones de consultas, los dos de `LazyLoadingTest`, la clave `items_count: null`, el 422 del ranking y el texto del 409). Suite completa: 868 tests en verde.
- **Consultas medidas** (con la caché vacía), pocos frente a muchos elementos: `GET /api/home` 16 (2 novedades) → 32 (10) antes, 12 → 12 después; `GET /api/items` 6 (3 ítems) → 15 (12) antes, 3 → 3 después; `GET /api/items/{id}/comments` 10 (2 hilos) → 22 (8 hilos) antes, 8 → 8 después.
- **R7:** la categoría anidada en los ítems devuelve `items_count: null` (la clave se mantiene; solo la leía `CategoryCard.jsx`, que es código muerto). `store`, `update` y `ranking` siguen dando el número real con `loadItemCount()`.
- **409 de `destroy`:** ahora cuenta todos los ítems con una consulta explícita; antes el texto contaba solo los activos y con un ítem inactivo decía "tiene 0 ítems" (parte de BE-C5; F5-04 puede revisarlo).
- **`my-proposals` sin `fresh()`:** la respuesta añade `category` y `reviewer`, que el Resource solo pone si la relación está cargada (parte de BE-R8; F5-03 ya no tiene que quitarlo).
- **Discrepancia sin tocar (R1/R6):** `show` cuenta con `loadCount('items')` todos los ítems, mientras que `index` cuenta solo los activos.
- **Violaciones de *lazy loading* destapadas por la suite:** una sola, en un test: `tests/Feature/Items/ItemCatalogFieldsTest.php:85` (`test_sources_devuelve_las_fuentes_del_item`) leía `$sources->first()->item` sin cargarla. Se cambia `$item->fresh()->sources` por `$item->fresh('sources.item')->sources`; la aserción no cambia. Ninguna más en `app/`.

---

## F5-03 · Moderación coherente con la DSA y propuestas (BE, LEGAL)

**Depende:** F5-01 · **Cierra:** BE-B2, BE-R16, BE-R8 · **Lee:** [../legal/cumplimiento.md](../legal/cumplimiento.md) §3 (puntos 3 y 5)

**Hallazgos:**
- **BE-B2:** `Backend/app/Http/Controllers/Item/ItemCommentController.php:105-136`. `hide`, `unhide` y `destroy` (los usa el panel: `Frontend/src/components/comments/CommentBox.jsx:70` y `hooks/admin/useAdminMutations.js:35`) no envían `ContentRemovedNotification` ni registran en `ModerationAuditLogger`. Solo lo hace `ResolveReportAction` (`app/Actions/ResolveReportAction.php:131-140`). Contradice cumplimiento §3: exposición de motivos (punto 3) y registro (punto 5). `ItemController::destroy` (`ItemController.php:96-101`) tampoco audita.
- **BE-R16:**
  - `app/Actions/ReviewProposalAction.php:39` bloquea la propuesta, pero no vuelve a comprobar que siga `pending`: dos aceptaciones simultáneas crean dos ítems.
  - `:90` mueve ficheros **dentro** de la transacción.
  - `ResolveReportAction.php:111` borra el avatar dentro de la transacción, al contrario que `DeleteAccountAction.php:100`, que lo hace fuera a propósito.
- **BE-R8:**
  - `app/Http/Controllers/Proposal/ProposalController.php:74`: `$proposals->fresh()` vuelve a consultar sin `with`, así que hay *lazy loading* de `category` en `ProposalListResource.php:41` y se pierde `reviewer`.
  - `:55-60`: un `catch (\Throwable)` vacío devuelve 201 aunque fallen las imágenes.
  - `:24-26`: constructor vacío.

**Pasos:**
1. `app/Actions/ModerateCommentAction.php` (nueva): ocultar, restaurar y borrar como admin, en una transacción corta y con el patrón de `ResolveReportAction`:
   - **ocultar y borrar** notifican al autor con `ContentRemovedNotification` (salvo que el admin sea el autor) y registran en `ModerationAuditLogger`;
   - **restaurar** solo se registra.
   
   Si hace falta un método nuevo en el *logger* (hoy tiene `logProposalReview`, `logItemModeration`, `logUserBanChange` y `logReportResolution`), se añade con el mismo formato. No se escriben textos legales nuevos: se reutilizan los de la notificación (guía §6).
2. `ItemCommentController::hide`, `unhide` y `destroy` (este último cuando borra un admin) delegan en la Action. Cuando el autor borra su propio comentario, no hay aviso.
3. `ResolveReportAction` reutiliza la Action para los comentarios, sin cambiar su comportamiento observable.
4. `ItemController::destroy` registra con `logItemModeration`.
5. `ReviewProposalAction`: después del `lockForUpdate`, si el estado no es `pending`, responde 409 (código `conflict`, formato `{"error"}`). El movimiento de ficheros va a `DB::afterCommit()`, y lo mismo el borrado del avatar en `ResolveReportAction`.
6. `ProposalController`:
   - sin `fresh()` en `my-proposals` (conserva el `with`, incluido `reviewer`);
   - en `store`, `report($e)` y `meta.images_failed: true` cuando fallan las imágenes (solo se añade un campo, R7);
   - quita el constructor vacío.

**Riesgo:** bajo-medio (moderación).

**Hecho cuando:**
- Estos tests **fallan antes** del cambio:
  - el autor recibe `ContentRemovedNotification` al ocultar desde `admin/comments/{id}/hide` y al borrar como admin;
  - hay registro de moderación (espía o *mock* de `ModerationAuditLogger`) al ocultar, restaurar y borrar, y al borrar un ítem;
  - revisar una propuesta que ya no está `pending` (la Action, llamada después de cambiar el estado) da 409 y no crea un segundo ítem;
  - `my-proposals` incluye `reviewer` sin *lazy loading* (con `preventLazyLoading` activo).
- `store` con el job de imágenes fallando responde 201 con `meta.images_failed`.
- Los tests de F3-04 (`tests/Feature/Reports/`) siguen en verde y `sail test` también.

**Notas (F5-03):**
- **Archivos tocados** (`Backend/`): `app/Actions/ModerateCommentAction.php` (nueva; `execute()` en transacción con estado, registro y aviso, y `apply()` solo con el cambio de estado), `app/Services/ModerationAuditLogger.php` (`logCommentModeration`), `app/Http/Controllers/Item/ItemCommentController.php` (`hide`, `unhide` y `destroy` delegan en la Action), `app/Actions/ResolveReportAction.php`, `app/Http/Controllers/Item/ItemController.php` (`destroy` registra `admin_delete_item`), `app/Actions/ReviewProposalAction.php` y `app/Http/Controllers/Proposal/ProposalController.php`.
- **Tests nuevos (17):** `Items/CommentModerationNoticeTest` (10), `Admin/ItemDeletionAuditTest` (1), `Reports/ResolveReportAfterCommitTest` (1), `Proposals/ProposalReviewConcurrencyTest` (3) y `Proposals/ProposalStoreImagesTest` (2). Antes del cambio fallaban 12 (los 3 de aviso al autor: ocultar, ocultar sin motivo y borrar; los 3 de registro de comentarios; el del logger, que no tenía el método; el del ítem; el del avatar tras el commit; el 409; los ficheros tras el commit; y `images_failed`). Suite completa: 885 tests en verde.
- **Discrepancia (R1):** `my-proposals` ya no usa `fresh()` y conserva `reviewer` desde la F5-02, y `LazyLoadingTest::test_mis_propuestas_traen_la_categoria_y_el_revisor_sin_carga_perezosa` ya lo cubre en verde, así que no hay test nuevo que falle antes.
- **Rama del autor en `destroy`:** hoy es inalcanzable, porque `ItemCommentPolicy::delete` solo deja borrar al admin (lo fija `test_el_autor_no_puede_borrar_su_comentario`). Se deja por si F5-05 cambia la policy.
- **Motivo por defecto:** ocultar sin motivo o borrar (que no lo pide) avisa con `Report::REASON_LABELS['otro']` ("Otro motivo"), para no escribir textos legales nuevos y no dejar "Motivo: ." en el aviso. Pendiente de validar por el humano.
- **`ResolveReportAction`** solo reutiliza `ModerateCommentAction::apply()`: el mock estricto de `AdminReportTest::test_registra_la_decision_en_el_log_de_moderacion` solo admite `logReportResolution`. Sigue avisando al autor aunque sea el propio admin (sin cambios).
- **Ficheros tras el commit:** un fallo al mover una imagen de la propuesta aceptada se reporta con `report()` y ya no deshace la aceptación; el ítem puede apuntar a una imagen que no se movió (decisión a validar).
- **`meta.images_failed`:** solo aparece con `true`, y solo cuando falla el job entero (p. ej., al resolver sus dependencias). Los fallos de una imagen concreta los sigue absorbiendo el job con `Log::warning`.
- **Comentarios desfasados sin tocar (R8):** `CommentModerationTest.php:12-15` y `ProposalFlowTest.php:204-205` siguen aludiendo a esta tarea.

---

## F5-04 · Contrato de errores y endpoints de cuenta (BE)

**Depende:** F5-01, F5-00 (D8) · **Cierra:** BE-R1, BE-R2, BE-R3, BE-R17, BE-C5, BE-C7

**Hallazgos:**
- **BE-R1:**
  - En `Backend/bootstrap/app.php:57-66` y `:107-129`, los *handlers* de `ModelNotFoundException` y `AuthorizationException` son código muerto: `Handler::render()` llama a `prepareException()` antes de los *callbacks* (`vendor/laravel/framework/.../Handler.php:616-618,668-672`). Por eso todo 404 de modelo responde `route_not_found` "La ruta solicitada no existe.", aunque `docs/backend/arquitectura.md:22` promete `not_found`.
  - Los 429 (`app/Http/Requests/Auth/LoginRequest.php:68` y `throttle:6,1` en `routes/auth.php:33,37`), los 405 y los 500 salen como `{"message": …}`.
  - Hay códigos fuera del catálogo: `CONFLICT_CATEGORY_HAS_ITEMS` (`app/Http/Controllers/Category/CategoryController.php:69`) e `invalid-hash` (`app/Http/Controllers/Auth/VerifyEmailController.php:26`).
- **BE-R2:**
  - `app/Http/Controllers/Auth/PasswordResetLinkController.php:31-35` responde 422 "We can't find a user…" con un correo inexistente y 200 con uno existente: enumera cuentas.
  - `app/Providers/AppServiceProvider.php:55` mete el correo sin codificar en la URL (`?email={...}`), así que `a+b@x.com` llega como `a b@x.com` (`Frontend/src/pages/auth/ResetPasswordPage.jsx:18`).
  - `register` y `forgot-password` no tienen límite.
- **BE-R3:** `config/app.php:95` usa `APP_LOCALE=es`, pero no existe `Backend/lang/`. Salen en inglés `__('auth.failed')` (`LoginRequest.php:49`), `__($status)` de los dos controladores de contraseña, los mensajes de validación por defecto y los correos de `app/Notifications/Auth/QueuedVerifyEmail.php` y `QueuedResetPassword.php`. También `app/Http/Controllers/User/DailyRewardController.php:37,52` (mensajes escritos a mano).
- **BE-R17:** los tokens caducan en un día (`config/sanctum.php:50`), pero `sanctum:prune-expired` no está programado en `routes/console.php`.
- **BE-C5:** el mensaje del 409 (`CategoryController.php:70`) usa `itemsCount` (solo los activos), mientras que la condición cuenta todos: con solo inactivos dice "tiene 0 ítems".
- **BE-C7:** `app/Http/Requests/Users/UpdateProfileRequest.php:45,77` exige 13 años; el registro y la documentación legal, 14.

**Pasos:**
1. **Handlers de `bootstrap/app.php`:**
   - En el de `NotFoundHttpException`, mira `$e->getPrevious()`: si es `ModelNotFoundException`, responde `not_found` con un mensaje por modelo ("El ítem no existe."…); si no, sigue con `route_not_found`.
   - 429 → `too_many_requests`, conservando la cabecera `Retry-After`.
   - 405 → `method_not_allowed`.
   - 500 → `server_error`, sin detalles fuera de `local` y `testing`.
   - Quita los dos *handlers* muertos o haz que sean alcanzables, y anota en **Notas** cuál de las dos cosas.
2. **Códigos normalizados:** `CONFLICT_CATEGORY_HAS_ITEMS` → `conflict` (o `category_has_items` en minúsculas, igual que el resto del catálogo de códigos) e `invalid-hash` → `invalid_hash`. El frontend no lee `error.code` en ningún sitio (comprobado), así que es inocuo para la SPA.
3. **`forgot-password`:** responde siempre 200 con el mismo mensaje, salvo los errores de validación. El correo va codificado en la URL (`http_build_query` o `rawurlencode`).
4. **Limitador `auth`:** un limitador con nombre `auth` (por IP, con la respuesta en el formato de la API, como `AppServiceProvider::tooManyRequests`) en `register` y `forgot-password`.
5. **Español:**
   - `lang/es/{auth,passwords,validation}.php` con al menos las claves que usa el proyecto;
   - `toMail()` propio en español en `QueuedVerifyEmail` y `QueuedResetPassword`;
   - los dos mensajes de `DailyRewardController`.
6. **Purga de tokens:** `Schedule::command('sanctum:prune-expired --hours=24')->daily()` en `routes/console.php`, en una hora libre (fuera de 03:00–05:30 UTC y de las copias de 03:30 y 03:45). Así pasan a ser **7** tareas programadas: anótalo para F5-13.
7. **Mensaje del 409:** que cuente todos los ítems (BE-C5), si no se arregló ya en F5-02.
8. **Edad:** `before:-14 years` y su mensaje en `UpdateProfileRequest` (BE-C7).
9. **Documentación:** actualiza `docs/backend/arquitectura.md` §1 (formato y códigos de error).

**Restricciones:**
- Es un cambio de contrato aprobado en D8.
- Los tests que esperan `route_not_found` se **actualizan, no se borran** (R8). Están en `tests/Feature/Users/PublicProfileTest.php:73,78,83,107-108`, `Categories/CategoryVisibilityTest.php:13,63,78,88` y `Notifications/NotificationApiTest.php:190`. Antes de tocar cada uno, comprueba si es un 404 de modelo (pasa a `not_found`) o de ruta o de `abort(404)` (se queda en `route_not_found`).

**Riesgo:** medio.

**Hecho cuando:**
- Estos tests **fallan antes** del cambio:
  - formato `{"error": {"code", "message"}}` en un 404 de modelo (`not_found`), un 429 del login, un 405 y un 500 simulado (sin traza fuera de `local` y `testing`);
  - `forgot-password` responde igual (200, mismo cuerpo) con un correo existente y con uno inexistente;
  - la URL del correo de restablecimiento lleva `a%2Bb%40x.com` para `a+b@x.com`;
  - el limitador `auth` responde 429 en el formato de la API;
  - el mensaje de `auth.failed` sale en español;
  - el perfil rechaza una fecha de nacimiento de 13 años.
- `sail artisan schedule:list` muestra 7 tareas.
- `sail test` en verde.

**Notas (F5-04):**
- **Archivos tocados** (`Backend/`): `bootstrap/app.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Controllers/Category/CategoryController.php`, `app/Http/Controllers/Auth/{VerifyEmailController,PasswordResetLinkController}.php`, `app/Http/Controllers/User/DailyRewardController.php`, `app/Http/Requests/Users/UpdateProfileRequest.php`, `app/Providers/AppServiceProvider.php`, `app/Notifications/Auth/{QueuedResetPassword,QueuedVerifyEmail}.php`, `config/kudos.php`, `routes/auth.php`, `routes/console.php` y `lang/es/{auth,passwords,validation}.php` + `lang/es.json` (nuevos).
- **Tests nuevos (24):** `Errors/ErrorResponseFormatTest` (8), `Auth/AuthRateLimitTest` (3), `Auth/SpanishMessagesTest` (4), `Auth/AuthNotificationMailTest` (3), `Auth/TokenPruneScheduleTest` (1), `Users/ProfileBirthdateTest` (2), más 2 en `PasswordResetTest` y 1 en `DailyClaimTest`. Antes del cambio fallaban 32 (los nuevos salvo `test_una_ruta_inexistente_sigue_respondiendo_route_not_found` y `test_el_perfil_acepta_una_fecha_de_nacimiento_de_14_anos`, más 10 tests con aserciones actualizadas: `PublicProfileTest` (2), `CategoryVisibilityTest` (3), `NotificationApiTest` (2), `ItemCrudTest`, `EmailVerificationTest` y `CategoryItemsCountTest`; 22 + 10). Suite completa: 909 tests en verde.
- **Handlers muertos:** se **quitan** los dos. El de `AuthorizationException` ya lo cubre `AccessDeniedHttpException` (el mismo cuerpo, `Handler::prepareException()` la convierte antes); el de `ModelNotFoundException` pasa al de `NotFoundHttpException` y se reconoce por `getPrevious()`, con más modelos en el mapa de mensajes (`ItemComment`, `ListItem`, `Proposal`, `Report`, `UserList`).
- **BE-C5** ya estaba resuelto en F5-02 (`destroy` cuenta todos los ítems): aquí solo cambia el código del 409 a `conflict`.
- **`auth.failed`** no llega a ninguna respuesta (el login responde 401 "Credenciales inválidas."), así que su test comprueba la traducción y no una respuesta HTTP.
- **Correos:** se sobrescribe `buildMailMessage()` y no `toMail()` (desviación menor respecto a la ficha), para no duplicar la lógica de la URL ni la de `toMailUsing()` de los padres. El *layout* del correo (saludo, despedida y *subcopy*) se traduce con `lang/es.json`.
- **`forgot-password`** también tapaba el estado `throttled` (segunda petición en menos de 60 s daba 422 y delataba la cuenta): ahora responde siempre 200 con el mismo cuerpo; `data.status` se mantiene con un valor fijo. `passwords.user` y `passwords.token` llevan el mismo texto para que `reset-password` tampoco enumere cuentas.
- **`LoginRequest`** añade `Retry-After` a su 429.
- **Limitador `auth`:** por IP y compartido entre `register` y `forgot-password` (6 por minuto, `kudos.auth.per_minute`, no es una variable de entorno).
- **7 tareas programadas** (la purga de tokens, a las 02:30 UTC), para F5-13: `docs/infra/produccion.md:103` dice "cinco" y lo rehace F5-13.
- **Fuera de alcance:** los `HttpException` con estados distintos de 404/403/405/409/422/429/500 (p. ej. 419 o 503) siguen con el formato por defecto.
- **Locale:** `config/app.php` sigue con `'en'` por defecto; el `es` sale de `APP_LOCALE` en los dos `.env.*.example`.
- **Edad mínima:** `UpdateProfileRequest` pasa a 14 años, como el registro.
- **Textos nuevos pendientes de validar por el humano:** los dos correos de cuenta, `lang/es/*` y `lang/es.json`, los mensajes de 404 por modelo, 405, 429 y 500 y los de la recompensa diaria.
- **Salida de `sail artisan schedule:list`:**
  ```
  0  3 * * *  php artisan items:recalculate-stats
  30 3 * * 1  php artisan items:recalculate-quality
  0  4 * * *  php artisan catalog:fetch-new --all
  0  5 * * *  php artisan catalog:refresh
  30 5 * * 0  php artisan catalog:purge-stale
  0  6 * * *  php artisan wishlist:notify-releases
  30 2 * * *  php artisan sanctum:prune-expired --hours=24
  ```

---

## F5-05 · Visibilidad y autorización coherentes (BE)

**Depende:** F5-01 · **Cierra:** BE-R4, BE-R7, BE-R12, BE-R13, BE-R14, BE-R15

**Hallazgos:**
- **BE-R4:** "activo, sin borrar y de categoría activa" se reescribe con `whereHas('category', fn … is_active)` en ~12 sitios:
  - `app/Services/HomeFeedService.php:146,163`, `RecommendationService.php:40` y `PublicProfileService.php:43,93`;
  - `app/Models/UserList.php:139` y `app/Services/UsageMetricsService.php:126`;
  - el comando y el job de la *wishlist*…

  `ItemController::index` (`app/Http/Controllers/Item/ItemController.php:33-39`) solo filtra `active()`: `/api/items` devuelve ítems de categorías desactivadas. `ItemPolicy::view` (`app/Policies/ItemPolicy.php:33`) tampoco la aplica.
- **BE-R7:** `app/Http/Requests/Items/ListItemCommentsRequest.php:25` usa `Gate::allows()` con la *guard* por defecto (`web`): en `GET /items/{id}/comments` el admin es anónimo y recibe 403 en un ítem inactivo. Conviven otras tres formas de resolver el usuario opcional: `$request->user('sanctum')`, `Auth::guard('sanctum')->user()` (`app/Http/Controllers/User/UserRankingController.php:16`) y `resolveViewer()`, duplicado en `ItemListResource` e `ItemDetailResource`.
- **BE-R12:** `app/Services/ItemStatsService.php:25` hace `Item::lockForUpdate()->findOrFail()`, que excluye los borrados: borrar o cambiar un voto de un ítem borrado da 404 y deshace la operación.
- **BE-R13:** `app/Http/Resources/ItemCommentResource.php:40` (`can_delete` es `true` para el autor, pero `ItemCommentPolicy::delete`, `:62`, solo deja al admin) e `ItemListResource.php:57` (`can_edit` para el creador, pero `ItemPolicy::update` solo deja al admin).
- **BE-R14:** `app/Http/Resources/MinimalProfileResource.php:19` calcula la posición con `count(total_kudos > los míos) + 1`, sin desempate y con una consulta dentro del Resource. El ranking usa `User::getKudosRank()` (`app/Models/User.php:265`).
- **BE-R15:** `Requests/Admin/{BanUser,UnbanUser,ShowAdminUser,ListAdminUsers,RevokeUserTokens,ListAdminReports,ResolveReport}Request.php` usan `hasRole('admin')`. No existen `UserPolicy` ni `ReportPolicy`, y la prohibición de que un admin borre su cuenta está en `app/Http/Controllers/User/ProfileController.php:120`.

**Pasos:**
1. `Category::scopeActive()` e `Item::scopeVisible()` (activo + categoría activa; los borrados ya quedan fuera por `SoftDeletes`). Úsalos en todos los sitios de BE-R4 (`grep -rn "whereHas('category'" Backend/app`) y en `ItemController::index`.
2. Ficha de un ítem de categoría inactiva: **404 para invitados y usuarios, visible para el admin**, igual que la categoría (`CategoryVisibilityTest`). Hazlo en `ItemPolicy::view` y anótalo en **Notas**.
3. Middleware `auth.optional` (alias en `bootstrap/app.php`) que haga `Auth::shouldUse('sanctum')` en las rutas públicas de `routes/api.php`. Quita las otras variantes (`resolveViewer()`, `Auth::guard('sanctum')`, `user('sanctum')` en esas rutas).
4. `withTrashed()` en `ItemStatsService::recalculate()`.
5. `can_delete` y `can_edit` con `$user->can('delete', $comment)` y `$user->can('update', $item)`. El campo no cambia de forma (R7): solo corrige su valor.
6. `MinimalProfileResource`: la posición se calcula en el controlador con `getKudosRank()` y se pasa al Resource.
7. `app/Policies/UserPolicy.php` (`viewAny`, `view`, `ban`, `unban`, `revokeTokens`, `deleteOwnAccount`) y `ReportPolicy.php` (`viewAny`, `resolve`). Los siete FormRequests usan `$this->user()->can(...)`, y la regla del controlador de perfil pasa a la policy.

**Restricciones:** el contrato JSON queda intacto (R7). No se desactiva ninguna comprobación (R8).

**Riesgo:** medio (muchos archivos).

**Hecho cuando:**
- Estos tests **fallan antes** del cambio:
  - un ítem de una categoría inactiva no sale en `/api/items` (ni en búsqueda ni en listados);
  - su ficha da 404 a un invitado y 200 al admin;
  - un admin con token lista los comentarios de un ítem inactivo (200);
  - borrar el voto de un ítem borrado funciona y el voto desaparece;
  - `can_delete` es `false` para el autor que no es admin (si la policy lo dice);
  - la posición de la cabecera coincide con la del ranking cuando hay empates.
- Hay tests de 403 por policy en `admin/users` y `admin/reports`.
- `sail test` en verde.

**Notas (F5-05):**
- **Archivos tocados** (`Backend/`):
  - Modelos y consultas: `app/Models/{Category,Item,UserList}.php`, `app/Services/{HomeFeed,Recommendation,PublicProfile,UsageMetrics,OnboardingItems,ItemStats}Service.php`, `app/Console/Commands/NotifyWishlistReleasesCommand.php` y `app/Jobs/NotifyWishlistReleasesJob.php`.
  - Autorización: `app/Policies/{ItemPolicy,UserPolicy,ReportPolicy}.php`, `app/Providers/AppServiceProvider.php` y los siete FormRequests de `app/Http/Requests/Admin/` (más `Items/ShowItemRequest.php`).
  - Sesión opcional: `app/Http/Middleware/AuthenticateOptionally.php` (nuevo), `bootstrap/app.php` y `routes/api.php`.
  - Controladores: `Category/CategoryController`, `Item/{ItemController,ItemCommentController}`, `Home/HomeController`, `Lists/ListController` y `User/{PublicProfileController,UserRankingController,ProfileController}`.
  - Resources: `ItemListResource`, `ItemDetailResource`, `ItemCommentResource`, `UserListResource` y `MinimalProfileResource`.
- **Tests nuevos (26):** `Items/ItemVisibilityTest` (7), `Auth/OptionalAuthTest` (5), `Items/PermissionFlagsTest` (5), `Votes/DeletedItemVoteTest` (1), `Users/MinimalProfileRankTest` (2) y `Admin/AdminPolicyTest` (6). Antes del cambio fallaban 15: 14 de los nuevos (5 de `ItemVisibilityTest`, 3 de `PermissionFlagsTest`, 3 de `AdminPolicyTest`, y uno de `OptionalAuthTest`, `DeletedItemVoteTest` y `MinimalProfileRankTest`) y el actualizado `SimilarItemsTest`. El de la posición fallaba antes por un error 500 del ranking con sesión (ver «Latente») y no por el «da 1» previsto. Suite completa: 935 tests en verde (909 + 26).
- **404 de la ficha:** lo da `ItemPolicy::view` con `Response::denyAsNotFound('El ítem no existe.')`, y `ShowItemRequest::authorize()` devuelve `Gate::inspect()` para conservar el estado. Como `Handler::prepareException()` lo convierte en un `HttpException(404)` genérico, `bootstrap/app.php` tiene una rama nueva 404 → `not_found` en el *handler* de `HttpExceptionInterface`; el cuerpo es idéntico al de un ítem inexistente. Los similares siguen a la ficha.
- **Ítem inactivo de una categoría activa:** sigue dando 403 al público, como hasta ahora. **Un ítem sin categoría** (`category_id` es nullable) se trata como no visible.
- **`deleteOwnAccount`:** la regla está en `UserPolicy`, pero se comprueba en `ProfileController::destroy` y no en `DeleteAccountRequest::authorize()`, para conservar el mensaje propio (el *handler* de 403 lo cambiaría por el genérico) y el orden actual (primero la contraseña, luego el 403).
- **Autobaneo y autorrevocación:** se quedan como 422 de validación (`BanUserRequest`, `RevokeUserTokensRequest`); pasarlos a la policy los convertiría en 403.
- **Ampliaciones (R1):** `ItemDetailResource` tenía el mismo `can_edit` erróneo que `ItemListResource` y se corrige igual; `can_edit` de un comentario oculto de su autor decía `true` aunque la policy lo deniega, y ahora sale de la policy.
- **Discrepancias con la ficha:**
  - `PublicProfileService.php:43` no usa `whereHas`, sino un `join` con `categories` (necesita `sort_order` y `name` para el `ROW_NUMBER`), y se queda como está.
  - Los sitios de `whereHas` de visibilidad eran 8, no ~12; las demás consultas `Category::…where('is_active', true)` pasan a `Category::active()`.
  - `ListItemCommentsRequest` no se toca: su `Gate::allows()` ya resuelve el usuario de `sanctum` gracias al *middleware*.
- **Tests actualizados (R8):** `SimilarItemsTest::test_un_item_de_una_categoria_inactiva_no_tiene_similares` ahora espera 404 para el invitado y `[]` para el admin; `ItemSearchTest::test_la_busqueda_global_carga_la_categoria_con_una_sola_consulta` filtra las consultas de `items`, porque el `exists` de `Item::scopeVisible()` también contiene `from "categories"`.
- **Latente (corregido de paso):** `GET /api/users/ranking` con sesión daba 500 con `preventLazyLoading` cuando había varios usuarios, porque la página del propio usuario no cargaba `profile`; `UserRankingController` la carga ahora con la misma consulta que la página Top.
- **Pendiente (no es de esta tarea):** los comentarios (`ItemCommentPolicy::viewAny`) y los votos (`VotePolicy::create`) de un ítem de una categoría inactiva siguen sin mirar la categoría.

---

## F5-06 · Código muerto y contratos heredados del backend (BE, INT)

**Depende:** F5-01, F5-00 (D6, D8) · **Cierra:** BE-R9, BE-R10, BE-C3; CAT-C-4, CAT-C-9, CAT-C-10, CAT-R-g

**Hallazgos:**
- **BE-R9 (código muerto o inalcanzable):**
  - `GET /api/items/my-items` (`Backend/routes/api.php:124`) queda tapada por `GET /items/{item}` (`:40`, sin `whereUuid`) y responde 404.
  - `ItemController::myItems` (`ItemController.php:103-`) y `ItemController::update` (`:89-94`) no tienen ruta útil; `app/Http/Requests/Admin/UpdateItemRequest.php` solo lo usa `update`.
  - `Category::loadAcceptedItemsWithDetails()` (`app/Models/Category.php:81`) carga la relación inexistente `fieldDefinitions`.
  - `app/Actions/EmitVoteAction.php:49-60`: el `catch (QueryException)` de "condición de carrera" ya no puede ocurrir (el bloqueo previo serializa) y en PostgreSQL tampoco funcionaría.
  - `app/Http/Middleware/CheckMinimumKudos.php`; `Item::scopeInactive`; `Proposal::scopeRejected/ChangesRequested`; `Item::kudosTransactions` y `Vote::kudosTransactions`; `UserList::coverItem`; `ListItem::userList`; `ItemComment::hiddenByUser`.
  - El comando `inspire` (`routes/console.php:8`) y el canal de log `kudos` (`config/logging.php:137`).
- **BE-R10:** `app/Http/Requests/Items/StoreItemRequest.php`, `Admin/AdminUpdateItemRequest.php` y `Proposals/UpdateProposalRequest.php` aceptan `images.*.{path,disk,alt,order}` y lo guardan tal cual, pero los Resources leen `variants.thumb/banner`. Un `PUT /admin/items/{id}` con `images` sustituye las imágenes reales del catálogo por entradas vacías.
- **BE-C3:** comentarios residuales en `app/Http/Controllers/Admin/AdminItemController.php:15,34,39` ("<-- Inyectamos el logger", "estaba en el repo"), `AdminUserController.php:67` ("movida desde AdminService"), `ProfileController.php:22` ("Display a listing of the resource.") y el *docblock* de `User.php:5-11`, que va antes de los `use`.
- **CAT-C-4:** constantes muertas en `app/Catalog/Providers/WikidataPlaceProvider.php:42-50` (`SOVEREIGN_STATE`, `HISTORICAL_COUNTRY`, `CITY`, `MUNICIPALITY` y `SPANISH_PROVINCE`) y `MAX_BYTES` en `app/Jobs/ProcessUserAvatarJob.php:25` y `ProcessProposalImagesJob.php:27`.
- **CAT-C-10:** `ProcessUserAvatarJob` (borrado del temporal en `:65`) y `ProcessProposalImagesJob::processItem()` (`:88-106`, borrado en `:104`) no borran el temporal si el procesado lanza una excepción. No tienen `finally`, a diferencia de `app/Jobs/Catalog/DownloadItemImagesJob.php:87-91`.
- **CAT-C-9:**
  - la ayuda de `catalog:import` solo cita `movie:603, tv:1399` (`app/Console/Commands/Catalog/ImportOneCommand.php:18`);
  - el mensaje de `catalog:refresh` (`RefreshCommand.php:84`) muestra el umbral global aunque se apliquen los de cada categoría.
- **CAT-R-g:** las migraciones `2026_11_04_000000_activate_videojuegos_category.php:16` y `2026_11_05_000000_activate_destinos_category.php:16` leen `IgdbGameProvider::ATTRIBUTE_SCHEMA` y `WikidataPlaceProvider::ATTRIBUTE_SCHEMA`. Si la constante cambia, `migrate:fresh` aplica otro esquema, y si la clase se renombra, se rompe.

**Pasos:**
1. **Ruta `my-items`:** quita la ruta y `ItemController::myItems` (D8). El *hook* `useMyItems` del frontend lo borra F5-12.
2. **Resto de BE-R9:** quita `ItemController::update` con `UpdateItemRequest`, `loadAcceptedItemsWithDetails`, el `catch` muerto de `EmitVoteAction` (el `Vote::create` se queda), `CheckMinimumKudos` (y su alias, si lo tiene), los *scopes* y relaciones sin uso, `inspire` y el canal `kudos`. Antes de borrar cada cosa, comprueba con `grep -rn` en `Backend/` y `Frontend/src` que no tiene referencias.
3. **`images`:** quítalo de las reglas y los mensajes de las tres peticiones (D8). Las imágenes solo entran por el *pipeline* de propuestas, avatar y catálogo.
4. **Comentarios residuales (BE-C3):** solo en esas líneas, sin reformatear (R6).
5. **Constantes muertas (CAT-C-4).**
6. **Temporales:** `try/finally` que borra el temporal en los dos jobs heredados (CAT-C-10).
7. **Textos (CAT-C-9):** la ayuda de `catalog:import` con ejemplos de IGDB y de QID, y el mensaje de `catalog:refresh` con el umbral real de cada categoría.
8. **Migraciones (D6):** escribe el JSON literal del esquema actual en las dos migraciones (copiado del valor de la constante en `bdb7adc`) y quita los `use` de los proveedores. Es la **única** edición de migraciones existentes permitida (excepción a R5, F5-00).
9. **Documentación:** actualiza las trampas 5 y 6 de `docs/backend/arquitectura.md` §3 (pasan a "(resuelta, F5-06)").

**Riesgo:** bajo (lo que se borra no se usa). El punto 3 cambia lo que admite el panel: el frontend no envía `images` en esas peticiones (compruébalo con `grep -rn "images" Frontend/src/hooks`).

**Hecho cuando:**
- `sail artisan route:list` no muestra `my-items`, y un `grep` de cada símbolo borrado no da resultados.
- Un test de F5-01 o uno nuevo comprueba que `PUT /admin/items/{id}` con `images` **no** cambia las imágenes (falla antes).
- Hay un test de que el temporal se borra cuando el procesado lanza una excepción (falla antes).
- Contra `testing`: `migrate:fresh`, `migrate:rollback --step=2` y `migrate` funcionan.
- `ActivateVideojuegosCategoryMigrationTest` y `ActivateDestinosCategoryMigrationTest` siguen en verde, y `sail test` también.

---

## F5-07 · Catálogo: política de jobs, *throttle* acotado e importación concurrente (INT)

**Depende:** — · **Cierra:** CAT-R-a, CAT-R-b, CAT-R-c, CAT-R-h · **Lee:** [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md) y [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md) §1

**Hallazgos:**
- **CAT-R-a:** `Backend/app/Catalog/Clients/Concerns/ThrottlesRequests.php:14-21` duerme hasta que haya hueco en el RateLimiter, y las claves `catalog:wikimedia` (ventana de 60 s) y `catalog:steam` (2 s) se comparten con el worker. Un `POST /api/catalog/import` durante una carga puede esperar decenas de segundos dentro de PHP-FPM (5 procesos). El "timeout corto" de `catalog.on_demand` solo limita el socket (`app/Catalog/Concerns/CallsProvidersOnDemand.php:21-38`).
- **CAT-R-b:**
  - `app/Jobs/Catalog/ImportCatalogItemJob.php:17-44` y los otros tres jobs del catálogo no declaran `tries`, `timeout`, `backoff` ni unicidad. Dependen del worker (`compose.prod.yml:50`: `--tries=3 --backoff=60`, *timeout* por defecto de 60 s).
  - Una importación de IGDB puede pasar de 60 s (7 peticiones en serie, más las esperas).
  - Los errores permanentes (`UnexpectedValueException`, `InvalidArgumentException`) se reintentan sin sentido.
  - `fetch-new` y `refresh` pueden encolar el mismo título dos veces.
  - `retry_after` de la conexión `database` es 90 (`config/queue.php:43`).
- **CAT-R-c:** el candado `catalog:on-demand-import:*` (`app/Actions/ImportCatalogItemOnDemandAction.php:45`) solo lo toma la importación bajo demanda. Si el worker importa el mismo título a la vez, la segunda transacción choca con el índice único de `item_sources (provider, external_id)` o de `items.wikidata_id` (`app/Catalog/CatalogImporter.php:98-106`) y el usuario recibe un 500.
- **CAT-R-h:** el *docblock* de `CallsProvidersOnDemand.php:16` dice "TMDB, Wikidata y Wikipedia", pero ya afecta también a IGDB, Steam y Commons.

**Pasos:**
1. **Espera máxima en `throttle()`:** un parámetro o un valor en el contenedor (`maxWaitSeconds`). Si la espera necesaria lo supera, lanza una `RuntimeException`, que `callProvider` ya traduce en 503. Durante la petición bajo demanda vale `catalog.on_demand.timeout_seconds` (o una clave nueva `on_demand.max_throttle_wait_seconds` en `config/catalog.php`, comentada). En los jobs no tiene límite. Se restaura en el mismo `finally` que el resto de la configuración bajo demanda.
2. **`ImportCatalogItemJob`:**
   - `ShouldBeUnique` con `uniqueId = "{categorySlug}:{externalId}"` y `uniqueFor = 3600` (el candado va por la caché, que es Redis en producción);
   - `$tries = 3`, `$backoff = [30, 120]` y `$timeout = 120`;
   - `$this->fail($e)` ante `UnexpectedValueException` e `InvalidArgumentException`.
   
   Valores explícitos también en `FetchNewCatalogItemsJob`, `DownloadItemImagesJob` y `PurgeStaleCatalogItemsJob`.
3. **`retry_after`:** súbelo por encima del mayor *timeout* (p. ej. 180) en `config/queue.php` para `database` y `redis`. Si cambia el comando del worker en `compose.prod.yml:50` (`--timeout`), anótalo para F5-13 (no se despliega nada, R10).
4. **Candado de importación:** muévelo a `CatalogImporter` (un `importLocked(provider, externalId)` que usen el job y la acción). Alternativa: capturar `UniqueConstraintViolationException` y repetir una vez la búsqueda por fuente. Anota en **Notas** cuál se elige.
5. **Docblock:** corrige el de `CallsProvidersOnDemand`. El *singleton* `CatalogHttpProfile` es opcional: hazlo solo si simplifica el punto 1.

**Restricciones:** R4 (`StrayRequestException` siempre se vuelve a lanzar). Sin variables de entorno nuevas, salvo que hagan falta (R9).

**Riesgo:** medio (reintentos y cola).

**Hecho cuando:**
- Estos tests **fallan antes** del cambio:
  - con el RateLimiter saturado y `Sleep::fake()`, una importación bajo demanda responde 503 sin dormir más que el máximo;
  - el mismo título encolado dos veces da un solo job (`Queue::fake` con la unicidad);
  - una `UnexpectedValueException` hace `fail()` sin reintento;
  - una fuente creada a mitad de la importación (evento `creating` que inserta la fuente) no da 500 ni duplica el ítem.
- `CatalogImportApiTest` e `ImportCatalogItemJobTest` siguen en verde, y `sail test` también.

---

## F5-08 · Datos y recursos de terceros (BE, FE, LEGAL)

**Depende:** F5-00 (D1, D2, D5) · **Cierra:** TR-H2, CAT-B-1, FE-R8 · **Lee:** [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md) §1 y §3, y [../legal/cumplimiento.md](../legal/cumplimiento.md) §2 y §4

**Hallazgos:**
- **TR-H2:** `Backend/database/seed-data/` contiene volcados que el proyecto declara no redistribuibles, en un repositorio público. Son unos 3,3 MB en total:
  - RAWG: `videojuegos/rawg_videojuegos_es.json` y `_backup.json`;
  - iTunes: `albumes-musicales/itunes_albumes_es_global.json`;
  - TMDB: `peliculas/` y `series/`;
  - además: `artistas-musicales`, `ciudades`, `libros`, `paises` y `politicos`.
  
  Ver `docs/integraciones/fuentes-externas.md:43` (RAWG prohíbe redistribuir). Solo los lee `database/seeders/ItemSeeder.php:42-50`, que no se ejecuta (`DatabaseSeeder.php:33`). `ProposalSeeder` tampoco se ejecuta.
- **CAT-B-1:** `database/seeders/CategorySeeder.php:37,47,57` descarga portadas de Amazon/IMDb, Empire e IGN en `categories/{slug}/seed/`, y el seeder se ejecuta en producción.
- **FE-R8:** `Frontend/src/index.css:1` importa Inter desde Google Fonts, así que cada visita envía la IP a Google. `pages/legal/PrivacyPage.jsx` no lo menciona, y tampoco las teselas de OSM (F4-03).

**Pasos:**
1. **D2:** borra `Backend/database/seed-data/`, `ItemSeeder.php` y `ProposalSeeder.php`, y ajusta el comentario de `DatabaseSeeder.php:33`. Sin tocar el historial.
2. **D1:** `CategorySeeder` deja `image => null` en las cuatro categorías y ya no usa `DownloadsSeedImages`. Si el *trait* (`database/seeders/Trait/DownloadsSeedImages.php`) se queda sin usuarios, bórralo. Quita también el comentario de `CategorySeeder.php:31` sobre `seed-data`.
3. **Test de portadas:** `tests/Feature/Seeders/CategorySeederTest.php:35-40,97-100` espera portadas descargadas en `categories/{slug}/seed/`. **Modifícalo, no lo borres** (R8): ahora debe comprobar `image = null` y que no se hace ninguna petición.
4. **Frontend sin portada:** comprueba que la portada, `/buscar` y la página de categoría se ven bien sin portada, como ya pasa con `destinos`.
5. **D5, fuente:** Inter autoalojada, con `@fontsource-variable/inter` a versión fijada o con `public/fonts/` y `@font-face` (incluye su licencia OFL). Quita el `@import` de Google.
6. **D5, privacidad:** en `PrivacyPage.jsx`, añade OpenStreetMap (las teselas del mapa de destinos envían la IP a `tile.openstreetmap.org`) a la sección de terceros, con un texto **propuesto** y marcado para que el humano lo apruebe al verificar (guía §6).
7. **Documentación:** quita las menciones a `seed-data` y a las portadas de `README.md:56`, `Backend/README.md:187-189`, `docs/producto/vision.md:27`, `docs/infra/entorno-local.md` §3 y `docs/backend/arquitectura.md` (Media). Deja una línea en [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md) §3: "portadas de categoría: ninguna (D1)". El *runbook* (borrar `categories/*/seed/` del volumen) lo hace F5-13.

**Riesgo:** bajo en lo técnico y alto en lo legal (por eso va antes de `main`).

**Hecho cuando:**
- `git grep -n "seed-data\|ItemSeeder\|ProposalSeeder" -- Backend/app Backend/database Backend/tests` no da resultados.
- Contra `testing`, `migrate:fresh --seed` termina sin peticiones externas.
- `CategorySeederTest` y `DatabaseSeederTest` en verde, y `sail test` también.
- `pnpm build` y `pnpm test:seo` sin errores.
- En la prueba de navegador (`vite preview` + `puppeteer-core`, con las peticiones externas interceptadas), no hay ninguna petición a `fonts.googleapis.com` ni a `fonts.gstatic.com`, y Inter se carga desde el propio origen.
- El texto nuevo de `/privacidad` queda señalado en **Notas** para que lo apruebe el humano.

---

## F5-09 · Barrera de errores y tarjetas de ítem como enlaces (FE)

**Depende:** — · **Cierra:** FE-B1, FE-B2, FE-C1

**Hallazgos:**
- **FE-B1:**
  - `Frontend/src/core/AppErrorBoundary.jsx:3,28` pasa `icon={logo}` (una URL importada de `public/logo.svg`) a `FeedbackState`, que la pinta como componente (`<Icon size={40}/>`, `components/ui/FeedbackState.jsx:26`). El propio *fallback* lanza una excepción y la página queda en blanco.
  - `:33`: `onAction={this.handleReload}` es `undefined` (el método no existe), así que el botón "Recargar página" no sale.
  - `:20`: `console.error('Error chungo: ', …)`.
- **FE-B2:**
  - `components/items/ItemCard.jsx:14` es un `BouncyCard` (`motion.article`, `components/animations/BouncyCard.jsx:8-15`) con `onClick`: sin enlace, sin teclado (WCAG 2.1.1), sin "abrir en pestaña nueva" y sin enlaces rastreables.
  - `handleItemClick` está copiado en `hooks/pages/useListPage.js:26`, `usePublicProfilePage.js:23`, `useHomePage.js:33`, `useSearchPage.js:57`, `useExplorePage.js:61` y `useCategoryPage.js:63`, más `handleSimilarItemClick` en `pages/ItemDetailPage.jsx:22`.
  - Pasan `onItemClick` u `onClick`: `components/home/HomeItemRow.jsx`, `home/EditorialListCard.jsx`, `items/SimilarItemsRow.jsx`, `items/InfiniteItemSlider.jsx`, `profile/PublicTopCategoryRow.jsx` y `ui/ranking/Ranking.jsx`, además de las páginas `HomePage`, `CategoryPage`, `ItemDetailPage` y `PublicProfilePage`.
- **FE-C1:** textos poco profesionales:
  - `pages/ErrorPage.jsx:15`: "¡Enhorabuena, has me has roto el proyecto!", con errata;
  - `pages/auth/ForbiddenPage.jsx:15`: "¡Alto ahí, individuo!";
  - `AppErrorBoundary.jsx:20`;
  - "lobby" en `routes/guards/RequireGuest.jsx:22`.

**Pasos:**
1. **Barrera de errores:** icono de lucide (`AlertTriangle`), `handleReload = () => window.location.reload()` y un mensaje de log neutro.
2. **Textos:** reescribe los de FE-C1 en español claro, tuteando. Si cambia algún texto de cara al usuario más allá de esos, anótalo.
3. **Tarjeta como enlace:** `ItemCard` envuelve el contenido en `<Link to={buildItemUrl(item)}>` (`lib/search.js:17`) o en `motion(Link)`, conservando la animación. `buildItemUrl` devuelve `null` sin categoría: en ese caso, una tarjeta sin enlace.
4. **Botón dentro del enlace:** `components/lists/AddToListButton.jsx` añade `preventDefault()` a su `stopPropagation()`. Revisa cualquier otro botón dentro de la tarjeta.
5. **Limpieza:** quita los siete manejadores y las *props* `onItemClick`/`onClick` de la cadena. El *scroll* arriba de la ficha ya lo hace su `useEffect([itemId])`.

**Riesgo:** medio (muchos puntos de uso).

**Hecho cuando:**
- `docker compose run --rm kudos-frontend pnpm build` y `pnpm test:seo` sin errores.
- `grep -rn "handleItemClick\|onItemClick\|handleSimilarItemClick" Frontend/src` no da resultados.
- Prueba de navegador (`vite preview` + `puppeteer-core`, API local de solo lectura, peticiones externas interceptadas):
  - un `throw` temporal en una página muestra el *fallback* con "Recargar página" y no una página en blanco (el `throw` no se queda en el código);
  - con Tab + Enter se abre una ficha desde la portada, una categoría, `/buscar`, una lista y un perfil público;
  - las tarjetas son `<a href>` (clic central = pestaña nueva);
  - "Añadir a lista" abre el modal y no navega;
  - sin `pageerror`.

---

## F5-10 · Errores de API tipados en el frontend (FE)

**Depende:** F5-04 · **Cierra:** FE-R1

**Hallazgo (FE-R1):**
- `Frontend/src/core/axiosClient.js:41-55` hace `Promise.reject(new Error(finalMessage))`, que pierde `status`, `error.code` y `error.details` del formato `{"error": {...}}`. Consecuencias:
  - `pages/ItemDetailPage.jsx:46-55` responde "Intenta nuevamente en unos segundos" ante un 404;
  - los 422 no llegan a los campos de react-hook-form;
  - se pierden los detalles del baneo del login.
- `hooks/common/useBaseMutation.js:41` lee `error?.response?.data?.message`, que ya no existe: es código muerto.

**Pasos:**
1. **`core/apiError.js`:** `class ApiError extends Error { status, code, details }`. El interceptor lo rechaza conservando `message`, así que los *toasts* actuales siguen igual. Los errores sin respuesta (red) llevan `status = 0`.
2. **`useBaseMutation`:** acepta `onFieldErrors` o un `setError` de react-hook-form y, ante un 422, reparte `details` por campo. Quita la lectura muerta de `:41`.
3. **Ficha:** distingue `status === 404` ("Este ítem no existe o ya no está disponible") del resto de errores.
4. **Formularios:** el registro (`RegisterForm`), el perfil (`ProfileEditForm`) y la propuesta (`NewProposalForm`) marcan el campo del 422.
5. **Documentación:** documenta `ApiError` en `docs/frontend/arquitectura.md` §5.

**Riesgo:** medio (todo el manejo de errores pasa por aquí).

**Hecho cuando:**
- `pnpm build` y `pnpm test:seo` sin errores.
- Prueba de navegador con la API local:
  - una ficha con un UUID inexistente muestra el mensaje de "no existe";
  - un registro con un correo repetido marca el campo de correo;
  - un error genérico sigue saliendo como *toast*;
  - el login de una cuenta baneada sigue mostrando su mensaje;
  - sin `pageerror`.

---

## F5-11 · Accesibilidad de formularios, modal y botones (FE)

**Depende:** F5-09 · **Cierra:** FE-R3 y el `useDismiss` de FE-R10

**Hallazgos:**
- **FE-R3:**
  - `Frontend/src/components/ui/InputField.jsx:6,15`: el `<label>` no está asociado al `<input>`, y el error no lleva `aria-invalid` ni `aria-describedby`. Lo mismo en `ui/TextAreaField.jsx`.
  - `ui/Modal.jsx:83-116`: le faltan `role="dialog"`, `aria-modal` y `aria-labelledby`; no se cierra con Escape, no atrapa el foco y no lo devuelve al cerrar.
  - Botones de solo icono sin `aria-label`: `components/comments/CommentBox.jsx:93,127` y `Comment.jsx:104-105`.
  - Campos sin etiqueta: `CommentBox.jsx:87,118`, `ui/SearchFilter.jsx:8` y `ui/SelectFilter.jsx:7`.
  - `ui/Button.jsx` no reenvía `ref`, y por eso `hooks/reports/useReportMenu.js:96` busca el botón con `querySelector('button')`.
- **FE-R10 (parte):** "clic fuera + Escape" está copiado en `hooks/notifications/useNotificationBell.js:36-57` y `useReportMenu.js:86-107`.

**Pasos:**
1. `useId` en `InputField` y `TextAreaField`: `htmlFor` e `id`, y `aria-invalid` y `aria-describedby` con el error.
2. `Modal`: `role="dialog"`, `aria-modal="true"` y `aria-labelledby` (el título); Escape cierra; el foco queda atrapado dentro y vuelve al disparador al cerrar. Sin dependencias nuevas si es posible.
3. `aria-label` en español en los botones de icono y en los campos sin etiqueta citados.
4. `forwardRef` en `Button`, y `useReportMenu` usa la `ref`.
5. `hooks/common/useDismiss.js` (clic fuera + Escape), usado por la campana de notificaciones y el menú de reporte.

**Riesgo:** bajo-medio.

**Hecho cuando:**
- `pnpm build` y `pnpm test:seo` sin errores.
- Prueba de navegador:
  - un clic en la etiqueta enfoca el campo en el login y el registro;
  - un campo con error tiene `aria-invalid="true"`;
  - Escape cierra el modal de "Añadir a lista" y el foco vuelve al botón;
  - Tab no sale del modal abierto;
  - la campana y el menú de reporte se cierran con un clic fuera y con Escape.
- *axe-core* (inyectado en la prueba, sin añadirlo al `package.json`) no da errores `critical` en el login, el registro, una ficha y el panel de admin. Lo que quede por debajo de `critical` se anota en **Notas**.

---

## F5-12 · Clases sin definir, duplicados y código muerto del frontend (FE)

**Depende:** F5-06, F5-09 · **Cierra:** FE-R9, FE-R10 (iconos, `StatCard` y *observer*), FE-R11, FE-R13, FE-C2 y el favicon de FE-C6

**Hallazgos:**
- **FE-R9:**
  - `Frontend/src/pages/auth/AuthPage.jsx:18-20` usa `animate-blob`, `animation-delay-2000` y `animation-delay-4000`, que no existen.
  - `bg-surface/60` (`components/admin/AdminNav.jsx:12`), `border-border/60` (`components/items/ItemImage.jsx:41`) y `text-text-btn/80` (`components/ui/ActionButton.jsx:31`) no se generan: los colores son `var(--color-…)` en hexadecimal y Tailwind 3 no puede aplicarles opacidad.
- **FE-R10:**
  - hay tres mapas de iconos (`lib/constants.js:90,104,117`);
  - `components/home/CategoryShortcuts.jsx:13` usa `getCategoryIcon(category.name)` (`lib/formatters.js:65`) e ignora `category.icon`, mientras que `components/ui/ItemPlaceholder.jsx:18` sí lo usa;
  - `components/ui/ranking/Ranking.jsx:20-40` reimplementa el `IntersectionObserver` (ya existe `hooks/common/useInfiniteScroll.js`);
  - hay dos `StatCard`: `components/admin/StatCard.jsx` y `components/profile/StatCard.jsx`.
- **FE-R11:**
  - archivos sin importar: `components/category/CategoryCard.jsx` y `components/ui/skeletons/CategoryCardSkeleton.jsx`;
  - *exports* sin uso: `useAdminCreateItem` y `useAdminUpdateItem` (`hooks/admin/useAdminMutations.js:49,56`), `useMyItems` (`hooks/items/useItemQueries.js:62`; su ruta la quita F5-06), `useMyVotes` (`hooks/votes/useVoteQueries.js:11`), `usePendingProposals` (`hooks/admin/useAdminQueries.js:52`) y `EASING` (`lib/animations.js`);
  - `useProposal` y `useResendVerificationEmail` **se conservan**: los usan F5-18 y F5-17;
  - `vite.config.js:5`: `env` sin uso;
  - `@types/react`, `@types/react-dom` y `@types/node` en `package.json`, sin TypeScript (y `@types/react@19` con React 18).
- **FE-R13:** `components/ui/StorageImage.jsx:33` no usa `loading="lazy"` ni `decoding="async"`.
- **FE-C2:** `ui/Button.jsx:67` concatena `className` sin `twMerge`.
- **FE-C6 (favicon):** `Frontend/index.html` apunta a `./public/favicon.svg` y `./public/favicon.ico`, en lugar de `/favicon.svg` y `/favicon.ico`.

**Pasos:**
1. **Colores:** pasa los de `index.css` a canales RGB y `tailwind.config.js` a `rgb(var(--…) / <alpha-value>)`, sin cambiar los colores visibles. Alternativa: quitar los modificadores de opacidad. Define `blob` y sus retardos en `tailwind.config.js` o quítalos de `AuthPage`.
2. **Iconos:** una sola `resolveCategoryIcon(category)` (primero `category.icon`, después el *slug*, después un icono genérico) en `lib/`, usada por `CategoryShortcuts`, `ItemPlaceholder` y el resto; quita los mapas sobrantes.
3. **Ranking:** `Ranking.jsx` con `useInfiniteScroll`.
4. **`StatCard`:** uno solo, si las dos variantes caben con *props*. Si no, renómbralos para que no se confundan y anótalo.
5. **Código muerto:** borra los archivos, *exports* y dependencias de FE-R11, comprobando antes cada uno con `grep -rn`. Regenera `pnpm-lock.yaml` dentro del contenedor.
6. **Imágenes:** `loading="lazy"` y `decoding="async"` por defecto en `StorageImage`, sobrescribibles. La imagen principal de la ficha va con `loading="eager"`.
7. **Botón:** `twMerge(clsx(...))` en `Button`.
8. **Favicon:** rutas absolutas en `index.html`.
9. **Documentación:** `docs/frontend/arquitectura.md` §3 (colores con opacidad).

**Riesgo:** bajo.

**Hecho cuando:**
- `pnpm build` y `pnpm test:seo` sin errores.
- El script que compara las clases usadas en `src/` con el CSS generado (el de la auditoría: extraer las clases de los `className` y buscarlas en `dist/assets/*.css`) no devuelve ninguna clase de Tailwind sin generar. El script se deja en `Frontend/scripts/` o se describe en **Notas**.
- `grep` de cada símbolo borrado vacío.
- Prueba de navegador: la portada muestra el icono correcto de las 4 categorías, el ranking carga más al hacer *scroll*, el favicon carga y no hay `pageerror`.

---

## F5-13 · *Runbook* único del paso a Kudos 2.0 y salvaguardas de `main` (INFRA, DOCS)

**Depende:** F5-00 (D7), F5-07, F5-08 · **Cierra:** TR-H3, TR-H9 y la frontera "log de moderación" del informe del backend · **Lee:** [../infra/produccion.md](../infra/produccion.md) §3 y §5–§7

**Hallazgos:**
- **TR-H3:** `docs/infra/produccion.md:57-108` (§5) asume "`main` con la Fase 1 fusionada" (`:62`). Además:
  - espera "solo `peliculas` y `series`" (`:102`, `:107`) y "las cinco tareas" (`:103`), cuando `routes/console.php` programa 6 (7 tras F5-04);
  - los requisitos (`:61-64`) no piden `IGDB_CLIENT_ID` ni `IGDB_CLIENT_SECRET`, que solo aparecen en §6 (`:117`);
  - §6 y §7 dicen "con producción ya sembrada (§5)";
  - el *push* a `main` despliega Vercel al instante (`deploy.sh:7`).
  
  `deploy/README.md:264` también dice "las cinco tareas", y `:282-290` dice que "la duración de la importación nueva se anotará en la F1-11".
- **TR-H9:** `.claude/settings.json:9-10` permite sin preguntar `git switch *` y `git merge --no-ff *`, y por tanto `git switch main && git merge --no-ff rediseno-fase-0`. El `deny` (`:12`) solo cubre `push`, `deploy.sh` y `ssh`.
- **Log de moderación:** el canal `moderation` escribe en un fichero de `storage/logs` y en producción `LOG_CHANNEL=stderr`. Hay que confirmar que el volumen es persistente: `compose.prod.yml:28` monta `storage-logs` en `app`; comprueba también `worker` y `scheduler`.

**Pasos:**
1. **Un solo procedimiento:** funde §5, §6 y §7 de `produccion.md` en "§5. *Runbook*: paso a Kudos 2.0 (fases 0–4)", que ejecuta el humano en F5-16 (D7). Orden:
   1. variables en `Backend/.env.production`: `TMDB_API_TOKEN`, `IGDB_CLIENT_ID`, `IGDB_CLIENT_SECRET`, `CATALOG_USER_AGENT`, `CATALOG_IMPORT_LIMIT`, `LEGAL_CONTACT_EMAIL`, `SEED_ADMIN_*` y las de Vercel (cotejadas con `.env.production.example` y §3);
   2. **pausar el despliegue automático de Vercel** (en el panel de Vercel; el *runbook* indica dónde y cómo comprobarlo);
   3. copias de seguridad (base de datos e imágenes);
   4. *push* a `main` (lo hace el humano);
   5. `deploy.sh`;
   6. parar el worker y el scheduler, `migrate:fresh --seed --force` y limpiar el volumen: `items/*`, `proposals/*`, `avatars/*` y **`categories/*`** (D1: ya no hay portadas de categoría);
   7. `cache:clear` y arrancar el worker y el scheduler;
   8. `catalog:fetch-new --all` y la carga de destinos (`--category=destinos`, en el proceso del comando);
   9. comprobaciones;
   10. **reanudar Vercel** y comprobar que la web nueva apunta a la API nueva.
2. **Coherencia con F5-07:** si F5-07 cambió el comando del worker (`--timeout`) o `retry_after`, el *runbook* lo refleja.
3. **Comprobaciones:**
   - `/api/categories` devuelve las **4** categorías activas en orden (`peliculas`, `series`, `videojuegos`, `destinos`), con `image = null`;
   - `schedule:list` muestra el número real de tareas (`grep -c "Schedule::" Backend/routes/console.php`: 6 en `bdb7adc`, 7 tras F5-04);
   - cola vacía y sin fallos;
   - recuento por categoría;
   - la web enseña las 4 categorías con atribuciones.
   
   Los resultados se anotan en la ficha de F5-16.
4. **`deploy/README.md`:** actualiza `:264` (número de tareas) y `:282-290` (la duración se anota en F5-16, no en F1-11). Revisa también `:173`.
5. **Log de moderación:** confirma con `compose.prod.yml` que `storage/logs` es persistente en los servicios que escriben en él. Si no lo es, anótalo como requisito del *runbook* (no se toca producción, R10).
6. **`.claude/settings.json`:** añade a `deny` `"Bash(git switch main*)"`, `"Bash(git checkout main*)"` y `"Bash(git merge * main*)"`. Comprueba que el flujo normal (`git switch rediseno-fase-0`, `git merge --no-ff fase-5/...`) sigue permitido.
7. **Enlaces:** la ficha de F1-11 ya está `bloqueada` y remite a F5-16 (F5-00). Comprueba que `produccion.md` y `deploy/README.md` no la citan como pendiente.

**Restricciones:** no se ejecuta nada contra producción ni el VPS, y no se ejecuta `deploy.sh` (R10).

**Riesgo:** bajo en el repositorio; alto si no se hace.

**Hecho cuando:**
- El *runbook* se ha revisado contra el código: el número de `Schedule::`, las migraciones `activate_*` (4 categorías), las variables de `.env.production.example` (todas las que necesita el *runbook* están en §3) y las rutas del volumen.
- `grep -n "F1-11\|cinco tareas\|solo .peliculas. y .series." docs/infra/produccion.md deploy/README.md` no da resultados obsoletos.
- `.claude/settings.json` es JSON válido (`python3 -m json.tool`).
- El orden del *runbook* queda señalado en **Notas** para que lo valide el humano.

---

## F5-14 · Documentación lista para `main` (DOCS)

**Depende:** F5-01…F5-13, F5-00 (D3, D9) · **Cierra:** TR-H4, TR-H5, TR-H6, TR-H7, TR-H10 y la parte documental de FE-C6

**Hallazgos:**
- **TR-H4:** `README.md:126-127`, `Backend/README.md:148` y `:231-232` enlazan a `Backend/docs/api-contract.md` y `api-reference.md`, que solo existen en local: `Backend/.gitignore:25` ignora `/docs` entero.
- **TR-H5:**
  - `docs/README.md:3` ("`docs/` solo existe en ella") y `:9` ("Siempre: guia-agentes.md");
  - `README.md:9` ("Kudos 2.0 en curso…");
  - `AGENTS.md:10` y `docs/guia-agentes.md:28,37` hablan de `rediseno-fase-0` como rama base.
- **TR-H6:**
  - `README.md:116` dice "El frontend no tiene tests" (existen `Frontend/tests/seo/`);
  - al árbol de `README.md:35-86` le faltan `app/Catalog/`, `app/Support/`, `Frontend/api/`, `Frontend/tests/` y `Frontend/scripts/`;
  - `Backend/README.md:26` (comandos), `:46-121` (módulos) y `:76-78` (kudos por "primer voto") están desfasados;
  - `Frontend/README.md:5` dice "Esqueleto base".
- **TR-H7:** `.github/workflows/docs.yml:31` usa `-d ./Backend/app/Repositories`, que no existe, y `:23-35` no incluye `app/Catalog`, `Actions`, `Jobs`, `Console`, `Support` ni `Notifications`. Usa `actions/checkout@v4`, mientras que `tests.yml` usa `@v7`.
- **TR-H10:**
  - `docs/backend/testing.md:26` (*fixtures*: faltan `igdb/`, `steam/` y `commons/`), `:84` (factorías: faltan `ItemComment`, `ListItem`, `Report` y `UserList`) y `:132` (falta `pnpm test:seo`);
  - `docs/backend/arquitectura.md:14` (lista 5 de las 15 Actions) y `:17` (Jobs);
  - la trampa 7 (`:137`) está resuelta y no lo dice.
- **FE-C6:** `docs/frontend/arquitectura.md` §1 no menciona `app/` y dice que "cada página tiene su hook" y que no hay clases sin definir.

**Pasos:**
1. **D9:**
   - `Backend/.gitignore` ignora solo `/docs/api`;
   - revisa `Backend/docs/api-contract.md` contra el código (formato y códigos de error de F5-04; rutas actuales) y versiónalo;
   - `api-reference.md` se versiona solo si está al día; si no, se retira y los enlaces apuntan al contrato y a `docs/backend/arquitectura.md`;
   - `Kudos.drawio.xml`: por defecto no se versiona (anota la decisión).
2. **D9, `docs.yml`:** `-d ./Backend/app` (con `--ignore` si hace falta), `actions/checkout` y el resto de acciones a la versión de `tests.yml`. Actualiza la trampa 12 de `arquitectura.md`. No se ejecuta en el CI hasta el *push* a `main`, así que se valida su sintaxis.
3. **D3:**
   - `docs/README.md` se reescribe como índice para personas: arquitectura, puesta en marcha, producción, integraciones y legal, con una sección "Proceso de desarrollo" que enlaza la guía de agentes y el roadmap;
   - `docs/roadmap/README.md` recibe un aviso de "plan cerrado: histórico de Kudos 2.0" (las fichas no se mueven, porque el código las cita unas 450 veces).
4. **READMEs:** el raíz, `Backend/` y `Frontend/` describen el código actual y enlazan a `docs/` en vez de duplicarla. Sin "en curso".
5. **Referencia técnica:** `testing.md` y `arquitectura.md` (backend y frontend) al día con lo hecho en F5-01…F5-12.
6. **Rama base:** `AGENTS.md` y `docs/guia-agentes.md` usan `main` como rama base **a partir de la fusión** (redacción condicional: "hasta F5-16, `rediseno-fase-0`; después, `main`"), para que el ciclo siga funcionando hasta F5-16.

**Riesgo:** bajo.

**Hecho cuando:**
- Un comprobador de enlaces relativos sobre todos los `.md` versionados (extraer `](ruta)` y hacer `test -e` relativo al archivo) no da enlaces rotos.
- `git grep -n "solo existe en ella\|en curso\|no tiene tests\|Esqueleto base\|app/Repositories" -- ':!docs/roadmap'` no da resultados (el roadmap histórico queda fuera).
- `api-contract.md` está versionado y coincide con F5-04 (`not_found`, `too_many_requests`, `method_not_allowed`, `server_error`).
- `docs.yml` es YAML válido.

---

## F5-15 · Formato con Pint y controles de CI (INFRA)

**Depende:** F5-14, F5-00 (D4) · **Cierra:** BE-R18, TR-H8 y el Pint pendiente del catálogo

**Hallazgos:** `sail pint --test` falla en 180 de 399 archivos PHP (65 con tabuladores; p. ej. `app/Http/Middleware/EnsureUserIsAdmin.php:21-39`, `DailyRewardController.php:40,56-58` y `bootstrap/app.php:37`), entre ellos `config/catalog.php` y los jobs heredados. El CI (`.github/workflows/tests.yml:63-64`) no comprueba formato ni que las migraciones sean reversibles.

**Pasos:**
1. Con todo lo demás de la fase ya fusionado, `sail test` en verde y anota el número de tests.
2. `sail pint` sobre todo `Backend/` (con la configuración por defecto: no hay `pint.json`), en un **único commit** `General: formato con Pint`, sin ningún cambio de lógica (excepción a R6, D4).
3. `sail test` en verde otra vez, con el mismo número de tests.
4. Segundo commit: `.git-blame-ignore-revs` en la raíz con el hash del commit de formato y un comentario.
5. `tests.yml`:
   - paso `vendor/bin/pint --test` en el job del backend;
   - paso `php artisan migrate:fresh && php artisan migrate:rollback && php artisan migrate` contra la base del job (la `testing` del servicio de PostgreSQL), antes de los tests.
6. Documenta en `docs/backend/testing.md` §7 los pasos nuevos del CI, y en la guía, que R6 deja de aplicar al formato tras F5-15 (Pint se ejecuta sobre los archivos tocados).

**Riesgo:** nulo en la lógica y alto en conflictos. Por eso va la última y no puede haber ninguna rama de tarea abierta.

**Hecho cuando:**
- `sail pint --test` termina con 0.
- `sail test` en verde antes y después, con el mismo número de tests.
- `git show --stat -w <hash>` del commit de formato apenas tiene cambios (solo espacios y orden de `use`), y no toca nada fuera de `Backend/`.
- `.git-blame-ignore-revs` contiene el hash.
- El *workflow* es YAML válido y sus pasos funcionan en local: `pint --test` y el ciclo de migraciones contra `testing`, con los comandos de la guía §3.

---

## F5-16 · Fusión en `main` y paso a producción (HUMANO)

**Depende:** F5-15

Sustituye a la F1-11 (D7). El humano:
1. Revisa la rama y fusiona `rediseno-fase-0` en `main` (ningún agente lo hace, R11).
2. Ejecuta el *runbook* único de [../infra/produccion.md](../infra/produccion.md) §5 (F5-13), pausando el despliegue automático de Vercel durante la ventana.
3. Anota aquí la fecha y el resultado: duración de la importación, ítems por categoría, tareas programadas, incidencias. Marca F1-11 como `hecho` con una remisión a esta ficha.

**Hecho cuando:** producción sirve Kudos 2.0 con las 4 categorías, la web de Vercel apunta a la API nueva y las comprobaciones del *runbook* se cumplen.

---

## Después de `main`

Fichas más breves. Se planifican cuando F5-16 esté hecha. Para entonces, la rama base es `main` (F5-14).

## F5-17 · Sesión y verificación de correo en el frontend (FE)

**Depende:** F5-16 · **Cierra:** FE-R6, FE-R7

**Hallazgos:**
- **FE-R6:** el `user` del login se persiste en `Frontend/src/store/useSessionStore.js:13-14,21-41` y se parchea a mano en seis sitios (`hooks/users/useUserMutations.js:20,34,58,64`, `hooks/votes/useVoteMutations.js:28` y `hooks/auth/useAuthMutations.js:27`). `role` y `email_verified_at` no se refrescan. `selectIsBanned` lee `is_banned`, que el login no envía (`Backend/app/Http/Controllers/Auth/AuthenticatedSessionController.php:59-72`).
- **FE-R7** (latente con `AUTH_EMAIL_VERIFICATION=false`, `Backend/config/auth.php:133`):
  - `/verify-email` está dentro de `RequireGuest` (`routes/routes.jsx:50`);
  - `useResendVerificationEmail` no se usa;
  - `routes/guards/RequireVerified.jsx:12` manda a `/forbidden`;
  - `hooks/users/useDailyRewardCheck.js:15-43` reclama también sin verificar;
  - `hooks/auth/useAuthQueries.js:7` hace un `GET` de `verify_url` sin validar el origen.

**Pasos:**
1. `patchUser(partial)` en el store, usado en los seis sitios.
2. Al arrancar con un token, se pide `/profile/minimal` y se fusiona en la sesión.
3. Se quita `selectIsBanned`, o se añade `is_banned` al contrato (solo se añade, R7).
4. `/verify-email` fuera de `RequireGuest`, validando que el origen de `verify_url` coincide con `VITE_API_URL`.
5. Pantalla "Verifica tu correo" con "Reenviar" en lugar de `/forbidden`.
6. `useDailyRewardCheck` se salta las cuentas no verificadas.

**Hecho cuando:**
- Con `AUTH_EMAIL_VERIFICATION=true` en local, el flujo funciona: registro → login sin verificar → reenviar → abrir el enlace con sesión → acceso. No sale el *toast* de la recompensa diaria.
- Un `verify_url` de otro origen se rechaza.
- `pnpm build` sin errores.

## F5-18 · Convención de página y estado en la URL (FE)

**Depende:** F5-16 · **Cierra:** FE-R4, FE-R5, FE-C4

**Pasos:**
1. **Hooks de página:** `useItemDetailPage`, `useRankingPage`, `useProfilePage`, `useNewProposalPage`, `useResetPasswordPage` (y el de `ForgotPasswordPage`) y `useCommentThread`. Sacan la lógica de `pages/ItemDetailPage.jsx`, `RankingPage.jsx`, `profile/ProfilePage.jsx`, `NewProposalPage.jsx`, `auth/{Forgot,Reset}PasswordPage.jsx` y `components/comments/{CommentBox,Comment}.jsx:18-74`.
2. **Store:** selectores en lugar de `const { user } = useSessionStore()` (`CommentBox.jsx:33` y `Comment.jsx:15`).
3. **Pestaña del perfil en la URL** (`/profile/:tab`), con `/my-proposals` como alias (`routes.jsx:74` y `ProfilePage.jsx:16`).
4. **Edición de propuestas** por id con `useProposal` (`hooks/proposals/useProposalQueries.js:17`), sin `location.state` (`NewProposalPage.jsx:17-20`).
5. **Ubicación (FE-C4):** las pestañas de `pages/profile/` pasan a `components/profile/tabs/`.

**Hecho cuando:** `pnpm build` sin errores; los flujos de comentar, responder, ocultar y borrar funcionan; y recargar en `/profile/votes` y en la edición de una propuesta conserva el estado.

## F5-19 · *Code splitting*, ESLint y tests de `lib/` (FE, QA)

**Depende:** F5-16 · **Cierra:** FE-R2, FE-R12, FE-C5

**Pasos:**
1. `React.lazy` y `Suspense` en `routes/routes.jsx:7-38` para admin, legal, auth, perfil, listas propias y onboarding. Medición con `vite build`: hoy `index` pesa 385 kB (108 kB gzip).
2. ESLint 9 (*flat config*) con `react-hooks` y `jsx-a11y`, versiones fijadas y **sin formateo masivo**. `useMyListEditPage.js:44` ya tiene un `eslint-disable`.
3. Tests `node:test` de `lib/` (`search.js`, `combobox.js`, `authRedirect.js`, `wishlist.js`, `attributes.js`, `places.js`, `onboarding.js`), sin dependencias nuevas.
4. `pnpm lint` y `pnpm test` en la definición de hecho y en el CI.

**Hecho cuando:** el chunk `index` baja de forma medible y el panel de admin sale aparte; `pnpm lint` termina sin errores (los *warnings* se documentan); y `pnpm test` y el CI están en verde.

## F5-20 · Capa de aplicación: Actions y servicios para el código heredado (BE)

**Depende:** F5-16 · **Cierra:** BE-R11, BE-R19, BE-R20, BE-C1, BE-C2, BE-C8, BE-C9, BE-C10

**Pasos:**
1. **Actions nuevas:**
   - `CreateProposalAction` (desde `ProposalController::store`);
   - `UpdateProfileAction` (`ProfileController.php:46-84`, en una transacción);
   - `BanUserAction` (`AdminUserController.php:60-79`);
   - `ModerateItemAction` (`AdminItemController`).
2. **Subida temporal en un solo sitio:** hoy está en `ProfileController.php:57` y `ProposalController.php:40`.
3. **Servicios de usuario:** `UserStatisticsService` (una consulta agregada con `FILTER`) y `UserRankingService`, extraídos de `Models/User.php:135-290`.
4. **Duplicación:**
   - `paginationMeta()` en el `Controller` base (BE-C1);
   - *trait* `HasUniqueSlug` (`Item.php:97` y `UserList.php:74`, BE-C2);
   - DTO de resultado del voto en lugar de atributos dinámicos (`EmitVoteAction.php:41-42,70-71`, BE-C9);
   - `MeResource`, compartido por el login y el perfil (`AuthenticatedSessionController.php:56-74`, BE-C10).
5. **Enlace de `storage`:** `storage:link` pasa al *entrypoint* y sale de `AppServiceProvider.php:45-46,116-152` (BE-R20).
6. **Cupo diario de kudos:** se gasta solo cuando la recompensa es nueva (`KudosService.php:85,203-211`, BE-C8).

**Hecho cuando:** `sail test` en verde sin cambios de contrato; hay un test que fallaba antes para BE-C8; y la ficha anota las consultas antes y después en `/profile/statistics`.

## F5-21 · Catálogo: helpers comunes y Wikidata sin peticiones duplicadas (INT)

**Depende:** F5-16 · **Cierra:** CAT-R-d, CAT-R-e, CAT-R-f, CAT-C-1, CAT-C-2, CAT-C-3, CAT-C-5…C-8, CAT-C-11

**Pasos:**
1. **`App\Catalog\Support\SoftFail`:** `rememberNullable` y `orNull`, que vuelven a lanzar `StrayRequestException` (R4). Sustituye las 7 copias:
   - `DescriptionResolver.php:108-127,134-147`;
   - `IgdbGameProvider.php:384-408,417-437`;
   - `WikidataPlaceProvider.php:221,243,535-543`;
   - `CallsProvidersOnDemand.php:51`.
2. **Utilidades nuevas:**
   - `Qid::isValid()` (la expresión regular aparece 7 veces);
   - `Sources` con las claves y las URL base (CAT-C-3);
   - *trait* `UsesWikimediaQuota` y `request()` común (`WikidataClient.php:203`, `WikipediaClient.php:116` y `CommonsClient.php:122`);
   - `NormalizesProviderPayload` (`text()`, `names()`, imagen, CAT-C-1);
   - `InterleavedPager` (CAT-C-2).
3. **`WikidataClient::fetchEntity()`:** común a `getEntity()` y `label()` (`:32-72` y `:80-118`).
4. **Caché de la entidad de Wikidata:** compartida por el proveedor y el resolver, para que haya una sola petición por destino (`WikidataPlaceProvider.php:109` + `DescriptionResolver.php:112`).
5. **Cosméticos:** CAT-C-5 (test de `withDescription`), CAT-C-6 (coincidencia de `CATEGORY_SLUG` con la config), CAT-C-7 (`SupportsSearch`, opcional), CAT-C-8 (`Retry-After` con tope) y CAT-C-11 (*throttle* en `RemoteImageDownloader` para Commons).

**Hecho cuando:**
- `sail test` en verde **sin tocar los tests existentes** (R8).
- Un test nuevo con `Http::assertSentCount` demuestra **un solo** `wbgetentities` por destino.
- Pint limpio en los archivos tocados.

## F5-22 · Catálogo: fuentes huérfanas, popularidad y `config/discovery.php` (INT, BE, HUMANO)

**Depende:** F5-16 · **Cierra:** CAT-R-k, CAT-R-j, CAT-R-i

**Pasos:**
1. **Fuentes huérfanas (CAT-R-k):** al sincronizar, se borran las fuentes secundarias declaradas por el proveedor (p. ej. `IgdbGameProvider::SECONDARY_PROVIDERS = ['steam']`) que ya no vienen (`CatalogImporter.php:231-254`).
2. **Popularidad (CAT-R-j):** en IGDB, `max(hypes, total_rating_count)` (con `hypes` en `FIELDS`), y en `HomeFeedService.php:111-113`, reparto por turnos o normalizado por categoría. **Lo decide el humano antes de planificar**, porque cambia el orden de "Novedades" y de la cola.
3. **Configuración (CAT-R-i):** `queue_*`, `onboarding_items`, `similar`, `recommendations` y `home` pasan de `config/catalog.php:125-197` a `config/discovery.php`, junto con sus 5 servicios.

**Hecho cuando:**
- Hay un test de un juego que pierde su *appid* (su fuente `steam` desaparece) y otro del mapeo de `hypes`.
- `grep -rn "catalog\.\(queue_\|similar\|recommendations\|home\|onboarding\)" Backend/app` no da resultados.
- `sail test` en verde.

## F5-23 · Limpieza de tests y del entorno de desarrollo (QA, INFRA, HUMANO)

**Depende:** F5-16 · **Cierra:** TR-H11, TR-H12, TR-H13

**Pasos:**
1. **TR-H11 (decide el humano, R8):** los tests que solo fijan valores de configuración se quedan (documentados como decisión explícita) o se quitan. Son `tests/Feature/Kudos/VoteKudosTest.php:36-51`, `Lists/UserListLimitsTest.php:28-29`, `tests/Unit/Services/NextCategoryItemServiceTest.php:24-30` y `Catalog/CatalogSearchApiTest.php:234-235`.
2. **TR-H12:** quita las 41 llamadas redundantes a `Http::preventStrayRequests()` en los `setUp()`, porque ya la hace `tests/TestCase.php:45`.
3. **TR-H13:**
   - `LABEL` de plantilla en `Backend/docker/8.5/Dockerfile:4`;
   - imagen de `docs/infra/entorno-local.md:24` (`php84-composer` con `--ignore-platform-reqs` en un proyecto con PHP ^8.5);
   - versiones fijas de `mailpit` y `redis` en `Backend/compose.yml:50,62`;
   - quitar `laravel/breeze` de `require-dev`;
   - borrar las ramas locales ya fusionadas (`git branch -d`, nunca `-D` sin permiso).

**Hecho cuando:** `sail test` en verde con el mismo número de tests (salvo la decisión del punto 1, anotada), y `composer.lock` regenerado dentro de Sail.
