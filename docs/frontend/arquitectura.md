# Frontend: arquitectura, convenciones y decisiones de UI

**Responsable:** frontend.
- **Stack:** React 18.3 y Vite 6, en JSX (sin TypeScript), con React Router 7.
- **Datos y estado:** TanStack React Query 5 con axios, Zustand para la sesión, y react-hook-form con zod para formularios.
- **Interfaz:** Tailwind 3 con clsx y tailwind-merge, framer-motion, lucide-react y react-hot-toast.
- **Gestor de paquetes:** pnpm.
- **Despliegue:** Vercel, en `kudos.dcerdan.es`. Es una SPA: `vercel.json` reescribe todas las rutas a `index.html`.

## 1. Estructura de `src/`

| Carpeta | Contenido |
|---|---|
| `core/` | `axiosClient.js` (lee `VITE_API_URL`; token Bearer), `queryClient.js`, `AppErrorBoundary.jsx` |
| `store/useSessionStore.js` | Sesión del usuario (Zustand) |
| `routes/routes.jsx`, `routes/guards/` | Rutas y guardas: invitado, autenticado y verificado, admin |
| `pages/` | Una página por ruta: `HomePage`, `CategoryPage`, `ExplorePage`, `ItemDetailPage`, `VotePage`, `RankingPage`, `NewProposalPage`, `auth/`, `profile/`, `admin/` |
| `hooks/pages/useXPage.js` | **Patrón:** cada página tiene su hook, que devuelve `{ state, actions }`. La página solo pinta |
| `hooks/<dominio>/` | Consultas y mutaciones de React Query por dominio: `items/useItemQueries.js`, `votes/useVoteMutations.js`, `useVoteQueries.js`… |
| `hooks/common/` | `useFilters` (búsqueda con *debounce*), `useInfiniteScroll`, `useModal`, `usePointerScore` (estrellas por arrastre), `useBaseMutation` |
| `components/ui/` | Componentes base: `Button`, `Modal`, `StorageImage`, `ActionMenu`, `SearchFilter`, `SelectFilter`, `Pagination`, `FeedbackState`, `skeletons/`, `ranking/` |
| `components/items/` | `ItemCard`, `ItemImage` (fondo con el color dominante, 16:9), `ItemDetailColumn`, `InfiniteItemSlider` |
| `components/votes/` | `VoteStars` (10 estrellas con medios puntos), botones de voto |
| `components/layout/` | `MainHeader`, `NavBar`, `NavBarMobile`, `Footer` |
| `lib/` | `constants.js`, `schemas.js` (zod), `formatters.js`, `animations.js`, `buttonStyles.js` |

## 2. Rutas actuales

| Ruta | Página | Acceso |
|---|---|---|
| `/` | Rejilla de categorías | Público |
| `/:categorySlug` | Cabecera, destacados (`sort_by: 'vote_avg'`, en `useCategoryPage.js`), `ActionMenu` y ranking | Público |
| `/:categorySlug/explore` | Rejilla con búsqueda y orden | Público |
| `/:categorySlug/item/:itemId` | Ficha con comentarios | Público |
| `/:categorySlug/vote` | Votación de un ítem cada vez | Login y verificado |
| `/:categorySlug/proposals/...`, `/profile`, `/my-proposals` | Propuestas y perfil | Login y verificado |
| `/ranking` | Ranking global de kudos | Público |
| `/admin/*` | Administración | Admin |
| `/login`, `/register`… | Autenticación | Solo invitados |

> **Cuidado con el orden:** `/:categorySlug` captura cualquier primer segmento. Las rutas nuevas de primer nivel (`/buscar`, `/wishlist`, `/u/:username`, `/listas/...`, `/privacidad`) deben declararse **antes** que `/:categorySlug` en `routes.jsx`. Además, ningún slug de categoría debe coincidir con ellas.

## 3. Estilos y tema

- **Tokens en `tailwind.config.js`:**
  - `primary` es `#2563eb` y `accent` es `#facc15`;
  - los colores de superficie, texto y navegación son variables CSS definidas en `index.css`.
- **Estética:** tarjetas redondeadas (`rounded-3xl`, `shadow-xl`, `ring-1`) y titulares en `font-black` con la fuente Inter.
- **`plugins: []`:** no hay plugins de Tailwind instalados.

### Deuda de CSS conocida (tarea F0-08)

Clases usadas que **no existen** y, por tanto, no aplican ningún estilo. Aparecen en 13 archivos, entre ellos `CommentBox.jsx`, `ItemDetailColumn.jsx`, `ranking/Ranking.jsx`, `CategoryPage.jsx`, `ExplorePage.jsx`, `ItemDetailPage.jsx`, `VotePage.jsx` y varios de `pages/admin/`.

