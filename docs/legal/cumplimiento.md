# Cumplimiento

Es una guía técnica para no incumplir lo evidente, **no asesoramiento jurídico**. Los textos legales definitivos los aprueba el humano.

**Sin ánimo de lucro:** TMDB, RAWG e IGDB solo son gratuitas para uso no comercial. Por eso no hay publicidad, afiliados, funciones de pago ni muros de donación que bloqueen funciones. Si eso cambiara, habría que renegociar las licencias o retirar los datos.

Las reglas técnicas de uso de datos de terceros (TMDB 6 meses, sin payload en bruto, sin portadas de iTunes) están en [../integraciones/fuentes-externas.md](../integraciones/fuentes-externas.md) §1.

## 1. Atribuciones obligatorias

| Fuente | Qué mostrar | Dónde | Tarea |
|---|---|---|---|
| TMDB | Logo de TMDB + "This product uses the TMDB API but is not endorsed or certified by TMDB." (se puede añadir la traducción) | Pie o "Acerca de", y enlace a TMDB en cada ficha con sus datos | F1-08 |
| Wikipedia ES | "Descripción: Wikipedia, licencia CC BY-SA 4.0", con enlace a `description_url` y a la licencia | Cada ficha con `description_source = wikipedia_es` | F1-08 |
| Wikimedia Commons | Autor, licencia y enlace a la página del archivo | Pie de la imagen | F4-04 |
| OpenStreetMap | "© OpenStreetMap contributors" con enlace | En el mapa (Leaflet lo hace por defecto) | F4-03 |
| RAWG (si se usa) | Enlace activo a RAWG | **Cada página** con sus datos, incluidos los listados | F4-04 |
| IGDB (si se usa) | Lo que digan sus términos (F4-00) | — | F4-04 |

## 2. Contenido de terceros

- **Wikipedia (CC BY-SA):** un texto modificado sigue bajo CC BY-SA y se atribuye igual. Nunca se presenta como contenido propio.
- **Imágenes:** se guardan reducidas (`thumb`/`banner`), con `license`, `author` y `source_url`. Las portadas comerciales (TMDB, IGDB) llevan `license: "© titular de derechos"` y solo ilustran la ficha.

## 3. DSA (requisito de la Fase 3, antes de abrir listas y reseñas)

1. **Notificación:** botón "Reportar" en comentarios, reseñas, listas y perfiles (`reports`, F3-04), al menos para usuarios registrados, más un correo de contacto público.
2. **Cola de revisión** en el panel de admin: `pending`, `resolved` y `dismissed`.
3. **Exposición de motivos:** al retirar u ocultar contenido, se avisa al autor de qué contenido, por qué y cómo reclamar, con una notificación dentro de la app (F3-05).
4. **Punto de contacto:** correo visible en el aviso legal.
5. **Registro:** las decisiones se registran con `ModerationAuditLogger`.

## 4. RGPD (requisito de la Fase 3, antes de perfiles públicos y listas)

| Requisito | Implementación | Tarea |
|---|---|---|
| Política de privacidad | `/privacidad`: responsable, datos tratados (cuenta, votos, comentarios, listas, IP en los logs), finalidad, base legal (ejecución del servicio), conservación, derechos y contacto. **La aprueba el humano** | F3-07 |
| Aceptación en el registro | Casilla **sin marcar** + declaración de tener **14 años o más** (edad de consentimiento digital en España) | F3-07 |
| Privacidad por defecto | `profiles.is_public` y `lists.visibility` privados por defecto | F3-01, F3-03 |
| Supresión | `DELETE /api/profile` con contraseña: borra la cuenta, los votos, las listas, los tokens, los *likes* y las transacciones de kudos; los comentarios con respuestas se **anonimizan** | F3-08 |
| Acceso y portabilidad | `GET /api/profile/export` (JSON). Deseable, no bloqueante | F3-08 |
| Correos | Solo transaccionales sin consentimiento expreso; las notificaciones van solo dentro de la app | F3-05 |
| Cookies | El token está en `localStorage` y no hay analítica de terceros, así que no hace falta banner. **Si se añade analítica, se vuelve a evaluar** | — |
| Encargados | Vercel (frontend) y OVH (VPS), citados en la política | F3-07 |
