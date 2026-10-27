# Fase 3: comunidad mínima

**Objetivo:** listas de usuarios que mezclen categorías, reseñas, perfil público y notificaciones, **cumpliendo antes** la DSA y el RGPD.

**Orden recomendado dentro de la fase:** F3-07 → F3-05 → F3-04 → F3-08 → F3-03 → F3-01 → F3-02 → F3-06 → F3-09. La parte legal y la moderación van antes que el contenido público.

**Lecturas comunes:**
- [../legal/cumplimiento.md](../legal/cumplimiento.md)
- [../backend/base-de-datos.md](../backend/base-de-datos.md) (Fase 3)

---

## F3-07 · Páginas legales y requisitos en el registro

- **Responsabilidad:** FE, BE, HUMANO
- **Depende de:** —

**Frontend:**
- rutas `/privacidad` y `/aviso-legal`, antes de `/:categorySlug`, con enlaces en el pie;
- el texto es un **borrador** generado a partir de la checklist de [../legal/cumplimiento.md](../legal/cumplimiento.md) §5 y debe quedar **marcado para revisión humana**.

**Backend:**
- `RegisterRequest` exige `accept_privacy` (*accepted*) y `age_confirmation` (*accepted*: "Tengo 14 años o más");
- se guarda `users.privacy_accepted_at` (migración).

**Criterios de aceptación:**
- Registrarse sin las casillas da 422.
- Las páginas legales son accesibles.
- **El humano aprueba el texto** antes de fusionar.

---

## F3-05 · Notificaciones dentro de la app

- **Responsabilidad:** BE, FE
- **Depende de:** —

**Backend:**
- `sail artisan make:notifications-table` y canal `database`;
- endpoints `GET /api/notifications` (paginado, con las no leídas primero), `POST /api/notifications/{id}/read` y `POST /api/notifications/read-all`;
- clases de notificación: `ContentRemovedNotification` (F3-04), `CommentRepliedNotification` (F3-02), `ListLikedNotification` (F3-01) y `WishlistReleasedNotification` (F3-06);
- **sin correo:** solo se usa `via(['database'])`.

**Frontend:** campana en la cabecera con contador; React Query con `refetchInterval` de 60 s solo con la pestaña visible; sin websockets.

**Criterios de aceptación:** tests de los endpoints; `pnpm build` termina sin errores.

---

## F3-04 · Reportes (DSA)

- **Responsabilidad:** BE, FE
- **Depende de:** F3-05

**Backend:**
1. **Tabla `reports`** y modelo `Report` con una relación polimórfica. Se pueden reportar `ItemComment`, `UserList` y `User` (perfil).
2. **`POST /api/reports`:**
   - autenticado, con un límite de 10 por hora;
   - un mismo usuario no puede reportar dos veces el mismo contenido mientras siga `pending`.
3. **Administración:** `GET /api/admin/reports?status=` y `POST /api/admin/reports/{id}/resolve` con `{action: hide|delete|dismiss, note}`.
4. **Al ocultar o borrar:**
   - se reutiliza la moderación existente (`hide` de comentarios, por ejemplo);
   - se envía `ContentRemovedNotification` al autor, con el motivo y la vía de reclamación (el correo de contacto del aviso legal);
   - se registra con `ModerationAuditLogger`.

**Frontend:** menú "⋯ → Reportar" en comentarios, listas y perfiles, con un modal de motivo; vista de reportes en `/admin`.

**Criterios de aceptación:** tests del flujo completo (reportar, resolver, notificar al autor); `pnpm build` termina sin errores.

---

## F3-08 · Borrado de cuenta y exportación de datos

- **Responsabilidad:** BE, FE
- **Depende de:** —

**Backend:**
1. **`DELETE /api/profile`** con `{password}`, dentro de una transacción:
   - revoca los tokens;
   - **anonimiza** los comentarios que tienen respuestas (`user_id = null`, contenido "[comentario eliminado]") y **borra** los demás;
   - borra los votos (y recalcula las estadísticas de los ítems afectados con `ItemStatsService`), las listas, los *likes*, las transacciones de kudos, el perfil y el avatar en disco;
   - por último, borra el usuario.

   **Revisa las FK actuales** (`cascadeOnDelete` / `nullOnDelete`) para no dejar huérfanos ni romper restricciones.
