# Rediseño visual: auditoría del frontend

Estado del frontend antes del rediseño visual, tomado sobre `rediseno-fase-0` tras la F5-15 (noviembre de 2026). Cubre:

1. El mapa del proyecto.
2. La inspección con Playwright.
3. Los fallos funcionales.
4. Los problemas de diseño.
5. La identidad del producto.

Las direcciones de diseño están en [direcciones.md](direcciones.md). El plan y las decisiones del humano están en [plan.md](plan.md).

Las capturas están en [capturas/](capturas/) con el nombre `<ruta>-<ancho>.webp`, a página completa y a 375, 768 y 1440 px. Las interacciones siguen el patrón `interaccion-*.webp`.

## Documentos de partida

**Usados:**
- [../producto/vision.md](../producto/vision.md): propósito y principios.
- [../frontend/arquitectura.md](../frontend/arquitectura.md): stack, rutas, estilos y decisiones de interfaz.
- [../legal/cumplimiento.md](../legal/cumplimiento.md): atribuciones obligatorias y fuente autoalojada. No se puede usar Google Fonts.
- [../guia-agentes.md](../guia-agentes.md) y [../roadmap/README.md](../roadmap/README.md): flujo de ramas, PR y estado de la F5.
- `README.md` y `Frontend/README.md`.

**Descartados**, porque no afectan a lo visual:
- `docs/backend/*`, `docs/infra/*`, `docs/integraciones/*` y `Backend/docs/api-contract.md`.
- Las fichas `docs/roadmap/fase-*.md`, que son histórico. Solo se consultaron las notas de la F5-11 y la F5-12.
- `.claude/agents/*`, que describen el proceso, no el diseño.

**Discrepancias:** en todos los casos se toma el código como verdad.

1. El servidor de desarrollo escucha en el puerto **5174**, no en el 5173. `Frontend/README.md` acierta.
2. La primera frase de `README.md` describe Kudos 1.0 ("valoraciones multitemática… gamificación"). La visión 2.0 es "tu diario…".
3. En la interfaz siguen textos de Kudos 1.0:
   - "Valora candidatos" en `AuthPage`.
   - Las descripciones de categoría en la base de datos ("compiten en esta categoría…").
   - El subtítulo de la portada, "tu próxima peli o serie", aunque también hay juegos y destinos.
4. `arquitectura.md` §5 exige contraste AA, pero el código lo incumple en varios puntos (ver §4).
5. `text-text-subtitle` figura como token de texto, pero vale slate-200. Sobre blanco es casi invisible (`CatalogImportPanel`).

**Incidencia de entorno durante la auditoría:**
- **Qué pasó:** el contenedor de Vite (`frontend-kudos-frontend-1`) arrancó antes del cambio de `tailwind.config.js` de la F5-12 y no recargó la configuración. Los tokens salían como `var(--color-x)` sin `rgb()`, y todo color de token se pintaba negro o transparente.
- **Cómo se resolvió:** se reinició el contenedor y se repitieron las capturas. `dist/` siempre estuvo bien.
- **Para el futuro:** después de tocar `tailwind.config.js`, reinicia el contenedor (`docker compose restart kudos-frontend` en `Frontend/`).

## 1. Mapa del proyecto

**Stack de estilos:**
- Tailwind **3.4.19** con `tailwind.config.js`, sin `@theme` y sin plugins (ni `forms` ni `typography`).
- PostCSS con autoprefixer.
- Inter autoalojada con `@fontsource-variable/inter`.

**Tokens:**
- `primary` (`#2563eb`) y `accent` (`#facc15`) tienen valores fijos.
- `background`, `surface`, `border`, `text.{highlight,normal,btn,subtitle}` y `nav.*` son canales RGB definidos en `src/index.css`.
- `logo.*` no se usa.
- `darkMode: 'class'` y el bloque `.dark` existen, pero no hay ninguna clase `dark:` y nada activa la clase.

