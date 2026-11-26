# Rediseño visual: plan y decisiones

Este documento recoge el plan por fases del rediseño visual del frontend y las respuestas del humano a las preguntas de la auditoría. El estado de partida está en [auditoria.md](auditoria.md) y las propuestas de estilo en [direcciones.md](direcciones.md).

**Restricciones del rediseño:**
- No cambia rutas ni funcionalidad, salvo para corregir fallos.
- No añade dependencias pesadas sin proponerlas antes.
- Mantiene o mejora la accesibilidad: contraste AA, foco visible y navegación con teclado.
- Los textos sí cambian (decisión 2).

## 1. Decisiones del humano

| # | Pregunta | Respuesta | Qué implica |
|---|---|---|---|
| 1 | Marca: ¿se puede tocar el logo y el nombre? | **Solo "Kudos"** | El nombre visible pasa a ser "Kudos" en `MainHeader`, `Footer`, el `<title>` de `index.html` y `MyStatsPage`. El repositorio y los enlaces a GitHub (`Proyecto-Kudos`) no cambian. Se interpreta que el logotipo se puede redibujar para encajar con la dirección elegida, conservando la estrella. |
| 2 | Textos de Kudos 1.0 y Title Case | **Se pueden corregir** | Hay una tarea de textos: mayúscula solo inicial ("Iniciar sesión"), fuera los restos de 1.0 ("Bienvenido a…", "Valora candidatos", el icono de mando) y un subtítulo de portada que cubra las cuatro categorías. Las descripciones de categoría viven en la base de datos: se cambian en el *seeder*, y llegan a producción con el *reset* de la F5-16. También se corrige la primera frase de `README.md`. |
| 3 | Modo oscuro | **Se incluye** | Tokens claros y oscuros desde RB-1, conmutador en la cabecera y un *script* en `index.html` que aplica el tema antes de pintar para evitar el parpadeo. Cada página se verifica en los dos modos. Se actualizan `vision.md` y `arquitectura.md` §3. |
| 4 | Admin | **Rediseño completo** | La página 13 pasa a esfuerzo L, con sus propias primitivas de tabla y de filtros. |
| 5 | Kudos y ranking | **Se mantienen destacados** | Los kudos tienen un color propio y un componente `KudosBadge`. El contador de la cabecera queda visible en todos los anchos, a 12 px como mínimo. El podio se rediseña sin perder protagonismo. |
| 6 | Pie y fechas | **El pie se queda; no hay fecha de entrega** | El autor, "Desarrollo de Aplicaciones Web · IES Paco Mollà", GitHub y las atribuciones se mantienen tal cual. El orden lo marca la calidad, no un plazo. |

## 2. Flujo de trabajo

**Ramas, issues y PR:**
- Cada tarea trabaja en una rama `rediseno/RX-YY-…` creada desde `rediseno-fase-0`.
- Cada tarea tiene su issue, con la etiqueta `rediseno`.
- Cada tarea entra por PR contra `rediseno-fase-0` ([../guia-agentes.md](../guia-agentes.md) §4.1 y §4.2).
- Así, la fusión en `main` de la F5-16 incluye el rediseño.

**Tablero:**
- Justo después de R0, un PR añade la **Fase R: rediseño visual** al tablero, con sus fichas en `docs/roadmap/fase-r-rediseno.md` y sus issues.
- Con eso, `/ciclo-tareas` puede ejecutarla con su planificador, ejecutor, verificador e integrador.
- Las tareas de las fases b) y c) dependen de R1, que es una decisión del humano.

**Verificación de cada tarea:**
- `pnpm build` y `pnpm check:classes`.
- Capturas de Playwright a 360, 768 y 1440 px, en claro y en oscuro, comparadas con las de [capturas/](capturas/).
- El barrido de contraste y el recorrido con Tab de la auditoría. Se guardan como *script* reutilizable que no añade dependencias, porque usa el contenedor del MCP de Playwright.

**Aviso de entorno:** después de tocar `tailwind.config.js`, reinicia el contenedor de Vite ([auditoria.md](auditoria.md), "Incidencia de entorno").

## 3. Plan por fases

Esfuerzo: **S** ≈ medio día · **M** ≈ 1–2 días · **L** ≈ 3 días o más.

### R0: guardar la auditoría (este PR)

Guarda en este directorio `auditoria.md`, `direcciones.md`, `plan.md` y las 113 capturas, convertidas a WebP (34 MB → 6,4 MB). También anota en `vision.md` y `arquitectura.md` que el modo oscuro entra en el rediseño.

### R1: maquetas (HUMANO elige)

