# Fase 3: comunidad mínima

**Objetivo:** listas de usuarios que mezclan categorías, reseñas, perfil público y notificaciones, cumpliendo **antes** la DSA y el RGPD.

**Orden:** F3-07 → F3-05 → F3-04 → F3-08 → F3-03 → F3-01 → F3-02 → F3-06 → F3-09. Lo legal y la moderación van antes que el contenido público.

**Lee para toda la fase:** [../legal/cumplimiento.md](../legal/cumplimiento.md) §3–4 y [../backend/base-de-datos.md](../backend/base-de-datos.md) §3 (Fase 3).

---

## F3-07 · Páginas legales y requisitos en el registro

**Depende:** — · **Archivos:** `Backend/app/Http/Requests/Auth/RegisterRequest.php`, `Frontend/src/routes/routes.jsx`, `Footer.jsx`

**Frontend:** rutas `/privacidad` y `/aviso-legal`, enlazadas desde el pie. El texto es un **borrador** basado en la tabla de cumplimiento §4, marcado de forma visible para que lo revise el humano.

**Backend:** `RegisterRequest` exige `accept_privacy` (*accepted*) y `age_confirmation` (*accepted*: "Tengo 14 años o más"). Migración `users.privacy_accepted_at`, que se rellena al registrarse.

**Hecho cuando:** registrarse sin las casillas da 422, las páginas son accesibles y **el humano ha aprobado el texto** antes de fusionar.

**Notas (F3-07):**
- **Estado: hecha. El humano aprobó el texto de `/privacidad` y `/aviso-legal` tal cual el 2026-11-01** (puerta de la ficha) y validó el endpoint público `GET /api/legal` y que las cuentas anteriores queden con `privacy_accepted_at = null` sin volver a pedirles la aceptación. Se retiró el aviso "Borrador pendiente de revisión": el componente `LegalDraftNotice` se borró. Los datos que completa el humano fuera del código siguen como `<LegalPending>` visibles (`grep -rn "<LegalPending" Frontend/src`): quedan 2.
- Migración `2026_11_01_100000_add_privacy_accepted_at_to_users_table` (`timestampTz` nullable), probada contra `testing` (`migrate:fresh`, `migrate:rollback --step=1` y `migrate`). `User::$casts` la trata como `datetime`; **no** es asignable en masa: solo la escribe `RegisteredUserController` (`new User([...])`, `privacy_accepted_at = now()` y `save()`). Las cuentas anteriores a la F3-07 quedan con `null` (no se les pide aceptar de nuevo).
- `RegisterRequest`: `accept_privacy` y `age_confirmation` con `['required', 'accepted']` y mensajes en español ("Debes aceptar la política de privacidad." / "Debes confirmar que tienes 14 años o más."). La respuesta del registro no cambia (R7).
- **Desviación (R1): endpoint nuevo `GET /api/legal`** (`Legal/LegalInfoController`, público, `{"data": {"contact_email": string|null}}`, fuente única `config('kudos.legal.contact_email')`). La ficha no preveía cómo llega al frontend el correo `LEGAL_CONTACT_EMAIL` que F3-04 y `docs/legal/cumplimiento.md` §3.4 piden mostrar en `/aviso-legal`; la alternativa era duplicarlo en una variable `VITE_*`. Sin variables de entorno nuevas (R9).
- Frontend: `/privacidad` y `/aviso-legal` fuera de las guardas, enlazadas desde el pie (`nav` "Información legal"; la atribución de TMDB no se tocó). Componentes `components/legal/` (`LegalPending`, `LegalContactEmail`, `LegalPageLayout`), hook `hooks/legal/useLegalQueries.js` y `hooks/pages/useLegalPage.js`. Sin correo configurado, las páginas muestran el marcador `LEGAL_CONTACT_EMAIL`.
- `RegisterForm`: dos casillas **sin marcar** (`defaultValues` a `false`), con el enlace a `/privacidad` en otra pestaña y validación en cliente (`z.literal(true)` en `registerSchema`).
- `config/catalog.php`: `privacidad` y `aviso-legal` pasan de "previstas" a "actuales" en `reserved_slugs` (el contenido del array no cambia).
- **Datos aportados por el humano (2026-11-01) y ya aplicados:** titular "David Cerdán Valero"; solo se publican nombre y correo (**sin NIF ni domicilio**, eliminados); base legal de los registros de seguridad: interés legítimo (art. 6.1.f RGPD); plazos tomados del repo (registros del servidor rotados por tamaño, Docker 10 MB × 3 según `Backend/.env.production.example`; registro de auditoría de moderación 30 días, `config/logging.php`; copias de seguridad 7 días, `RETENTION_DAYS` de `deploy/backup-db.sh` y `deploy/backup-storage.sh`); transferencias de Vercel (Marco de Privacidad de Datos UE-EE. UU. y/o cláusulas contractuales tipo); exención de responsabilidad y legislación española con juzgados del domicilio del consumidor; "Última actualización: 1 de noviembre de 2026".
- **Tareas del humano (2 `LegalPending` visibles):** (1) el correo de contacto legal: `LEGAL_CONTACT_EMAIL` queda vacía y el humano la pondrá en `Backend/.env.production` antes de desplegar (mientras tanto las páginas muestran el marcador; está en `LegalContactEmail`, así que sale en varias secciones); (2) confirmar en el VPS la rotación de los registros del servidor: el repo solo la documenta (`Backend/.env.production.example`: "Docker ya los rota (10 MB x 3)"), no la configura (`compose.prod.yml` no declara límites de registros) ni da un plazo en días. Ambos marcadores se quitan al completar el dato (poniendo la variable en el servidor y confirmando la rotación, o retirando esa frase).
- Prueba en el navegador (Chrome 124 `zenika/alpine-chrome` + `puppeteer-core`, Vite y API locales; script `.claude/ciclo/F3-07/e2e/f3-07.mjs`): escritorio 1440x900 y móvil 360x740 `Todo OK`. El pie enlaza a las dos páginas, ambas cargan sin sesión sin aviso de borrador, con el titular, la fecha y el marcador del correo (31 comprobaciones cada una), `/register` tiene las dos casillas sin marcar, sin ellas muestra los errores y no envía `POST /register`, y con ellas se crea el usuario (`privacy_accepted_at` guardado). Usuarios temporales borrados de la base de desarrollo.
- Verificación:
  - `sail test`: `Tests: 553 passed (2730 assertions)`.
  - `sail test --filter='RegisterTest|LegalInfoTest|ReservedCategorySlugTest'`: `Tests: 15 passed (87 assertions)`.
  - Migraciones contra `testing`: `migrate:fresh` termina con `2026_11_01_100000_add_privacy_accepted_at_to_users_table ....... 1.36ms DONE`; `migrate:rollback --step=1` quita solo esa (`... 8.06ms DONE`); `migrate` la vuelve a aplicar (`... 4.72ms DONE`).
  - `docker compose run --rm kudos-frontend pnpm build`: `✓ 2428 modules transformed.` y `✓ built in 3.14s`; tras quitar el aviso de borrador (ronda final): `✓ 2427 modules transformed.` y `✓ built in 3.22s`.
  - `docker compose run --rm kudos-frontend pnpm test:seo`: `# tests 25`, `# pass 25`, `# fail 0`.

