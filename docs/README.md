# Kudos 2.0: documentación del rediseño

Plan aprobado el 26-10-2026. Rama de integración: `rediseno-fase-0`; `docs/` solo existe en ella. El estado de cada tarea está **solo** en [roadmap/README.md](roadmap/README.md).

**Objetivo:** *"Tu diario de pelis, series y juegos en español, y destinos que descubrir, con listas que lo conectan todo."* Tres categorías vivas (Pelis/Series, Juegos, Destinos) alimentadas por APIs gratuitas; votar como "¿Lo conoces? → valóralo / me interesa / no me interesa"; descubrimiento que no depende del volumen de usuarios.

**Restricciones:** coste cero y sin ánimo de lucro; contenido en español; un VPS de 4 GB compartido; un solo desarrollador; los datos actuales son de prueba y se pueden resetear.

## Lectura

- **Siempre:** [guia-agentes.md](guia-agentes.md), y después la ficha de tu tarea.
- **Según la ficha:** las secciones que cite, de esta lista:

| Documento | Contenido |
|---|---|
| [producto/vision.md](producto/vision.md) | Decisiones de producto, funciones aplazadas, métricas |
| [infra/entorno-local.md](infra/entorno-local.md) | Sail, base de datos de tests, frontend, comandos, problemas frecuentes |
| [infra/produccion.md](infra/produccion.md) | `compose.prod.yml`, variables nuevas, copias, *runbook* del reset |
| [backend/arquitectura.md](backend/arquitectura.md) | Capas, mapa de archivos, trampas conocidas, patrones |
| [backend/base-de-datos.md](backend/base-de-datos.md) | Esquema actual y migraciones de cada fase |
| [backend/testing.md](backend/testing.md) | `TestCase`, estructura, convenciones, APIs falseadas |
| [frontend/arquitectura.md](frontend/arquitectura.md) | Stack, estructura, rutas, estilos, decisiones de UI |
| [integraciones/fuentes-externas.md](integraciones/fuentes-externas.md) | Reglas y APIs permitidas, endpoints, cobertura |
| [integraciones/pipeline-catalogo.md](integraciones/pipeline-catalogo.md) | Módulo `app/Catalog`: contratos, importación, refresco |
| [legal/cumplimiento.md](legal/cumplimiento.md) | Atribuciones, datos de terceros, DSA, RGPD |
| [roadmap/](roadmap/README.md) | Tablero y fichas de las fases 0–4 |