- **Qué se hace:** HTML estático de las tres direcciones en `docs/redesign/mockups/`, con la portada, la ficha y votar, en móvil y escritorio, en claro y oscuro. Solo usa CSS y las fuentes en local; no toca la aplicación.
- **Qué decide el humano:** elige una dirección o una mezcla.
- **Esfuerzo:** M.
- **Paralelizable:** sí, con un subagente por dirección.
- **Estado:** hechas, en [mockups/index.html](mockups/index.html), con 36 capturas en [mockups/capturas/](mockups/capturas/).
- **Cómo abrirlas:** sírvelas por HTTP, porque Chrome bloquea las fuentes locales con `file://`. Ejecuta `python3 -m http.server 8766` dentro de `docs/redesign/mockups/` y abre `http://localhost:8766/`.
- **Qué se ha verificado con Playwright:**
  - Ningún error en consola.
  - Ningún desbordamiento horizontal de 360 a 1440 px.
  - Un `h1` por pantalla.
  - Contraste AA del texto en los dos modos.
  - Foco visible con Tab y "Saltar al contenido".
  - Los atajos de votar (S, N, 1–5 y Esc).
- **Datos inventados en las maquetas:** los otros dos usuarios del ranking de la semana y sus kudos.

### a) Fallos funcionales

Van separados del rediseño, en ramas `rediseno/RA-…`. No dependen de R1 y pueden empezar en cuanto exista el tablero.

| Tarea | Fallos | Archivos | Esfuerzo |
|---|---|---|---|
| RA-1 | F4 y F9: menú móvil accesible y "Saltar al contenido" | `NavBar`, `NavBarMobile` y `App`; reutiliza `hooks/common/useDismiss` | S |
| RA-2 | F6 y F7: filtros con borde y etiquetas visibles | `SearchFilter`, `SelectFilter` y `CatalogImportPanel` | S |
| RA-3 | F5: no pedir ítems ni ranking hasta que exista la categoría | `hooks/pages/useCategoryPage.js` | S |
| RA-4 | F8: pluralización y nota vacía | `ItemDetailColumn` y una función nueva en `lib/formatters.js` | S |
| RA-5 | F10: investigar la posición en caso de empate | `UserInfo` o el backend (con Sail y tests sobre PostgreSQL) | S–M |

- F1, F2 y F3 son de *layout*: se corrigen dentro del rediseño de la ficha y de votar (páginas 3 y 4), para no hacer el trabajo dos veces.
- **Paralelizable:** RA-1 a RA-4 no comparten archivos, así que pueden ir a la vez, con subagentes en *worktrees*.

### b) Sistema de diseño

Va en serie, sin paralelizar, porque todo lo demás depende de esto.

**RB-1, tokens y tema (M).** Cambia `tailwind.config.js`, `src/index.css` e `index.html`.
- Colores semánticos como canales RGB, en claro (`:root`) y en oscuro (`.dark`): `canvas`, `surface`, `ink`, `ink-muted`, `line`, `line-strong`, `accent`, `accent-ink`, `success`, `warning`, `danger`, `info`, `kudos` y `cat-*`.
- Escala tipográfica: `display`, `h1`–`h3`, `body`, `small` y `caption`, este último de 12 px como mínimo.
- Radios `sm` (6), `md` (8), `lg` (12) y `full`; dos sombras; un `container` único; y el `ring` de foco.
- Fuentes de `@fontsource`. Es una dependencia ligera y se propone en el PR.
- Conmutador de tema con `useTheme`, que arranca con `prefers-color-scheme` y guarda la elección en `localStorage` dentro de un `try/catch`. Va acompañado del *script* que evita el parpadeo.
- Bloque `prefers-reduced-motion`.
- Los nombres de token actuales se mantienen como alias, para que nada se rompa mientras dura la migración, y se borra `logo.*`.
- Actualiza `docs/legal/cumplimiento.md` si cambia la fuente autoalojada declarada.

**RB-2, primitivas (M).**
- `Button` con la prop `as`, para usarlo también como `Link`, y las variantes `primary`, `secondary`, `ghost`, `danger` y `link`, con `focus-visible`. Así desaparecen los ~10 enlaces con aspecto de botón escritos a mano.
- `Input`, `Textarea`, `Select`, `Checkbox` y `Radio`, todos con un mismo estilo.
- `Badge`/`Chip`, `KudosBadge` y `Card` (dos variantes).
- `PageHeader`, que sustituye a `SectionHeader` y garantiza un `h1` por página.
- `Tabs`, para el perfil, el admin y el login.
- `Skeleton`, y una revisión de `FeedbackState`.
- Animaciones reducidas a `fade` y `fade-up`, con `useReducedMotion` en `components/animations/*`.

