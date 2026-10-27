# Fase 2: descubrimiento

**Objetivo:**
- Que votar sirva para descubrir: "¿Lo conoces?" lleva a valorar o a apuntar en la *wishlist*.
- Una cola de votación con sentido.
- Recomendaciones por contenido y una portada viva, todo sin depender del volumen de usuarios.

**Lecturas comunes:**
- [../producto/vision.md](../producto/vision.md)
- [../backend/base-de-datos.md](../backend/base-de-datos.md) (Fase 2)
- [../frontend/arquitectura.md](../frontend/arquitectura.md) §4

---

## F2-01 · Tipos de voto `want` y `not_interested`

- **Responsabilidad:** BE
- **Depende de:** F1-10. El reset de producción (F1-11) puede ir en paralelo.

**Pasos:**
1. **Migración `extend_votes_type_check`,** según [../backend/base-de-datos.md](../backend/base-de-datos.md). Verifica primero el nombre real de la restricción. Añade el índice `(user_id, type)`.
2. **`Vote`:** las constantes `TYPE_WANT = 'want'` y `TYPE_NOT_INTERESTED = 'not_interested'`, y el helper `Vote::RATED_TYPES = [TYPE_VOTE]`.
3. **`StoreVoteRequest`:**
   - acepta `vote`, `want`, `not_interested` y `skip`, y en `prepareForValidation` convierte `skip` en `not_interested`;
   - `score` es obligatorio solo con `vote` y está prohibido en los demás tipos.
4. **`ListMyVotesRequest`:** acepta los tipos nuevos en el filtro.
5. **Cálculos:**
   - `ItemStatsService` y los kudos siguen contando solo `type = 'vote'`;
   - `NextCategoryItemService` excluye cualquier tipo de voto del usuario, como ahora.
6. **Revisa `User::getProfileStatistics()`** y los paneles de estadísticas que cuentan `skip`, y añade los tipos nuevos.
7. **Contrato de `user_vote`:** `ItemListResource` e `ItemDetailResource` devuelven `user_vote` sin `type` y `can_vote = false` en cuanto hay cualquier voto. Añade `type` a `user_vote` (campo nuevo, no rompe el contrato) para que el frontend distinga una valoración de un `want`/`not_interested`.

**Criterios de aceptación:**
- Tests: crear un voto de cada tipo; `skip` se guarda como `not_interested`; `want` con `score` da 422; las medias no cambian con `want` ni con `not_interested`.

---

## F2-02 · Transiciones entre tipos

- **Responsabilidad:** BE
- **Depende de:** F2-01

**Reglas:**

| Desde | Hacia | Permitido | Efectos |
|---|---|---|---|
| `want` / `not_interested` | `vote` + score | Sí | Recalcula las estadísticas y da `vote_first_time_item` (es idempotente: si ya lo recibió, no da más) |
| `want` | `not_interested` y viceversa | Sí | Ninguno |
| `vote` | `want` / `not_interested` | **No** (se borra el voto con DELETE y se crea otro) | — |
| `vote` | `vote` (cambiar la nota) | Sí | Recalcula las estadísticas |

**Pasos:**
- Implementa las transiciones en `ChangeVoteAction`, o en una Action nueva `TransitionVoteAction` si la lógica crece.
- En `UpdateVoteRequest`, quita la restricción "solo se edita si `type === vote`" y valida la tabla anterior.
- La respuesta incluye `kudos_awarded`, igual que al emitir un voto.

**Criterios de aceptación:** un test por cada fila de la tabla; `kudos:audit-consistency` sin desajustes.

---

## F2-03 · Reajuste de las recompensas de kudos

- **Responsabilidad:** BE
- **Depende de:** —

**Cambio en `config/kudos.php`:**

| Clave | Antes | Después |
|---|---|---|
| `voting.reward` | 5 | 2 |
| `voting.diminishing_returns.threshold` | 20 | 20 |
| `voting.diminishing_returns.new_reward` | 2 | 1 |
| `voting.daily_cap` | 50 | 20 |

- Se mantienen la recompensa por propuesta aceptada y las rachas.
- Ojo: `rewards.vote_first_time_item` (5) es una clave muerta; `KudosService::calculateVoteReward` usa `voting.*`. Cambia `voting.*` y elimina la clave muerta.
- Comenta en el config el motivo: no premiar el voto masivo.
- Actualiza los tests de kudos de la F0-02 a los valores nuevos.

**Criterios de aceptación:** tests actualizados y en verde.

---

## F2-04 · Votación en dos pasos (interfaz)

- **Responsabilidad:** FE
- **Depende de:** F2-02
- **Lectura:** [../frontend/arquitectura.md](../frontend/arquitectura.md) §4

