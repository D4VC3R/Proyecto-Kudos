# Tablero de tareas

Estados: `pendiente` · `en curso` · `hecho` · `bloqueada` (con el motivo en la ficha). Coge la primera `pendiente` con todas sus dependencias en `hecho` ([../guia-agentes.md](../guia-agentes.md) §2).

Resp.: `BE` backend · `FE` frontend · `INFRA` infraestructura · `INT` integraciones · `QA` tests · `LEGAL` cumplimiento · `DOCS` documentación · `HUMANO` lo hace o decide el humano.

## Fase 0: higiene y base. [Fichas](fase-0-higiene.md)

| ID | Tarea | Resp. | Depende de | Estado |
|---|---|---|---|---|
| F0-01 | Tests sobre PostgreSQL: configuración, `TestCase` con protección | INFRA, QA | — | hecho |
| F0-02 | Batería base de tests de votos, kudos, ranking e ítems | QA, BE | F0-01 | hecho |
| F0-03 | Medias exactas: corregir el truncado de medios puntos y recalcular con `AVG` | BE | F0-01 | hecho |
| F0-04 | Ranking bayesiano (`bayes_score`) | BE | F0-03 | hecho |
| F0-05 | `quality_score` y excluir las fichas pobres de la cola y de los destacados | BE | F0-01 | hecho |
| F0-06 | *Placeholder* generativo para ítems sin imagen | FE | — | hecho |
| F0-07 | Ocultar secciones vacías en la ficha | FE | — | hecho |
| F0-08 | Corregir las clases CSS sin definir | FE | — | hecho |
| F0-09 | Búsqueda global (cabecera y página `/buscar`) y slugs reservados | FE, BE | F0-01 | hecho |
| F0-10 | Servicio `scheduler`, cola `catalog` y memoria del worker en producción | INFRA | — | hecho |
| F0-11 | Copia de seguridad del volumen de imágenes | INFRA | — | hecho |
| F0-12 | Corregir el README (PostgreSQL, tests, estructura) | DOCS | F0-01 | hecho |
| F0-13 | CI de GitHub Actions que ejecute los tests con PostgreSQL | INFRA | F0-02 | hecho |
| F0-14 | Los invitados no ven el detalle de los ítems inactivos (`ShowItemRequest`) | BE | F0-02 | hecho |
| F0-15 | Escapar `%`, `_` y `\` en las búsquedas `ilike` | BE | F0-09 | hecho |
| F0-16 | Evitar el *deadlock* al votar el mismo ítem | BE | F0-03 | hecho |
| F0-17 | La cola de votación no ofrece ítems ya votados | BE | F0-05 | hecho |
| F0-18 | `Http::preventStrayRequests()` para todos los tests | QA | F0-02 | hecho |
| F0-19 | Herramientas: `composer.lock`, Sail en PHP 8.5.12 y pnpm fijado | INFRA | F0-13 | hecho |
| F0-20 | Accesibilidad del buscador global | FE | F0-09 | hecho |
| F0-21 | Limpieza menor de la Fase 0 | FE, BE, DOCS | F0-08 | hecho |
| F0-22 | Licencia MIT | DOCS | — | hecho |
| F0-23 | Vulnerabilidad de `symfony/yaml` y versión mínima de PHP | BE, INFRA | F0-19 | hecho |
| F0-24 | Cabecera sin login rápido y vuelta a la página de origen tras el login | FE | F0-09 | hecho |

## Fase 1: catálogo vivo de películas y series. [Fichas](fase-1-catalogo-vivo.md)

| ID | Tarea | Resp. | Depende de | Estado |
|---|---|---|---|---|
| F1-00 | Dar de alta el token de TMDB y la variable `CATALOG_USER_AGENT` | HUMANO | — | pendiente |
| F1-01 | Migraciones del catálogo: `items`, `item_sources`, `categories` | BE | F0-05 | hecho |
| F1-02 | Módulo `app/Catalog`: contratos, DTO, `config/catalog.php` y clientes HTTP | INT | F1-01 | hecho |
| F1-03 | Proveedores de TMDB (películas y series) | INT | F1-02 | hecho |
| F1-04 | `DescriptionResolver` (Wikidata y Wikipedia ES) y `CatalogImporter` | INT, BE | F1-02 | hecho |
| F1-05 | Comandos `fetch-new`, `refresh`, `purge-stale` e `import`, y su programación | BE | F1-03, F1-04, F0-10 | hecho |
| F1-06 | La API expone los campos nuevos; las categorías inactivas quedan ocultas | BE | F1-01 | hecho |
| F1-07 | Importación bajo demanda (API y la interfaz en `/buscar`) | BE, FE | F1-05, F0-09 | pendiente |
| F1-08 | Atribución en la interfaz (TMDB y Wikipedia) | FE, LEGAL | F1-06 | pendiente |
| F1-09 | Chips de atributos genéricos a partir de `attribute_schema` | FE | F1-06 | pendiente |
| F1-10 | Sembrado nuevo y *runbook* del reset de producción | BE, INFRA | F1-05 | pendiente |
| F1-11 | Ejecutar el reset en producción | HUMANO | F1-10 | pendiente |

## Fase 2: descubrimiento. [Fichas](fase-2-descubrimiento.md)

| ID | Tarea | Resp. | Depende de | Estado |
|---|---|---|---|---|
| F2-01 | Tipos de voto `want` y `not_interested`; normalizar `skip` | BE | F1-10 | pendiente |
| F2-02 | Transiciones entre tipos de voto (`want` → valoración), con kudos una sola vez | BE | F2-01 | pendiente |
| F2-03 | Reajuste de las recompensas de kudos | BE | — | pendiente |
| F2-04 | Votación en dos pasos (interfaz) y limpieza de `ActionMenu` | FE | F2-02 | pendiente |
| F2-05 | *Wishlist* (API y página) | BE, FE | F2-01 | pendiente |
| F2-06 | Cola de votación ponderada | BE | F2-01 | pendiente |
| F2-07 | Onboarding: intereses y *speed-rating* | BE, FE | F2-04 | pendiente |
| F2-08 | "Similares" y "Porque te gustó X" por contenido | BE, FE | F1-09 | pendiente |
| F2-09 | Listas editoriales (esquema de listas y administración) y portada nueva | BE, FE | F2-08 | pendiente |
| F2-10 | SEO: meta tags y OG de las fichas servidos desde Vercel | FE, INFRA | F1-06 | pendiente |
| F2-11 | Comando de métricas de uso | BE | F2-01 | pendiente |

## Fase 3: comunidad mínima. [Fichas](fase-3-comunidad.md)

| ID | Tarea | Resp. | Depende de | Estado |
|---|---|---|---|---|
| F3-01 | Listas de usuarios: CRUD, visibilidad y *likes* | BE, FE | F2-09, F3-04, F3-07 | pendiente |
| F3-02 | Reseñas ligadas a una nota y respuestas de un nivel | BE, FE | F3-04 | pendiente |
| F3-03 | Perfil público (`username`, `is_public`, `/u/:username`) | BE, FE | F3-07 | pendiente |
| F3-04 | Reportes (DSA) y cola de revisión del admin | BE, FE | F3-05 | pendiente |
| F3-05 | Notificaciones dentro de la app | BE, FE | — | pendiente |
| F3-06 | Aviso de estreno de los ítems de la *wishlist* | BE | F3-05, F2-05 | pendiente |
| F3-07 | Páginas legales y requisitos en el registro (casilla, 14 años) | FE, BE, HUMANO | — | pendiente |
| F3-08 | Borrado de cuenta y exportación de datos | BE, FE | — | pendiente |
| F3-09 | OG de listas y perfiles | FE | F2-10, F3-01 | pendiente |

## Fase 4: más categorías. [Fichas](fase-4-mas-categorias.md)

| ID | Tarea | Resp. | Depende de | Estado |
|---|---|---|---|---|
| F4-00 | Verificar los términos de IGDB y RAWG y elegir proveedor | LEGAL, HUMANO | — | pendiente |
| F4-01 | Proveedor de videojuegos | INT | F4-00, F1-05 | pendiente |
| F4-02 | Proveedor de Destinos (Wikidata y Commons) | INT | F1-05 | pendiente |
| F4-03 | Ficha de destinos con mapa (Leaflet y OSM) | FE | F4-02 | pendiente |
| F4-04 | Atribuciones de Commons, RAWG o IGDB en la interfaz | FE, LEGAL | F4-01, F4-02 | pendiente |

## Aplazado

No se implementa sin orden del humano: ver [../producto/vision.md](../producto/vision.md) §3.
