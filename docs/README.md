# Documentación del rediseño de Kudos ("Kudos 2.0")

Esta carpeta contiene todo lo necesario para que una persona o un agente de IA ejecute el rediseño de Kudos sin necesitar el contexto de la conversación en la que se diseñó.

> **Estado del documento:** plan aprobado el 26-10-2026. Nada de este plan está implementado todavía.
> La rama `rediseno-fase-0` se creó en local a partir de `php-8.5-php2shell` y aún no tiene cambios de código.

## Orden de lectura

**Primera vez:**
1. [guia-agentes.md](guia-agentes.md): reglas de trabajo, definición de "hecho", flujo de git y qué está prohibido. **Obligatoria antes de tocar código.**
2. [producto/vision.md](producto/vision.md): qué es Kudos 2.0, por qué y qué se ha decidido no hacer.
3. [infra/entorno-local.md](infra/entorno-local.md): cómo levantar el proyecto en Linux con Docker.
4. [roadmap/README.md](roadmap/README.md): tablero de tareas con su estado. Allí se elige la siguiente tarea.

**Antes de cada tarea:** la propia tarea indica qué documentos de referencia leer.

## Mapa de documentos por responsabilidad

| Responsabilidad | Documento | Contenido |
|---|---|---|
| Producto | [producto/vision.md](producto/vision.md) | Diagnóstico, enfoque, decisiones tomadas, funciones aplazadas y su umbral de activación |
| Entorno | [infra/entorno-local.md](infra/entorno-local.md) | Puesta en marcha en Linux, comandos, base de datos de tests, problemas frecuentes |
| Infraestructura | [infra/produccion.md](infra/produccion.md) | Cambios en `compose.prod.yml` (scheduler, colas, memoria), variables nuevas, runbook del reset |
| Backend | [backend/arquitectura.md](backend/arquitectura.md) | Capas (Controller / Action / Service), convenciones, mapa de archivos, trampas conocidas |
| Backend | [backend/base-de-datos.md](backend/base-de-datos.md) | Esquema actual y cambios previstos por fase, con la especificación de cada migración |
| Backend | [backend/testing.md](backend/testing.md) | Tests sobre PostgreSQL, estructura, convenciones, *fakes* de APIs externas |
| Frontend | [frontend/arquitectura.md](frontend/arquitectura.md) | Stack, patrones (`useXPage`), estilos, deuda de CSS conocida, decisiones de UI |
| Integraciones | [integraciones/fuentes-externas.md](integraciones/fuentes-externas.md) | APIs gratuitas permitidas, límites, cobertura en español, lo que está prohibido |
| Integraciones | [integraciones/pipeline-catalogo.md](integraciones/pipeline-catalogo.md) | Diseño del módulo `app/Catalog`: proveedores, enriquecimiento, importación, refresco |
| Legal | [legal/cumplimiento.md](legal/cumplimiento.md) | Términos de las APIs, atribuciones, licencias CC, DSA y RGPD |
| Planificación | [roadmap/README.md](roadmap/README.md) | Tablero de tareas, dependencias y estado |
| Planificación | [roadmap/fase-0-higiene.md](roadmap/fase-0-higiene.md) … [roadmap/fase-4-mas-categorias.md](roadmap/fase-4-mas-categorias.md) | Tareas detalladas, con sus criterios de aceptación |

## Resumen en 30 segundos

- **Problema.** Kudos pide valorar ítems aleatorios de un catálogo estático e incompleto: 9 categorías importadas una sola vez, muchas sin imagen ni descripción. Además no tiene capa personal ni social.
- **Objetivo.** *"Tu diario de pelis, series y juegos en español, y destinos que descubrir, con listas que lo conectan todo."*
- **Catálogo.** Tres categorías vivas (Pelis/Series, Juegos y Destinos), alimentadas por APIs gratuitas y guardadas en la base de datos propia.
- **Votar.** Votar pasa a ser "¿Lo conoces? → valóralo / me interesa / no me interesa".
- **Descubrir.** Funciona sin depender del volumen de usuarios: recomendaciones por contenido, listas editoriales, novedades y búsqueda con importación bajo demanda.
- **Restricciones.**
  - Coste cero y sin ánimo de lucro.
  - Contenido en español.
  - Un VPS de 4 GB compartido.
  - Un solo desarrollador.
  - Los datos actuales son de prueba y se pueden resetear.