**Librerías de interfaz:**
- framer-motion 12: 18 variantes en `lib/animations.js` y envoltorios en `components/animations/`.
- lucide-react, react-hot-toast y react-remove-scroll.
- Leaflet y react-leaflet, en un chunk aparte.
- clsx y tailwind-merge.
- No hay librería de componentes.

**Rutas** (React Router 7, `src/routes/routes.jsx`):

| Acceso | Rutas |
|---|---|
| Públicas | `/`, `/:categorySlug`, `/:categorySlug/explore`, `/:categorySlug/item/:itemId`, `/ranking`, `/buscar`, `/listas/:slug`, `/u/:username`, `/privacidad`, `/aviso-legal`, `/forbidden`, `/404` |
| Invitado | `/login`, `/register`, `/verify-email`, `/forgot-password`, `/password-reset/:token` |
| Sesión verificada | `/profile` (pestañas: información, propuestas, votos, estadísticas, privacidad), `/my-proposals`, `/wishlist`, `/mis-listas`, `/mis-listas/:id`, `/onboarding`, `/:slug/vote`, `/:slug/proposals/new`, `/:slug/proposals/:id/edit` |
| Admin | `/admin/{users,users/:id,categories,items,proposals,lists,lists/:id,reports}` |

**Esqueleto:**
- `App.jsx` monta `MainHeader`, el contenido en `<main max-w-7xl px-4 sm:px-6 lg:px-8 py-8>` y `Footer`.
- También monta tres elementos globales: `AddToListModal`, `DailyRewardChecker` y `ScrollToTopButton`.

**Componentes compartidos.** Son la base del futuro sistema de diseño:

| Componente | Usos |
|---|---|
| `ui/Button` | ~50 archivos |
| `ui/SectionHeader` | 26 |
| `ui/FeedbackState` | ~31 |
| `ui/Modal` y `ModalButtons` | 16 y 13 |
| `ui/InputField` y `TextAreaField` | 13 y 11 |
| `ui/StorageImage` | 13 |
| `items/ItemCard` | 7 |
| `ui/SearchFilter` y `ui/SelectFilter` | 8 y 7 |
| `ui/Pagination` | 5 |

Otros componentes compartidos:
- `ui/skeletons/*`, `ui/ranking/Ranking`, `ui/ItemPlaceholder`, `ItemImage` y `BackButton`.
- `comments/CommentBox`, `lists/AddToListButton`, `reports/ReportMenu` y `votes/VoteScoreLevels`.
- Las animaciones `FadeUp`, `StaggerGrid`, `AnimatedCard`, `ScaleFadeIn` y `BouncyCard`.

**Componentes de una sola página:**
- Todo `admin/*`, `home/*`, `onboarding/*` y `proposals/*`.
- Casi todo `profile/*` y `votes/*`.
- `catalog/CatalogImportPanel`, `ActionMenu`, `InfiniteItemSlider` y `PlaceMap`.

**Código muerto:** `animations/HoverFadeText.jsx` y `hoverFadeTextVariants`.

**Estilos fijos o repetidos:**
- **Paleta directa en lugar de tokens.** Hay ~585 usos de colores de paleta frente a 439 de tokens: slate 233, blue 96, red 92, yellow 47… Por ejemplo, `text-slate-800` aparece 31 veces aunque existe `text-text-highlight`, y `ring-slate-200` (27 veces) convive con `ring-border` (11), que tiene el mismo valor.
- **53 valores arbitrarios:**
  - `text-[9px|10px|11px]` (13 veces).
  - `h-[800px]` (3 veces).
  - `min-h-[50|60|70|80vh]`.
  - El desfase `top-[72px]` frente a `h-[80px]`.
  - 3 sombras con `rgba` en `lib/constants.js`.
- **9 radios distintos:**
  - Tarjetas: en `3xl` (~15), las filas en `2xl` y `PublicReviewCard` en `xl`.
  - Campos: `xl`, `2xl` y `lg`.
  - Chips: `full`, `lg`, `xl` y `md`.
