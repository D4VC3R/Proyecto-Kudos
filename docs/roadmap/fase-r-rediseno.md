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

## RA-03 · Categoría inexistente sin consultas inútiles (FE)

**Depende:** — · **Cierra:** F5

**Pasos:** en `hooks/pages/useCategoryPage.js`, las consultas de ítems y de ranking se activan (`enabled`) solo cuando la categoría existe. Mientras carga, se ve un *skeleton*; si no existe, solo "Categoría no encontrada".

**Hecho cuando:** `/no-existe-xyz` no muestra "catálogo de undefined" en ningún momento y no lanza la petición `/api/items?category_slug=…` (comprobado con el registro de red del script de RA-00 o con Playwright).

## RA-04 · Pluralización y nota vacía (FE)

**Depende:** — · **Cierra:** F8

**Pasos:**
1. En `lib/formatters.js`, añade `formatVotes(n)` ("1 voto", "2 votos", "Sin votos") y `formatScore(avg)` (que devuelve `null` si no hay votos).
2. Úsalas en `ItemDetailColumn.jsx`, en la tarjeta y en los demás sitios que pinten la nota o los votos.
3. Sin votos, todos muestran "Sin nota", como en la maqueta, en lugar de "0.0" o "-.-".

**Hecho cuando:** una ficha sin votos y otra con 1 voto se ven bien en la ficha y en la tarjeta, y `pnpm build` no da errores.

## RA-05 · Posición del ranking con empates (BE o FE)

**Depende:** — · **Cierra:** F10

**Pasos:**
1. Investiga por qué la cabecera muestra #3 y luego #1 cuando `/ranking` muestra #3 con empate de kudos. Compara `ranking_position` (backend) con el orden del ranking y con `UserInfo`.
2. Corrige la fuente para que la cabecera y `/ranking` usen el mismo criterio de desempate.
3. Si es backend: con Sail, un test sobre PostgreSQL que reproduce el empate, y el contrato solo se amplía (R7).

**Hecho cuando:** con dos usuarios empatados, la cabecera y `/ranking` muestran la misma posición, y el test del caso pasa (`sail test --filter`) junto con la suite completa.

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
