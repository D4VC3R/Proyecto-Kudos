# Tablero de tareas

> **Plan cerrado: histórico de Kudos 2.0.** Las fichas de las fases 0–5 cuentan cómo se hizo el rediseño y se quedan donde están, porque el código las cita. Hasta la fusión en `main` (F5-16), que espera al rediseño visual (Fase R), el tablero sigue en uso; las tareas de "Después de `main`" se gestionan con sus issues.

Estados: `pendiente` · `en curso` · `hecho` · `bloqueada` (con el motivo en la ficha). Coge la primera `pendiente` con todas sus dependencias en `hecho` ([../guia-agentes.md](../guia-agentes.md) §2). Desde la Fase 5, cada tarea pendiente tiene su issue en GitHub (columna **Issue**); este tablero sigue siendo la fuente de verdad (guía §4.2).

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
| F1-00 | Dar de alta el token de TMDB y la variable `CATALOG_USER_AGENT` | HUMANO | — | hecho |
| F1-01 | Migraciones del catálogo: `items`, `item_sources`, `categories` | BE | F0-05 | hecho |
| F1-02 | Módulo `app/Catalog`: contratos, DTO, `config/catalog.php` y clientes HTTP | INT | F1-01 | hecho |
| F1-03 | Proveedores de TMDB (películas y series) | INT | F1-02 | hecho |
| F1-04 | `DescriptionResolver` (Wikidata y Wikipedia ES) y `CatalogImporter` | INT, BE | F1-02 | hecho |
| F1-05 | Comandos `fetch-new`, `refresh`, `purge-stale` e `import`, y su programación | BE | F1-03, F1-04, F0-10 | hecho |
| F1-06 | La API expone los campos nuevos; las categorías inactivas quedan ocultas | BE | F1-01 | hecho |
| F1-07 | Importación bajo demanda (API y la interfaz en `/buscar`) | BE, FE | F1-05, F0-09 | hecho |
| F1-08 | Atribución en la interfaz (TMDB y Wikipedia) | FE, LEGAL | F1-06 | hecho |
| F1-09 | Chips de atributos genéricos a partir de `attribute_schema` | FE | F1-06 | hecho |
| F1-10 | Sembrado nuevo y *runbook* del reset de producción | BE, INFRA | F1-05 | hecho |
| F1-11 | Ejecutar el reset en producción | HUMANO | F1-10 | bloqueada |

## Fase 2: descubrimiento. [Fichas](fase-2-descubrimiento.md)

| ID | Tarea | Resp. | Depende de | Estado |
|---|---|---|---|---|
| F2-01 | Tipos de voto `want` y `not_interested`; normalizar `skip` | BE | F1-10 | hecho |
| F2-02 | Transiciones entre tipos de voto (`want` → valoración), con kudos una sola vez | BE | F2-01 | hecho |
| F2-03 | Reajuste de las recompensas de kudos | BE | — | hecho |
| F2-04 | Votación en dos pasos (interfaz) y limpieza de `ActionMenu` | FE | F2-02 | hecho |
| F2-05 | *Wishlist* (API y página) | BE, FE | F2-01 | hecho |
| F2-06 | Cola de votación ponderada | BE | F2-01 | hecho |
| F2-07 | Onboarding: intereses y *speed-rating* | BE, FE | F2-04 | hecho |
| F2-08 | "Similares" y "Porque te gustó X" por contenido | BE, FE | F1-09 | hecho |
| F2-09 | Listas editoriales (esquema de listas y administración) y portada nueva | BE, FE | F2-08 | hecho |
| F2-10 | SEO: meta tags y OG de las fichas servidos desde Vercel | FE, INFRA | F1-06 | hecho |
| F2-11 | Comando de métricas de uso | BE | F2-01 | hecho |

## Fase 3: comunidad mínima. [Fichas](fase-3-comunidad.md)

