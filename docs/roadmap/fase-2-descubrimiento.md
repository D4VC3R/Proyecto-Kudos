# Fase 2: descubrimiento

**Objetivo:** que votar sirva para descubrir ("¿Lo conoces?" lleva a valorar o a la *wishlist*), una cola de votación con sentido, y recomendaciones por contenido y una portada viva que no dependan del volumen de usuarios.

**Lee para toda la fase:** [../backend/base-de-datos.md](../backend/base-de-datos.md) §3 (Fase 2) y [../frontend/arquitectura.md](../frontend/arquitectura.md) §4.

---

## F2-01 · Tipos de voto `want` y `not_interested`

**Depende:** F1-10 (la F1-11 puede ir en paralelo) · **Lee:** arquitectura §3 (trampa 4) · **Archivos:** `Backend/app/Models/{Vote,User}.php`, `Backend/app/Http/Requests/Votes/{StoreVote,ListMyVotes}Request.php`, `Backend/app/Http/Resources/{ItemList,ItemDetail}Resource.php`

**Pasos:**
1. Migración `extend_votes_type_check` según base-de-datos §3 (Fase 2): antes, verifica el nombre real de la restricción; añade el índice `(user_id, type)`.
2. `Vote`: `TYPE_WANT = 'want'`, `TYPE_NOT_INTERESTED = 'not_interested'` y `RATED_TYPES = [TYPE_VOTE]`.
3. `StoreVoteRequest`:
   - acepta `vote`, `want`, `not_interested` y `skip`; en `prepareForValidation`, `skip` → `not_interested`;
   - `score` es obligatorio con `vote` y está prohibido en los demás tipos.
4. `ListMyVotesRequest` acepta los tipos nuevos en el filtro.
5. `ItemStatsService` y los kudos siguen contando solo `type = 'vote'`. `NextCategoryItemService` sigue excluyendo cualquier voto del usuario.
6. `User::getProfileStatistics()` (hoy cuenta `skip`) y los paneles de estadísticas: añade los tipos nuevos.
7. Añade `type` a `user_vote` en los dos Resources. Hoy `can_vote = false` en cuanto existe cualquier voto, así que el frontend necesita distinguir los tipos.

**Hecho cuando:** hay tests de un voto de cada tipo; `skip` se guarda como `not_interested`; `want` con `score` da 422; las medias no cambian con `want` ni con `not_interested`; `user_vote.type` está presente.

**Notas:**
- Migración `2026_10_30_000000_extend_votes_type_check.php`: la restricción real es `votes_type_check` (verificada en la BD) y el índice nuevo es `votes_user_id_type_index`. Probada con `migrate:fresh`, `migrate:rollback --step=1` y `migrate` contra `testing`.
- La migración no convierte las filas `skip` existentes (no las hay: local 0, y producción hará `migrate:fresh` en la F1-11). Las lecturas tratan `skip` como `not_interested` (`Vote::NOT_INTERESTED_TYPES`): el filtro `type=skip` o `type=not_interested` de `my-votes` devuelve ambos.
- `GET /api/profile/statistics`: `total_skips` se conserva como alias de `total_not_interested` (R7); campos nuevos `total_want` y `total_not_interested`.
- `meta.reason` de `POST /api/votes`: valor nuevo `already_wanted`; `not_interested` mantiene `already_skipped`. `meta.vote_type` con un `skip` de entrada vale ahora `not_interested`.
- `DeleteVoteAction` ya no recalcula al borrar `want`/`not_interested`/`skip` (usa `Vote::RATED_TYPES`; está fuera de **Archivos**, cambio mínimo).
- Test adaptado: `EmitVoteTest::test_un_skip_no_cambia_la_media_ni_el_contador` (ahora espera `not_interested`, R8). Tests nuevos en `VoteTypesTest`, ampliación de `ItemDetailTest` y `NextCategoryItemTest`.
- `votes_count` del panel de administración sigue contando todas las interacciones, como ya hacía con `skip`.
- Pendiente para el frontend (F2-04/F2-05): `MyVoteItemCard` pinta un `not_interested` como "Votado" sin nota; `MyStatsPage` no muestra `total_want` ni `total_not_interested`; `MyVotesHeader` no tiene vista de `want`.

---

## F2-02 · Transiciones entre tipos