---

## F3-05 · Notificaciones dentro de la app

**Depende:** —

**Backend:**
- `sail artisan make:notifications-table`; canal `database` únicamente, `via(['database'])` y sin correo.
- `GET /api/notifications` (paginado, no leídas primero), `POST /api/notifications/{id}/read` y `POST /api/notifications/read-all`.
- Clases: `ContentRemovedNotification` (F3-04), `CommentRepliedNotification` (F3-02), `ListLikedNotification` (F3-01) y `WishlistReleasedNotification` (F3-06).

**Frontend:** campana en la cabecera con contador; React Query con `refetchInterval` de 60 s solo con la pestaña visible; sin websockets.

**Hecho cuando:** hay tests de los endpoints.

**Notas (F3-05):**
- Migración nueva (R5) `2026_10_31_191137_create_notifications_table` con `down()`. **Discrepancia (R1):** la ficha y base-de-datos §3 hablaban de la tabla estándar de `make:notifications-table`, pero su stub usa `morphs('notifiable')` (`notifiable_id` bigint) y `users.id` es UUID, así que el INSERT habría fallado; se cambió a `uuidMorphs('notifiable')`. Probada contra `testing`: `migrate:fresh`, `migrate:rollback --step=1` (borra solo `notifications`) y `migrate`, sin errores.
- Contrato de `data` (lo escribe cada clase en `toArray()`; el frontend solo pinta `title` y `message` y abre `url`): siempre `kind`, `title`, `message` (español ya redactado) y `url` (ruta del frontend o `null`), más:
  - `content_removed`: `content_type`, `content_excerpt` (100 caracteres), `action` (`hidden`/`deleted`), `reason` y `appeal_contact`;
  - `comment_replied`: `comment_id`, `item_id`, `item_name`, `replier_name` y `excerpt`; `url` = `/:categorySlug/item/:itemId`;
  - `list_liked`: `list_id`, `list_slug`, `list_title`, `liker_id` y `liker_name`; `url` = `/listas/:slug`;
  - `wishlist_released`: `item_id`, `item_name` y `released_at` (Y-m-d); `url` = `/:categorySlug/item/:itemId`.
- Las cuatro clases están **creadas y probadas, pero sin disparar**: el disparador lo pone cada tarea (F3-04 `ContentRemovedNotification`, con la vía de reclamación como parámetro `appealContact`; F3-02 `CommentRepliedNotification`; F3-01 `ListLikedNotification`, que también se encarga del límite de 1 al día por lista; F3-06 `WishlistReleasedNotification`). Todas heredan de `InAppNotification` (`via()` → `['database']`, sin correo). Son síncronas (sin `ShouldQueue`): se insertan en la misma petición.
- Endpoints (`auth:sanctum`, `verified`, `not_banned`; sin sesión 401, sin verificar 403):
  - `GET /api/notifications` (`per_page` 1-50, por defecto 15): no leídas primero y después `created_at desc` (desempate por `id`); `meta` con `current_page`, `last_page`, `per_page`, `total` y `unread_count` (el contador de la campana, sin endpoint aparte);
  - `POST /api/notifications/{id}/read`: idempotente (no cambia `read_at` si ya estaba leída); la de otro usuario o inexistente da 404;
  - `POST /api/notifications/read-all`: `meta.updated` y `meta.unread_count = 0`.
- **Desviación R1 (código de error del 404):** el plan suponía `not_found` con el mensaje "La notificación no existe.", pero el `Handler` de Laravel convierte `ModelNotFoundException` en `NotFoundHttpException` antes de los `render()` de `bootstrap/app.php` (igual que en la F1-06), así que el 404 de una notificación ajena, inexistente o con id no UUID responde `route_not_found`. Los tests aseguran `route_not_found`. La entrada `'DatabaseNotification'` del mapa de mensajes de `bootstrap/app.php` se añadió como pedía el plan, pero es código que no llega a ejecutarse (igual que las demás).
- Campana (`NotificationBell` en `MainHeader`, antes de `UserInfo`, solo con sesión verificada): desplegable con las 10 últimas (no leídas primero), sondeo de 60 s con `refetchInterval` solo con la pestaña visible (`refetchIntervalInBackground: false`), sin página propia ni websockets. Clic en una: la marca como leída y abre su `url`; "Marcar todas como leídas". Escape y el clic fuera cierran; Escape devuelve el foco a la campana. En móvil el panel es `fixed` a lo ancho (`inset-x-4`) para no salirse a 360 px. Desviación menor del plan: la fecha de cada notificación usa `text-text-normal` y no `text-text-subtitle`, que en el tema claro es `slate-200` y no se leía sobre blanco ni sobre `bg-blue-50`.
- Verificación:
  - `sail test`: `Tests: 488 passed (2353 assertions)`; `NotificationApiTest` 12 tests y `InAppNotificationsTest` 6; `pnpm build` sin errores; `route:list --path=api/notifications` muestra las 3 rutas con `Authenticate:sanctum`, `EnsureEmailIsVerified` y `EnsureUserIsNotBanned`.
  - Prueba en navegador (Chrome 124 `zenika/alpine-chrome` + `puppeteer-core`, frontend Vite y API locales reales, usuario temporal verificado con 12 notificaciones y otro sin verificar; script `.claude/ciclo/F3-05/e2e/f3-05.mjs`, salidas en `e2e/salida-*.txt`, capturas en `e2e/capturas/`): móvil 360x740 `32/32 OK`; escritorio 1440x900 `34/35 OK` y el fallo restante (volver a visible) era un fallo del script (el evento `visibilitychange` no burbujeaba hasta `window`); repetido con `f3-05-reanudacion.mjs` `2/2 OK`. Comprobado: "9+" con 10 no leídas, 10 en el desplegable y las no leídas primero, pulsar una con `url` envía `POST …/read` y navega a la ficha, "Marcar todas como leídas" envía `POST read-all` y el contador desaparece, Escape y el clic fuera cierran, sin desbordamiento horizontal, panel dentro de la pantalla, sin campana ni petición a `/notifications` sin sesión o sin verificar, y sondeo periódico (1 → 2 peticiones en 65 s con la pestaña visible, 1 → 1 con la pestaña oculta). La pestaña oculta se **emula** redefiniendo `document.visibilityState`/`hidden` (no se pudo cambiar la visibilidad real en Chrome sin interfaz). Limpieza: usuarios temporales y sus notificaciones borrados de la base de desarrollo (`notifications` queda a 0); `migrate` aplicó en la base de desarrollo solo `create_notifications_table`.