| Clase | Solución decidida |
|---|---|
| `animate-fade-in` | Definir un `keyframes.fadeIn` y una `animation['fade-in']` en `theme.extend` |
| `animate-in fade-in` (sintaxis de `tailwindcss-animate`) | Sustituir por `animate-fade-in` |
| `h-page-content`, `min-h-state` | Definirlas en `theme.extend` (`height` y `minHeight`) con el valor que pretendía el diseño. Revisa su uso para deducirlo; por ejemplo, `min-h-state` ≈ `min-h-[50vh]` para los estados vacíos o de error |
| `prose` | Sustituir por clases explícitas (`text-base leading-relaxed text-text-normal`…) en lugar de instalar `@tailwindcss/typography` |
| `scrollbar-thin` | Quitarla, o definir una utilidad en `index.css` con `@layer utilities` |

Antes de cerrar la tarea, comprueba que no queda ninguna con `grep -rn "h-page-content\|min-h-state\|animate-in\|prose\|scrollbar-thin" src`.

### Modo oscuro

**Aplazado.** Existen `darkMode: 'class'` y las variables `.dark`, pero nada activa la clase y hay unas 157 clases `slate-*` fijas en unos 60 archivos. **No lo actives a medias.**

## 4. Decisiones de UI del rediseño

| Decisión | Detalle | Tarea |
|---|---|---|
| ***Placeholder* generativo** | Si un ítem no tiene imagen, se muestra un degradado calculado a partir del hash del nombre, con el icono de la categoría (`categories.icon`, lucide) y las iniciales. Se usa en `StorageImage` e `ItemImage` en lugar del icono `ImageOff` | F0-06 |
| **Sin secciones vacías** | Si no hay descripción, no se muestra el bloque "Acerca de…" de `ItemDetailColumn`. Lo mismo vale para cualquier sección sin datos | F0-07 |
| **Búsqueda global** | Campo en `MainHeader` (y en `NavBarMobile`) que lleva a `/buscar?q=`. Los resultados se agrupan por categoría y usan `GET /api/items?search=` | F0-09 |
| **Importación bajo demanda** | En `/buscar`, si hay pocos resultados, aparece la sección "¿No lo encuentras? Búscalo en TMDB" con el botón "Añadir a Kudos" | F1-07 |
| **Atribución** | Pie de página con el logo y el aviso de TMDB. En la ficha, una línea "Datos: TMDB · Descripción: Wikipedia (CC BY-SA)" con enlaces | F1-08 |
| **Chips de atributos** | Se renderizan de forma genérica a partir de `category.attribute_schema` (`show_in_card` en tarjetas; todos en la ficha) | F1-09 |
| **Votar en dos pasos** | Primero "¿Lo conoces?". **Sí** lleva a 5 niveles: Me encantó / Muy bien / Bien / Flojo / No me gustó, que se guardan como 10/8/6/4/2. **No** lleva a "Me interesa" o "No me interesa". En móvil son tarjetas deslizables (framer-motion `drag`); en escritorio, botones y atajos de teclado. Sustituye a `VoteStars` en el flujo de voto | F2-04 |
| **Mostrar notas antiguas** | Las notas de 0–10 se muestran como media numérica con un decimal | — |
| ***Wishlist*** | Página `/wishlist` | F2-05 |
| **Onboarding** | Elegir intereses y hacer un *speed-rating* de unos 10 ítems populares | F2-07 |
| **Portada nueva** | Secciones "Para ti", "Novedades", "Listas destacadas" (editoriales) y "Mejor valorados". La rejilla de categorías pasa a ser una fila de accesos | F2-09 |
| **`ActionMenu`** | Se quitan las entradas "Cara a cara" y "Trivia", que estaban marcadas "En desarrollo" (aplazadas) | F2-04 |

## 5. Convenciones

- **Organización:**
  - la lógica va en hooks y los componentes solo pintan;
  - cada página nueva tiene su `useXPage`;
  - las llamadas a la API van **solo** en `hooks/<dominio>/`, usando `axiosClient`.
- **Claves de React Query:** arrays con el dominio primero, por ejemplo `['items', 'search', q]`. Invalidar con precisión tras las mutaciones (por ejemplo, votar invalida la cola, la *wishlist* y la ficha).
- **Textos:** en español, de tú, cortos.
- **Accesibilidad:**
  - botones con `aria-label` cuando solo tienen icono;
  - el voto en dos pasos debe poder usarse con teclado;
  - contraste AA.
- **Responsive:** diseño *mobile-first*; probar a 360 px de ancho.
- **Verificación:** `pnpm build` (ver [../infra/entorno-local.md](../infra/entorno-local.md)) más una prueba manual en el navegador del flujo tocado.