**Depende:** F2-01 · **Lee:** arquitectura §3 (trampa 3) · **Archivos:** `Backend/app/Actions/ChangeVoteAction.php` (o una `TransitionVoteAction` nueva si crece), `Backend/app/Http/Requests/Votes/UpdateVoteRequest.php`

| Desde | Hacia | ¿Permitido? | Efectos |
|---|---|---|---|
| `want` / `not_interested` | `vote` + score | Sí | Recalcula las estadísticas y da `vote_first_time_item` (idempotente) |
| `want` | `not_interested`, y al revés | Sí | Ninguno |
| `vote` | `want` / `not_interested` | **No**: DELETE y crear de nuevo | — |
| `vote` | `vote` (otra nota) | Sí | Recalcula las estadísticas |

**Pasos:** quita de `UpdateVoteRequest::withValidator` la restricción "solo si `type === vote`" y valida esta tabla. La respuesta incluye `kudos_awarded`, igual que al emitir.

**Hecho cuando:** hay un test por cada fila y `kudos:audit-consistency` no encuentra desajustes.

**Notas (ejecución):**
- Se amplía `ChangeVoteAction` (no hay `TransitionVoteAction`) y recibe `KudosService`.
- `type` es opcional en `PUT /api/votes/{id}`; si falta, se mantiene el tipo actual. `skip` se normaliza a `not_interested` (también las filas heredadas al editarlas). Enviar solo `score` sobre un `want` da 422 (no se infiere `vote`): puntuar un `want` exige `type: "vote"`.
- `vote` → `want`/`not_interested`/`skip` da 422 con el error en `vote` (antes `vote` → `skip` pasaba y solo cambiaba la nota).
- `PUT /api/votes/{id}` devuelve ahora `meta` con `total_kudos`, `kudos_awarded` y `vote_type` (R7: solo se añade); mensajes nuevos para `want` y `not_interested`; desaparece el mensaje de `skip`.
- Un ítem en papelera da 422 en `PUT` (antes 404 accidental desde `recalculate()`).
- `vote` → `vote` no llama a `KudosService` (no consume el contador diario).
- Tests nuevos en `Backend/tests/Feature/Votes/VoteTransitionsTest.php`, incluida la salida de `kudos:audit-consistency` (`Desajustes detectados: 0`).

---

## F2-03 · Reajuste de las recompensas de kudos

**Depende:** — · **Archivos:** `Backend/config/kudos.php`, los tests de kudos de la F0-02

| Clave | Antes | Después |
|---|---|---|
| `voting.reward` | 5 | 2 |
| `voting.diminishing_returns.threshold` | 20 | 20 |
| `voting.diminishing_returns.new_reward` | 2 | 1 |
| `voting.daily_cap` | 50 | 20 |

- Se mantienen la recompensa por propuesta aceptada y las rachas.
- Comenta el motivo en el config: no premiar el voto masivo.
- `rewards.vote_first_time_item` es una clave **muerta** (`KudosService::calculateVoteReward` usa `voting.*`): elimínala.

**Hecho cuando:** los tests de kudos están actualizados a los valores nuevos y en verde.

**Notas (ejecución):**
- Valores aplicados: `voting.reward` 2, `diminishing_returns.threshold` 20, `new_reward` 1 y `daily_cap` 20. `config/kudos.php` lleva un comentario con el motivo (no premiar el voto masivo).
- R1: `rewards.vote_first_time_item` no estaba del todo muerta: la leía `VoteSeeder`. Se ha eliminado la clave y el seeder usa ahora `kudos.voting.reward` (sin rendimiento decreciente ni tope diario, como antes). `reasons.vote_first_time_item` se mantiene. `VoteSeeder` está fuera de **Archivos** (consecuencia directa).
- Con umbral = tope (20 y 20) el tramo de `new_reward` no se alcanza con los valores por defecto: el voto 21 supera el tope y no da kudos (máximo 40 kudos al día por votar). El test del rendimiento decreciente sube el tope a 50 en su propio `config()` para seguir comprobando el mecanismo. Decisión para validar: si se quería un tramo decreciente real, habría que bajar el umbral (p. ej. 10) o subir el tope.
- Test renombrado `test_el_tope_diario_de_votos_con_kudos_es_50` -> `test_el_tope_diario_de_votos_con_kudos_es_20` (R8: no se borra, se actualiza al valor nuevo: 20 votos dan 40 kudos y el 21.º da 0).
- Tests nuevos: `VoteKudosTest::test_los_valores_de_recompensa_por_voto_son_los_de_la_f2_03` y `DatabaseSeederTest::test_vote_seeder_da_la_recompensa_de_voting_reward_por_voto` (falló al borrar la clave sin cambiar el seeder y pasa con el arreglo).