---

## F3-04 · Reportes (DSA)

**Depende:** F3-05 · **Lee:** cumplimiento §3

**Backend:**
1. Tabla `reports` y modelo `Report` polimórfico. Se pueden reportar `ItemComment`, `UserList` y `User` (perfil).
2. `POST /api/reports`: con sesión, límite de 10 por hora; un usuario no puede reportar dos veces el mismo contenido mientras siga `pending`.
3. `GET /api/admin/reports?status=` y `POST /api/admin/reports/{id}/resolve` con `{action: hide|delete|dismiss, note}`.
4. Al ocultar o borrar: reutiliza la moderación existente (`hide` de comentarios), envía `ContentRemovedNotification` al autor con el motivo y la vía de reclamación (el correo del aviso legal) y registra la decisión con `ModerationAuditLogger`.

**Frontend:** "⋯ → Reportar" en comentarios, listas y perfiles, con un modal para el motivo; vista de reportes en `/admin`.

**Hecho cuando:** hay tests del flujo completo: reportar, resolver y notificar al autor.

**Notas (F3-04):**
- Migración nueva (R5) `2026_10_31_200000_create_reports_table` con `down()` (`dropIfExists`; el índice parcial se borra con la tabla). Probada contra `testing`: `migrate:fresh`, `migrate:rollback --step=1` (borra solo `reports`) y `migrate`. `reportable_type` guarda el **FQCN** (`App\Models\ItemComment`, `UserList` o `User`), sin *morph map*: Spatie (`model_has_roles.model_type`) y `notifications.notifiable_type` también guardan el FQCN y un mapa rompería los roles existentes. La API traduce los alias `comment`, `list` y `user`.
- Índice parcial único `reports_pending_unique` sobre `(reporter_id, reportable_type, reportable_id) WHERE status = 'pending'`: un usuario no puede tener dos reportes pendientes del mismo contenido, ni siquiera con dos peticiones simultáneas (`CreateReportAction` comprueba antes y captura `UniqueConstraintViolationException`).
- `POST /api/reports` (`auth:sanctum`, `verified`, `not_banned`, `throttle:reports` = `kudos.reports.per_hour`, 10 por usuario y hora): entrada `reportable_type` (`comment|list|user`), `reportable_id` (uuid), `reason` (`spam|odio|acoso|ilegal|spoiler|otro`) y `details` (opcional, máx. 1000; obligatorio con `otro`). **201** con `data` `{id, reportable_type, reportable_id, reason, reason_label, details, status, created_at}`; **401** sin sesión y **403** sin verificar; **409** `conflict` si ya hay un reporte pendiente del mismo usuario sobre ese contenido; **422** `validation_error` si el tipo, el motivo o los detalles no valen, si el contenido no existe (o está borrado, o es una lista `private`) o si es propio (comentario o lista propios; el propio perfil); **429** `too_many_requests` al pasar el límite. No se avisa al denunciante de la decisión (el art. 16.5 de la DSA lo pide; haría falta otra notificación con texto nuevo).
- `GET /api/admin/reports` (rol admin; 403 al resto): filtros `status` (`pending|resolved|dismissed`), `reportable_type` (`comment|list|user`), `per_page` (1-100, 20 por defecto) y `page`; orden `created_at desc`, `id desc`. Cada elemento añade al anterior `resolution_note`, `resolved_at`, `reporter` y `resolver` (`{id, name}` o `null`) y `content` (`null` si el contenido ya no existe): `{excerpt, author, is_hidden, is_deleted, url}`. `meta`: `current_page`, `last_page`, `per_page`, `total` y `pending_count` (siempre el total de pendientes, sin filtros).
- `POST /api/admin/reports/{id}/resolve` con `{action: hide|delete|dismiss, note}`: `note` obligatoria con `hide` y `delete` (se envía al autor) y opcional con `dismiss` (nota interna). Devuelve el reporte y `meta.content_action` (`hidden|deleted|none`) y `meta.closed_reports`. **409** si ya está resuelto; **422** si falta la nota o si se intenta `hide` sobre un perfil.
- Semántica por tipo (`ResolveReportAction`, dentro de una transacción con el reporte bloqueado):
  - comentario: `hide` = `ItemComment::hideByModerator()` (el mismo método que usa el ocultado manual del admin) y `delete` = borrado suave;
  - lista: `hide` = `visibility = private` y `delete` = borrado suave; ambos llaman a `HomeFeedService::forgetCache()`. **Riesgo:** con la F3-01 el dueño podría volver a publicar una lista ocultada; la F3-01 debería impedirlo; *(resuelto en la F3-01 con `lists.hidden_by_moderation_at`)*
  - perfil: solo `delete`, que **no borra la cuenta**, sino que vacía `biography`, `social_links`, `city` y `avatar` (y borra el archivo del avatar en el disco `public`); para bloquear a un usuario está el baneo. En la F3-03, donde habrá `profiles.is_public`, `hide` podrá poner `is_public = false`;
  - `dismiss` no toca el contenido ni notifica.