- **Cadenas de clases duplicadas:**
  - La tarjeta de fila (5 veces).
  - El panel de autenticación (`max-w-md rounded-3xl … p-8 sm:p-10 shadow-2xl ring-1`, 4 veces).
  - La tarjeta de admin (5 veces).
  - El chip de atajo de teclado (5 veces).
  - El rótulo `text-sm font-bold text-slate-400 uppercase tracking-widest` (4 veces).
- **~10 enlaces con aspecto de botón escritos a mano**, porque `Button` no puede renderizarse como `<Link>`.
- **`SectionHeader`** ignora la prop `icon`, que se le pasa ~15 veces, y construye `text-${color}` de forma dinámica.

## 2. Inspección visual

**Alcance:**
- Herramienta: Playwright (`mcr.microsoft.com/playwright/mcp`).
- 18 rutas públicas y 15 con la sesión del admin del seeder local, a 375, 768 y 1440 px.
- 99 capturas de página y 14 de interacción.
- Solo se navegó y se pulsó "Sí" en `/votar` para ver el paso 2. No se envió ningún voto ni formulario.
- Quedan sin ver con datos `/listas/:slug` y `/u/:username`, porque no hay listas ni perfiles públicos sembrados.

**Lo que funciona bien:**
- No hay errores de JavaScript ni desbordamientos horizontales en ninguna ruta.
- El buscador funciona como *combobox* accesible, con ↑ y ↓ y `aria-activedescendant` ([interaccion-buscador-1440](capturas/interaccion-buscador-1440.webp)).
- El modal atrapa el foco, se cierra con Escape y devuelve el foco al botón que lo abrió ([interaccion-modal-anadir-lista-1440](capturas/interaccion-modal-anadir-lista-1440.webp)).

## 3. Fallos funcionales