**RB-3, layout y marca (M).**
- `MainHeader`, con el logotipo redibujado, el nombre "Kudos", el conmutador de tema y el contador de kudos.
- `NavBar`, con texto siempre visible desde `lg` y las opciones secundarias en un menú de usuario.
- `NavBarMobile`, con un panel que atrapa el foco.
- `Footer`, con las atribuciones intactas y legibles en los dos modos: los logos de TMDB, IGDB y OSM.
- Un ancho de página por tipo de página: lectura, contenido o panel.

### c) Rediseño página a página

Ordenado por prioridad, según cuánto se usa cada página y cuánto daño visual tiene. Todas se verifican en claro y en oscuro.

| # | Página | Componentes | Esfuerzo |
|---|---|---|---|
| 1 | Tarjeta de ítem (base de 7 páginas) | `ItemCard`, `ItemPlaceholder`, `AttributeChips`, `AddToListButton` | M |
| 2 | Portada | `HomeSection`, `HomeItemRow` (con flechas y pista de desplazamiento), `CategoryShortcuts`, `EditorialListCard` | M |
| 3 | Ficha (corrige F1 y F2) | `ItemDetailPage`, `ItemDetailColumn`, `ItemImage`, `ItemImageCredit`, `ItemAttribution`, `ItemPlaceMap`, `SimilarItemsRow`, `CommentBox`/`Comment` | L |
| 4 | Votar (corrige F3) | `VotePage`, `VoteKnowStep`, `VoteScoreLevels`, `VoteInterestStep`, `SwipeVoteCard`, `EmptyVoteState` | M |
| 5 | Categoría y Explorar | `CategoryPage`, `ActionMenu`/`ActionButton`, `InfiniteItemSlider`, `ui/ranking/*`, el podio de `constants.js`, `ExplorePage` | M |
| 6 | Buscar y buscador global | `SearchPage`, `GlobalSearch`, `CatalogImportPanel` | S |
| 7 | Listas | `ListPage`, `MyListsPage`, `MyListEditPage`, `lists/*`, `AddToListModal` | M |
| 8 | Wishlist y onboarding | `WishlistPage`, `WishlistItemCard`, `OnboardingPage`, `onboarding/*` | M |
| 9 | Perfil y perfil público | `ProfilePage` y sus pestañas, `profile/*`, `votes/MyVote*`, `proposals/*` | M |
| 10 | Autenticación | `AuthPage`, `LoginForm`, `RegisterForm`, Forgot, Reset y Verify | S |
| 11 | Ranking global | `RankingPage`, `UserRanking*` | S |
| 12 | Legal, 404, Forbidden y error | `LegalPageLayout`, `ErrorPage`, `ForbiddenPage`, `AppErrorBoundary` | S |
| 13 | Admin (8 subpáginas) | `AdminLayout`, `AdminNav`, `admin/*`, `admin/modals/*`, y las primitivas `DataTable` y `FilterBar` | L |
| 14 | Globales | `DailyRewardModal`, `MysteryBox`, `RewardReveal`, `NotificationBell`, `ReportMenu`/`ReportModal`, *toasts* | S |
| 15 | Textos (decisión 2) | Title Case → mayúscula inicial en toda la interfaz, restos de 1.0 y descripciones de categoría en el *seeder* (backend, con Sail) | S–M |

**Qué se puede paralelizar:**
- La página 1 va sola, porque es la base de las demás.
- Después pueden ir en paralelo estos grupos, cada página con su rama y su PR, con subagentes en *worktrees*: {2, 6}, {3}, {5}, {7, 8}, {9, 10, 11}, {12, 14} y {13}.
- La página 4 va después de la 3, porque comparten `ItemImage`.
- La 15 va al final, cuando las páginas ya están cerradas, para no provocar conflictos de texto.
- Los PR se fusionan de uno en uno en `rediseno-fase-0`, y cada rama se rebasa sobre la anterior antes de su PR.

### d) Revisión final (M, en serie)

- Repetir todas las capturas en claro y en oscuro y compararlas lado a lado con las de la auditoría.
- Barrido de contraste en todas las rutas y los dos modos.
- Recorrido completo con Tab en cada plantilla.
- Comprobación a 360 px y con `prefers-reduced-motion`.
- `grep` de colores de paleta y de valores arbitrarios `-[`. El objetivo es 0, salvo casos justificados.
- Borrar los alias de tokens antiguos.
- Actualizar `docs/frontend/arquitectura.md` §3–4 y este plan con lo que se haya hecho.

## 4. Preguntas

Las seis preguntas de la auditoría ya tienen respuesta (§1). Quedan dos puntos abiertos, que se resuelven en R1:

- **Qué dirección**, o qué mezcla, se elige.
- **Si el logotipo se redibuja** o solo cambia de color. Por ahora se interpreta que se puede redibujar (decisión 1).