- Resolver con `hide` o `delete` **cierra también los demás reportes pendientes del mismo contenido** (mismo `reportable_type` y `reportable_id`) con la misma nota; `dismiss` solo cierra ese. Si el contenido ya no existe (borrado antes), el reporte se resuelve con `content_action = none` y sin notificación.
- Motivo que recibe el autor: `<etiqueta del motivo>. <nota>` (por ejemplo, "Spam o publicidad. Es publicidad"), o solo la nota si el motivo es `otro`. `ContentRemovedNotification` se dispara desde `ResolveReportAction`; para `profile` el mensaje empieza por "El contenido de tu perfil" (borrar el perfil no elimina la cuenta). Cada decisión se registra con `ModerationAuditLogger::logReportResolution()` (`admin.report.resolve` en el canal `moderation`).
- **Vía de reclamación (`appealContact`):** no existía ningún correo de contacto legal en el código (la F3-07 sigue pendiente). Sale de `config('kudos.legal.contact_email')`, que lee `LEGAL_CONTACT_EMAIL` (vacía en `.env.example` y `.env.production.example`, documentada en `docs/infra/produccion.md` §3). **Lo elige el humano** y debe ser un buzón que reciba correo; vacía, la notificación no incluye la frase de reclamación. La F3-07 debe mostrar el mismo correo en `/aviso-legal`.
- **Discrepancia (R1) de los perfiles:** la ficha pide "⋯ → Reportar" también en perfiles, pero en el frontend no hay ninguna página de perfil ajeno donde ponerlo (`/u/:username` es la F3-03). El backend ya acepta `user` y `ReportMenu` admite `type="user"`; el botón en perfiles se pone en la F3-03 (hecho: `/u/:username`).
- Frontend: `ReportMenu` ("⋯" con "Reportar") y `ReportModal` (motivos con *radios*, detalles, validación en el cliente) en `Comment` (solo con sesión verificada, sin ser el autor ni admin y si no está oculto) y en `ListPage` (verificado y no admin); `/admin/reports` (pestaña "Reportes", `AdminReports`, `AdminReportCard`, modal con la nota). El panel del "⋯" se alinea a la izquierda o se abre hacia arriba si no cabe en el contenedor que recorta (en móvil, con el botón a la izquierda del comentario, se salía por la izquierda).
- Verificación: `sail test`: `Tests: 516 passed (2556 assertions)` (`ReportApiTest` 11 tests, `AdminReportTest` 16 e `InAppNotificationsTest` 7); `pnpm build` sin errores; migración probada contra `testing` (`migrate:fresh`, `migrate:rollback --step=1`, que solo borra `reports`, y `migrate`); `route:list` muestra `POST api/reports` con `Authenticate:sanctum`, `EnsureEmailIsVerified`, `EnsureUserIsNotBanned` y `ThrottleRequests:reports`, y las dos rutas de `api/admin/reports` con `EnsureUserIsAdmin`. Prueba en navegador (Chrome 124 `zenika/alpine-chrome` + `puppeteer-core`, Vite y API locales reales, tres usuarios temporales; script `.claude/ciclo/F3-04/e2e/f3-04.mjs`): escritorio 1440x900 y móvil 360x740, `47/47 OK` cada uno. Limpieza hecha: usuarios temporales, su lista, comentarios, reportes, notificaciones y tokens borrados de la base de desarrollo; `migrate` aplicó solo `create_reports_table`. Observación fuera de la tarea: a 360 px con sesión verificada la página desborda unos 7 px en horizontal (la cabecera, ya sin el menú de reportes, y los *sliders* de la ficha); no es de la F3-04.

---

## F3-08 · Borrado de cuenta y exportación

**Depende:** — · **Lee:** cumplimiento §4

**Backend:**
1. `DELETE /api/profile` con `{password}`, en una transacción:
   - revoca los tokens;
   - **anonimiza** los comentarios con respuestas (`user_id = null`, "[comentario eliminado]") y borra los demás;
   - borra los votos (y recalcula los ítems afectados con `ItemStatsService`), las listas, los *likes*, las transacciones de kudos, el perfil y el avatar en disco;
   - por último, borra el usuario.

   Revisa antes las FK actuales (`cascadeOnDelete` / `nullOnDelete`).
2. `GET /api/profile/export`: JSON con el perfil, los votos, los comentarios y las listas.

**Frontend:** sección "Privacidad" en el perfil, con la exportación y un borrado con doble confirmación.

**Hecho cuando:** tras el borrado no queda ninguna fila con ese `user_id` (salvo los comentarios anonimizados, con `user_id` nulo) y las medias de los ítems afectados son correctas.

---

## F3-03 · Perfil público

**Depende:** F3-07

**Backend:**
- Migraciones `users.username` (único, nullable, 3–30 caracteres `[a-z0-9_]`, con una lista de reservados: `admin`, `api`, `buscar`…) y `profiles.is_public` (por defecto `false`).
- `GET /api/users/{username}`: 404 si el perfil no es público. Devuelve el avatar, la biografía, el top 5 por categoría, las listas públicas y las reseñas recientes.

**Frontend:** elegir `username` y visibilidad en el perfil (o en el onboarding, de forma opcional); página `/u/:username`.

**Hecho cuando:** un perfil privado da 404 a terceros y es visible para su dueño.

