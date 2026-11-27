# Fase R: rediseño visual

**Objetivo:** rediseñar todo el frontend con la dirección A ("Cuaderno"), con la paleta Azulejo por defecto y otras tres paletas que el usuario puede elegir. También se corrigen los fallos funcionales de la auditoría. No cambian el contenido, las rutas ni la funcionalidad, salvo para corregir fallos y los textos de la decisión 2.

**Por qué:** la auditoría del 26-11-2026 ([../redesign/auditoria.md](../redesign/auditoria.md)) encontró cinco problemas:
- No hay sistema visual: unos 585 usos de colores sueltos y 9 radios distintos.
- El contraste no llega a AA en varios puntos.
- El foco es inconsistente.
- Las alturas de la ficha y de votar están rotas.
- Hay diez fallos funcionales (F1–F10).

**Decisiones del humano:** [../redesign/plan.md](../redesign/plan.md) §1, decisiones 1–8.
- La marca pasa a ser solo "Kudos".
- Los textos se pueden corregir.
- Entra el modo oscuro.
- El admin se rediseña por completo.
- Los kudos y el ranking siguen destacados.
- El pie se queda como está.
- La dirección es A.
- Hay cuatro paletas que el usuario puede elegir.

**Orden:**
1. R0 y R1 (hechas).
2. RA-00, la herramienta de verificación.
3. Los fallos RA-01…RA-05.
4. El sistema de diseño, en serie: RB-01 → RB-02 → RB-03.
5. Las páginas RC-01…RC-14.
6. Los textos RC-15.
7. La revisión final RD-01.
8. **F5-16 depende de RD-01:** la fusión en `main` incluye el rediseño. Mientras tanto, la Fase R es la fase actual del tablero.

**Ramas:** `rediseno/RX-YY-descripcion-corta`, creadas desde `rediseno-fase-0` y con un PR contra ella. La etiqueta de fase es `rediseno`.

**Reglas durante la fase:** siguen R1–R13.
- **R13:** ningún comentario nuevo del código cita IDs de esta fase (`RA-01`, `RC-03`…) ni de la auditoría (`F1`…`F10`).
- **Diseño ya decidido.** Para la guía §6, el diseño visual de esta fase no es "diseño visual nuevo": la referencia es la maqueta, y lo que no esté en ella se resuelve con sus mismos tokens y piezas. Solo se pregunta al humano si una pantalla necesita algo que la maqueta no permite deducir. Si ocurre, se anota en las **Notas** de la ficha.
- **Dependencias aprobadas:** `@fontsource-variable/fraunces`, porque va con la dirección A (decisión 7). Ninguna más sin preguntar.

**Lee para toda la fase:**
- [../guia-agentes.md](../guia-agentes.md).
- [../frontend/arquitectura.md](../frontend/arquitectura.md).
- [../redesign/plan.md](../redesign/plan.md) §1 y la sección de su fase.
- La referencia visual: [../redesign/mockups/a-variantes.html](../redesign/mockups/a-variantes.html). Sus tokens (bloque `:root` y los bloques `html[data-palette=…]`), sus componentes y su capa de movimiento son la especificación. Sírvela con `python3 -m http.server 8766` dentro de `docs/redesign/mockups/`.
- Las capturas de antes, en [../redesign/capturas/](../redesign/capturas/), y las de la maqueta, en [../redesign/mockups/capturas/](../redesign/mockups/capturas/) (`a-azulejo-*`).

### Verificación visual (todas las tareas RA-01 en adelante que toquen la interfaz)

Además de `pnpm build` y `pnpm check:classes`, la tarea pasa el script de RA-00 sobre las rutas que toca:
- A 360, 768 y 1440 px.
- En las combinaciones que pida la ficha. Por defecto, las 8: 4 paletas × claro y oscuro, desde RB-01; antes, solo el tema actual.

El script comprueba:
- Ningún error de JS.
- Ningún desbordamiento horizontal.
- Un `h1` por página.
- Texto ≥ 4,5:1, y ≥ 3:1 en el texto grande, los bordes de campo y el anillo de foco.

También deja capturas en `.claude/ciclo/<ID>/capturas/`. El verificador las mira junto a las de la maqueta y las de antes.

### Lo que NO se toca

- Las rutas, la capa de datos (`hooks/<dominio>/`, las claves de React Query), `lib/` y el contrato de la API, salvo RA-05 si su investigación lo justifica.
- Las atribuciones del pie y de las fichas (TMDB, IGDB, Wikipedia, Commons y OSM): pueden cambiar de estilo, pero no de texto ni de visibilidad.
- Leaflet, que sigue aislado con `lazy`.
- La fuente se autoaloja; nunca Google Fonts.

---

## R0-01 · Auditoría, direcciones y plan (DOCS)

**Depende:** —

Hecha en el PR #49: [../redesign/auditoria.md](../redesign/auditoria.md), [../redesign/direcciones.md](../redesign/direcciones.md), [../redesign/plan.md](../redesign/plan.md) y 113 capturas.

## R1-01 · Maquetas y elección de dirección (HUMANO, DOCS)

**Depende:** R0-01

Hecha en el PR #51:
- Tres direcciones en [../redesign/mockups/](../redesign/mockups/).
- Las variantes de paleta de A.
- La elección del humano: A con Azulejo por defecto y temas a elegir (plan.md §1, decisiones 7 y 8).

## R1-02 · Tablero de la Fase R (DOCS)

**Depende:** R1-01

Esta ficha, la fase en el tablero y una issue por tarea. También adapta la guía, los agentes y `/ciclo-tareas` a los IDs `R…` y a las ramas `rediseno/…`, y hace que F5-16 dependa de RD-01.

---

## RA-00 · Herramienta de verificación visual (FE, QA)

**Depende:** —

**Por qué:** el humano no verifica a mano. Los agentes no tienen el MCP de Playwright, pero sí Docker, y la imagen `mcr.microsoft.com/playwright/mcp:latest` trae Playwright y Chromium.

**Pasos:**
1. Crea `Frontend/scripts/visual-check.mjs`, un script de Node sin dependencias nuevas. Usa el paquete `playwright` de la imagen y lanza Chromium con `chromium.launch({ channel: 'chromium' })`, porque la imagen no trae el *headless shell*. Recibe:
   - `--base` (por defecto, `http://localhost:5174`);
   - `--routes` (una lista, o un archivo JSON con rutas y si necesitan sesión);
   - `--widths` (por defecto, `360,768,1440`);
   - `--themes` (por defecto, `current`; desde RB-01, `all` = 4 paletas × claro y oscuro, aplicadas con `localStorage` antes de cargar);
   - `--out` (la carpeta de las capturas).
2. **Sesión:** inicia sesión por la interfaz con el usuario del *seeder* local (`admin@kudos.com`; contraseña en el *seeder*) y guarda el `storageState` en la carpeta de salida.
3. **Comprobaciones por ruta, ancho y tema:**
   - errores de JS y de consola;
   - desbordamiento horizontal;
   - número de `h1`;
   - contraste de cada nodo de texto visible frente a su fondo efectivo (el primer fondo opaco subiendo por los ancestros), con 4,5:1 en el texto normal y 3:1 en el grande;
   - el anillo de foco: recorre con Tab los 15 primeros elementos y comprueba que el `outline` o el `box-shadow` es visible y tiene ≥ 3:1.
4. **Salida:**
   - una tabla resumen por consola;
   - `report.json`;
   - capturas `ruta-ancho-tema.png` a página completa, con `reducedMotion: 'reduce'` y las imágenes en `loading=eager`;
   - código de salida ≠ 0 si algo falla.
5. Crea `Frontend/scripts/visual-check.sh`, que lanza el script en la imagen con `--network host`. Monta el script en solo lectura, lo copia a `/tmp` y enlaza `/app/node_modules`.
6. Documenta su uso en `Frontend/README.md` y en [../frontend/arquitectura.md](../frontend/arquitectura.md).
7. Ejecútalo sobre las rutas públicas de la auditoría (§2) y apunta en las **Notas** los fallos que encuentra hoy. No se corrigen aquí; sirven de línea base.

**Hecho cuando:**
- `Frontend/scripts/visual-check.sh --routes / --widths 360` termina, deja capturas y `report.json`, y lista los problemas de contraste ya conocidos de la auditoría, como el amarillo en el texto del admin. Esto demuestra que detecta fallos.
- Funciona con y sin sesión.
- No añade dependencias al `package.json`.

**Notas (RA-00):**
- **Archivos nuevos:** `Frontend/scripts/visual-check.mjs` y `visual-check.sh` (modo 755). Documentados en `Frontend/README.md` (§Scripts) y en `docs/frontend/arquitectura.md` §5. No se ha tocado `package.json` ni `pnpm-lock.yaml`, y no hay tests de Node nuevos (la SPA no tiene suite): la prueba ha sido ejecutar la herramienta. `pnpm build` sigue en verde.
- **Discrepancias con la ficha (R1, se sigue el código):**
  - La contraseña del *seeder* no está escrita en `UserSeeder`: la lee de `config('kudos.seed_admin')`, es decir, de `SEED_ADMIN_EMAIL` y `SEED_ADMIN_PASSWORD` en `Backend/.env`. `visual-check.sh` las lee de ahí (o de `KUDOS_VISUAL_EMAIL` y `KUDOS_VISUAL_PASSWORD`) y se las pasa al contenedor por nombre; no hay ninguna contraseña en el repo.
  - El amarillo del admin (auditoría §4) no está en `/` sino en `/admin` y `/admin/users`, con sesión. `--routes / --widths 360` demuestra que la herramienta corre; la detección se prueba con `--routes /admin,/admin/users --auth --widths 1440`: lista «Dashboard» a 1,46:1 y «Usuarios» a 1,53:1 (`rgb(250, 204, 21)` sobre `rgb(248, 250, 252)` y sobre blanco), y sale con código 1.
  - `/buscar?q=star` con sesión (1440 px) detecta además el panel de TMDB: «Categoría» a 1,23:1 (`rgb(226, 232, 240)` sobre blanco, F7).