---

## F2-04 · Votación en dos pasos (interfaz)

**Depende:** F2-02 · **Archivos:** `Frontend/src/pages/VotePage.jsx`, `Frontend/src/hooks/pages/useVotePage.js`, `Frontend/src/components/ui/ActionMenu.jsx`

**Pasos:**
1. `VotePage`/`useVotePage`:
   - paso 1, "¿Lo conoces?" → Sí / No;
   - paso 2a (Sí): 5 botones grandes con texto → `score` 10/8/6/4/2;
   - paso 2b (No): "Me interesa" (`want`) o "No me interesa" (`not_interested`).
2. Móvil: tarjeta con `framer-motion` `drag="x"` en el paso 1 (derecha = Sí, izquierda = No), con botones visibles como alternativa.
3. Teclado: S/N en el paso 1 y 1–5 en el paso 2a.
4. `VoteStars` sale del flujo de voto; puede quedarse para editar votos antiguos en "Mis votos" o sustituirse por los 5 niveles.
5. `ActionMenu`: quita "Cara a cara" y "Trivia".
6. Tras votar, invalida la cola, la *wishlist*, la ficha y el perfil.
7. Usa `user_vote.type` para mostrar "En tu wishlist" o "No te interesa" en lugar de "ya votado".

**Hecho cuando:** el flujo funciona con ratón, táctil y teclado. Describe la prueba manual en el resumen.

**Notas (F2-04):**
- Discrepancias con el código (R1): (1) el paso 7 dice "en lugar de 'ya votado'", pero el frontend no mostraba nada de `user_vote`; se muestra el estado en la ficha (`ItemDetailColumn`) y en "Mis votos" (`MyVoteItemCard`) con `VOTE_TYPE_BADGES`. (2) La página `/wishlist` no existe aún (F2-05): su caché será `GET /votes/my-votes?type=want`, que cuelga de `VOTE_KEYS.myVotesList()`; solo faltaba invalidar el perfil. (3) `MyVoteItemCard`, `MyStatsPage` y `MyVotesHeader` (pendientes de la F2-01) se han resuelto aquí: etiquetas de `want`/`not_interested`, nota solo en los `vote`, tarjetas "No me interesan" (`total_not_interested ?? total_skips`) y "En tu wishlist" (`total_want`), y filtro "No me interesan" (`type=not_interested`, el backend devuelve también los `skip` heredados). **No** se ha añadido una vista de `want` en "Mis votos" (la cubre `/wishlist`, F2-05).
- Teclas: S/N en el paso 1; 1 = "No me gustó" (2) … 5 = "Me encantó" (10) en el paso 2a; 1 = "Me interesa", 2 = "No me interesa" en el paso 2b; Esc vuelve al paso 1. Los atajos no actúan mientras se escribe en un campo ni mientras se envía el voto o llega el siguiente ítem (`isBusy`).
- Orden de los botones de `VoteScoreLevels`: de peor a mejor de izquierda a derecha en escritorio (alineados con las teclas); "Me encantó" arriba en móvil (`flex-col-reverse`). El componente es reutilizable (F2-05 "valorar", F2-07 *speed-rating*).
- Deslizamiento (`SwipeVoteCard`): 100 px de desplazamiento o 500 px/s de velocidad; los arrastres cortos no hacen nada. Los botones siguen visibles como alternativa.
- `VOTE_INVALIDATE_KEYS` (`useVoteMutations.js`): añade `USER_KEYS.profile` (incluye perfil mínimo y estadísticas) y lo usan `useCreateVote`, `useUpdateVote` y `useDeleteVote`. El *toast* sale también con `want`.
- `ActionMenu` sin "Cara a cara" ni "Trivia". `VoteActionButtons` ya no tiene "No me interesa" (vive en el paso 2b y ya no se envía `skip`).
- `VoteStars`, `StarItem`, `usePointerScore` y `STAR_SIZE_CLASSES` se quedan sin uso (cambio mínimo); se propone borrarlos en la F2-05 si "valorar" desde la *wishlist* usa `VoteScoreLevels`.
- Sin tests de frontend en el repositorio ni dependencias nuevas. Verificado con `pnpm build` y `sail test` (371 passed).
- Prueba del flujo (automatizada: Chrome 124 sin interfaz en Docker + `puppeteer-core` 22.15 desde una carpeta ignorada por git, con la API **simulada**; ninguna petición real, las fuentes de Google se abortan): **38/38 comprobaciones OK**. Escritorio con teclado (S, 5, N, Esc, 2; el paso se reinicia con cada ítem; tras cada voto hay un nuevo `GET next-item` y `GET profile/minimal`; escribir "s" en el campo de comentarios no cambia de paso; no se envía ningún `skip`); escritorio con ratón (Sí, Flojo → `score` 4; No, Me interesa → `want` sin `score`, con *toast*); móvil 375x740 táctil (arrastre de 30 px sin efecto, de 160 px a la derecha → valorar → "Bien" = `score` 6, de 160 px a la izquierda → interés → "No me interesa"; sin desbordamiento horizontal); ficha de los ítems 7, 8 y 9: "En tu wishlist", "No te interesa" y "Tu nota: 8". Se han revisado las capturas (en móvil los 5 niveles se apilan y la página hace *scroll* vertical). No se ha probado en un dispositivo táctil real ni en otros navegadores. El navegador de prueba necesitó `--shm-size=1g` (con los 64 MB por defecto de Docker, Chrome dejaba de cargar módulos de Vite con `ERR_INSUFFICIENT_RESOURCES`).

