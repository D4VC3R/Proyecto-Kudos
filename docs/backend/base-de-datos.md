# Base de datos: esquema actual y cambios previstos

**Responsable:** backend/datos. Motor: **PostgreSQL 18**, con UUID como clave primaria en todas las tablas y la extensión `pg_trgm`, que se crea en `0001_01_01_000000_create_users_table.php`.

## 1. Principios

1. **La base de datos propia es la fuente de lectura.** Las APIs externas solo se llaman para importar, refrescar o buscar bajo demanda. **Nunca al servir una página.**
2. **Cambios mínimos.** Se añaden columnas y la única tabla nueva del catálogo es `item_sources`. Se descartó una tabla de traducciones, otra de imágenes, otra de estadísticas y `user_items`, porque romperían unos 30 archivos sin beneficio real.
3. **Migraciones siempre nuevas**, con un `down()` funcional. No se editan las migraciones existentes, aunque los datos sean de prueba.
4. **Sobre qué datos externos se guardan:**
   - **Se guarda** la ficha normalizada: textos en español, atributos, fechas, IDs externos e imágenes reducidas con su licencia.
   - **No se guarda** el payload en bruto de las APIs. TMDB prohíbe conservar sus datos más de 6 meses.
   - Los datos volátiles (por ejemplo, dónde ver una película) solo se guardan en caché de Redis, con TTL.

## 2. Esquema actual (resumen)

| Tabla | Columnas relevantes |
|---|---|
| `users` | `name` (no es único), `email`, `email_verified_at`, `total_kudos`, `creations_accepted`, campos de racha, `is_banned`, `banned_until`, `ban_reason`, `banned_by` |
| `profiles` | Relación 1:1 con `users`: `avatar`, `biography`, `social_links` (jsonb), `city`, `birthdate` |
| `categories` | `name` (único), `description`, `slug` (único, es la clave de ruta), `image` |
| `items` | `name`, `description` (text, **NOT NULL**), `images` (jsonb), `status` (enum `active`/`inactive`), `vote_avg` (float), `vote_count`, `creator_id`, `category_id`, borrado suave. Índices: `(status, vote_avg, vote_count)`, `(category_id, status)`, GIN trigram `items_name_trgm_idx(name)` |
| `votes` | `type` (enum `vote`/`skip`), `score` decimal(4,1) nullable, `user_id`, `item_id`. Índice único `votes_user_item_unique(user_id, item_id)` e índice `(item_id, type)` |
| `item_comments` | `content`, `is_hidden`, `hidden_reason`, `hidden_by`, borrado suave |
| `proposals` | La misma forma que `items`, más `status`, `reviewed_by` y `admin_notes` |
| `kudos_transactions` | `kudos_amount`, `reason`, `action_key` (único), referencia polimórfica |
| Otras | Tablas de spatie (roles y permisos), `personal_access_tokens`, `cache`, `jobs`, `failed_jobs` |

## 3. Cambios por fase

### Fase 0: ranking y calidad (tareas F0-04 y F0-05)

Migración `add_ranking_and_quality_to_items_table`:

| Columna | Tipo | Notas |
|---|---|---|
| `bayes_score` | `float`, default 0 | Media bayesiana; ver la fórmula en [../roadmap/fase-0-higiene.md](../roadmap/fase-0-higiene.md) |
| `quality_score` | `smallInteger`, default 0 | De 0 a 100. Se calcula al importar o editar y con el comando `items:recalculate-quality` |

Índices:
- `(category_id, status, bayes_score)`, para el ranking y el orden por "mejor valorados";
- `(category_id, status, quality_score)`, para la cola de votación y la portada.

### Fase 1: catálogo vivo (tarea F1-01)

**Migración `add_catalog_fields_to_items_table`:**