- **Decisiones de implementación:** el contraste mide el fondo subiendo por los ancestros, como pedía el plan, pero cuenta como `skipped` (no medible) el texto que tiene detrás una imagen, un degradado, un fondo que no es de un ancestro o que está recortado; sin eso, los títulos y *chips* de las tarjetas sobre un póster daban blanco sobre blanco. Para ello el script hace scroll al medir, así que la captura va antes del contraste. Los textos `sr-only` se descartan por el tamaño del elemento (su rectángulo de texto desborda de 1 px). Antes del recorrido con Tab se desactivan las transiciones, para leer el estilo de foco ya terminado.
- **Línea base** (rutas públicas de la auditoría §2, 18 rutas × 360, 768 y 1440 px = 54 filas, todas con fallo; salida en `.claude/ciclo/RA-00/capturas/report.json`, ignorada por git). Sin errores de JS ni desbordamiento horizontal en ninguna:
  - **Foco (todas las rutas):** el anillo de foco da 1,42:1 en los botones y enlaces `inline-flex` (1,36:1 en algunos botones), con 2 a 4 elementos entre los 15 primeros Tab. Ninguno se queda sin indicador.
  - **`h1`:** 0 visibles en `/videojuegos/explore` y en `/buscar?q=star` (en las demás rutas, 1).
  - **Contraste:** `/peliculas` (3 anchos): tres textos a 2,8:1 en un `div.flex.shrink-0` (4,91, 4,33 y 4,21); `/login` y `/register`: el enlace «No tengo cuenta» / «Ya tengo cuenta» a 4,34:1 y, a 768 y 1440 px, el lema «Valora candidatos, descubre joyas ocultas…» a 4,24:1 (`text-blue-100`). Sin fallos de contraste en las demás.
  - **Consola:** un 404 («Failed to load resource») en `/listas/no-existe` y `/u/no-existe`, que la herramienta cuenta como fallo.
- **Comprobaciones de uso:** sin `--routes` o con `--themes foo` sale con código 2 y un mensaje; con el frontend parado (`--base` a un puerto sin servidor) da «No responde…» y código 2; sin contraseña, «Falta KUDOS_VISUAL_PASSWORD…» y código 2. `--themes all` genera las 8 capturas `home-360-<paleta>-<modo>.png`, idénticas entre sí porque hasta RB-01 no hay temas.

## RA-01 · Menú móvil accesible y "Saltar al contenido" (FE)

**Depende:** RA-00 · **Cierra:** F4, F9 ([../redesign/auditoria.md](../redesign/auditoria.md) §3)

**Pasos:**
1. `NavBar.jsx` y `NavBarMobile.jsx`:
   - el botón ☰ con `aria-expanded`, `aria-controls` y una etiqueta que cambia ("Abrir menú" / "Cerrar menú");
   - el menú se cierra con Escape (y el foco vuelve al botón), con un clic fuera y al navegar, reutilizando `hooks/common/useDismiss`;
   - el panel empieza justo debajo de la cabecera, sin el desfase `top-[72px]` frente a `h-[80px]`.
2. `App.jsx`: enlace "Saltar al contenido" como primer elemento enfocable, visible al recibir el foco, que lleva a `<main id="contenido" tabindex="-1">`.

**Hecho cuando:**
- Con el script de RA-00 a 360 px, el primer Tab enfoca "Saltar al contenido".
- Al pulsar Esc con el menú abierto, el menú se cierra y el foco vuelve al botón.
- El menú no tapa la cabecera.
- `pnpm build` y `pnpm check:classes` sin errores.

**Notas (RA-01):**
- **Archivos:** `Frontend/src/App.jsx` (enlace "Saltar al contenido" y `<main id="contenido" tabIndex={-1}>` con `scroll-mt-20 outline-none`), `components/layout/NavBar.jsx` (☰ con `aria-expanded`/`aria-controls`, `useDismiss`, `NavBarMobile` dentro del contenedor del botón para que pulsar ☰ no cuente como clic fuera), `components/layout/NavBarMobile.jsx` (recibe `id` y `onClose`; `top-full` en lugar de `top-[72px]` y `border-y`, porque el panel tapaba 8 px de la cabecera de 80 px) y `hooks/common/useDismiss.js`. Documentado en `docs/frontend/arquitectura.md` §4 y §5 y en `Frontend/README.md`. Sin tests nuevos (la SPA no tiene suite): la prueba son `visual-check.sh` y un script de interacción con Playwright fuera del repo (`.claude/ciclo/RA-01/menu-check.mjs`).
- **Ampliación del informe (R1):** `visual-check.mjs` guarda ahora, por fila, `focus.order` (el elemento que recibe cada Tab, con su `aria-label` o texto), porque `steps` y `failures` no permitían comprobar qué se enfoca primero.
- **Guarda en `useDismiss`:** ignora un Escape con `defaultPrevented`. Sin ella, el Escape con el que el buscador cierra sus resultados cerraba también el menú móvil entero.
- **Resultados:** `pnpm build` y `pnpm check:classes` en verde. `visual-check.sh --routes /,/ranking --widths 360,768,1440`: `focus.order[0]` es «Saltar al contenido» en las 6 filas, sin ningún fallo de foco en ese enlace; sin errores de JS, sin desbordamiento y un `h1`. Frente a RA-00, los mismos fallos de foco (el anillo a 1,42:1 de los botones y enlaces ya existentes, salida con código 1) y ninguno nuevo; solo sube en 1 el número de elementos recorridos por el Tab (el enlace nuevo). `menu-check.mjs` a 360 px, 7 pasos en OK: primer Tab sobre el enlace (177 px de ancho), Enter lleva el foco a `main#contenido`, menú abierto con `aria-expanded="true"`, etiqueta "Cerrar menú" y panel con el borde superior en 80 px (el inferior de la cabecera), Escape lo cierra y el foco vuelve al botón, el clic fuera y el clic en «Ranking» también lo cierran.
- **Comprobación "falla antes":** sin la guarda `!event.defaultPrevented` en `useDismiss`, el paso 6 de `menu-check.mjs` falla (el Escape con resultados abiertos cierra el menú); con ella, pasa.
- **Desviación:** en el paso 6 se ha escrito `star` en el buscador en lugar de `zz`, porque `zz` no devuelve resultados y no abre el listbox (el paso quedaba como "no aplica"); con `star` el listbox se abre y la guarda queda comprobada.

## RA-02 · Filtros con borde y etiquetas visibles (FE)

**Depende:** RA-00 · **Cierra:** F6, F7

**Pasos:**
1. `ui/SearchFilter.jsx` y `ui/SelectFilter.jsx`: un borde visible (≥ 3:1) y un único indicador de foco con `focus-visible`, sin el *outline* del navegador sumado al *ring*.
2. `catalog/CatalogImportPanel.jsx`: las etiquetas con `text-text-subtitle` pasan a un token de texto con ≥ 4,5:1.

**Hecho cuando:** el script de RA-00 sobre `/videojuegos/explore` y `/buscar?q=star` (con sesión) no da fallos de contraste en esos componentes, y los dos filtros muestran un solo anillo de foco.

**Notas (RA-02):**
- **Archivos:** `Frontend/src/index.css` (token nuevo `--color-border-strong`: slate-500 en claro y slate-400 en `.dark`), `Frontend/tailwind.config.js` (`border-strong`, que genera `border-border-strong`), `components/ui/SearchFilter.jsx` y `components/ui/SelectFilter.jsx` (`border border-border-strong`, `focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500`) y `components/catalog/CatalogImportPanel.jsx` (las tres etiquetas de `text-text-subtitle` a `text-text-normal`). Documentado en `docs/frontend/arquitectura.md` §3 y §4. Sin tests nuevos (la SPA no tiene suite): la prueba es `visual-check.sh` y un script de interacción con Playwright fuera del repo (`.claude/ciclo/RA-02/filters-check.mjs`).
- **Causa:** `border-border` sin `border` no pinta nada (el *preflight* pone el ancho a 0), y `--color-border` (slate-200) da 1,23:1 sobre blanco, así que no vale para un campo; la maqueta usa `--line-strong`. `--color-text-subtitle` (slate-200) se queda sin usos: su sitio lo decide el sistema de diseño.
- **Ampliación menor:** el `select#catalog-category` del panel copiaba las clases de `SelectFilter` y tenía el mismo defecto, así que lleva el mismo borde y el mismo foco.
- **Resultados:** `pnpm build` y `pnpm check:classes` en verde (732 clases). `filters-check.mjs`, 6 pasos en OK: bordes de 1 px sólidos con 4,55:1 (`SearchFilter`, sobre `background`) y 4,76:1 (`SelectFilter` y `select` del panel); foco con Tab sin *outline* (`rgba(0,0,0,0)`) y un solo anillo `box-shadow` azul a 3,68:1; «Categoría», el año y «No disponible» a 4,76:1. La búsqueda en TMDB va simulada con `page.route` (R4). `visual-check.sh --routes /videojuegos/explore,/buscar?q=star` (con y sin sesión, 360/768/1440): 0 fallos de contraste en las 12 filas, sin errores de JS ni desbordamiento y los filtros (`select` y `input`) en `focus.order` sin fallos de foco. Los fallos de foco restantes son los de RA-00 (el anillo a 1,42:1 y 1,36:1 de `button` y enlaces `ring-blue-200`) y no hay ninguno nuevo; la salida es 1 por ellos.
- **Comprobación "falla antes":** sobre el código sin cambiar, los 6 pasos de `filters-check.mjs` fallan (borde de 0 px, *outline* `auto` del navegador visible junto al *ring*, etiqueta, año y «No disponible» a 1,23:1); con los cambios, pasan.
- **Discrepancia con el plan:** el plan pide `h1Count: 1`, pero el informe de RA-00 ya da `h1Count: 0` en `/videojuegos/explore` y `/buscar?q=star` (las dos filas sin sesión; el resto no estaba en RA-00). No cambia con esta tarea; queda para quien cierre las cabeceras de esas páginas.

