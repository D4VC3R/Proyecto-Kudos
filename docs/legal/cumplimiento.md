# Cumplimiento: términos de las APIs, licencias, DSA y RGPD

**Responsable:** legal (la decisión final es del humano). Esto es una guía técnica para no incumplir lo evidente, **no asesoramiento jurídico**. Los textos legales definitivos (política de privacidad, aviso legal) los revisa y aprueba el humano.

## 1. Proyecto sin ánimo de lucro

Varias APIs (TMDB, RAWG, IGDB) son gratuitas **solo para uso no comercial**. Por tanto:
- nada de publicidad, afiliados ni funciones de pago;
- nada de muros de donación que bloqueen funciones;
- si eso cambiara algún día, habría que renegociar las licencias o retirar los datos.

## 2. Atribuciones obligatorias

| Fuente | Qué mostrar | Dónde | Tarea |
|---|---|---|---|
| TMDB | Logo de TMDB y el texto "This product uses the TMDB API but is not endorsed or certified by TMDB." (se puede añadir la traducción al lado) | Pie de página o página "Acerca de", y enlace a TMDB en las fichas con datos de TMDB | F1-08 |
| Wikipedia ES (texto) | "Descripción: Wikipedia, licencia CC BY-SA 4.0", con un enlace a `description_url` y a la licencia | En cada ficha cuyo `description_source = wikipedia_es` | F1-08 |
| Wikimedia Commons (fotos) | Autor, licencia y enlace a la página del archivo | Pie de la imagen en la ficha | F4 |
| OpenStreetMap | "© OpenStreetMap contributors" con enlace | En el propio mapa (Leaflet lo hace por defecto) | F4 |
| RAWG (si se usa) | Enlace activo a RAWG en **cada página** que use sus datos | Fichas y listados de juegos | F4 |
| IGDB (si se usa) | Según sus términos, que se verifican en la F4 | — | F4 |

## 3. Reglas de datos de terceros

- **TMDB:**
  - no se guarda ningún dato más de **6 meses** sin refrescarlo (ver [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md) §5);
  - no se guarda el payload en bruto;
  - si se revoca la clave, hay que borrar los datos de TMDB.
- **Apple / iTunes:** no se usan sus portadas. Las que hay en el snapshot actual se eliminan con el reset.
- **Wikipedia (CC BY-SA):**
  - si un usuario o un proceso **modifica** un texto de la Wikipedia, el resultado sigue bajo CC BY-SA y se atribuye igual;
  - **no** se presenta como contenido propio.
- **Imágenes:**
  - se guardan reducidas (`thumb`/`banner`) junto con `license`, `author` y `source_url`;
  - las portadas comerciales (TMDB, IGDB) llevan `license: "© titular de derechos"` y se muestran solo como ilustración de la ficha.

## 4. DSA (Reglamento de Servicios Digitales de la UE)

Kudos aloja contenido de usuarios (comentarios y, más adelante, listas, reseñas y perfiles). Como **mínimo** necesita lo siguiente, que es requisito de la Fase 3 antes de abrir listas y reseñas:

1. **Mecanismo de notificación:**
   - un botón "Reportar" en comentarios, reseñas, listas y perfiles (tabla `reports`, tarea F3-04);
   - sirve también para usuarios no registrados (con un correo de contacto) o, como mínimo, para los registrados más un correo de contacto público.
2. **Cola de revisión** en el panel de admin, con estados `pending`, `resolved` y `dismissed`.
3. **Exposición de motivos:** al retirar u ocultar contenido de un usuario, se le notifica qué contenido, por qué motivo y cómo reclamar. Se hace con una notificación dentro de la app (tarea F3-05).
4. **Punto de contacto:** un correo de contacto visible en el aviso legal.
5. **Registro:** las decisiones de moderación quedan registradas. `ModerationAuditLogger` ya existe y se reutiliza.

## 5. RGPD (protección de datos)

Requisitos antes de abrir perfiles públicos y listas (Fase 3):

| Requisito | Implementación | Tarea |
|---|---|---|
| Política de privacidad | Página `/privacidad`. Contenido: responsable, datos tratados (cuenta, votos, comentarios, listas, IP en los logs), finalidad, base legal (ejecución del servicio), conservación, derechos y contacto. **El texto lo aprueba el humano** | F3-07 |
| Aceptación en el registro | Casilla **sin marcar** de aceptación de la política, y declaración de tener **14 años o más** (edad de consentimiento digital en España) | F3-07 |
| Privacidad por defecto | `profiles.is_public` y `lists.visibility` son privados por defecto; el usuario los hace públicos de forma explícita | F3-01, F3-03 |
| Derecho de supresión | `DELETE /api/profile` (pide la contraseña): borra la cuenta, los votos, las listas, los tokens y los *likes*. Los comentarios con respuestas se **anonimizan** (autor nulo, contenido "[comentario eliminado]"). Las transacciones de kudos se borran | F3-08 |
| Derecho de acceso y portabilidad | `GET /api/profile/export`: JSON con perfil, votos, comentarios y listas. Deseable, no bloqueante | F3-08 |
| Correos | Ningún correo que no sea transaccional sin consentimiento expreso. Las notificaciones son **solo dentro de la app** | F3-05 |
| Cookies | El token de sesión se guarda en `localStorage` y no hay analítica de terceros, así que no hace falta banner de cookies. **Si se añade analítica, vuelve a evaluarse** | — |
| Encargados del tratamiento | Vercel (frontend) y el proveedor del VPS (OVH). Se mencionan en la política | F3-07 |
