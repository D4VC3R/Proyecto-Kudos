# Cumplimiento

Es una guía técnica para no incumplir lo evidente, **no asesoramiento jurídico**. Los textos legales definitivos los aprueba el humano.

**Sin ánimo de lucro:** TMDB e IGDB solo son gratuitas para uso no comercial. Por eso no hay publicidad, afiliados, funciones de pago ni muros de donación que bloqueen funciones. Si eso cambiara, habría que renegociar las licencias o retirar los datos.

Las reglas técnicas de uso de datos de terceros (TMDB 6 meses, sin payload en bruto, sin portadas de iTunes) están en [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md) §1.

## 1. Atribuciones obligatorias

| Fuente | Qué mostrar | Dónde | Tarea |
|---|---|---|---|
| TMDB | Logo de TMDB, menos prominente que el de Kudos (`Frontend/public/tmdb-logo.svg`, descargado sin modificar de https://www.themoviedb.org/about/logos-attribution) + "This website uses TMDB and the TMDB APIs but is not endorsed, certified, or otherwise approved by TMDB." (literal de los [términos](https://www.themoviedb.org/api-terms-of-use) §3; traducción: "Esta web usa TMDB y sus API, pero TMDB no la respalda, certifica ni aprueba.") | Pie o "Acerca de", y enlace a TMDB en cada ficha con sus datos | F1-08 |
| Wikipedia ES | "Descripción: Wikipedia, licencia CC BY-SA 4.0", con enlace a `description_url` y a la licencia | Cada ficha con `description_source = wikipedia_es` | F1-08 |
| Wikimedia Commons | Autor, licencia y enlace a la página del archivo | Pie de la imagen | F4-04 |
| OpenStreetMap | "© OpenStreetMap contributors" con enlace | En el mapa (Leaflet lo hace por defecto) | F4-03 |
| IGDB | Atribución visible y en un lugar fijo, no en un registro de cambios (FAQ de [api-docs.igdb.com](https://api-docs.igdb.com); solo es obligatoria en la asociación comercial, pero se pone siempre) | `data_source` en cada ficha con sus datos, y pie o "Acerca de" | F4-04 |
| Steam | "Descripción: Steam", con enlace sin `nofollow` a la página de la tienda; sin logo de Steam ni nada que sugiera respaldo de Valve | Cada ficha con `description_source = steam` | F4-04 |

## 2. Contenido de terceros

- **Wikipedia (CC BY-SA):** un texto modificado sigue bajo CC BY-SA y se atribuye igual. Nunca se presenta como contenido propio.
- **Descripciones de Steam:** son texto promocional del editor sin licencia explícita (riesgo aceptado en la F4-00). Solo se guarda la `short_description` en español, atribuida y enlazada, y se deja de usar si Valve o el editor lo piden.
- **Imágenes:** se guardan reducidas (`thumb`/`banner`), con `license`, `author` y `source_url`. Las portadas comerciales (TMDB, IGDB) llevan `license: "© titular de derechos"` y solo ilustran la ficha.

## 3. DSA (requisito de la Fase 3, antes de abrir listas y reseñas)

1. **Notificación:** botón "Reportar" en comentarios, reseñas, listas y perfiles (`reports`, F3-04), al menos para usuarios registrados, más un correo de contacto público. *Implementado en la F3-04 para comentarios y listas; el botón en perfiles, implementado en la F3-03 (`/u/:username`). Las reseñas y las respuestas (F3-02) usan el mismo componente `Comment` que los comentarios, así que también tienen "Reportar".*
2. **Cola de revisión** en el panel de admin: `pending`, `resolved` y `dismissed`. *Implementada en la F3-04 (`/admin/reports`).*
3. **Exposición de motivos:** al retirar u ocultar contenido, se avisa al autor de qué contenido, por qué y cómo reclamar, con una notificación dentro de la app (F3-05). *Implementado en la F3-04 con `ContentRemovedNotification`; no se avisa al denunciante de la decisión.*
4. **Punto de contacto:** correo visible en el aviso legal. *El correo sale de `LEGAL_CONTACT_EMAIL` (`config('kudos.legal.contact_email')`, F3-04), que elige el humano. Implementado en la F3-07: `/aviso-legal` (y `/privacidad`) lo muestran leyéndolo de `GET /api/legal`.*
5. **Registro:** las decisiones se registran con `ModerationAuditLogger` (`logReportResolution`, F3-04).

## 4. RGPD (requisito de la Fase 3, antes de perfiles públicos y listas)

| Requisito | Implementación | Tarea |
|---|---|---|
| Política de privacidad | `/privacidad`: responsable, datos tratados (cuenta, votos, comentarios, listas, IP en los logs), finalidad, base legal (ejecución del servicio), conservación, derechos y contacto. **La aprueba el humano**. *Implementado en la F3-07; texto aprobado por el humano el 2026-11-01. Quedan dos datos del humano marcados como pendientes en la página (correo de contacto y confirmar la rotación de los logs en el VPS)* | F3-07 |
| Aceptación en el registro | Casilla **sin marcar** + declaración de tener **14 años o más** (edad de consentimiento digital en España). *Implementado en la F3-07 (texto aprobado el 2026-11-01): se guarda `users.privacy_accepted_at`; las cuentas anteriores quedan con `null` sin volver a pedirles la aceptación* | F3-07 |
| Privacidad por defecto | `profiles.is_public` y `lists.visibility` privados por defecto. *`lists.visibility` privada por defecto implementado en la F3-01; `profiles.is_public` privado por defecto implementado en la F3-03* | F3-01, F3-03 |
| Supresión | `DELETE /api/profile` con contraseña: borra la cuenta, los votos, las listas, los tokens, los *likes* y las transacciones de kudos; los comentarios con respuestas se **anonimizan**. *Implementado en la F3-08 (perfil → pestaña "Privacidad"): también se borran el perfil y el avatar, las notificaciones que nombran al usuario, y los reportes que envió se conservan sin denunciante; los administradores no pueden borrarse desde el perfil* | F3-08 |
| Acceso y portabilidad | `GET /api/profile/export` (JSON). Deseable, no bloqueante. *Implementado en la F3-08 (perfil → pestaña "Privacidad" → "Descargar mis datos"): cuenta, perfil, votos, comentarios, listas, "me gusta", propuestas y kudos, sin contraseña ni datos de otros usuarios* | F3-08 |
| Correos | Solo transaccionales sin consentimiento expreso; las notificaciones van solo dentro de la app | F3-05 |
| Cookies | El token está en `localStorage` y no hay analítica de terceros, así que no hace falta banner. **Si se añade analítica, se vuelve a evaluar** | — |
| Encargados | Vercel (frontend) y OVH (VPS), citados en la política. *Implementado en la F3-07 (texto aprobado el 2026-11-01)* | F3-07 |