| ID | Tarea | Resp. | Depende de | Estado |
|---|---|---|---|---|
| F3-01 | Listas de usuarios: CRUD, visibilidad y *likes* | BE, FE | F2-09, F3-04, F3-07 | hecho |
| F3-02 | Reseñas ligadas a una nota y respuestas de un nivel | BE, FE | F3-04 | hecho |
| F3-03 | Perfil público (`username`, `is_public`, `/u/:username`) | BE, FE | F3-07 | hecho |
| F3-04 | Reportes (DSA) y cola de revisión del admin | BE, FE | F3-05 | hecho |
| F3-05 | Notificaciones dentro de la app | BE, FE | — | hecho |
| F3-06 | Aviso de estreno de los ítems de la *wishlist* | BE | F3-05, F2-05 | hecho |
| F3-07 | Páginas legales y requisitos en el registro (casilla, 14 años) | FE, BE, HUMANO | — | hecho |
| F3-08 | Borrado de cuenta y exportación de datos | BE, FE | — | hecho |
| F3-09 | OG de listas y perfiles | FE | F2-10, F3-01 | hecho |

## Fase 4: más categorías. [Fichas](fase-4-mas-categorias.md)

| ID | Tarea | Resp. | Depende de | Estado |
|---|---|---|---|---|
| F4-00 | Verificar los términos de IGDB y RAWG y elegir proveedor | LEGAL, HUMANO | — | hecho |
| F4-01 | Proveedor de videojuegos | INT | F4-00, F1-05 | hecho |
| F4-02 | Proveedor de Destinos (Wikidata y Commons) | INT | F1-05 | hecho |
| F4-03 | Ficha de destinos con mapa (Leaflet y OSM) | FE | F4-02 | hecho |
| F4-04 | Atribuciones de Commons, IGDB y Steam en la interfaz | FE, LEGAL | F4-01, F4-02 | hecho |

## Fase 5: refactor y preparación para `main`. [Fichas](fase-5-refactor.md)

F5-02, F5-07 y F5-09 no dependen de nada y pueden ir en paralelo con F5-01. F5-15 (Pint) va la última, antes de la fusión. F5-16 espera a la Fase R (RD-01).