---

## F2-05 · *Wishlist*

**Depende:** F2-01

**Backend:** `GET /api/votes/my-votes?type=want` ya existe. Comprueba que ordena por fecha e incluye `released_at`. "Valorar" usa `PUT /api/votes/{id}` (F2-02).

**Frontend:**
- Página `/wishlist`, agrupada en "Ya disponible" y "Próximamente" según `released_at`.
- Acciones: "Ya lo he visto/jugado → valorar" (transición a `vote`) y "Quitar" (`not_interested` o borrar).
- Enlace en la navegación.

**Hecho cuando:** las transiciones desde la *wishlist* funcionan.

**Pendiente de la F2-01/F2-04:** "Mis votos" no tiene vista de `want`; decide si basta con `/wishlist` o se añade una pestaña en `MyVotesHeader`. La caché de la wishlist debe colgar de `VOTE_KEYS.myVotesList()` para que la invalide `VOTE_INVALIDATE_KEYS`.

**Notas (F2-05):**
- R1: `my-votes` **no** incluía `released_at` (ni el `select` del `with` ni `VoteResource`). Se añade `item.released_at` (`YYYY-MM-DD` o `null`, como `ItemListResource`; solo se añade un campo, R7) y un desempate `orderByDesc('id')` tras `latest()`: `created_at` tiene precisión de segundos y la paginación por *offset* del *scroll* infinito podía repetir u omitir filas con votos del mismo segundo.
- "Quitar" = `PUT /api/votes/{id}` con `{type: 'not_interested'}`: el ítem no vuelve a la cola de votación y se deshace borrando el voto en "Mis votos" → "No me interesan". No se ofrece "borrar" en la *wishlist*. Sin modal de confirmación (es reversible).
- Agrupación (`lib/wishlist.js`): sin `released_at` o con fecha de hoy o anterior → "Ya disponible" (orden de alta, el de la API); fecha futura → "Próximamente" (fecha más cercana primero). Se agrupa en el cliente sobre las páginas cargadas (50 por página, *scroll* infinito). Sin secciones vacías. Los ítems en la papelera (llegan con `item.id === null`) no se pintan; el contador "En tu lista" usa `meta.total` de la API, así que sí los cuenta.
- "Ya lo conozco" (en vez de "Ya lo he visto/jugado", que no encaja con libros o lugares) abre un modal con `VoteScoreLevels`, que gana las props `showKeys` (oculta los `<kbd>`, aquí no hay atajos) y `stacked` (mantiene la columna también en escritorio); `VotePage` usa los valores por defecto. `useUpdateVote` muestra ahora el mensaje del backend (`'Voto actualizado correctamente.'` / `'Ítem marcado como "no me interesa".'`).
- La caché es `useInfiniteMyVotes({ type: 'want' }, 50)`, que cuelga de `VOTE_KEYS.myVotesList()` y la invalida `VOTE_INVALIDATE_KEYS`.
- "Mis votos": sin pestaña de `want` (la vista "Todos" ya los muestra con "En tu wishlist" y la lista propia es `/wishlist`).
- Enlace "Wishlist" en la navegación (`NavBar`, y por tanto en el menú móvil) solo con sesión verificada. `/wishlist` va dentro de `RequireAuth` → `RequireVerified`.
- Borrados `VoteStars`, `StarItem`, `usePointerScore` y `STAR_SIZE_CLASSES` (sin uso desde la F2-04).
- Tests nuevos: `Backend/tests/Feature/Votes/WishlistTest.php` (7). Antes del arreglo fallaron `test_la_wishlist_incluye_la_fecha_de_lanzamiento_del_item` (falta `item.released_at`) y también `test_la_wishlist_desempata_por_id_con_la_misma_fecha`. `sail test`: 378 passed (371 + 7). Sin migraciones ni variables de entorno nuevas.
- Prueba del flujo (automatizada: Chrome 124 en Docker + `puppeteer-core` 22.15 desde una carpeta ignorada por git, API **simulada**, sin peticiones externas): **46/46 comprobaciones OK**. Escritorio con ratón (secciones y orden, ítem de la papelera oculto, `type=want`, enlace de la navegación, "Ya lo conozco" → modal sin `<kbd>` → "Muy bien" → `PUT` `{type: 'vote', score: 8}`, *toast*, el ítem desaparece tras el nuevo `GET my-votes`; "Quitar" → `PUT` `{type: 'not_interested'}` sin `score`, *toast*, y la sección "Ya disponible" vacía deja de pintarse; ningún `POST` ni `DELETE`); móvil 375x740 táctil (menú → "Wishlist", sin desbordamiento, 5 niveles apilados en el modal, "Bien" → `score` 6); lista vacía; sin sesión (no hay enlace y `/wishlist` redirige a `/login`); regresión F2-04 (`/peliculas/vote`, tecla `s`: las 5 teclas `<kbd>` siguen visibles en cinco columnas). Capturas revisadas. No se ha probado en un dispositivo táctil real ni con paginación de más de una página.