## RA-03 · Categoría inexistente sin consultas inútiles (FE)

**Depende:** — · **Cierra:** F5

**Pasos:** en `hooks/pages/useCategoryPage.js`, las consultas de ítems y de ranking se activan (`enabled`) solo cuando la categoría existe. Mientras carga, se ve un *skeleton*; si no existe, solo "Categoría no encontrada".

**Hecho cuando:** `/no-existe-xyz` no muestra "catálogo de undefined" en ningún momento y no lanza la petición `/api/items?category_slug=…` (comprobado con el registro de red del script de RA-00 o con Playwright).

**Notas (RA-03):**
- **Archivos:** `Frontend/src/hooks/pages/useCategoryPage.js` (la categoría sale de `useCategories()` y no del ranking; el ranking y los destacados solo se activan si existe; `isLoadingCategory` en `state`; `showNotFound = !isLoadingCategory && !category`) y `Frontend/src/pages/CategoryPage.jsx` (salida temprana con *skeletons* mientras no se sabe si existe, sin `ActionMenu`; la cabecera pinta `SectionHeader` directamente). Documentado en `docs/frontend/arquitectura.md` §2 y §4. Sin tests nuevos (la SPA no tiene suite): la prueba es un script de Playwright fuera del repo (`.claude/ciclo/RA-03/category-check.mjs`) y `visual-check.sh`.
- **Causa:** la categoría solo se conocía por la respuesta del ranking, así que para saber que no existía había que pedirlo. Con el `retry: 1` global, el 404 se reintentaba y, mientras, se lanzaba `/api/items?category_slug=…` (422) y `ActionMenu` pintaba "catálogo de undefined". "Existe" es "está en `GET /categories`" (solo activas), igual que el `ensureActive` del backend; la lista está cacheada 24 h y las mutaciones de admin, catálogo y votos invalidan `['categories']`. Para dejar el ranking parado sin tocar `hooks/categories/`, se le pasa `category?.slug` (`undefined` si no existe).
- **Decisión a validar:** si `GET /categories` falla (red o 500), la página acaba en "Categoría no encontrada" y no en un error genérico. Un estado de error propio sería un texto nuevo; se deja así por ser un caso raro.
- **Falla antes:** `category-check.mjs` sobre el código sin cambiar (con `/api/categories*` retrasado 1 s): `/no-existe-xyz` lanzaba dos peticiones a `/api/items?category_slug=no-existe-xyz…` y una a `/api/categories/no-existe-xyz/ranking`, mostró "undefined" y no tenía ningún `h1`; `/peliculas` también mostraba "undefined" durante la carga. Salida 1.
- **Resultados:** `pnpm build` y `pnpm check:classes` en verde (733 clases). `category-check.mjs`, 8 pasos en OK: `/no-existe-xyz` sin peticiones a `/api/items` ni a `/api/categories/no-existe-xyz*`, sin "undefined" y con un único `h1` "Categoría no encontrada"; `/peliculas` pide el ranking y los ítems con `category_slug=peliculas`, sin "undefined" y con su `h1`. La captura a los 400 ms (`capturas/no-existe-xyz-cargando-1440.png`) solo muestra *skeletons*. `visual-check.sh --routes /no-existe-xyz,/peliculas --widths 360,768,1440`: sin errores de consola ni de JS, sin desbordamiento y un `h1` en las 6 filas. `/no-existe-xyz` solo da los fallos de foco de RA-00 (anillo a 1,42:1 y 1,36:1) y `/peliculas` los mismos de la línea base (tres textos a 2,8:1 en `div.flex.shrink-0` y el foco); ninguno es nuevo, y la salida es 1 por ellos.

## RA-04 · Pluralización y nota vacía (FE)

**Depende:** — · **Cierra:** F8

**Pasos:**
1. En `lib/formatters.js`, añade `formatVotes(n)` ("1 voto", "2 votos", "Sin votos") y `formatScore(avg)` (que devuelve `null` si no hay votos).
2. Úsalas en `ItemDetailColumn.jsx`, en la tarjeta y en los demás sitios que pinten la nota o los votos.
3. Sin votos, todos muestran "Sin nota", como en la maqueta, en lugar de "0.0" o "-.-".

**Hecho cuando:** una ficha sin votos y otra con 1 voto se ven bien en la ficha y en la tarjeta, y `pnpm build` no da errores.

**Notas (RA-04):**
- **Archivos:** `Frontend/src/lib/formatters.js` (`formatVotes` y `formatScore`, solo funciones puras nuevas), `Frontend/src/components/items/ItemDetailColumn.jsx`, `Frontend/src/components/items/ItemCard.jsx` y `Frontend/src/components/admin/AdminItemCard.jsx` (el único sitio más que pinta la nota media y el recuento de un ítem). `PodiumItem` (siempre con votos) y `MyStatsPage` (la media del usuario, no la de un ítem) se quedan como están. Documentado en `docs/frontend/arquitectura.md` §1 (fila `lib/`). Sin tests nuevos (la SPA no tiene suite): la comprobación de los helpers es `.claude/ciclo/RA-04/formatters-check.mjs` (fuera del repo, copia las dos funciones en línea porque `formatters.js` arrastra `constants.js` y `lucide-react`).
- **Causa:** la ficha pintaba `vote_avg?.toFixed(1) || '0.0'` y `(N votos)` fijo, lo que daba "0.0 (0 votos)" y "(1 votos)"; la tarjeta pintaba "-.-" y el admin la media sin formatear.
- **Discrepancia con la ficha (R1):** la ficha pide `formatScore(avg)`, pero `StoreVoteRequest` admite `score` desde 0, así que una media de 0 no siempre significa "sin votos". La firma es `formatScore(avg, count)`: manda el recuento y, si la respuesta no lo trae, la media. Además, "Lo que NO se toca" de la fase incluye `lib/`; se toca solo para añadir funciones puras, sin tocar la capa de datos.
- **Decisiones a validar:** el decimal sigue siendo el punto ("8.4"), como en el podio y las estadísticas; la maqueta usa la coma ("8,4") y el cambio se deja para las tareas RC de la tarjeta y la ficha. La insignia de la tarjeta no necesitó `whitespace-nowrap`: "Sin nota" cabe sin partirse a 360 px.
- **Falla antes:** no hay prueba automática que falle sobre el código anterior (no hay suite de componentes y el script copia las funciones nuevas); la causa se ha comprobado leyendo el código (`|| '0.0'`, `{item.vote_count ?? 0} votos`, `'-.-'`).
- **Resultados:** `pnpm build` y `pnpm check:classes` en verde (733 clases). `formatters-check.mjs`, 10 casos OK, salida 0. Visual con dos ítems reales de `/destinos` (Madrid, 0 votos; España, 1 voto; sin crear votos): Madrid muestra "Sin nota" en la ficha y en la tarjeta de `/buscar`; España muestra "2.0 (1 voto)". `visual-check.sh` sobre las dos fichas y `/buscar?q=Madrid` y `/buscar?q=España` a 360, 768 y 1440 px: sin errores de consola ni de JS y sin desbordamiento; las fichas dan un `h1` y 0 fallos de contraste, y solo los fallos de foco de la línea base de RA-00 (4, 3 y 3). `/buscar` da `h1` 0, que ya estaba en la línea base de RA-00. `/admin/items` a 1440 con sesión: sin errores de JS ni de consola, sin desbordamiento y un `h1`; fallos de contraste de "Dashboard" e "Ítems" (cabecera) y de "Sin nota" (`text-yellow-600` sobre blanco, 2,94:1; es la misma clase y el mismo color que tenía el texto anterior de la puntuación) y dos de foco. La salida es 1 por los fallos de la línea base.

## RA-05 · Posición del ranking con empates (BE o FE)

**Depende:** — · **Cierra:** F10

**Pasos:**
1. Investiga por qué la cabecera muestra #3 y luego #1 cuando `/ranking` muestra #3 con empate de kudos. Compara `ranking_position` (backend) con el orden del ranking y con `UserInfo`.
2. Corrige la fuente para que la cabecera y `/ranking` usen el mismo criterio de desempate.
3. Si es backend: con Sail, un test sobre PostgreSQL que reproduce el empate, y el contrato solo se amplía (R7).

**Hecho cuando:** con dos usuarios empatados, la cabecera y `/ranking` muestran la misma posición, y el test del caso pasa (`sail test --filter`) junto con la suite completa.