**Pasos:**
1. **Rehaz `VotePage`/`useVotePage`:**
   - **paso 1:** "¿Lo conoces?" con los botones *Sí* / *No*;
   - **paso 2a (Sí):** 5 botones grandes con texto, que se envían como `score` 10/8/6/4/2;
   - **paso 2b (No):** "Me interesa" (`want`) o "No me interesa" (`not_interested`).
2. **Móvil:** tarjeta deslizable con `framer-motion` `drag="x"` en el paso 1 (derecha = Sí, izquierda = No) y botones visibles como alternativa.
3. **Teclado:** S/N en el paso 1 y 1–5 en el paso 2a.
4. **Componentes heredados:** `VoteStars` deja de usarse en el flujo de voto. Puede quedarse para editar votos antiguos en "Mis votos", o sustituirse por los 5 niveles.
5. **`ActionMenu`:** quita "Cara a cara" y "Trivia".
6. **Invalidaciones de React Query** tras votar: la cola, la *wishlist*, la ficha y el perfil.
7. **Estado del ítem:** usa `user_vote.type` (F2-01) para mostrar "En tu wishlist" o "No te interesa" en lugar de "ya votado".

**Criterios de aceptación:**
- El flujo completo funciona con ratón, táctil y teclado.
- `pnpm build` termina sin errores.
- Pega una captura o descripción de la prueba manual en el resumen.

---

## F2-05 · *Wishlist*

- **Responsabilidad:** BE, FE
- **Depende de:** F2-01

**Backend:** `GET /api/votes/my-votes?type=want`, que ya existe con el filtro. Comprueba que ordena por fecha y que incluye `released_at` del ítem. La transición "valorar" usa `PUT /api/votes/{id}` (F2-02).

**Frontend:**
- **Página `/wishlist`,** declarada antes de `/:categorySlug`:
  - agrupada en "Ya disponible" y "Próximamente", según `released_at`;
  - acciones "Ya lo he visto/jugado → valorar" (transición a `vote`) y "Quitar" (pasa a `not_interested` o se borra).
- **Enlace** en la navegación.

**Criterios de aceptación:** las transiciones desde la *wishlist* funcionan y `pnpm build` termina sin errores.

---

## F2-06 · Cola de votación ponderada

- **Responsabilidad:** BE
- **Depende de:** F2-01

**Pasos:**
1. **Sustituye `inRandomOrder()` en `NextCategoryItemService::generateNewBatch()`:**
   - candidatos: ítems activos de la categoría, con `quality_score >= umbral` y no votados por el usuario. Se toman unos 300 ordenados por `popularity desc`, más unos 100 aleatorios;
   - peso de cada candidato: `w_pop·norm(popularity) + w_new·recencia(released_at) + w_qual·quality_score/100 + w_rand·random()`;
   - se ordenan por peso y se cogen 50.

   Los pesos van en `config/catalog.php` → `queue_weights`, por defecto `pop 0.45`, `new 0.2`, `qual 0.15` y `rand 0.2`.
2. **Recencia:** 1 si el lanzamiento es de los últimos 90 días o próximo; decrece hasta 0 a los 5 años.
3. **Intereses:** si el usuario los tiene (F2-07), no cambian la cola de una categoría, pero sí qué categorías se proponen en la portada.

**Criterios de aceptación:**
- Tests: nunca devuelve ítems votados, inactivos ni de baja calidad; con una semilla aleatoria fija, el orden favorece la popularidad.
- El rendimiento es aceptable con 5.000 ítems: una sola consulta y el cálculo en PHP.

---

## F2-07 · Onboarding

- **Responsabilidad:** BE, FE
- **Depende de:** F2-04

**Backend:**
- migración `profiles.interests` (jsonb);
- `PUT /api/profile` acepta `interests` (slugs de categorías activas);
- `GET /api/onboarding/items?categories=a,b` devuelve hasta 10 ítems populares (`popularity desc`, con calidad suficiente) repartidos entre esas categorías.

**Frontend:**
- **Cuándo aparece:** tras el registro, o en el primer login si `interests` está vacío. Se puede saltar.
- **Paso 1:** elegir las categorías.
- **Paso 2:** *speed-rating* con el mismo componente del voto en dos pasos.
- **Final:** lleva a la portada.

**Criterios de aceptación:** se completa en menos de 2 minutos; al saltarlo no vuelve a molestar en esa sesión; `pnpm build` termina sin errores.

---

## F2-08 · "Similares" y "Porque te gustó X"

- **Responsabilidad:** BE, FE
- **Depende de:** F1-09