**Notas (F3-03):**
- Migraciones nuevas, con `down()`: `2026_11_02_000000_add_username_to_users_table` (`users.username`, string(30), nullable y único) y `2026_11_02_000100_add_is_public_to_profiles_table` (`profiles.is_public`, bool, default `false`). Probadas contra `testing`: `docker compose exec -T -e DB_DATABASE=testing kudos-app php artisan migrate:fresh` (`2026_11_02_000000_add_username_to_users_table .... 1.88ms DONE` y `2026_11_02_000100_add_is_public_to_profiles_table .... 1.62ms DONE`), `migrate:rollback --step=2` (`2026_11_02_000100_add_is_public_to_profiles_table .... 9.06ms DONE` y `2026_11_02_000000_add_username_to_users_table .... 2.74ms DONE`, solo las dos nuevas) y `migrate` (las dos `DONE`).
- `GET /api/users/{username}` (público, `PublicProfileController`; `{username}` admite `[A-Za-z0-9_]{1,30}` y se busca en minúsculas; la ruta va después de `/users/ranking`, que además es un slug reservado). `data`: `id` (para "Reportar"), `username`, `name`, `avatar`, `biography`, `is_public`, `is_owner`, `member_since`, `top_by_category` (`[{category: {id, name, slug, icon}, items: [{score, item: ItemListResource}]}]`, hasta `kudos.profiles.top_per_category` ítems por categoría, por nota y después por voto más reciente; solo ítems activos y sin borrar de categorías activas), `lists` (`UserListResource`: solo las `public` de usuario, sin editoriales ni borradas; las más recientes primero) y `reviews` (`{id, content, score, created_at, item}`: comentarios con `vote_id`, sin respuestas ni ocultos, de ítems visibles). **Nunca** el correo, la ciudad, la fecha de nacimiento, los enlaces sociales ni los intereses.
- Un perfil sin `is_public` (o con `is_public` pero sin `username`) y un nombre que no existe dan **el mismo 404** (el `Handler` de Laravel lo convierte en `route_not_found`, igual que las listas privadas y las categorías inactivas, y no el `not_found` que describe `bootstrap/app.php`), sin sesión, con otro usuario y **también con un admin**: para moderar ya existe `/admin/users/:id`. El dueño lo ve privado, con `is_owner = true`.
- `PUT|GET /api/profile` añaden `username` (se pasa a minúsculas y se recortan los espacios; 3–30 `[a-z0-9_]`; único; no puede estar en `kudos.profiles.reserved_usernames` ni en `catalog.reserved_slugs`, regla `NotReservedUsername`; `null` o vacío lo quita) e `is_public` (bool). Sin la clave no se tocan. Un perfil público sin nombre de usuario da 422 (en `is_public`, o en `username` si se intenta quitar el nombre de un perfil público). `username` se guarda en `users` con `forceFill` (no está en `$fillable`) y la carrera del índice único se traduce en el mismo 422. Solo se añaden campos (R7).
- Límites y listas en `config/kudos.php` → `profiles`: `reserved_usernames` (lista propia: `admin`, `api`, `kudos`, `root`, `soporte`, `moderador`, `staff`, `perfil`, `ajustes`, …), `top_per_category` (5), `lists_limit` (20) y `reviews_limit` (10). `u` pasa a "rutas actuales" de `catalog.reserved_slugs`.
- `UserListResource` añade `author` (`{username, name, avatar}` o `null`; solo si se ha cargado `user`, es decir, en `GET /api/lists/{slug}`; `null` si la lista es editorial o el autor no tiene perfil público). La cola de reportes devuelve `content.url = "/u/<username>"` para un perfil público (y `null` si es privado: daría 404 también al admin).
- Frontend: `/u/:username` (`PublicProfilePage` + `usePublicProfilePage`), con "⋯ → Reportar" (`ReportMenu type="user"`) para usuarios verificados que no sean admin ni el dueño, aviso al dueño ("Tu perfil es privado…" / "Así ven tu perfil los demás."), secciones "Sus favoritos", "Listas" y "Reseñas recientes" solo con datos y "No encontramos este perfil" si da 404. En `/profile`, sección "Perfil público" (nombre de usuario, casilla y enlace "Ver mi perfil público"); el esquema zod impone el nombre antes de enviar un perfil público. `/listas/:slug` muestra "Por <nombre>" enlazando al perfil.
- **Desviaciones (R1) y decisiones:** (1) la ficha no fijaba la forma de la respuesta ni los límites de listas y reseñas: se fijan en `config/kudos.php`. (2) El **onboarding no ofrece** elegir el nombre de usuario (la ficha lo deja como opcional). (3) **`hide` sobre un perfil sigue dando 422** (la F3-04 anticipaba que podría poner `is_public = false`; no se hace: cambiaría un contrato y un test que ya existen y, sin una marca de moderación, el dueño podría volver a publicarlo, como pasó con las listas); queda la acción `delete` (vaciar el perfil). (4) **`/privacidad` no se toca** (texto legal aprobado): no menciona que, si el usuario lo activa, su perfil y sus notas se ven en público; lo decide el humano. Mientras tanto la casilla del perfil explica qué se publica. (5) Un usuario baneado con perfil público sigue visible.
- Verificación: `sail test --filter='ProfileUsernameTest|PublicProfileTest|PublicListTest|AdminReportTest|ReservedCategorySlugTest|ProfileInterestsTest|RegisterTest'` → `Tests:    62 passed (409 assertions)`; `sail test` → `Tests:    611 passed (3130 assertions)`. `test_un_perfil_privado_da_404_a_terceros` falla si en el controlador se quita la condición `! $user->hasPublicProfile()` (comprobado, y repuesta). `route:list --path=api/users`: `GET|HEAD api/users/ranking` y `GET|HEAD api/users/{username}`, ambas solo con el middleware `api` (sin `auth:sanctum`). `pint --test` solo sobre los PHP nuevos: `{"tool":"pint","result":"passed"}`. `docker compose run --rm kudos-frontend pnpm build`: `✓ 2447 modules transformed.` y `✓ built in 3.17s`. Prueba en navegador (Chrome 124 `zenika/alpine-chrome` + `puppeteer-core`, Vite y API locales, dos usuarios temporales `f303-a`/`f303-b`; script `.claude/ciclo/F3-03/e2e/f3-03.mjs`): escritorio 1440x900 y móvil 360x740, `Todo OK` (32 comprobaciones cada uno). Cubre: A elige el nombre (`  Ana_F303 ` → `ana_f303`) con el perfil privado, "Ver mi perfil público" abre `/u/ana_f303` con "Tu perfil es privado" y sin "Reportar"; B y un visitante ven "No encontramos este perfil"; A lo hace público y B ve el nombre, la biografía, "Sus favoritos" (con "Su nota: 9"), "Listas" (solo la pública, no la privada ni la no listada) y "Reseñas recientes", sin correo ni ciudad, y "⋯ → Reportar" abre el modal; un perfil público sin nombre muestra el error en el cliente y no envía ningún `PUT`; `/listas/publica-f303` muestra "Por Ana F303" y enlaza al perfil. Sin peticiones externas, sin errores de página y sin desbordamiento horizontal. No se ha tocado la cabecera. Limpieza: usuarios `f303-*`, votos, reseñas, listas, notificaciones y tokens borrados de la base de desarrollo (51 usuarios, 0 `username`, 0 perfiles públicos, 218 votos en total antes y después); `migrate` aplicó en desarrollo solo las dos migraciones nuevas; contenedores `kudos-f303-chrome` y `kudos-f303-vite` borrados.