**Notas (RA-05):**
- **Archivos:** `Frontend/src/hooks/users/useUserMutations.js` (`useClaimDailyReward` invalida `USER_KEYS.profile` y `USER_KEYS.ranking` solo en la rama `claimed`; `already_claimed` no invalida nada porque los kudos no cambian) y `Backend/tests/Feature/Users/MinimalProfileRankTest.php` (dos tests nuevos, los dos que había quedan intactos). Documentado en `docs/frontend/arquitectura.md` §5 (viñeta de claves de React Query). Sin cambios en el backend de producción ni en ninguna clave de React Query ni en el contrato de la API.
- **Causa (en el frontend):** el backend ya usaba un único criterio desde F5-05 (`total_kudos desc, created_at, id`) en `getRankingPaginator` y `getKudosRank`, que usan `/profile/minimal`, `meta.my_position.rank` y la fila de la tabla. El fallo era que `useClaimDailyReward` daba kudos al entrar y solo actualizaba el store de sesión, sin invalidar `['profile', 'minimal']` ni `['users', 'ranking']`: la cabecera se quedaba con la posición anterior al reclamo (#3) y cambiaba (#1) al volver a pedirse, y `/ranking` podía seguir con la caché (`staleTime` de 5 min). Con empates, una recompensa pequeña ya cambia la posición.
- **Tests nuevos:** `test_tras_la_recompensa_diaria_la_cabecera_y_el_ranking_dan_la_misma_posicion` (posición 3 antes, reclamo, y 1 en la cabecera, en `meta.my_position.rank` y en la fila del usuario) y `test_con_la_misma_fecha_de_alta_el_desempate_por_id_coincide` (cabecera, `my_position.rank` y fila coinciden con el mismo `created_at`; no se fija el número porque depende del orden de los UUID).
- **Falla antes:** no hay test que falle antes del arreglo. Los dos tests de backend pasan ya sin cambiar nada (el backend era correcto) y fijan el contrato en el que se apoya el arreglo; el arreglo es del frontend y no hay herramienta de tests de React (la fase no aprueba dependencias nuevas), así que no tiene test automático.
- **Decisión a validar:** los kudos que llegan sin acción del usuario (propuesta aceptada por un admin) siguen tardando hasta 5 min (`staleTime`) en verse; queda fuera de esta tarea.
- **Resultados:** `sail test --filter=MinimalProfileRankTest` 4 passed y `sail test` 960 passed; `pint --test` del archivo, en verde. `pnpm build` y `pnpm check:classes` en verde (733 clases). `visual-check.sh --auth` sobre `/` y `/ranking` a 1440 px: sin errores de JS ni de consola, sin desbordamiento y un `h1`; solo los fallos de foco (1,42:1) de la línea base de RA-00 (salida 1). En las capturas (`.claude/ciclo/RA-05/capturas/`) la cabecera y la tarjeta "Tu posición" de `/ranking` muestran la misma posición (#1, 72 K).

---

## RB-01 · Tokens, temas y modo oscuro (FE)

**Depende:** RA-00

**Lee:** plan.md, RB-1 y la decisión 8; los bloques de tokens de `a-variantes.html`.

**Pasos:**
1. **Colores semánticos en `src/index.css`, como canales RGB:**
   - Los nombres: `canvas`, `surface`, `ink`, `ink-muted`, `line`, `line-strong`, `accent`, `on-accent`, `success`, `warning`, `danger`, `info`, `kudos`, `on-kudos`, `stamp`, `rule`, `kicker`, `cat-icon` y el juego `band`, `band-ink`, `band-muted`, `band-line` y `band-accent`.
   - Un bloque por paleta (`[data-palette="azulejo"]`, `original`, `taquilla` y `videoclub`), con su variante `.dark`.
   - Los valores salen de la maqueta.
   - `warning`, `danger` e `info` se derivan con contraste AA en cada paleta, y se miden.
   - "Original" también define la banda: tinta oscura en claro y superficie elevada en oscuro, con el texto en AA.
2. **`tailwind.config.js`:**
   - Los colores con `rgb(var(--x) / <alpha-value>)`.
   - La escala tipográfica: `display`, `h1`–`h3`, `body`, `small` y `caption`, este último de 12 px como mínimo.
   - Los radios `sm` 6, `md` 8, `lg` 12 y `full`; dos sombras; el `container`; y el `ring` de foco.
   - Las fuentes: Fraunces (`@fontsource-variable/fraunces`, eje `opsz`) para los titulares e Inter para el texto.
   - Los tokens actuales quedan como alias que apuntan a los nuevos; se borra `logo.*`.
3. **`hooks/common/useTheme.js`:**
   - Expone `{ palette, mode, setPalette, setMode }`, con `mode` ∈ `light | dark | system`.
   - Aplica `data-palette` y `.dark` a `<html>`.
   - Guarda `kudos.theme` en `localStorage` dentro de un `try/catch`.
   - Escucha el evento `storage` y `prefers-color-scheme`, y actualiza `<meta name="theme-color">`.
   - Por defecto: `azulejo` y `system`.
4. **`index.html`:** un *script* en línea que aplica la paleta y el modo antes de pintar.
5. Bloque `prefers-reduced-motion` global.
6. Mide el contraste de todos los pares de texto de las 4 paletas × 2 modos y apunta la tabla en las **Notas**.
7. Actualiza `docs/frontend/arquitectura.md` §3 y, si cambia la fuente declarada, [../legal/cumplimiento.md](../legal/cumplimiento.md).
8. **Reinicia el contenedor de Vite** después de tocar `tailwind.config.js`.

**Hecho cuando:**
- La aplicación se ve igual que antes, porque los alias funcionan, y el script de RA-00 en `--themes all` no da errores de JS.
- Cambiando `localStorage` a mano, cambian el fondo y el texto base.
- No hay parpadeo al recargar con una paleta guardada.
- `pnpm build` y `pnpm check:classes` sin errores.

**Notas (RB-01):**
- **Archivos:** `Frontend/src/index.css` (tokens `--color-*` por paleta y modo, `body` con `bg-canvas text-ink` y `prefers-reduced-motion`), `Frontend/tailwind.config.js`, `Frontend/src/hooks/common/useTheme.js` (nuevo), `Frontend/src/app/AppProviders.jsx` (llama a `useTheme()`), `Frontend/index.html` (`data-palette`, `theme-color` y el script de tema en línea), `Frontend/src/main.jsx` (importa Fraunces), `Frontend/package.json` y `pnpm-lock.yaml`. Más el cambio `accent` → `kudos` en 9 archivos de `components/` y `lib/constants.js` y en los 11 de `pages/admin/` y `components/admin/` que pasan `highlightColor="accent"` (ver Desviaciones). Documentado en `docs/frontend/arquitectura.md` §3 y `Frontend/README.md`. Sin tests nuevos (la SPA no tiene suite): se comprueba con el build, `check:classes`, `test:seo` y `visual-check.sh`.
- **Fraunces:** `@fontsource-variable/fraunces` **5.3.0**, fijada, como Inter; es la única dependencia nueva. Se importa `opsz.css` (existe) y declara `font-family: 'Fraunces Variable'`.
- **Decisiones:** los alias (`primary`, `background`, `border`, `border-strong`, `text.*`, `nav.*`) apuntan a los tokens nuevos y desaparecen al migrar las páginas (RC); `logo.*` se borra (sin usos). `accent` es ahora el acento de la paleta (cobalto en Azulejo) y el amarillo de los kudos pasa a `kudos` (`bg-`, `text-`, `ring-` y `border-` en 10 usos, incluida la cadena del podio en `lib/constants.js`: solo estilo). Azulejo vive en `:root`/`.dark`; las otras tres, en `[data-palette]` y `.dark[data-palette]`. Los valores salen de la maqueta, salvo los derivados.
- **Derivados (no están en la maqueta):** `warning` `#8A4B00`/`#F2B33D`, `danger` `#B42318`/`#FF8A80` e `info` `#1D5FA8`/`#7CB8FF` (claro/oscuro, comunes a las 4 paletas). Banda de Original: claro `#1C1917` con texto `#FAF7F2`, apagado `#D6D0C8`, línea `#44403C` y acento `#F2B33D`; oscuro `#2B2621` con `#F3EEE7`, `#BDB4AA`, `#3D362F` y `#EE8A66`. `stamp`, `rule`, `kicker` y `cat-icon` valen `accent` en las demás paletas y tinta en Original.
- **Contraste (WCAG, mínimo sobre `canvas` y `surface`; `on-accent` sobre `accent`, `on-kudos` sobre `kudos`, `band-*` sobre `band`; recalculado con `python3` sobre `index.css`):**

| Combinación | ink | ink-muted | accent | success | warning | danger | info | on-accent | on-kudos | line-strong | band-ink | band-muted | band-accent |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| azulejo-light | 16.44 | 7.06 | 7.45 | 6.13 | 6.34 | 6.13 | 6.02 | 7.99 | 9.48 | 3.35 | 9.43 | 6.85 | 9.43 |
| azulejo-dark | 15.17 | 7.61 | 7.58 | 8.09 | 9.15 | 7.45 | 8.23 | 8.42 | 9.48 | 3.99 | 13.71 | 8.31 | 8.22 |
| original-light | 16.37 | 7.14 | 5.40 | 6.67 | 6.37 | 6.15 | 6.04 | 5.77 | 9.40 | 3.58 | 16.37 | 11.42 | 9.40 |
| original-dark | 14.50 | 6.74 | 6.75 | 7.96 | 9.00 | 7.33 | 8.10 | 7.05 | 9.40 | 3.46 | 12.98 | 7.33 | 6.04 |
| taquilla-light | 15.52 | 6.60 | 6.10 | 6.55 | 6.25 | 6.04 | 5.93 | 6.64 | 9.09 | 3.33 | 9.42 | 6.69 | 10.18 |
| taquilla-dark | 14.56 | 7.49 | 9.51 | 9.51 | 9.00 | 7.33 | 8.10 | 10.53 | 9.09 | 3.98 | 10.87 | 6.97 | 8.27 |
| videoclub-light | 16.92 | 7.37 | 6.30 | 6.71 | 6.40 | 6.18 | 6.07 | 6.70 | 9.67 | 3.66 | 15.39 | 9.88 | 8.02 |
| videoclub-dark | 15.74 | 7.91 | 8.33 | 8.50 | 9.61 | 7.83 | 8.64 | 8.39 | 9.67 | 3.60 | 14.74 | 8.51 | 8.72 |

  Todo el texto sale por encima de 4,5, `line-strong` por encima de 3 y el foco (`accent` sobre `canvas`) por encima de 3. `band-line` es solo un separador decorativo (1,3–1,7): no se exige.
- **Cambios esperados de "se ve igual":** el azul de los botones y titulares pasa de `#2563eb` a `#2342C2` (un tono más profundo); `rounded-md` pasa de 6 a 8 px y `rounded-lg` de 8 a 12 px; el lienzo pasa de `#f8fafc` a `#F5F7FB`; el amarillo de los kudos es `#F2B33D` y no `#facc15`; el texto base del `body` es `ink` (`#0F1733`). En `azulejo-light` las capturas coinciden con `antes/` salvo eso: sin colores perdidos ni clases sin estilo. En oscuro y en las otras paletas, las clases `slate-*` y `bg-white` fijas siguen hasta las RC y dan fallos de contraste esperados (login, etiquetas, botón "Sig").
- **Discrepancia con el plan (R1):** `ringColor.DEFAULT` y `ringOffsetColor.DEFAULT` no pueden usar `rgb(var(--color-x) / <alpha-value>)`: Tailwind vuelca el valor por defecto tal cual en `--tw-ring-offset-color` (con `<alpha-value>` sin sustituir, inválido) y descarta el de `--tw-ring-color`, y el anillo de foco quedaba a 1:1 (496 fallos nuevos y "sin indicador de foco" en el logo). Se dejan `rgb(var(--color-accent) / 1)` y `rgb(var(--color-canvas))`, con un comentario en la configuración. Las utilidades `ring-accent` etc. siguen usando `<alpha-value>`.
- **Desviaciones:** (1) el plan lista 10 usos de `accent` pero `SectionHeader` monta `text-${highlightColor}` y 11 archivos del admin pasan `highlightColor="accent"`: ese `text-accent` ya no existía como literal y se habría quedado sin estilo (o cobalto). Se cambian a `"kudos"` para que sigan en amarillo. (2) La corrección del anillo de foco de la discrepancia anterior.
- **Falla antes:** no hay prueba automática (no hay suite de componentes). Comprobación manual: antes de tocar nada, `localStorage['kudos.theme']` no cambiaba las capturas (`--themes all` daba 8 imágenes iguales); ahora las 8 combinaciones cambian el fondo y el texto base.
- **Resultados:** `pnpm build`, `pnpm check:classes` (733 clases) y `pnpm test:seo` (45 pass) en verde; `dist/index.html` lleva el script en línea (`kudos.theme`) antes del `<script type="module">`. `visual-check.sh --themes all` sobre `/`, `/ranking`, `/login` y `/privacidad` (96 filas, 8 combinaciones × 4 rutas × 3 anchos): `jsErrors` y `consoleErrors` vacíos y `error: null` en todas, sin desbordamiento y un `h1` por ruta, igual que `antes/`. Salida 1 por los fallos de contraste en oscuro y en otras paletas (clases fijas, esperado hasta las RC) y por el foco de 1,42:1 de la línea base de RA-00 (igual que en `antes/`, 136 en 96 filas frente a 34 en 12), más 24 de 1,06:1 en las pestañas de `/login` en oscuro (`slate-*` fijo).

## RB-02 · Primitivas (FE)

**Depende:** RB-01

**Pasos:**
1. **`Button`:**
   - la prop `as`, para usarlo también como `Link`;
   - las variantes `primary`, `secondary`, `ghost`, `danger` y `link`, y los tamaños `md` y `lg`;
   - `focus-visible`, con el anillo de la maqueta;
   - sustituye a los unos 10 enlaces con aspecto de botón escritos a mano.
2. **Campos:** `Input`, `Textarea`, `Select`, `Checkbox` y `Radio`, con un solo estilo, el borde `line-strong` y la etiqueta asociada.
3. **Otras piezas:**
   - `Chip`, `KudosBadge` (la píldora dorada de la maqueta, con su `sr-only` "kudos" y la animación `bump`) y `ScoreStamp` (pequeño y grande, "Sin nota" incluido);
   - `Card` (dos variantes) y `PageHeader` (sustituye a `SectionHeader` y garantiza un `h1`);
   - `SectionHead` (titular serif, filete `rule` y "Ver todo");
   - `Tabs` y `Skeleton`.
4. **`FeedbackState`:** se revisa.
5. **Animaciones:** `components/animations/*` se reducen a `fade`, `fade-up` y la cascada de tarjetas, con `useReducedMotion`. Se borra el código muerto (`HoverFadeText`).

**Hecho cuando:**
- Las primitivas están en uso al menos en un sitio cada una.
- El script de RA-00 sobre la portada y la ficha en `--themes all` no empeora la línea base de RA-00.
- `pnpm build` y `pnpm check:classes` sin errores.

**Notas (RB-02):**
- **Archivos (todo bajo `Frontend/`):** `src/components/ui/` nuevos: `Input`, `Textarea`, `Select`, `ChoiceField` (interno), `Checkbox`, `Radio`, `fieldStyles.js`, `Chip`, `KudosBadge`, `ScoreStamp`, `Card`, `PageHeader`, `SectionHead` y `Tabs`; reescritos `Button` (`as` y variantes nuevas), `FeedbackState` (`titleAs`), `Skeleton`, y `InputField`/`TextAreaField` (reexportan `Input`/`Textarea`), `SelectFilter` (usa `Select`) y `SectionHeader` (solo un comentario de "en desuso"). Nuevo `src/lib/twMerge.js` (ver Desviaciones). `tailwind.config.js` (`animate-bump` y `animate-stamp`), `scripts/check-tailwind-classes.mjs` (`font-size` en `IGNORE`). Animaciones: borrados `HoverFadeText.jsx` y `ScaleFadeIn.jsx` (y `hoverFadeTextVariants`), `useReducedMotion` en `Fade`, `FadeUp`, `StaggerGrid`, `StaggerItem` y `KudosCounter`, y `MotionConfig reducedMotion="user"` en `AppProviders`. Usos: los 11 enlaces con aspecto de botón (`HeaderLogin` ×2, `CatalogImportPanel`, `WishlistPage`, `MyVotesEmpty`, `MyListEditPage`, `ListPage`, `AdminListDetail`, `MyListCard` ×2, `AdminListCard`), `CommentBox` (2 `Input` y `Checkbox`), `ListVisibilityField` (`Radio`), `AttributeChips` (`Chip`), `ProfileSummaryCard` (`KudosBadge`), `ItemDetailColumn` y `AdminItemCard` (`ScoreStamp`), `EditorialListCard`, `CategoryShortcuts` y la caja "Acerca de…" (`Card`), `HomePage` (`PageHeader` y `titleAs="h2"`), `HomeSection` (`SectionHead`), `AuthPage` y `ProfileTabs` (`Tabs`, modo navegación y modo `tablist`). Documentado en `docs/frontend/arquitectura.md` §1, §3 y §4. Sin tests nuevos (la SPA no tiene suite): se comprueba con el build, `check:classes`, `test:seo` y `visual-check.sh`; no se borra ningún test.
- **Decisiones del plan (validadas):** (1) `Button` tiene dos rutas. La **heredada** (hay `color`, `variant` ∈ `solid|soft|outline|tab|ring` o `size` ∉ `md|lg`) conserva `VARIANTS`/`SIZES`/`RADII` de `lib/buttonStyles.js`; la **nueva** usa `primary|secondary|ghost|danger|link` × `md|lg`. En las dos, foco `focus-visible:ring-2 ring-offset-2` en lugar de `focus:ring-blue-200` (el 1,42:1 de la línea base). (2) `danger` usa `text-canvas`, porque no hay token `on-danger`. (3) La maqueta mide 13 y 15 px: se ajusta a `text-caption` (12) y `text-small` (14) para no usar valores arbitrarios. (4) `SectionHeader` queda en desuso sin migrar (25 archivos). (5) Las animaciones se reducen de forma acotada: se borran `HoverFadeText` y `ScaleFadeIn` (sus 5 usos pasan a `FadeUp`) y las de función se conservan.
- **Pendiente de migrar (lista para RD-01).** Lo que sigue en el código a propósito, con la tarea que debe retirarlo:
  - **API antigua de `Button`** (`color`, `solid`/`soft`/`outline`/`tab`/`ring`, tamaños `sm` e `icon*`, `radius`) y `lib/buttonStyles.js`: 46 archivos con `color=` (recuento con `grep -rl 'color=' src | xargs grep -l '<Button'`). Cada RC pasa los de su página a las variantes nuevas: `ModalButtons`/`Modal` (RC-14); `Comment`, `CommentBox`, `VoteScoreLevels`, `VoteKnowStep`, `VoteInterestStep`, `VoteActionButtons`, `EmptyVoteState`, `ItemDetailColumn` (RC-03, RC-04); `Pagination` (RC-05); `lists/*`, `ListPage`, `MyListsPage`, `MyListEditPage` y `AddToListModal` (RC-07); `WishlistItemCard` y `Onboarding*` (RC-08); `profile/*`, `MyVote*`, `MyProposalItemCard`, `ProfilePrivacyPage` y `BackButton` (RC-09); `LoginForm`, `RegisterForm` y las páginas de `auth/` (RC-10); `DailyRewardModal` y `ReportMenu` (RC-14); `admin/*` y `pages/admin/*` (RC-13). Al terminar, RD-01 borra `lib/buttonStyles.js` y la ruta heredada, y `grep -rln "buttonStyles" src` debe quedar vacío.
  - **`SectionHeader`** (25 archivos con un uso, p. ej. `pages/*` y `pages/admin/*`; solo `HomePage` ya usa `PageHeader`). Cada RC lo sustituye por `PageHeader` (el `h1`) o `SectionHead` en su página; las de administración, en RC-13. RD-01 borra el archivo, y `grep -rl SectionHeader src` debe quedar vacío (también el `highlightColor`).
  - **Animaciones de función conservadas** (frenadas por `MotionConfig`, no por `useReducedMotion`): `AnimatedCard` (`MyListCard`: RC-07; las 8 tarjetas de `components/admin/`: RC-13), `AnimatedItem` (`VotePage` y `EmptyVoteState`: RC-04; `OnboardingRatingStep`: RC-08), `BouncyCard` (`ItemCard`: RC-01; `ActionButton`: RC-05), `InfiniteSlider` (`InfiniteItemSlider`, en `VotePage` y `CategoryPage`: RC-04 y RC-05), `ModalTransition` (`Modal`: RC-14), `MysteryBox` y `RewardReveal` (`DailyRewardModal`: RC-14), `PopButton` (`ScrollToTopButton`: RC-14), `SlideTransition` (`AuthPage`: RC-10) y los `motion.*` directos (`SwipeVoteCard` y `VotePage`: RC-04; `MyListEntryRow`/`MyListEditPage`/`MyListsPage`: RC-07; `PodiumItem`: RC-11; `pages/admin/*`: RC-13). Cada RC decide si la animación se queda (con `useReducedMotion` o cubierta por `MotionConfig`) o se borra. `components/animations/` queda con `Fade`, `FadeUp` y la cascada (`StaggerGrid`/`StaggerItem`) y `scaleFadeVariants` solo mientras lo use `AnimatedCard`; RD-01 comprueba que no queda ninguna otra.
- **Discrepancias con el plan (R1):** (1) El plan dice que los ~95 usos de `Button` no cambian, pero 6 llamadas sin `color` ni `variant` heredada (`ProfilePrivacyPage`, `MyListEditPage` "Guardar", `CatalogImportPanel` ×3 y `AddToListModal`) pasan a la ruta nueva: ahora son `primary`/`ghost` del sistema (mismo color de acento, 44 px de alto y radio 6 px). (2) `ItemDetailColumn`: el `h3` y el `p` de "Acerca de…" pasan de `text-slate-*` a `text-ink`; con `Card` (`bg-surface`) y el texto fijo, el modo oscuro daba 1,16:1.
- **Desviaciones:** (1) **`lib/twMerge.js`:** `tailwind-merge` de serie no conoce la escala propia y toma `text-small`, `text-caption` o `text-body` por colores de texto: `twMerge('text-on-accent text-small')` se quedaba solo con `text-small` y "Regístrate" salía sin color (contraste 2,21:1 en el primer `visual-check`). Se crea `extendTailwindMerge` con la escala (`display`, `h1`, `h2`, `h3`, `body`, `small`, `caption`) y las primitivas nuevas y `Button` importan `twMerge` de ahí. El resto de archivos, que no usan la escala propia, siguen con el de serie. (2) `check:classes` extraía `font-size` (el nombre del grupo) como si fuera una clase: se añade a `IGNORE` con un comentario. (3) `CommentBox` pasa `registration={register('content')}` en lugar de `{...register('content')}`: `Input` es un componente de función (React 18) y un `ref` por `props` no llega al `<input>`. (4) `StaggerGrid` reenvía `...props` (para el `role="status"` de `FeedbackState` al cargar). (5) Pequeños añadidos para no cambiar el aspecto: `className="self-start"` en "Ver lista completa", `whitespace-nowrap` en el enlace de `CatalogImportPanel`, `pr-8` en `Select` (flecha nativa) y `ScoreStamp lg` sin sello ni animación cuando no hay nota.
- **Falla antes:** no hay prueba automática. Comprobación con `visual-check.sh` (`antes/` capturado antes de tocar nada): los fallos de foco de 1,42:1 eran 7 en la portada y 10 en la ficha (suma de 3 anchos, en cada paleta clara) y ahora son 0; coinciden con los de `HeaderLogin` (`focus:ring-blue-200`) y los otros `Button`.
- **Resultados:** `pnpm build`, `pnpm check:classes` (790 clases) y `pnpm test:seo` (45 pass) en verde; `grep -rn 'HoverFadeText\|ScaleFadeIn\|hoverFadeTextVariants' src` vacío y `git diff rediseno-fase-0 -- src | grep '^+' | grep -c -- '-\['` = 0. `visual-check.sh --themes all` sobre `/` y la ficha de "Street Fighter" (48 filas, 8 combinaciones × 2 rutas × 3 anchos): `jsErrors` y `consoleErrors` vacíos, `error: null`, sin desbordamiento y `h1Count` 1 en todas. Salida 1 por los 12 casos de la ficha en oscuro (33 fallos de contraste por 3 anchos y paleta, de clases `slate-*` fijas hasta RC-03; igual que en `antes/`). Fila a fila no sube ningún fallo de contraste ni de foco. Frente a RA-00 en `azulejo-light`, contraste 0 = 0 y foco 3/2/2 (portada) y 4/3/3 (ficha) → 0/0/0.

| Ruta (por paleta) | Modo | Contraste antes → ahora | Foco antes → ahora |
|---|---|---|---|
| `/` | claro | 0 → 0 | 7 → 0 |
| `/` | oscuro | 0 → 0 | 0 → 0 |
| ficha | claro | 0 → 0 | 10 → 0 |
| ficha | oscuro | 33 → 33 | 0 → 0 |

  (Sumas de los 3 anchos; las 4 paletas dan lo mismo.) `/login` y `/register` (las usa `Tabs` y los campos) pasan sin errores ni fallos.
- **Pruebas manuales:** el ejecutor no tiene navegador interactivo, así que **no** se han hecho a mano: foco con Tab en "Entrar" y "Regístrate" y en una categoría, ←/→ y Tab en `Tabs` del perfil y en `/login` ↔ `/register`, clic y espacio en el checkbox de reseña y en los radios de visibilidad, y `prefers-reduced-motion` en DevTools (portada y sello). Sí está comprobado por script: el foco de los enlaces y botones de ambas rutas (0 fallos), que `/login` y `/register` pintan las pestañas y los campos y que `reduce` solo cambia `initial`. Las cuatro pruebas interactivas quedan para el verificador.

## RB-03 · Cabecera, navegación, pie y selector de tema (FE)

**Depende:** RB-02

**Pasos:**
1. **`MainHeader`, como en la maqueta:**
   - el logotipo redibujado: un sello de doble filete con la estrella dorada;
   - el nombre "Kudos";
   - el conmutador de modo, con la transición en círculo (View Transitions con *fallback*, sin ella con `prefers-reduced-motion`);
   - la campana;
   - la píldora de usuario con `KudosBadge` y la posición, visible en todos los anchos y con 12 px como mínimo.
2. **`NavBar`:** texto siempre visible desde `lg`; "Administración" pasa al menú de usuario.
3. **Menú de usuario:**
   - un grupo **"Cambiar tema"** con `menuitemradio`: una muestra de color y el nombre de cada paleta, y debajo el modo (claro, oscuro o sistema);
   - se maneja con el teclado (flechas, Intro y Esc).
4. **`NavBarMobile`:** un panel que atrapa el foco e incluye el mismo selector de tema. Sin sesión, el selector va también en el pie.
5. **`Footer`:** como la maqueta. Las atribuciones de TMDB, IGDB y OSM quedan intactas y legibles en los dos modos.
6. **Anchos de página por tipo:** lectura, contenido y panel.

**Hecho cuando:**
- El script de RA-00 en `--themes all` pasa sin fallos en la cabecera y el pie, en todas las rutas públicas a 360 y 1440 px.
- Cambiar de tema desde el menú se aplica al instante y se conserva al recargar.
- El selector se maneja solo con el teclado.
- `pnpm build` y `pnpm check:classes` sin errores.

**Notas (RB-03):**
- **Archivos (todo bajo `Frontend/`):** nuevos en `src/components/layout/`: `headerStyles.js` (`FOCUS_RING`, `ICON_BUTTON`, `MENU_ITEM`, `NAV_ACTIVE`), `ThemePicker`, `ThemeToggle`, `LogoMark` y `UserMenu`; en `src/hooks/common/`: `themeTransition.js`, `useNavItems.js` y `useFocusTrap.js`. Reescritos: `MainHeader`, `NavBar` (solo escritorio), `NavBarMobile` (el ☰ y el panel, con el estado, `useDismiss` y `useFocusTrap`) y `Footer`. Retocados: `useTheme.js` (`isDarkFor` y `isDark`; vuelve a pintar al cambiar el sistema), `index.css` (la transición de vista), `NotificationBell` (`ICON_BUTTON`, contador a 12 px, panel a `top-16`), `GlobalSearch` (borde `line-strong` y `FOCUS_RING`), `App.jsx` (`pageWidthFor`) y `tailwind.config.js` (`maxWidth` y `h-page-content`). Borrados: `profile/UserInfo.jsx`, `auth/LogoutButton.jsx` y `animations/KudosCounter.jsx` (sin otros usos, comprobado con `grep -rn`). Documentación: `docs/frontend/arquitectura.md` (§1, §3 y §4).
- **Tests nuevos:** ninguno de componentes (la SPA no tiene suite; R8). Script `.claude/ciclo/RB-03/tema-check.mjs` (Playwright en la imagen de Docker, 12 comprobaciones con sesión y sin ella): menú con teclado, paleta, modo, Escape, persistencia, transición (una llamada a `startViewTransition` y sin `data-vt` al final), `reduce` (0 llamadas), trampa de foco del ☰ (con el modo `light`, para que el último radio no sea el activo), radios del panel y del pie, el panel del ☰ en un móvil apaisado (640×360, con «Cerrar sesión» al alcance al desplazarlo) y los textos de cabecera y pie para el filtro de contraste. Vuelve a pasar `RA-01/menu-check.mjs`: 7 OK.
- **Decisiones del plan (validadas):** (1) «Cambiar tema» siempre visible en el menú de usuario, que no se cierra al elegir (`menuitemradio`). (2) La muestra de cada paleta es un degradado de su lienzo y su acento claros, en línea. (3) Anchos de página: `max-w-reading` 48 rem (`/privacidad` y `/aviso-legal`), `max-w-content` 75 rem (el resto, y la cabecera y el pie) y `max-w-panel` 80 rem (`/admin…`, el ancho de antes). (4) «Mis estadísticas» no entra en el menú: no tiene ruta (es una pestaña de `/profile`). (5) «Administración» sale de la navegación de escritorio y solo está en el menú de usuario y en el ☰. (6) El pie dice «Kudos», en lugar de «Proyecto Kudos».
- **Discrepancias con el plan (R1):** (1) El pie no tenía atribución de OSM (está bajo el mapa de la ficha): TMDB e IGDB se quedan con el mismo texto. (2) `h-page-content` pasa de `calc(100dvh - 9rem)` a `calc(100dvh - 8rem)` (cabecera de 64 px en lugar de 80). (3) El selector de tema de `ThemePicker` en el menú agrupa los modos en una rejilla de 3 columnas con el icono sobre el nombre (a 12 px), para que quepan en los 256 px del panel.
- **Desviaciones:** (1) `useFocusTrap` excluye también los `button`, `a` e `input` con `tabindex="-1"`. El selector literal del plan (`button:not([disabled])`) incluía los radios no activos del `ThemePicker` (que llevan `tabIndex={-1}`): con el modo `light`, el último enfocable por Tab es «Claro» y la trampa miraba «Sistema», con lo que el foco se escapaba del panel. (2) El panel del ☰ usa `max-h-below-header` (`calc(100dvh - 4rem)`) en lugar de `max-h-dvh`, porque con `top-full` los últimos 64 px quedaban fuera de la ventana y, a 640×360, «Cerrar sesión» no se podía ver.
- **Falla antes:** no hay prueba previa automática. En `HEAD` no existe nada de lo que `tema-check.mjs` busca en la portada: `git grep -c 'aria-haspopup\|startViewTransition\|role="radiogroup"\|role="radio"' HEAD -- Frontend/src` solo da `ReportMenu.jsx` (que no está en la cabecera ni en el pie), así que las pruebas 1, 2, 3, 4, 6, 8, 9 y 10 no podían pasar; con el cambio, las 12 pasan (la 12 dio `FALLO` con `max-h-dvh`: `lastBottom` 402,6 con una ventana de 360).
- **Resultados:** `pnpm build`, `pnpm check:classes` (789 clases) y `pnpm test:seo` (45 pass) en verde; `git diff rediseno-fase-0 -- src tailwind.config.js | grep '^+' | grep -c -- '-\['` = 0. `visual-check.sh --themes all`, rutas públicas a 360 y 1440: sin errores de JS, y los de consola y los `h1` iguales que en la línea base de RA-00 (404 de `/listas/no-existe` y `/u/no-existe`, y 0 `h1` en `/buscar` y `/videojuegos/explore`, de sus RC); sin desbordamiento. Con sesión (`/` y `/ranking`, 360 a 1440, 8 temas): 0 errores, 0 desbordes y 1 `h1` en todas las filas; 0 fallos de foco en la cabecera (los 8 de las públicas son del mapa de Leaflet en `/destinos/item/…`). Anchos 768 y 1024 sin sesión: sin desbordamiento. El filtro `jq` da `[]` en `publicas`; en `auth` da 32 coincidencias de «#» y «1» en `/ranking` en oscuro, que no son de la cabecera sino del podio (`div.text-3xl.font-black.text-kudos`, de la RC del ranking): el «#1» de la píldora cumple (el filtro compara solo el texto).
- **Pendiente (lista para RD-01):** `LogoKudos` se queda solo para `MysteryBox` y sale con él en RC-14. Ya no existen `NavBar` con la API antigua de `Button`, `LogoutButton`, `UserInfo` ni `KudosCounter`. Capturas en `.claude/ciclo/RB-03/capturas/` (ignorada por git).

---

## RC-01 · Tarjeta de ítem (FE)

**Depende:** RB-03

**Pasos:**
1. `ItemCard`, `ItemPlaceholder`, `AttributeChips` y `AddToListButton`, como en la maqueta:
   - el póster 2:3 con un filete de 1 px;
   - el título debajo, en 2 líneas como máximo, sin tapar la imagen;
   - el icono de categoría, el año y un chip;
   - el sello de nota y el botón de lista.
2. **Interacción:** al pasar por encima, el póster se levanta con una sombra del color del acento. La tarjeta tiene un solo enlace, y el botón de lista va fuera de él.
3. **`view-transition-name`:** se pone en el póster solo mientras navega a su ficha (ver RC-03).

**Hecho cuando:** el script de RA-00 en `--themes all` sobre la portada y `/peliculas/explore` no da fallos en las tarjetas, y las capturas coinciden con la maqueta.

**Notas (RC-01):**
- **Archivos (todo bajo `Frontend/`):** `tailwind.config.js` (`aspect-poster`, `ease-soft`, `duration-250`, `underline-offset-3` y la animación `rise`, con el retraso en `--i`), `src/components/items/ItemCard.jsx` (reescrito: un solo enlace con póster y título; meta, sello y botón fuera; prop `index`; `view-transition-name: poster` solo en el póster pulsado), `items/AttributeChips.jsx` (la variante `card` pinta el icono de la categoría, el primer valor y un chip con el segundo; nueva prop `category`; sin `CARD_MAX_VALUES`), `ui/ItemPlaceholder.jsx` (prop `decorative`; iniciales en serif), `lists/AddToListButton.jsx` (la variante `card` es un `<button>` nativo de 32 px, sin `preventDefault` ni `stopPropagation`) y `home/HomeItemRow.jsx` (pasa `index`). Documentación: `docs/frontend/arquitectura.md` (§1, §3 y §4).
- **Tests nuevos:** ninguno de componentes (la SPA no tiene suite; R8). Script `.claude/ciclo/RC-01/tarjeta-check.mjs` (Playwright en la imagen de Docker, 8 comprobaciones): un enlace por tarjeta y sin botón dentro, el botón de lista fuera del enlace (con sesión), el nombre del enlace igual al título (`alt=""`, sin `role="img"`), sombra del acento y desplazamiento del póster al pasar por encima, cascada (`rise` y `--i` = 1; `none` con `reduce`), `view-transition-name` solo en el póster pulsado (y en ninguno con Control) y los textos de las tarjetas para el filtro de contraste.
- **Decisiones del plan (validadas):** (1) El título de la tarjeta es un `span` y deja de ser `h3`. (2) El `aria-label` del botón pasa a «Añadir «X» a una lista». (3) Las iniciales del *placeholder* pasan a serif, también en las miniaturas de búsqueda, votos y wishlist.
- **Discrepancias con el plan (R1):** ninguna.
- **Desviaciones:** el `article` lleva `relative`, que el plan no preveía. Con `prefers-reduced-motion` no hay animación y los `sr-only` de la tarjeta (el nombre de la categoría y el texto del sello), que son `position: absolute`, dejaban de tener a la tarjeta como bloque contenedor: se colocaban fuera de la fila con *scroll* y `/` desbordaba (`scrollWidth` 2285 con 1440 de ventana). Lo detectó `visual-check.sh` (que captura con `reduce`) y con `relative` desaparece.
- **Falla antes:** no hay prueba previa automática. En `HEAD` no existe `data-item-card` (`git grep -c data-item-card HEAD -- Frontend/src` vacío), así que ninguna de las 8 podía pasar antes del cambio; con el cambio, las 8 pasan.
- **Resultados:** `pnpm build`, `pnpm check:classes` (788 clases) y `pnpm test:seo` (45 pass) en verde; 0 clases arbitrarias nuevas. `visual-check.sh --themes all` sobre `/` y `/peliculas/explore`, con sesión y sin ella: 0 errores de JS y de consola, sin desbordamiento, y los dos filtros `jq` (contraste de los textos de las tarjetas y foco de `a.group.block.rounded-md` y `button.inline-grid.h-8`) dan `[]`. Los fallos que quedan no son de las tarjetas: `/peliculas/explore` sin `h1` y con filtros en `slate-*` (RC-05) y, con sesión, los `h3` `text-slate-800` de las filas de «Para ti» de `/` (RC-02). Las capturas de `/` coinciden con la maqueta (póster 2:3 con filete, título en 2 líneas, icono, año y chip, sello y botón de lista).
- **Pendiente:** `BouncyCard` solo queda en `ActionButton` (RC-05). `ExplorePage` sigue con `StaggerItem` además de `rise`: RC-05 debe quitarlo y pasar `index`. RC-03 debe evitar el doble `poster` al abrir una ficha desde Similares. Capturas en `.claude/ciclo/RC-01/capturas/` (ignorada por git).

## RC-02 · Portada (FE)

**Depende:** RC-01

**Pasos:**
1. `HomeSection` y `HomeItemRow`, con flechas, una pista de desplazamiento y *scroll snap*.
2. `CategoryShortcuts` y `EditorialListCard`, este con collage.
3. El bloque **ranking de la semana** como banda (`band-*`), con la firma decorativa de cada paleta: cenefa, troquel o franjas. "Original" no lleva firma.
4. La cascada de las tarjetas al entrar.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre `/` pasa, con sesión y sin ella, y las capturas coinciden con `a-azulejo-home-*`.

## RC-03 · Ficha (FE)

**Depende:** RC-01 · **Cierra:** F1, F2

**Pasos:**
1. **`ItemDetailPage` y `ItemDetailColumn`:**
   - dos columnas: el póster *sticky* a la izquierda y el texto a la derecha;
   - sin altura fija ni *scroll* interno;
   - el `h1` serif de la maqueta, la tabla de datos, el sello grande con la animación "estampar" y las barras de distribución que crecen.
2. **`ItemImage`:** usa `banner` o la variante grande cuando existe, no `thumb` ampliada.
3. **Comentarios y mapa:** `CommentBox`/`Comment` y `ItemPlaceMap` con el estilo de la maqueta. La columna de comentarios vacía ya no ocupa toda la altura.
4. **Transición del póster:** `Link` con `viewTransition` (React Router 7) desde la tarjeta, y CSS `::view-transition-*` como en la maqueta.
5. **Atribuciones:** `ItemImageCredit` e `ItemAttribution` quedan intactas.

**Hecho cuando:**
- El script de RA-00 en `--themes all` sobre la ficha de una película, un juego y un destino pasa.
- A 1440 px no hay *scroll* interno y el póster se ve entero.
- La transición funciona en Chromium y no rompe nada sin soporte.

## RC-04 · Votar (FE)

**Depende:** RC-03 · **Cierra:** F3

**Pasos:**
1. `VotePage`, `VoteKnowStep`, `VoteScoreLevels`, `VoteInterestStep`, `SwipeVoteCard` y `EmptyVoteState`, como en la maqueta:
   - el póster a un lado;
   - el `h1` con el título del ítem;
   - la pregunta en su caja, nunca encima de la imagen;
   - los niveles con puntos y atajos;
   - un solo "Volver".
2. Al votar, el `KudosBadge` de la cabecera da un salto y sale un "+N" flotando.

**Hecho cuando:**
- El script de RA-00 en `--themes all` sobre `/peliculas/vote` pasa.
- Los atajos S, N, 1–5 y Esc funcionan (comprobado con Playwright, sin enviar votos reales a producción).

## RC-05 · Categoría y Explorar (FE)

**Depende:** RC-01

**Pasos:**
1. `CategoryPage` sin los bloques de degradado. `ActionMenu`/`ActionButton` como tarjetas sobrias.
2. `InfiniteItemSlider` sin cortar tarjetas.
3. `ui/ranking/*`: el podio de la maqueta (el primero en dorado y grande), sin los degradados de oro, plata y bronce. Se limpian las sombras de `constants.js`.
4. `ExplorePage`, sin el relleno duplicado ni los desplazamientos laterales.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre `/peliculas` y `/videojuegos/explore` pasa.

## RC-06 · Buscar y buscador global (FE)

**Depende:** RC-01, RA-02

**Pasos:** `SearchPage`, `GlobalSearch` (el *combobox* se mantiene, con su teclado y su `aria-activedescendant`) y `CatalogImportPanel`, con las primitivas.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre `/buscar?q=star`, con sesión y sin ella, pasa, y el buscador de la cabecera se maneja con ↑, ↓, Intro y Esc.

## RC-07 · Listas (FE)

**Depende:** RC-01

**Pasos:** `ListPage`, `MyListsPage`, `MyListEditPage`, `lists/*` y `AddToListModal`, con las tarjetas de lista de la maqueta (collage y meta) y las primitivas.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre `/mis-listas`, una lista propia y una pública pasa (crea los datos en local si faltan, sin tocar producción).

## RC-08 · Wishlist y onboarding (FE)

**Depende:** RC-01

**Pasos:** `WishlistPage`, `WishlistItemCard`, `OnboardingPage` y `onboarding/*`.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre `/wishlist` y `/onboarding` pasa.

## RC-09 · Perfil y perfil público (FE)

**Depende:** RC-01

**Pasos:**
1. `ProfilePage` con `Tabs` y sus pestañas, `profile/*`, `votes/MyVote*`, `proposals/*` y `/u/:username`.
2. Una sola tarjeta de identidad.
3. Las fechas con `Intl` en español.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre `/profile` (cada pestaña) y un perfil público pasa.

## RC-10 · Autenticación (FE)

**Depende:** RB-03

**Pasos:**
1. `AuthPage`, `LoginForm`, `RegisterForm`, Forgot, Reset y Verify.
2. Un panel sobrio sin manchas ni icono de mando, y un solo estilo de pestañas (`Tabs`).
3. Los requisitos legales del registro se quedan intactos.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre `/login`, `/register` y `/forgot-password`, sin sesión, pasa.

## RC-11 · Ranking global (FE)

**Depende:** RC-05

**Pasos:** `RankingPage` y `UserRanking*` con el podio de RC-05. La paginación va dentro del listado.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre `/ranking` pasa.

## RC-12 · Legal, 404, Forbidden y error (FE)

**Depende:** RB-03

**Pasos:** `LegalPageLayout`, con el ancho de lectura y el `h1` de `PageHeader`; y `ErrorPage`, `ForbiddenPage` y `AppErrorBoundary`, con jerarquía y una acción para volver.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre `/privacidad`, `/aviso-legal`, `/404` y `/forbidden` pasa.

## RC-13 · Administración (FE)

**Depende:** RB-03

**Pasos:**
1. `AdminLayout`, `AdminNav`, `admin/*` y `admin/modals/*`.
2. Primitivas nuevas: `DataTable` (cabecera fija, filas con filete y acciones con texto o con `aria-label`, nunca solo un color) y `FilterBar`.
3. El amarillo deja de usarse como color de texto. Las etiquetas pasan a 12 px como mínimo.

**Hecho cuando:** el script de RA-00 en `--themes all` sobre las 8 rutas `/admin/*` pasa.

## RC-14 · Piezas globales (FE)

**Depende:** RB-03

**Pasos:**
1. `DailyRewardModal`, `MysteryBox` y `RewardReveal`, con el dorado de los kudos.
2. `NotificationBell`, `ReportMenu`/`ReportModal` y los *toasts* de `react-hot-toast`, con los tokens de los dos modos.
3. Un foco visible en todos.

**Hecho cuando:** el script de RA-00 en `--themes all` pasa con cada pieza abierta. Las capturas se hacen abriéndolas con Playwright.

## RC-15 · Textos (FE, BE)

**Depende:** RC-01…RC-14

**Pasos:**
1. Title Case → mayúscula solo inicial en toda la interfaz ("Iniciar sesión", "Guardar cambios", "Mi perfil").
2. Fuera los restos de Kudos 1.0: "Bienvenido a…", "Valora candidatos", el icono de mando en el login.
3. Un subtítulo de portada que cubra las cuatro categorías.
4. Las descripciones de categoría en el *seeder*, con Sail; llegan a producción con el *reset* de F5-16.
5. La primera frase de `README.md`.
6. El nombre visible "Kudos" en el `<title>` y en `MyStatsPage`.

**Hecho cuando:**
- `grep` de los textos antiguos no da resultados.
- `sail test` pasa entero si se toca el *seeder*.
- `pnpm build` sin errores.

---

## RD-01 · Revisión final (QA, FE, DOCS)

**Depende:** RC-15

**Pasos:**
1. Script de RA-00 en `--themes all` sobre todas las rutas de la auditoría (§2) a 360, 768 y 1440 px, con sesión y sin ella.
2. Capturas completas con Azulejo en claro y en oscuro, comparadas lado a lado con las de la auditoría.
3. Comprobación con `prefers-reduced-motion`.
4. `grep` de colores de paleta de Tailwind (`slate-`, `blue-`…) y de valores arbitrarios `-[`: el objetivo es 0, salvo casos justificados en las **Notas**.
5. Borra los alias de tokens antiguos de RB-01.
6. Actualiza `docs/frontend/arquitectura.md` §3–4 y [../redesign/plan.md](../redesign/plan.md) con lo hecho. Las capturas finales se guardan en `docs/redesign/capturas/final/` en WebP.

**Hecho cuando:**
- El script pasa sin fallos en todas las rutas y combinaciones.
- No quedan alias.
- `pnpm build` y `pnpm check:classes` sin errores.
- La documentación está al día.