**Backend:**
1. **`GET /api/items/{id}/similar`:**
   - misma categoría;
   - puntuación = número de géneros en común (`attributes->'genres'`, con `jsonb_exists_any(...)` o normalizando en PHP; **no uses el operador `?|`** en SQL crudo: PDO confunde `?` con un *placeholder*, salvo que lo escapes como `??|`) + 2 si comparte dirección o creador + 1 si es de la misma década;
   - desempate por `bayes_score` y luego por `popularity`;
   - devuelve 12, excluye el propio ítem y los de baja calidad;
   - caché de 24 h.
2. **`GET /api/recommendations`** (autenticado):
   - parte de los últimos 5 ítems que el usuario ha valorado con 8 o más;
   - para cada uno, toma similares no votados por él;
   - devuelve grupos `{because: {item}, items: [...]}`.

   Si el usuario tiene menos de 3 valoraciones de 8 o más, devuelve populares de sus intereses.

**Frontend:** fila "Similares" en la ficha; la sección "Porque te gustó X" llega a la portada en la F2-09.

**Criterios de aceptación:** tests de ordenación y exclusiones; `pnpm build` termina sin errores.

---

## F2-09 · Listas editoriales y portada nueva

- **Responsabilidad:** BE, FE
- **Depende de:** F2-08

**Backend:**
1. **Migraciones** de `lists` y `list_items` ([../backend/base-de-datos.md](../backend/base-de-datos.md), Fase 2).
2. **Modelos** `UserList` (tabla `lists`; evita el nombre `List`, que es palabra reservada en PHP) y `ListItem`.
3. **CRUD de administración** de listas editoriales (`is_editorial = true`, `visibility = public`): crear, editar, añadir, quitar y reordenar ítems.
4. **`GET /api/home`** devuelve las secciones:
   - `for_you`: recomendaciones si hay sesión, si no populares;
   - `new_releases`: `released_at` en los últimos 60 días o próximos 30, de categorías activas, con calidad suficiente;
   - `editorial_lists`: hasta 4, cada una con sus 6 primeros ítems;
   - `top_rated`: por `bayes_score` y con al menos 3 votos;
   - `categories`: las activas.

   Caché de 10 minutos para la parte anónima.

**Frontend:**
- `HomePage` con esas secciones; la rejilla de categorías pasa a ser una fila de accesos;
- página pública de lista `/listas/:slug`;
- gestión de listas editoriales en `/admin`.

**Criterios de aceptación:** tests del endpoint `home` con y sin sesión; `pnpm build` termina sin errores.

**HUMANO:** crear 3–4 listas editoriales de arranque (por ejemplo "Clásicos imprescindibles", "Estrenos del mes", "Series cortas para un finde").

---

## F2-10 · SEO: meta tags y OG de las fichas

- **Responsabilidad:** FE, INFRA
- **Depende de:** F1-06

**Contexto:** el frontend es una SPA en **Vercel** (`kudos.dcerdan.es`) y la API vive en otro dominio. Los rastreadores y las previsualizaciones de redes sociales no ejecutan JavaScript, así que las meta tags deben servirse desde Vercel. **No vale una ruta de Laravel** (corrección respecto al primer borrador del plan).

**Pasos:**
1. **Función serverless de Vercel,** por ejemplo `Frontend/api/meta.js`:
   - recibe la ruta;
   - para `/:categorySlug/item/:itemId` (y más adelante `/listas/:slug` y `/u/:username`) consulta la API pública;
   - devuelve el `index.html` compilado con `<title>`, `description`, `og:*`, `twitter:card` y JSON-LD (`Movie` / `TVSeries` / `VideoGame` / `Place`) inyectados.
2. **Reescritura en `vercel.json`:**
   - solo para *user-agents* de rastreadores (regla `has` con un regex de UA) o para todas las peticiones a esas rutas, si el coste es aceptable;
   - el resto sigue yendo a `index.html`.
3. **Imagen OG:** la variante `banner` del ítem (URL absoluta del almacenamiento de la API).
4. **Antes de implementar,** verifica en la documentación actual de Vercel el mecanismo para proyectos Vite (Functions o Routing Middleware) y los límites del plan Hobby, que es gratuito.

**Criterios de aceptación:**
- `curl -A "facebookexternalhit" https://<preview>/peliculas/item/<id>` devuelve el HTML con las meta tags correctas.
- Un navegador normal sigue recibiendo la SPA.
- La prueba se hace en un *preview deploy* de Vercel, que crea el humano.

---

## F2-11 · Comando de métricas de uso

- **Responsabilidad:** BE
- **Depende de:** F2-01

**Pasos:** comando `kudos:metrics {--days=7}` que muestra en una tabla las métricas de [../producto/vision.md](../producto/vision.md) §6. Sin analítica externa.

**Criterios de aceptación:** un test con datos creados por factories que comprueba los conteos.