---

## F3-01 · Listas de usuarios

**Depende:** F2-09, F3-04, F3-07

**Backend:**
1. CRUD de listas propias. La visibilidad se elige de forma explícita (`private` por defecto).
2. Ítems: añadir, quitar, reordenar y escribir una nota de hasta 280 caracteres. Se pueden mezclar categorías.
3. Límites: 50 listas por usuario y 100 ítems por lista.
4. *Policy*: solo el dueño edita; `private` da 404 a terceros; `unlisted` se abre con el enlace pero no aparece en los listados.
5. "Me gusta": migración `list_likes`, `POST`/`DELETE /api/lists/{id}/like`, contador `likes_count` y `ListLikedNotification` al dueño (como mucho 1 al día por lista).

**Frontend:** "Añadir a lista" en la ficha y la tarjeta; "Mis listas" y edición con `framer-motion` `Reorder`; vista pública `/listas/:slug`.

**Hecho cuando:** hay tests de la *policy* y de los límites.

**Notas (F3-01):**
- Migraciones nuevas, con `down()`: `2026_11_01_200000_add_hidden_by_moderation_at_to_lists_table` (`lists.hidden_by_moderation_at`, timestamp nullable) y `2026_11_01_200100_create_list_likes_table` (`user_id` y `list_id` con FK en cascada, `created_at`, PK `(user_id, list_id)` e índice `(list_id)`; sin modelo propio, se escribe con `DB::table`). Probadas contra `testing`: `migrate:fresh`, `migrate:rollback --step=2` (deshace solo las dos nuevas) y `migrate`.
- **Desviación (R1): rutas del CRUD.** La ficha no las fijaba y `GET /api/lists/{list:slug}` es público y por slug, así que cualquier `GET /api/lists/<palabra>` chocaría con un slug. El CRUD del dueño va bajo `/api/profile/lists` (como `/api/profile/statistics`); el "me gusta" va donde decía la ficha. Todas con `auth:sanctum`, `verified` y `not_banned`; `GET /api/lists/{slug}` sigue público.
- Contrato nuevo (solo se añaden campos, R7):
  - `GET /api/profile/lists` (lista propia no editorial, la editada más reciente primero; `?item_id=` opcional añade `contains_item` a cada lista) con `meta` `{total, max_lists, max_items}`; `POST` (201, privada por defecto; acepta `title`, `description`, `visibility`, `is_ranked`, `cover_item_id` e ignora `is_editorial`, `user_id`, `slug` y `likes_count`); `GET|PUT|DELETE /api/profile/lists/{id}` (el detalle incluye las entradas de ítems inactivos o borrados para que el reordenado cuadre; `DELETE` es borrado suave); `POST /{id}/items` (solo ítems activos y sin repetir), `PATCH /{id}/items/{itemId}` (`{note}`, `null` la borra), `DELETE /{id}/items/{itemId}` y `PUT /{id}/items/order` (`{item_ids}`, todos).
  - `POST|DELETE /api/lists/{id}/like`: idempotentes, 200 con `{"message", "data": {"liked", "likes_count"}}`. Privada ajena: 404 (no se delata); lista propia: 403 (no se infla la métrica).
  - `UserListResource` añade `is_hidden_by_moderation`, `can_edit` (solo con sesión: dueño y no editorial), `liked_by_me` (solo en `GET /api/lists/{slug}` con sesión) y `contains_item` (solo en el índice con `?item_id=`).
