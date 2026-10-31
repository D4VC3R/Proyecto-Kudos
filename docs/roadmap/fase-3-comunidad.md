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

---

## F3-06 · Aviso de estreno de la *wishlist*

**Depende:** F3-05, F2-05

**Pasos:** comando diario `wishlist:notify-releases`. Busca los ítems con `released_at` entre la última ejecución (marca en caché) y hoy, notifica a los usuarios que los tienen en `want` y es idempotente. Prográmalo en `routes/console.php`.

**Hecho cuando:** un test con `travelTo` comprueba que el aviso se envía una sola vez.

---

## F3-09 · OG de listas y perfiles

**Depende:** F2-10, F3-01

**Pasos:** amplía la función de la F2-10 a `/listas/:slug` y `/u/:username`. La imagen OG de una lista es el banner de `cover_item_id` o, si no hay, el del primer ítem.

**Hecho cuando:** pasan las mismas comprobaciones con `curl` que en la F2-10.
