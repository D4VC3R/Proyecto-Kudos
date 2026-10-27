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