| ID | Tarea | Resp. | Depende de | Estado | Issue |
|---|---|---|---|---|---|
| F5-00 | Decisiones previas a la fase (D1–D9) | HUMANO | — | hecho | — |
| F5-01 | Tests de caracterización de la API heredada | QA, BE | — | hecho | — |
| F5-02 | Rendimiento de los listados: N+1 y límites | BE | — | hecho | [#6](https://github.com/D4VC3R/Proyecto-Kudos/issues/6) |
| F5-03 | Moderación coherente con la DSA y propuestas | BE, LEGAL | F5-01 | hecho | [#7](https://github.com/D4VC3R/Proyecto-Kudos/issues/7) |
| F5-04 | Contrato de errores y endpoints de cuenta | BE | F5-01, F5-00 | hecho | [#8](https://github.com/D4VC3R/Proyecto-Kudos/issues/8) |
| F5-05 | Visibilidad y autorización coherentes | BE | F5-01 | hecho | [#9](https://github.com/D4VC3R/Proyecto-Kudos/issues/9) |
| F5-06 | Código muerto y contratos heredados del backend | BE, INT | F5-01, F5-00 | hecho | [#10](https://github.com/D4VC3R/Proyecto-Kudos/issues/10) |
| F5-07 | Catálogo: política de jobs, *throttle* acotado e importación concurrente | INT | — | hecho | [#11](https://github.com/D4VC3R/Proyecto-Kudos/issues/11) |
| F5-08 | Datos y recursos de terceros (`seed-data`, portadas, fuente, privacidad) | BE, FE, LEGAL | F5-00 | hecho | [#12](https://github.com/D4VC3R/Proyecto-Kudos/issues/12) |
| F5-09 | Barrera de errores y tarjetas de ítem como enlaces | FE | — | hecho | [#13](https://github.com/D4VC3R/Proyecto-Kudos/issues/13) |
| F5-10 | Errores de API tipados en el frontend | FE | F5-04 | hecho | [#14](https://github.com/D4VC3R/Proyecto-Kudos/issues/14) |
| F5-11 | Accesibilidad de formularios, modal y botones | FE | F5-09 | hecho | [#15](https://github.com/D4VC3R/Proyecto-Kudos/issues/15) |
| F5-12 | Clases sin definir, duplicados y código muerto del frontend | FE | F5-06, F5-09 | hecho | [#16](https://github.com/D4VC3R/Proyecto-Kudos/issues/16) |
| F5-13 | *Runbook* único del paso a Kudos 2.0 y salvaguardas de `main` | INFRA, DOCS | F5-00, F5-07, F5-08 | hecho | [#17](https://github.com/D4VC3R/Proyecto-Kudos/issues/17) |
| F5-14 | Documentación lista para `main` | DOCS | F5-01…F5-13 | hecho | [#18](https://github.com/D4VC3R/Proyecto-Kudos/issues/18) |
| F5-15 | Formato con Pint y controles de CI | INFRA | F5-14, F5-00 | hecho | [#19](https://github.com/D4VC3R/Proyecto-Kudos/issues/19) |
| F5-16 | Fusión en `main` y paso a producción (sustituye a F1-11) | HUMANO | F5-15, RD-01 | pendiente | [#20](https://github.com/D4VC3R/Proyecto-Kudos/issues/20) |
| F5-17 | Sesión y verificación de correo en el frontend | FE | F5-16 | pendiente | [#21](https://github.com/D4VC3R/Proyecto-Kudos/issues/21) |
| F5-18 | Convención de página y estado en la URL | FE | F5-16 | pendiente | [#22](https://github.com/D4VC3R/Proyecto-Kudos/issues/22) |
| F5-19 | *Code splitting*, ESLint y tests de `lib/` | FE, QA | F5-16 | pendiente | [#23](https://github.com/D4VC3R/Proyecto-Kudos/issues/23) |
| F5-20 | Capa de aplicación: Actions y servicios para el código heredado | BE | F5-16 | pendiente | [#24](https://github.com/D4VC3R/Proyecto-Kudos/issues/24) |
| F5-21 | Catálogo: helpers comunes y Wikidata sin peticiones duplicadas | INT | F5-16 | pendiente | [#25](https://github.com/D4VC3R/Proyecto-Kudos/issues/25) |
| F5-22 | Catálogo: fuentes huérfanas, popularidad y `config/discovery.php` | INT, BE, HUMANO | F5-16 | pendiente | [#26](https://github.com/D4VC3R/Proyecto-Kudos/issues/26) |
| F5-23 | Limpieza de tests y del entorno de desarrollo | QA, INFRA, HUMANO | F5-16 | pendiente | [#27](https://github.com/D4VC3R/Proyecto-Kudos/issues/27) |

## Fase R: rediseño visual. [Fichas](fase-r-rediseno.md)

Va **antes de F5-16**: la fusión en `main` incluye el rediseño, así que mientras quede una tarea R pendiente, la fase actual es esta. Ramas `rediseno/RX-YY-…` y etiqueta `rediseno`. Por orden: RA-00 (herramienta de verificación) → fallos RA → sistema de diseño RB, en serie → páginas RC → RD-01. Las tareas RA-03, RA-04 y RA-05 no dependen de nada.

| ID | Tarea | Resp. | Depende de | Estado | Issue |
|---|---|---|---|---|---|
| R0-01 | Auditoría, direcciones y plan | DOCS | — | hecho | [#48](https://github.com/D4VC3R/Proyecto-Kudos/issues/48) |
| R1-01 | Maquetas y elección de dirección | HUMANO, DOCS | R0-01 | hecho | [#50](https://github.com/D4VC3R/Proyecto-Kudos/issues/50) |
| R1-02 | Tablero de la Fase R | DOCS | R1-01 | hecho | [#52](https://github.com/D4VC3R/Proyecto-Kudos/issues/52) |
| RA-00 | Herramienta de verificación visual | FE, QA | — | hecho | [#53](https://github.com/D4VC3R/Proyecto-Kudos/issues/53) |
| RA-01 | Menú móvil accesible y "Saltar al contenido" | FE | RA-00 | hecho | [#54](https://github.com/D4VC3R/Proyecto-Kudos/issues/54) |
| RA-02 | Filtros con borde y etiquetas visibles | FE | RA-00 | hecho | [#55](https://github.com/D4VC3R/Proyecto-Kudos/issues/55) |
| RA-03 | Categoría inexistente sin consultas inútiles | FE | — | pendiente | [#56](https://github.com/D4VC3R/Proyecto-Kudos/issues/56) |
| RA-04 | Pluralización y nota vacía | FE | — | pendiente | [#57](https://github.com/D4VC3R/Proyecto-Kudos/issues/57) |
| RA-05 | Posición del ranking con empates | BE, FE | — | pendiente | [#58](https://github.com/D4VC3R/Proyecto-Kudos/issues/58) |
| RB-01 | Tokens, temas y modo oscuro | FE | RA-00 | pendiente | [#59](https://github.com/D4VC3R/Proyecto-Kudos/issues/59) |
| RB-02 | Primitivas | FE | RB-01 | pendiente | [#60](https://github.com/D4VC3R/Proyecto-Kudos/issues/60) |
| RB-03 | Cabecera, navegación, pie y selector de tema | FE | RB-02 | pendiente | [#61](https://github.com/D4VC3R/Proyecto-Kudos/issues/61) |
| RC-01 | Tarjeta de ítem | FE | RB-03 | pendiente | [#62](https://github.com/D4VC3R/Proyecto-Kudos/issues/62) |
| RC-02 | Portada | FE | RC-01 | pendiente | [#63](https://github.com/D4VC3R/Proyecto-Kudos/issues/63) |
| RC-03 | Ficha | FE | RC-01 | pendiente | [#64](https://github.com/D4VC3R/Proyecto-Kudos/issues/64) |
| RC-04 | Votar | FE | RC-03 | pendiente | [#65](https://github.com/D4VC3R/Proyecto-Kudos/issues/65) |
| RC-05 | Categoría y Explorar | FE | RC-01 | pendiente | [#66](https://github.com/D4VC3R/Proyecto-Kudos/issues/66) |
| RC-06 | Buscar y buscador global | FE | RC-01, RA-02 | pendiente | [#67](https://github.com/D4VC3R/Proyecto-Kudos/issues/67) |
| RC-07 | Listas | FE | RC-01 | pendiente | [#68](https://github.com/D4VC3R/Proyecto-Kudos/issues/68) |
| RC-08 | Wishlist y onboarding | FE | RC-01 | pendiente | [#69](https://github.com/D4VC3R/Proyecto-Kudos/issues/69) |
| RC-09 | Perfil y perfil público | FE | RC-01 | pendiente | [#70](https://github.com/D4VC3R/Proyecto-Kudos/issues/70) |
| RC-10 | Autenticación | FE | RB-03 | pendiente | [#71](https://github.com/D4VC3R/Proyecto-Kudos/issues/71) |
| RC-11 | Ranking global | FE | RC-05 | pendiente | [#72](https://github.com/D4VC3R/Proyecto-Kudos/issues/72) |
| RC-12 | Legal, 404, Forbidden y error | FE | RB-03 | pendiente | [#73](https://github.com/D4VC3R/Proyecto-Kudos/issues/73) |
| RC-13 | Administración | FE | RB-03 | pendiente | [#74](https://github.com/D4VC3R/Proyecto-Kudos/issues/74) |
| RC-14 | Piezas globales | FE | RB-03 | pendiente | [#75](https://github.com/D4VC3R/Proyecto-Kudos/issues/75) |
| RC-15 | Textos | FE, BE | RC-01…RC-14 | pendiente | [#76](https://github.com/D4VC3R/Proyecto-Kudos/issues/76) |
| RD-01 | Revisión final | QA, FE, DOCS | RC-15 | pendiente | [#77](https://github.com/D4VC3R/Proyecto-Kudos/issues/77) |

## Aplazado

No se implementa sin orden del humano: ver [../producto/vision.md](../producto/vision.md) §3.