---

## F2-06 · Cola de votación ponderada

**Depende:** F2-01 · **Archivos:** `Backend/app/Services/NextCategoryItemService.php`, `Backend/config/catalog.php`

**Pasos:**
1. Sustituye `inRandomOrder()` en `generateNewBatch()`:
   - candidatos: activos de la categoría, `quality_score >= umbral` y no votados por el usuario. Unos 300 por `popularity desc` + unos 100 aleatorios, **en una sola consulta**;
   - peso, calculado en PHP: `w_pop·norm(popularity) + w_new·recencia(released_at) + w_qual·quality_score/100 + w_rand·random()`;
   - se ordenan por peso y se toman 50.
2. Pesos en `config/catalog.php` → `queue_weights`: `pop 0.45`, `new 0.2`, `qual 0.15`, `rand 0.2`.
3. Recencia: 1 si el lanzamiento es de los últimos 90 días o futuro; baja hasta 0 a los 5 años.
4. Los intereses del usuario (F2-07) no cambian la cola de una categoría; solo qué categorías se proponen en la portada.

**Hecho cuando:** los tests confirman que nunca salen ítems votados, inactivos ni de baja calidad, y que con una semilla fija el orden favorece la popularidad. El rendimiento es aceptable con 5.000 ítems.

---

## F2-07 · Onboarding

**Depende:** F2-04

**Backend:**
- Migración `profiles.interests` (base-de-datos §3, Fase 2).
- `PUT /api/profile` acepta `interests` (slugs de categorías activas).
- `GET /api/onboarding/items?categories=a,b` devuelve hasta 10 ítems populares (`popularity desc`, con calidad suficiente) repartidos entre esas categorías.

**Frontend:**
- Aparece tras el registro o en el primer login con `interests` vacío. Se puede saltar.
- Paso 1: elegir categorías. Paso 2: *speed-rating* con el componente de la F2-04. Al final, la portada.