2. **`GET /api/profile/export`:** JSON con el perfil, los votos, los comentarios y las listas.

**Frontend:** sección "Privacidad" en el perfil, con la exportación y un borrado con doble confirmación.

**Criterios de aceptación:**
- Test: después del borrado no queda ninguna fila con el `user_id`, salvo los comentarios anonimizados con `user_id` nulo.
- Las medias de los ítems afectados son correctas.

---

## F3-03 · Perfil público

- **Responsabilidad:** BE, FE
- **Depende de:** F3-07

**Backend:**
- migraciones `users.username` (único y nullable; 3–30 caracteres `[a-z0-9_]`; lista de reservados `admin`, `api`, `buscar`…) y `profiles.is_public` (por defecto `false`);
- `GET /api/users/{username}`, que da 404 si el perfil no es público. Devuelve el avatar, la biografía, el top 5 por categoría (sus notas más altas), las listas públicas y las reseñas recientes.

**Frontend:**
- elegir `username` y visibilidad en el perfil, o en el onboarding como paso opcional;
- página `/u/:username`.

**Criterios de aceptación:** un perfil privado da 404 a terceros y es visible para su dueño; `pnpm build` termina sin errores.

---

## F3-01 · Listas de usuarios

- **Responsabilidad:** BE, FE
- **Depende de:** F2-09, F3-04, F3-07

**Backend:**
1. **CRUD** de las listas propias. Al crear una lista, la visibilidad se elige de forma explícita, `private` por defecto.
2. **Ítems:** añadir, quitar, reordenar y escribir una nota de hasta 280 caracteres. **Se pueden mezclar categorías.**
3. **Límites:** 50 listas por usuario y 100 ítems por lista.
4. **Policy:** solo el dueño edita; `private` da 404 a terceros; `unlisted` es accesible con el enlace pero no aparece en los listados.
5. **"Me gusta":** migración `list_likes`, `POST`/`DELETE /api/lists/{id}/like`, contador `likes_count` y `ListLikedNotification` al dueño (agrupando para no saturar: como mucho 1 al día por lista).

**Frontend:**
- botón "Añadir a lista" en la ficha y en la tarjeta;
- páginas "Mis listas" y de edición, con arrastrar y soltar para ordenar (framer-motion `Reorder`);
- vista pública `/listas/:slug`.

**Criterios de aceptación:** tests de la *policy* y los límites; `pnpm build` termina sin errores.

---

## F3-02 · Reseñas y respuestas

- **Responsabilidad:** BE, FE
- **Depende de:** F3-04

**Backend:**
1. **Migración:** `item_comments.parent_id` y `vote_id`.
2. **Respuestas:** `StoreItemCommentRequest` acepta `parent_id`. El padre debe ser del mismo ítem y **no** tener `parent_id` propio, porque solo hay un nivel. Se notifica al autor del padre.
3. **Reseñas:** si el usuario tiene un voto de tipo `vote` en el ítem, puede marcar el comentario como reseña (`is_review: true` → `vote_id`). Como máximo una reseña por usuario e ítem.
4. **Listado:** las reseñas salen primero, con su nota, y las respuestas anidadas.

**Frontend:** `CommentBox` muestra las reseñas destacadas con la nota y permite responder.

**Criterios de aceptación:** tests de anidación (el tercer nivel da 422) y de reseña única; `pnpm build` termina sin errores.

---

## F3-06 · Aviso de estreno de la *wishlist*

- **Responsabilidad:** BE
- **Depende de:** F3-05, F2-05

**Pasos:** comando diario `wishlist:notify-releases`:
- busca los ítems con `released_at = hoy` (o entre la última ejecución y hoy, guardando la marca en caché);
- notifica a los usuarios con un voto `want` sobre ellos;
- es idempotente: no envía dos veces el mismo aviso.

Se programa en `routes/console.php`.

**Criterios de aceptación:** test con `travelTo` que comprueba que se notifica una sola vez.

---

## F3-09 · OG de listas y perfiles

- **Responsabilidad:** FE
- **Depende de:** F2-10, F3-01

**Pasos:** amplía la función de meta tags de la F2-10 a `/listas/:slug` y `/u/:username`. La imagen OG de una lista es el banner de `cover_item_id` o del primer ítem.

**Criterios de aceptación:** las mismas comprobaciones con `curl` que en la F2-10.