| Columna | Tipo | Notas |
|---|---|---|
| `slug` | `string`, nullable, **único** | Formato `{nombre-en-slug}-{año}`, con un sufijo numérico si colisiona. Nullable para los ítems creados por propuestas antiguas |
| `original_title` | `string`, nullable | Título original (p. ej. "Spirited Away"). `name` guarda el título en español ("El viaje de Chihiro") |
| `released_at` | `date`, nullable | Fecha de estreno o lanzamiento. Base de "Novedades" y de las notificaciones de la *wishlist* |
| `attributes` | `jsonb`, default `'{}'` | Atributos de la categoría, según `categories.attribute_schema`. Índice **GIN** |
| `popularity` | `float`, default 0 | Popularidad **externa** (TMDB `popularity`, IGDB `total_rating_count`…). Pondera la cola de votación sin depender de los votos propios |
| `wikidata_id` | `string(16)`, nullable, **único** | QID, por ejemplo `Q12345`. La llave para cruzar fuentes |
| `description_source` | `string(32)`, nullable | Uno de `tmdb`, `igdb`, `rawg`, `wikipedia_es`, `wikidata`, `manual` |
| `description_url` | `string`, nullable | URL de la fuente del texto. Obligatoria si viene de la Wikipedia (CC BY-SA) |
| `last_synced_at` | `timestampTz`, nullable | Última sincronización con el proveedor principal |

- Además: `description` pasa a ser **nullable**. "Sin descripción" es un estado válido que baja `quality_score`, y no debe volver a rellenarse con plantillas como "Item importado.".
- Índice GIN trigram sobre `original_title`: `CREATE INDEX items_original_title_trgm_idx ON items USING GIN (original_title gin_trgm_ops);`.
- Índice `(category_id, status, released_at)`.

**Tabla nueva `item_sources`:**

| Columna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `item_id` | uuid FK → `items`, `cascadeOnDelete` | |
| `provider` | `string(32)` | Uno de `tmdb`, `igdb`, `rawg`, `wikidata`, `wikipedia_es` |
| `external_id` | `string(64)` | Para TMDB: `movie:603` / `tv:1399`, porque los IDs de película y serie se solapan |
| `url` | `string`, nullable | Ficha pública en la fuente, para la atribución y el enlace |
| `is_primary` | `boolean`, default false | El proveedor que manda en la ficha. Solo uno por ítem |
| `last_synced_at` | `timestampTz`, nullable | |
| timestamps | | |

Índices: **único** `(provider, external_id)` (evita importar dos veces lo mismo) e `(item_id)`.

**Migración `add_catalog_fields_to_categories_table`:**

| Columna | Tipo | Notas |
|---|---|---|
| `attribute_schema` | `jsonb`, default `'[]'` | Lista de definiciones de campo (ver más abajo) |
| `is_active` | `boolean`, default true | Las categorías inactivas no salen en la portada ni en la API pública |
| `sort_order` | `smallInteger`, default 0 | Orden en la portada |
| `icon` | `string(32)`, nullable | Nombre del icono de lucide-react (por ejemplo `clapperboard`, `gamepad-2`, `map-pin`), usado por los *placeholders* |

**Formato de `attribute_schema`:**

```json
[
  { "key": "year",      "label": "Año",        "type": "integer", "filterable": true,  "show_in_card": true },
  { "key": "genres",    "label": "Géneros",    "type": "tags",    "filterable": true,  "show_in_card": true },
  { "key": "runtime",   "label": "Duración",   "type": "minutes", "filterable": false, "show_in_card": false },
  { "key": "directors", "label": "Dirección",  "type": "tags",    "filterable": false, "show_in_card": false }
]
```

Tipos permitidos: `string`, `integer`, `minutes`, `date`, `tags` (array de strings), `coordinates` (`{lat, lng}`) y `url`. El frontend renderiza los atributos de forma genérica a partir de este esquema.

**Claves nuevas en cada entrada de `items.images`** (no requieren migración):
- `license`: por ejemplo `"© titular de derechos"`, `"CC BY-SA 4.0"` o `"Dominio público"`;
- `author`: nullable;
- `source_url`: página de origen de la imagen, por ejemplo la página del archivo en Commons.

### Fase 2: votar con intención y listas editoriales (tareas F2-01 y F2-09)

**Tipos de voto.** La migración `extend_votes_type_check` hace lo siguiente:

```sql
ALTER TABLE votes DROP CONSTRAINT votes_type_check;
ALTER TABLE votes ADD CONSTRAINT votes_type_check
  CHECK (type IN ('vote', 'skip', 'want', 'not_interested'));
```

- **Verifica el nombre real de la restricción** antes de escribir la migración:

  ```sql
  SELECT conname FROM pg_constraint WHERE conrelid = 'votes'::regclass;
  ```