| # | Fallo | Cómo reproducirlo | Gravedad | Causa probable |
|---|---|---|---|---|
| F1 | En escritorio, la ficha tiene altura fija y *scroll* interno. La imagen principal queda cortada bajo el pliegue y el contenido parece truncado ([ficha-pelicula-1440](capturas/ficha-pelicula-1440.webp)) | `/peliculas/item/…` a 1440 | Alta | `h-page-content` (`calc(100dvh-9rem)`) y `h-[500px]` en `ItemDetailPage.jsx:61`, con `overflow` en las columnas |
| F2 | La imagen de la ficha y de `/votar` se ve borrosa porque se amplía la variante `thumb` ([ficha-juego-1440](capturas/ficha-juego-1440.webp)) | Ficha de un juego a 1440 | Media | `ItemImage` elige `thumb` en vez de `banner` aunque exista |
| F3 | En `/votar`, los botones se montan sobre el póster. La pregunta ("¿Lo conoces?" o "¿Qué te ha parecido?") se pinta encima de la imagen y no se lee. No hay título del ítem ni encabezado ([votar-1440](capturas/votar-1440.webp), [interaccion-votar-paso2-1440](capturas/interaccion-votar-paso2-1440.webp)) | `/peliculas/vote` a 1440, pulsar S | Alta | `VotePage.jsx` e `ItemImage` superpuestos; falta el `h1` |
| F4 | El menú móvil no tiene `aria-expanded` y no se cierra con Escape ni con un clic fuera. Además, el panel se monta 8 px por encima del borde de la cabecera ([interaccion-menu-movil-375](capturas/interaccion-menu-movil-375.webp)) | 375 px, abrir ☰ y pulsar Esc | Media | `NavBar.jsx:60-71` y `NavBarMobile.jsx:14` (`top-[72px]` frente a `h-[80px]`); no usa `useDismiss` |
| F5 | Una categoría inexistente pinta primero la página entera con "catálogo de undefined" y lanza un 422 inútil (`/api/items?category_slug=…`) antes de mostrar "Categoría no encontrada" | `/no-existe-xyz` | Baja | `useCategoryPage` lanza las consultas sin esperar a la categoría |
| F6 | `SearchFilter` y `SelectFilter` no tienen borde (les falta la clase `border`) y muestran a la vez el *outline* del navegador y el *ring* ([explorar-1440](capturas/explorar-1440.webp)) | `/videojuegos/explore` | Media | `ui/SearchFilter.jsx:14` y `ui/SelectFilter.jsx:8` |
| F7 | Las etiquetas del panel "Búscalo en TMDB" son casi invisibles (contraste 1,23:1) | `/buscar?q=star` con sesión | Media | `text-text-subtitle` (slate-200) en `CatalogImportPanel.jsx:37,63,87` |
| F8 | La pluralización y la nota vacía son incoherentes: "(1 votos)", y "0.0 (0 votos)" en la ficha frente a "-.-" en la tarjeta | Ficha sin votos | Baja | `ItemDetailColumn.jsx:34` |
| F9 | No hay enlace "Saltar al contenido" ([interaccion-foco-tab2-1440](capturas/interaccion-foco-tab2-1440.webp)) | Tab desde el principio de la página | Media | `App.jsx` |
| F10 | Con empates, la posición del usuario en la cabecera (#3 y luego #1) no coincide con `/ranking` (#3) | Comparar la cabecera y el ranking | Baja (por verificar) | `ranking_position` frente al orden del ranking |

## 4. Problemas de diseño

### Globales

- **Color:**
  - La única marca es el azul `#2563eb`, el de Tailwind por defecto.
  - El amarillo `accent` se usa como color de texto: "Admin **Dashboard**" tiene un contraste de 1,46:1 sobre slate-50 y "Gestión de **Usuarios**" de 1,53:1 ([admin-usuarios-1440](capturas/admin-usuarios-1440.webp)).
  - Rojo, verde y naranja saturados, sin un significado coherente. "Crear propuesta" va en rojo, que es el color de peligro, y "Cerrar sesión" es un botón rojo siempre visible.
  - Las notas del ranking van en blanco sobre naranja-500 (2,8:1).
  - Textos útiles en `text-slate-400` (2,56:1), como "Atajos…" o "¿Lo conoces?".
- **Tipografía:**
  - Todo va en Inter `font-black` (59 usos) o `font-bold` (174), sin contraste entre titular y cuerpo.
  - El `h1` cambia de tamaño según la página: `6xl` en Home y Categoría, `3xl` en Login y Legal, `2xl` en Forgot y FeedbackState.
  - No tienen `h1` Explorar, Buscar y Editar lista, y `/votar` no tiene ningún encabezado.
  - Hay 13 textos de 9–11 px, entre ellos el contador de kudos de la cabecera.
  - Se usa Title Case calcado del inglés ("Iniciar Sesión", "Guardar Cambios", "Mi Perfil").
- **Superficies:**
  - Hay 4 recetas de tarjeta con 3 radios y 6 sombras.
  - Se mezclan `ring` y `border`.
  - Conviven dos grises de borde (slate-100 y slate-200).
- **Espaciado:**
  - Cinco anchos de contenido: 7xl, 5xl, 4xl, 3xl y md.
  - El relleno se duplica en `ExplorePage` y `NewProposalPage` (`container px-4` dentro de `main px-4`).
  - "Destacados de esta semana" está sangrado `px-8` respecto al `h1`.
- **Activo frente a foco:**
  - La pestaña activa de la navegación (`ring-2 ring-offset-2`) se parece a un anillo de foco.
  - El foco real es incoherente:
    - En los accesos a categorías se ve el del navegador.
    - En `Button` es `ring-blue-200` (≈1,5:1) con `focus:`, así que también aparece al hacer clic.
    - En `ReportMenu`, `GlobalSearch` y `NotificationBell` es casi invisible ([interaccion-foco-tab5-1440](capturas/interaccion-foco-tab5-1440.webp), [interaccion-foco-tab9-1440](capturas/interaccion-foco-tab9-1440.webp)).
- **Navegación:**
  - Con sesión, entre md y xl, la navegación de escritorio se queda en 5 iconos sin texto. Al admin le pasa siempre.
  - En móvil, la cabecera con sesión no cabe bien y la píldora de usuario usa texto de 9 px ([home-con-sesion-375](capturas/home-con-sesion-375.webp)).
- **Movimiento:**
  - Casi todas las listas usan escalonados y *springs*, así que las tarjetas aparecen tarde ([explorar-375-tras-scroll](capturas/explorar-375-tras-scroll.webp)).
  - No se respeta `prefers-reduced-motion`.
- **Tono:** se mezclan textos de Kudos 1.0 ("Bienvenido a Películas", "Valora candidatos", el icono de mando del login aunque hay cuatro categorías) y de 2.0.

### Por página

- **Portada** ([home-1440](capturas/home-1440.webp), [home-con-sesion-375](capturas/home-con-sesion-375.webp)):
  - Las filas horizontales se cortan en el borde, sin flechas ni pista de desplazamiento.
  - Los títulos de las tarjetas ocupan 2–3 líneas y tapan el póster.
  - Los chips de géneros salen cortados ("Fant", "Dran").
  - Con sesión, el póster acumula tres capas: la tarjeta, el botón de lista arriba a la izquierda y la nota arriba a la derecha.
- **Categoría** ([categoria-peliculas-1440](capturas/categoria-peliculas-1440.webp)):
  - Tres bloques gigantes con degradados azul, rojo y verde, más llamativos que el contenido.
  - El podio usa degradados oro, plata y bronce, sombras de color y notas en colores de semáforo, y en móvil trunca los nombres ("Street…").
  - El *slider* infinito con fundido corta tarjetas por los dos lados.
- **Ficha** ([ficha-pelicula-1440](capturas/ficha-pelicula-1440.webp), [ficha-destino-375](capturas/ficha-destino-375.webp)):
  - Además de F1 y F2, el `h1` de 60 px empuja la imagen fuera de la vista.
  - La columna de comentarios ocupa toda la altura aunque esté vacía.
  - En móvil, "Acerca de" es un bloque gris con texto de 18 px y líneas cortas.
- **Ranking** ([ranking-1440](capturas/ranking-1440.webp)): repite el podio de Categoría, y la paginación va en una tarjeta separada del listado.
- **Login y registro** ([login-1440](capturas/login-1440.webp)):
  - Panel azul con manchas difuminadas estáticas e icono de mando.
  - Dos estilos de pestaña distintos: "Ya tengo cuenta" y las pestañas del perfil.
- **Explorar** ([explorar-1440](capturas/explorar-1440.webp)): además de F6, la rejilla entra con desplazamientos laterales alternos de ±50 px.
- **Perfil** ([perfil-1440](capturas/perfil-1440.webp)):
  - Pestañas subrayadas.
  - Una tarjeta de identidad usa `bg-background` y la otra `bg-surface`.
  - La fecha sale en el formato del navegador.
- **Votar:** además de F3, el paso 2 tiene dos botones "Volver" y el póster va a tamaño fijo con bandas blancas.
- **Admin** ([admin-items-1440](capturas/admin-items-1440.webp)):
  - El amarillo como resalte no tiene contraste suficiente.
  - Los botones de acción son solo iconos en rojo y naranja.
  - Las etiquetas van en `text-[10px] uppercase`.
- **Legal** ([privacidad-1440](capturas/privacidad-1440.webp)): está bien, pero tiene su propio ancho (`max-w-3xl`) y su propio `h1`.
- **404 y Forbidden** ([404-1440](capturas/404-1440.webp)): solo un icono y un título, con poca jerarquía.

## 5. Identidad y objetivo

Kudos es un diario cultural en español. Sirve para guardar, puntuar y comentar pelis, series y videojuegos y para descubrir destinos, y sus listas pueden mezclarlo todo. Es un proyecto sin ánimo de lucro y sin anuncios, de un solo desarrollador, con datos de TMDB, IGDB y Wikimedia.

Su público es gente hispanohablante que quiere llevar la cuenta de lo que ve, juega y visita y encontrar qué hacer a continuación. Debe transmitir un sitio personal, cálido y fiable, más cuaderno que tienda. El contenido (pósteres y fotos) es el protagonista. Los kudos y el ranking son la recompensa visible de participar, y el humano ha decidido mantenerlos destacados (ver [plan.md](plan.md), decisiones).
