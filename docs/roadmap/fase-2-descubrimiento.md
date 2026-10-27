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

---

## F2-05 · *Wishlist*

**Depende:** F2-01

**Backend:** `GET /api/votes/my-votes?type=want` ya existe. Comprueba que ordena por fecha e incluye `released_at`. "Valorar" usa `PUT /api/votes/{id}` (F2-02).

**Frontend:**
- Página `/wishlist`, agrupada en "Ya disponible" y "Próximamente" según `released_at`.
- Acciones: "Ya lo he visto/jugado → valorar" (transición a `vote`) y "Quitar" (`not_interested` o borrar).
- Enlace en la navegación.

**Hecho cuando:** las transiciones desde la *wishlist* funcionan.

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
