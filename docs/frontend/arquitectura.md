# Frontend: arquitectura

**Stack:**
- React 18.3 + Vite 6, JSX sin TypeScript, React Router 7.
- TanStack React Query 5 + axios; Zustand para la sesión; react-hook-form + zod.
- Tailwind 3, clsx, tailwind-merge, framer-motion, lucide-react y react-hot-toast.
- pnpm y Node 22.

**Despliegue:** Vercel, en `kudos.dcerdan.es`. Es una SPA: `vercel.json` reescribe todo a `index.html` y tiene un `ignoreCommand` que solo construye si cambia `Frontend/`.

Las rutas son relativas a `Frontend/src/`.

## 1. Estructura

| Carpeta | Contenido |
|---|---|
| `core/` | `axiosClient.js` (`VITE_API_URL`, token Bearer), `queryClient.js`, `AppErrorBoundary.jsx` |
| `store/useSessionStore.js` | Sesión (Zustand) |
| `routes/routes.jsx`, `routes/guards/` | Rutas y guardas: invitado, autenticado + verificado, admin |
| `pages/` | Una página por ruta: `HomePage`, `CategoryPage`, `ExplorePage`, `ItemDetailPage`, `VotePage`, `RankingPage`, `NewProposalPage`, `auth/`, `profile/`, `admin/` |
| `hooks/pages/useXPage.js` | Cada página tiene su hook, que devuelve `{ state, actions }`; la página solo pinta |
| `hooks/<dominio>/` | Consultas y mutaciones de React Query: `items/useItemQueries.js`, `votes/useVoteMutations.js`, `useVoteQueries.js`… |
| `hooks/common/` | `useFilters` (búsqueda con *debounce*), `useInfiniteScroll`, `useModal`, `usePointerScore`, `useBaseMutation` |
| `components/ui/` | `Button`, `Modal`, `StorageImage` (props `fallbackIcon`, `fallback` y `onError`), `ItemPlaceholder` (degradado, icono e iniciales; variantes `card`/`banner`/`thumb`), `ActionMenu`, `SearchFilter`, `SelectFilter`, `Pagination`, `FeedbackState`, `skeletons/`, `ranking/` |
| `components/items/` | `ItemCard`, `ItemImage` (16:9, fondo del color dominante; muestra `ItemPlaceholder` si no hay imagen o falla), `ItemDetailColumn`, `InfiniteItemSlider` |
| `components/votes/` | `VoteStars` (10 estrellas con medios puntos), botones de voto, "Mis votos" |
| `components/layout/` | `MainHeader`, `NavBar`, `NavBarMobile`, `Footer` |
| `lib/` | `constants.js`, `schemas.js` (zod), `formatters.js`, `animations.js`, `buttonStyles.js`, `placeholder.js` (hash, colores e iniciales del *placeholder*), `text.js` (`hasText`, para no pintar secciones vacías). **No hay `cn()`**: `clsx` y `tailwind-merge` se importan directamente |

## 2. Rutas

| Ruta | Página | Acceso |
|---|---|---|
| `/` | Rejilla de categorías | Público |
| `/:categorySlug` | Cabecera, destacados (`sort_by: 'vote_avg'` en `useCategoryPage.js`), `ActionMenu` y ranking | Público |
| `/:categorySlug/explore` | Rejilla con búsqueda y orden | Público |
| `/:categorySlug/item/:itemId` | Ficha con comentarios | Público |
| `/:categorySlug/vote` | Votación de un ítem cada vez | Login y verificado |
| `/:categorySlug/proposals/...`, `/profile`, `/my-proposals` | Propuestas y perfil | Login y verificado |
| `/ranking` | Ranking global de kudos | Público |
| `/admin/*` | Administración | Admin |
| `/login`, `/register`, `/verify-email`, `/forgot-password`, `/password-reset/:token` | Autenticación | Invitados |

**Slugs reservados.** React Router 7 elige la ruta por especificidad: `/buscar` gana a `/:categorySlug` sin importar el orden. El riesgo real es una categoría cuyo slug coincida con una ruta de primer nivel, porque quedaría inaccesible. La lista vive en `config/catalog.php` → `reserved_slugs` y se valida en `StoreCategoryRequest`/`UpdateCategoryRequest` (F0-09). Al añadir una ruta de primer nivel (`/buscar`, `/wishlist`, `/u/:username`, `/listas/...`, `/privacidad`, `/aviso-legal`), añádela a esa lista.

## 3. Estilos