- *Policy* (`UserListPolicy`): `viewAny`, `create`, `update` y `delete` siguen siendo del admin y solo editoriales (los usan los Form Requests de `/api/admin/lists`); se añaden `view(?User)`, `createOwn`, `manage` (dueño y no editorial) y `like`. Admin ajeno: 403 en las rutas de usuario. `GET /api/lists/{slug}` usa `view`: una `private` da 404 a terceros y sin sesión y la ve su dueño; una `unlisted` se abre con el enlace y no está en `publiclyVisible()` ni en la portada.
- Límites en `config/kudos.php` → `lists.max_per_user` (50, sin contar editoriales ni borradas; `CreateUserListAction` bloquea al usuario con `lockForUpdate`) y `lists.max_items` (100; `AddItemToListAction`, por lo que **también vale para las editoriales**). Superarlos da 422.
- "Me gusta": `LikeListAction`/`UnlikeListAction` bloquean la lista, usan `insertOrIgnore`/`delete` y recalculan `likes_count` con `DB::table` (no se toca `updated_at`: la portada ordena por él). `ListLikedNotification` se dispara al dueño como mucho **1 al día por lista**: día natural UTC, comprobado en `notifications.data::jsonb ->> 'list_id'` (`data` es `text`); quitar y volver a dar me gusta el mismo día no repite el aviso. Las listas editoriales no avisan. `kudos:metrics` cuenta ya las listas con me gusta (`likes_count > 0`).
- **Cierre del riesgo de F3-04:** `ResolveReportAction` (`hide` sobre una lista) escribe `lists.hidden_by_moderation_at`; `UpdateOwnListRequest` rechaza (422 en `visibility`) volver a `public` o `unlisted` una lista con esa marca. Limitación conocida: el admin no tiene botón para deshacerlo (una reclamación aceptada exigiría tocar la base de datos o una tarea futura).
- Frontend: modal global "Añadir a una lista" (`components/lists/AddToListModal`, montado una vez en `App.jsx` y abierto con `store/useAddToListStore`, porque `Modal` no usa portal y dentro de `ItemCard` —un `motion.article` con *transform*— quedaría mal posicionado); `AddToListButton` en la tarjeta (esquina superior izquierda) y en la ficha; `/mis-listas` (`MyListsPage`) y `/mis-listas/:listId` (`MyListEditPage`) con `framer-motion` `Reorder` (asa `GripVertical` con `useDragControls` y `touch-none`, y botones ↑/↓ para teclado y móvil; el orden solo se envía al soltar); en `/listas/:slug`, botón "Me gusta" (`Heart`, `aria-pressed`), etiqueta de visibilidad y enlace "Editar" para el dueño; `canReport` excluye al dueño. Claves de React Query `['my-lists', …]` (`MY_LIST_KEYS`), distintas de `['lists', …]`. Enlace "Mis listas" en la barra de navegación (con sesión verificada).
- **Cabecera (R1):** con el enlace nuevo la cabecera de escritorio desbordaba a 1024 px (a 768 px ya lo hacía antes). `NavBar` muestra ahora solo el icono por debajo de `xl` (el nombre sigue en `aria-label` y `title`), `whitespace-nowrap` y separación `gap-2` hasta `2xl`. Con cinco enlaces (admin) solo se ve el icono a cualquier ancho, para que el buscador conserve su sitio. Comprobado sin desbordamiento a 768, 1024, 1280 y 1440 px con un usuario sin rol y con un admin; ancho del buscador (sin rol / admin): 1024 px → 193 / 135 px, 1280 px → 163 / 320 px, 1440 px → 178 / 320 px (a 768 px, 115 / 57 px: ya era estrecho antes de la tarea).
- Extra: `mis-listas` añadido a `reserved_slugs` de `config/catalog.php` (ruta de primer nivel nueva; sin ello, una categoría con ese slug quedaría inaccesible).
- Verificación: `sail test --filter='OwnListCrudTest|UserListPolicyTest|UserListLimitsTest|ListLikeTest|UserListModerationTest'` → `Tests:    37 passed (254 assertions)`; `sail test` → `Tests:    590 passed (2985 assertions)`. `UserListModerationTest` falla sin `hidden_by_moderation_at` (comprobado revirtiendo `ResolveReportAction`: `Failed asserting that null is not null`). `route:list` muestra las 9 rutas de `api/profile/lists` y las dos de `like` con `Authenticate:sanctum`, `EnsureEmailIsVerified` y `EnsureUserIsNotBanned`, y `GET api/lists/{list}` sin ellos. `pint --test` sobre los PHP nuevos: `{"tool":"pint","result":"passed"}`. `pnpm build`: `✓ 2441 modules transformed.` y `✓ built in 3.23s`. Prueba en navegador (Chrome 124 `zenika/alpine-chrome` + `puppeteer-core`, Vite y API locales, dos usuarios temporales; script `.claude/ciclo/F3-01/e2e/f3-01.mjs`): escritorio 1440x900 y móvil 360x740, `Todo OK` (42 y 40 comprobaciones). Cubre añadir desde la ficha y desde una tarjeta de otra categoría (sin abrir la ficha), crear y borrar desde "Mis listas", reordenar con ↓ y arrastrando el asa (persiste al recargar), nota guardada, privada → 404 para el otro usuario, "No listada" visible con el enlace, me gusta/quitar/volver a dar (contador y `aria-pressed`), una sola notificación en la campana, y en su propia lista "Me gusta" deshabilitado, sin "Reportar" y con "Editar". Sin peticiones externas ni errores de página. Limpieza: usuarios `f301-*`, sus listas, notificaciones y tokens borrados de la base de desarrollo (51 usuarios, 0 listas, 0 `list_likes`); `migrate` aplicó solo las dos migraciones nuevas; contenedores `kudos-f301-chrome` y `kudos-f301-vite` borrados.

---

## F3-02 · Reseñas y respuestas

**Depende:** F3-04

**Backend:**
1. Migración `item_comments.parent_id` y `vote_id`.
2. `StoreItemCommentRequest` acepta `parent_id`: el padre debe ser del mismo ítem y no tener `parent_id` (un solo nivel). Notifica al autor del padre.
3. Reseña: si el usuario tiene un voto `vote` en el ítem, puede marcar `is_review: true` → `vote_id`. Como mucho, una por usuario e ítem.
4. Listado: primero las reseñas, con su nota, y las respuestas anidadas.

**Frontend:** `CommentBox` destaca las reseñas con la nota y permite responder.

**Hecho cuando:** un tercer nivel da 422 y hay tests de reseña única.