- `down()` convierte `want` y `not_interested` en `skip` y restaura la restricción original.
- Semántica:
  - `vote` = "lo conozco y lo valoro", con `score` obligatorio;
  - `want` = "me interesa" (la *wishlist*);
  - `not_interested` = "no me interesa";
  - `skip` = valor heredado, que la API acepta y **normaliza a `not_interested`**.
- Añade el índice `(user_id, type)` para la *wishlist*.

**Intereses del usuario (onboarding).** La migración añade a `profiles` la columna `interests` (jsonb, default `'[]'`), con un array de slugs de categoría.

**Listas (esquema completo, que se crea ya en la F2 para las listas editoriales):**

| Tabla | Columnas |
|---|---|
| `lists` | `id` uuid, `user_id` FK (cascade), `title` string(120), `slug` string único, `description` text nullable, `visibility` (enum `public`/`unlisted`/`private`, default `private`), `is_ranked` bool, `is_editorial` bool default false, `cover_item_id` FK nullable (`nullOnDelete`), `items_count` int default 0, `likes_count` int default 0, timestamps, borrado suave. Índices `(user_id)` y `(is_editorial, visibility)` |
| `list_items` | `id` uuid, `list_id` FK (cascade), `item_id` FK (cascade), `position` int, `note` string(280) nullable, timestamps. Índices: único `(list_id, item_id)` e `(list_id, position)` |
| `list_likes` | `user_id` FK (cascade), `list_id` FK (cascade), `created_at`. PK compuesta `(user_id, list_id)`. **Se crea en la F3** |

### Fase 3: comunidad (tareas F3-02, F3-04 y F3-05)

- **`item_comments`:**
  - `parent_id`: uuid nullable con FK a la misma tabla (`cascadeOnDelete`). **Solo un nivel de anidación**, y eso se valida en el Form Request;
  - `vote_id`: uuid nullable con FK a `votes` (`nullOnDelete`). Si tiene valor, el comentario es una **reseña** ligada a la nota del usuario. Máximo una reseña por usuario e ítem.
- **`reports`** (DSA): `id` uuid, `reporter_id` FK nullable (`nullOnDelete`), `reportable_type`, `reportable_id` (uuid), `reason` (enum `spam`/`odio`/`acoso`/`ilegal`/`spoiler`/`otro`), `details` text nullable, `status` (enum `pending`/`resolved`/`dismissed`, default `pending`), `resolved_by` FK nullable, `resolution_note` text nullable, `resolved_at` nullable, timestamps. Índices `(status, created_at)` y `(reportable_type, reportable_id)`.
- **`notifications`:** la tabla estándar de Laravel (`sail artisan make:notifications-table`).
- **Perfil público:**
  - `users.username`: string(30), único y nullable. Se rellena en el onboarding y sirve para las URLs `/u/{username}`;
  - `profiles.is_public`: bool, default **false** (privacidad por defecto, RGPD).
- **`users.privacy_accepted_at`:** timestampTz nullable. Se rellena al registrarse aceptando la política (F3-07).

### Fase 4: más categorías

No hay cambios de esquema. "Destinos" y "Videojuegos" usan las mismas columnas con su propio `attribute_schema`.

## 4. Lo que NO se crea (y por qué)

| Descartado | Motivo |
|---|---|
| `item_translations` | Solo hay español. Basta con `name` + `original_title` |
| `item_media` | `images` en jsonb con claves de licencia es suficiente |
| `item_stats` | El bloqueo de fila de `EmitVoteAction` es breve y con este tráfico no provoca contención |
| `user_items` (sustituto de `votes`) | Rompería unos 30 archivos. Ampliar los tipos de `votes` da el mismo resultado |
| `tags`, `tag_provider_map`, `item_tag` | `attributes` en jsonb con índice GIN cubre los filtros. Los géneros se traducen con un mapa en `config/catalog.php` |
| `item_relations`, `item_revisions`, `pairwise_votes`, `item_similarities`, `follows`, `badges`, `sync_runs` | Aplazados (ver [../producto/vision.md](../producto/vision.md)). Para seguir las sincronizaciones basta con los logs y `failed_jobs` |