- `tailwind.config.js`: `primary` `#2563eb` y `accent` `#facc15`; los colores de superficie, texto y navegación son variables CSS de `index.css`. Utilidades propias en `theme.extend`: `animate-fade-in` (`keyframes.fadeIn`, 0,5 s), `h-page-content` (`calc(100dvh - 9rem)`) y `min-h-state` (`60vh`). No hay utilidades de scroll propias ni `prose`. `plugins: []`.
- Estética: `rounded-3xl`, `shadow-xl`, `ring-1`; titulares `font-black` en Inter.

**Clases sin definir:** resuelto en la F0-08. Solución aplicada a cada clase:

| Clase | Solución aplicada |
|---|---|
| `animate-fade-in` | Definida: `keyframes.fadeIn` + `animation['fade-in']` (0,5 s, `ease-out`) en `theme.extend` |
| `animate-in fade-in duration-500` (sintaxis de `tailwindcss-animate`, no instalado) | Sustituido por `animate-fade-in` (`CategoryPage`) |
| `h-page-content`, `min-h-state` | Definidas en `theme.extend` (`height`: `calc(100dvh - 9rem)`; `minHeight`: `60vh`, el valor que usaba el código original) |
| `prose` | Quitado de `ItemDetailColumn`: los hijos ya llevan la tipografía explícita; no se instala `@tailwindcss/typography` |
| `scrollbar-thin`, `scrollbar-thumb-slate-200` | Quitadas (no se aplicaban; sin plugin `tailwind-scrollbar`) |

Comprobación: `grep -rn "animate-in\|prose\|scrollbar-thin\|scrollbar-thumb" Frontend/src` no debe devolver nada (las clases que se definen, `animate-fade-in`, `h-page-content` y `min-h-state`, se comprueban en el CSS generado). Pendiente fuera de la F0-08: `scrollbar-hide` (`ProfileTabs`, `MyVotesHeader`) tampoco está definida.

**Modo oscuro:** aplazado. Existen `darkMode: 'class'` y las variables `.dark`, pero nada activa la clase y hay muchas clases `slate-*` fijas (`grep -rc "slate-" Frontend/src`). **No lo actives a medias.**

## 4. Decisiones de UI

| Decisión | Detalle | Tarea |
|---|---|---|
| *Placeholder* generativo | Degradado a partir del hash del nombre, con el icono de la categoría (`categories.icon`) y las iniciales. Sustituye a `ImageOff` en `StorageImage` e `ItemImage` | F0-06 |
| Sin secciones vacías | Sin datos, no hay bloque (empezando por "Acerca de…" en `ItemDetailColumn`) | F0-07 |
| Búsqueda global | Campo en `MainHeader` y `NavBarMobile` → `/buscar?q=`, con resultados agrupados por categoría | F0-09 |
| Importación bajo demanda | En `/buscar`, con pocos resultados: "¿No lo encuentras? Búscalo en TMDB" → "Añadir a Kudos" | F1-07 |
| Atribución | Logo y aviso de TMDB en el pie; en la ficha, "Datos: TMDB · Descripción: Wikipedia (CC BY-SA)" con enlaces | F1-08 |
| Chips de atributos | Genéricos a partir de `attribute_schema` (`show_in_card` en las tarjetas; todos en la ficha) | F1-09 |
| Votar en dos pasos | "¿Lo conoces?". Sí → Me encantó / Muy bien / Bien / Flojo / No me gustó = 10/8/6/4/2. No → Me interesa / No me interesa. Móvil: tarjeta deslizable; escritorio: botones y teclado. Sustituye a `VoteStars` en el flujo de voto | F2-04 |
| Notas antiguas | Media numérica con un decimal | — |
| *Wishlist* | Página `/wishlist` | F2-05 |
| Onboarding | Intereses + *speed-rating* de unos 10 ítems populares | F2-07 |
| Portada nueva | "Para ti", "Novedades", "Listas destacadas", "Mejor valorados"; las categorías pasan a una fila de accesos | F2-09 |
| `ActionMenu` | Se quitan "Cara a cara" y "Trivia" (aplazados) | F2-04 |

## 5. Convenciones

- La lógica va en hooks; los componentes solo pintan. Las llamadas a la API, solo en `hooks/<dominio>/` con `axiosClient`.
- Claves de React Query: arrays con el dominio primero (`['items', 'search', q]`). Invalida con precisión tras cada mutación (votar invalida la cola, la *wishlist* y la ficha).
- Textos en español, tuteando y cortos.
- Accesibilidad: `aria-label` en los botones que solo tienen icono; el voto se puede usar con teclado; contraste AA.
- *Mobile-first*: prueba a 360 px.
- `item.creator` puede ser `null` (ítems importados): protégelo siempre.
- No hay linter ni tests de frontend. La verificación es `pnpm build` + prueba manual ([../infra/entorno-local.md](../infra/entorno-local.md) §5).