**Hecho cuando:** se completa en menos de 2 minutos y, si se salta, no vuelve a aparecer en esa sesión.

---

## F2-08 · "Similares" y "Porque te gustó X"

**Depende:** F1-09

**Backend:**
1. `GET /api/items/{id}/similar`:
   - misma categoría; excluye el propio ítem y los de baja calidad; devuelve 12; caché de 24 h;
   - puntuación = géneros en común + 2 si comparte dirección o creador + 1 si es de la misma década;
   - desempate por `bayes_score` y luego por `popularity`;
   - en SQL, usa `jsonb_exists_any(attributes->'genres', ARRAY[...])` o normaliza en PHP. **No uses `?|`** (PDO lo confunde con un *placeholder*; si no hay más remedio, escápalo como `??|`).
2. `GET /api/recommendations` (con sesión): parte de los últimos 5 ítems valorados con 8 o más y busca similares no votados. Devuelve `[{because: {item}, items: [...]}]`. Con menos de 3 valoraciones de 8 o más, devuelve populares de sus intereses.

**Frontend:** fila "Similares" en la ficha. "Porque te gustó X" llega a la portada en la F2-09.

**Hecho cuando:** hay tests de orden y de exclusiones.

---

## F2-09 · Listas editoriales y portada nueva

**Depende:** F2-08

**Backend:**
1. Migraciones de `lists` y `list_items` (base-de-datos §3, Fase 2).
2. Modelos `UserList` (tabla `lists`; `List` es palabra reservada en PHP) y `ListItem`.
3. CRUD de administración de listas editoriales (`is_editorial = true`, `visibility = public`): crear, editar, añadir, quitar y reordenar.
4. `GET /api/home`, con caché de 10 minutos para la parte anónima:
   - `for_you`: recomendaciones con sesión; si no, populares;
   - `new_releases`: `released_at` en los últimos 60 días o los próximos 30, de categorías activas, con calidad suficiente;
   - `editorial_lists`: hasta 4, con sus 6 primeros ítems;
   - `top_rated`: por `bayes_score`, con al menos 3 votos;
   - `categories`: las activas.

**Frontend:** `HomePage` con esas secciones (la rejilla de categorías pasa a ser una fila de accesos), página pública `/listas/:slug` y gestión de listas editoriales en `/admin`.

**Hecho cuando:** hay tests de `home` con y sin sesión.

**HUMANO:** crea 3–4 listas de arranque (por ejemplo, "Clásicos imprescindibles", "Estrenos del mes", "Series cortas para un finde").

---

## F2-10 · SEO: meta tags y OG de las fichas

**Depende:** F1-06 · **Archivos:** `Frontend/api/meta.js`, `Frontend/vercel.json`

El frontend es una SPA en Vercel y la API está en otro dominio. Los rastreadores no ejecutan JS, así que las meta tags las sirve **Vercel**, no Laravel.

**Pasos:**
1. **Antes de nada,** verifica en la documentación actual de Vercel el mecanismo para proyectos Vite (Functions o Routing Middleware) y los límites del plan Hobby.
2. Función serverless (por ejemplo `Frontend/api/meta.js`): para `/:categorySlug/item/:itemId` (más adelante, `/listas/:slug` y `/u/:username`) consulta la API pública y devuelve el `index.html` compilado con `<title>`, `description`, `og:*`, `twitter:card` y JSON-LD (`Movie` / `TVSeries` / `VideoGame` / `Place`).
3. `vercel.json`: reescribe solo para los *user-agents* de rastreadores (regla `has` con regex de UA), o para todas las peticiones a esas rutas si el coste lo permite. El resto sigue en `index.html`. Ten en cuenta el `ignoreCommand` actual.
4. Imagen OG: la variante `banner` del ítem, con URL absoluta.

**Hecho cuando:** en un *preview deploy* (lo crea el humano), `curl -A "facebookexternalhit" https://<preview>/peliculas/item/<id>` devuelve las meta tags correctas y un navegador normal sigue recibiendo la SPA.

---

## F2-11 · Comando de métricas

**Depende:** F2-01

**Pasos:** comando `kudos:metrics {--days=7}`, que muestra en una tabla las métricas de [../producto/vision.md](../producto/vision.md) §4. Sin analítica externa.

**Hecho cuando:** un test con datos de factories comprueba los conteos.