**Notas (F3-02):**
- Migración `2026_11_01_000000_add_parent_and_vote_to_item_comments_table`: `parent_id` (FK a `item_comments`, `cascadeOnDelete`) y `vote_id` (FK a `votes`, `nullOnDelete`), con índices `(parent_id, created_at)` y `(vote_id)`. Índice parcial único `item_comments_review_unique` sobre `(item_id, user_id) WHERE vote_id IS NOT NULL AND deleted_at IS NULL`: como mucho una reseña viva por usuario e ítem, también ante dos peticiones simultáneas (las reseñas con borrado suave no cuentan; las ocultas sí). `down()` quita el índice, las dos FK y las dos columnas; probado contra `testing`.
- Contrato (R7, solo se amplía): `POST /api/items/{item}/comments` acepta `parent_id` (uuid) e `is_review` (bool); la respuesta añade `parent_id`, `is_review` y `score` (la nota del voto `vote`, o `null`), y `replies` solo en el listado. El mensaje de `store` no cambia.
- `GET /api/items/{item}/comments` devuelve **solo comentarios principales** (`parent_id` nulo): primero las reseñas y después los más recientes (`created_at desc`); cada uno lleva sus respuestas anidadas en `replies`, de la más antigua a la más reciente, con el mismo filtro de ocultos (los ve el admin y su autor). `meta.total` cuenta solo los principales.
- 422 `validation_error` (`error.details` por campo): responder a una respuesta (tercer nivel), padre de otro ítem, inexistente, borrado u oculto, `parent_id` con `is_review` (una respuesta no puede ser reseña), `is_review` sin voto `vote` (un `want` no vale) y reseña duplicada ("Ya has escrito una reseña de este ítem."). Las comprobaciones con base de datos están en `StoreItemCommentRequest::after()`; la creación y el aviso, en `CreateItemCommentAction`, que traduce la violación del índice único en el mismo 422.
- `CommentRepliedNotification` (F3-05) ya se dispara desde `CreateItemCommentAction` al autor del comentario respondido, y no cuando alguien se responde a sí mismo; se guarda en `notifications`.
- Frontend: `Comment` pinta la insignia "Reseña · nota" (estilo amarillo de la ficha) y el botón "Responder" (solo en comentarios principales); `CommentBox` anida las respuestas y ofrece la casilla "Publicar como reseña (tu nota: X)" solo en la ficha del ítem (`ItemDetailPage`, con `user_vote`) y mientras el usuario no tenga reseña; en `/votar` no se ofrece reseña.
- Decisiones: una reseña borrada deja de contar para el límite y una oculta no; no se puede responder a un comentario oculto; la reseña duplicada da 422 (no 409 como los reportes); si el usuario borra su voto, su reseña pasa a comentario normal (`nullOnDelete`).
- Verificación: `./vendor/bin/sail test --filter=ItemComment`: `Tests: 21 passed (95 assertions)` (`ItemCommentReplyTest` 11 y `ItemCommentReviewTest` 10); `./vendor/bin/sail test`: `Tests: 537 passed (2651 assertions)`; `pint --test` solo sobre los cuatro PHP nuevos: `{"tool":"pint","result":"passed"}`; `docker compose run --rm kudos-frontend pnpm build`: `✓ built in 3.14s`, sin errores. Migración contra `testing`: `migrate:fresh` (aplica `2026_11_01_000000_add_parent_and_vote_to_item_comments_table ... DONE`), `migrate:rollback --step=1` (`... 18.38ms DONE`; `db:table item_comments` vuelve a 10 columnas, sin `parent_id`, `vote_id` ni sus índices) y `migrate` (`... 13.24ms DONE`). Prueba en navegador (Chrome 124 `zenika/alpine-chrome` + `puppeteer-core`, Vite y API locales reales, dos usuarios temporales; script `.claude/ciclo/F3-02/e2e/f3-02.mjs`): escritorio 1440x900 y móvil 360x740, `Todo OK` en ambos (A vota con 8.5 y ve la casilla, publica la reseña con la insignia "Reseña · 8.5" y la casilla desaparece; B no ve la casilla, responde con "Responder", la respuesta sale anidada y sin botón "Responder", y un tercer nivel por API da 422; A ve "Han respondido a tu comentario" en la campana). Sin peticiones externas ni errores de página y sin desbordamiento horizontal nuevo (347/360 y 1425/1440, el de siempre por la barra de desplazamiento y la cabecera). Limpieza: usuarios temporales, votos, comentarios, notificaciones y tokens borrados de la base de desarrollo (51 usuarios, 218 votos, 0 comentarios, 0 notificaciones) y estadísticas del ítem recalculadas; `migrate` aplicó en desarrollo solo la migración nueva.

---

## F3-06 · Aviso de estreno de la *wishlist*

**Depende:** F3-05, F2-05

**Pasos:** comando diario `wishlist:notify-releases`. Busca los ítems con `released_at` entre la última ejecución (marca en caché) y hoy, notifica a los usuarios que los tienen en `want` y es idempotente. Prográmalo en `routes/console.php`.

**Hecho cuando:** un test con `travelTo` comprueba que el aviso se envía una sola vez.

**Notas (F3-06):**
- Comando `wishlist:notify-releases {--chunk=200}` (`NotifyWishlistReleasesCommand`) + job `NotifyWishlistReleasesJob` en la cola `default`. El comando solo encola (el scheduler tiene 128M) y el job envía los avisos.
- Ventana: `released_at` en `(marca, hoy]`. La marca `wishlist:notify-releases:last-run` (Y-m-d) se guarda en la caché por defecto; sin marca se revisan los últimos `kudos.wishlist.release_lookback_days` (7) días. Una segunda ejecución el mismo día no hace nada.
- **Desviación (R1):** la ficha solo hablaba de una marca en caché, pero en producción Redis tiene `allkeys-lru` y 64 MB, así que la marca puede desaparecer. La idempotencia la garantiza el job: no envía a quien ya tiene la `WishlistReleasedNotification` de ese ítem, filtrando `data::jsonb ->> 'item_id'` porque `data` es `text` (no jsonb).
- Visible = ítem activo, sin borrar, con `released_at` y de categoría activa. Reciben el aviso solo los usuarios con voto `want` en ese momento (también los baneados).
- Programado a diario a las 06:00 UTC en `routes/console.php`, después de `catalog:refresh` (05:00), con `withoutOverlapping()`.
- Limitaciones conocidas: si un refresco del catálogo adelanta `released_at` a un día anterior a la marca, ese ítem no se avisa; quien añade a la wishlist un ítem ya estrenado no recibe aviso; dos ejecuciones manuales simultáneas podrían duplicar (el scheduler usa `withoutOverlapping()`).
- Verificación:
  - `sail test --filter=WishlistReleaseNotificationsTest`: `Tests: 10 passed (43 assertions)`.
  - `sail test`: `Tests: 547 passed (2694 assertions)`.
  - `sail artisan schedule:list`: `0  6 * * *  php artisan wishlist:notify-releases ..... Next Due: en 16 horas`.
  - `sail artisan list wishlist`: `wishlist:notify-releases  Avisa a los usuarios de los estrenos de los ítems de su wishlist`.

---

## F3-09 · OG de listas y perfiles

**Depende:** F2-10, F3-01

**Pasos:** amplía la función de la F2-10 a `/listas/:slug` y `/u/:username`. La imagen OG de una lista es el banner de `cover_item_id` o, si no hay, el del primer ítem.

**Hecho cuando:** pasan las mismas comprobaciones con `curl` que en la F2-10.
