# Base de datos

PostgreSQL 18. Clave primaria UUID en todas las tablas. La extensión `pg_trgm` se crea en `0001_01_01_000000_create_users_table.php`.

## 1. Principios

1. **La base de datos propia es la fuente de lectura.** Las APIs externas solo se llaman para importar, refrescar o buscar bajo demanda; nunca al servir una página.
2. **Cambios mínimos:** columnas nuevas y una sola tabla nueva de catálogo (`item_sources`).
3. **Migraciones nuevas** con un `down()` que funcione (R5).
4. **Datos externos:** se guarda la ficha normalizada (textos en español, atributos, fechas, IDs externos e imágenes reducidas con su licencia). **Nunca** se guarda el payload en bruto (TMDB prohíbe conservarlo más de 6 meses). Lo volátil va solo en Redis, con TTL.

## 2. Esquema actual

| Tabla | Columnas relevantes |
|---|---|
| `users` | `name` (no único), `email`, `email_verified_at`, `total_kudos`, `creations_accepted`, rachas, `is_banned`, `banned_until`, `ban_reason`, `banned_by`, `username` (string(30), único, nullable; F3-03) |
| `profiles` | 1:1 con `users`: `avatar`, `biography`, `social_links` (jsonb), `city`, `birthdate`, `interests` (jsonb, default `'[]'`: slugs de categoría elegidos en el onboarding; F2-07), `is_public` (bool, default `false`; F3-03) |
| `categories` | `name` (único), `description` (NOT NULL), `slug` (único, clave de ruta), `image`, `attribute_schema` (jsonb, default `'[]'`), `is_active` (default true), `sort_order` (smallInteger, default 0), `icon` (string(32), nullable) |
| `items` | `name`, `description` (text, **nullable** desde la F1-01), `images` (jsonb), `status` (`active`/`inactive`), `vote_avg` (float), `vote_count`, `bayes_score` (float, default 0), `quality_score` (smallInteger, default 0), `creator_id` (nullable), `category_id`, borrado suave. Desde la F1-01: `slug` (único, nullable), `original_title`, `released_at` (date), `attributes` (jsonb, default `'{}'`), `popularity` (float, default 0), `wikidata_id` (string(16), único, nullable), `description_source`, `description_url` y `last_synced_at` (timestampTz). Índices: `(status, vote_avg, vote_count)`, `(category_id, status)`, `(category_id, status, bayes_score)`, `(category_id, status, quality_score)`, `(category_id, status, released_at)`, únicos `items_slug_unique` e `items_wikidata_id_unique`, y GIN: trigram `items_name_trgm_idx(name)`, trigram `items_original_title_trgm_idx(original_title)` y `items_attributes_gin_idx(attributes)` |
| `item_sources` | `item_id` (FK, cascade), `provider`, `external_id`, `url`, `is_primary`, `last_synced_at`, timestamps. Único `(provider, external_id)` e índice `(item_id)` |
| `lists` | F2-09. `user_id` (FK, cascade), `title` string(120), `slug` (único), `description` (nullable), `visibility` (`public`/`unlisted`/`private`, default `private`), `is_ranked` (default false), `is_editorial` (default false), `cover_item_id` (FK nullable, `nullOnDelete`), `items_count` y `likes_count` (default 0), `hidden_by_moderation_at` (timestamp nullable, F3-01: la moderación ocultó la lista y el dueño no puede volver a publicarla), timestamps, borrado suave. Índices `(user_id)` e `(is_editorial, visibility)` |
| `list_likes` | F3-01. `user_id` (FK, cascade), `list_id` (FK, cascade), `created_at`. PK `(user_id, list_id)` e índice `(list_id)` |
| `list_items` | F2-09. `list_id` (FK, cascade), `item_id` (FK, cascade), `position`, `note` string(280) nullable, timestamps. Único `(list_id, item_id)`; índice `(list_id, position)` |
| `notifications` | F3-05. `id` (uuid, PK), `type`, `notifiable_type` + `notifiable_id` (uuid; índice `(notifiable_type, notifiable_id)`), `data` (text con JSON), `read_at` (nullable), timestamps. Tabla estándar de Laravel con `uuidMorphs` |
| `votes` | `type` (`vote`/`want`/`not_interested`, y `skip` heredado; CHECK `votes_type_check`), `score` decimal(4,1) nullable, `user_id`, `item_id`. Único `votes_user_item_unique(user_id, item_id)`; índices `(item_id, type)` y `(user_id, type)` |
| `item_comments` | `user_id` nullable desde la F3-08 (comentario anonimizado), `content`, `is_hidden`, `hidden_reason`, `hidden_by`, borrado suave; **F3-02:** `parent_id` (FK a la propia tabla, `cascadeOnDelete`; índice `(parent_id, created_at)`) y `vote_id` (FK a `votes`, `nullOnDelete`; índice `(vote_id)`; índice parcial único `item_comments_review_unique`) |
| `proposals` | La misma forma que `items` + `status`, `reviewed_by`, `admin_notes` |
| `kudos_transactions` | `kudos_amount`, `reason`, `action_key` (único), referencia polimórfica |
| Otras | spatie (roles y permisos), `personal_access_tokens`, `cache`, `jobs`, `failed_jobs` |

## 3. Cambios por fase

### Fase 0 (F0-04, F0-05): dos migraciones (R5 impide editar una migración ya creada)

**`add_bayes_score_to_items_table`** (F0-04, hecha):

| Columna | Tipo | Notas |
|---|---|---|
| `bayes_score` | float, default 0 | Fórmula en la F0-04; la calcula `ItemStatsService` |

Índice: `(category_id, status, bayes_score)`.

**`add_quality_score_to_items_table`** (F0-05, hecha):

| Columna | Tipo | Notas |
|---|---|---|
| `quality_score` | smallInteger, default 0 | 0–100; reglas en la F0-05 (sin descripción, como máximo `min_quality_for_discovery` − 1, F1-04); la calcula `ItemQualityScorer` |

Índice: `(category_id, status, quality_score)`.

### Fase 1 (F1-01, hecha)

**`add_catalog_fields_to_items_table`:**

| Columna | Tipo | Notas |
|---|---|---|
| `slug` | string, nullable, único | `{nombre-en-slug}-{año}` con sufijo si colisiona. Nullable para los ítems de propuestas antiguas |
| `original_title` | string, nullable | `name` = título en español; `original_title` = título original |
| `released_at` | date, nullable | Base de "Novedades" y de los avisos de la *wishlist* |
| `attributes` | jsonb, default `'{}'`, índice GIN | Según `categories.attribute_schema` |
| `popularity` | float, default 0 | Popularidad **externa** (TMDB `popularity`, IGDB `total_rating_count`) |
| `wikidata_id` | string(16), nullable, único | QID. El importador solo cruza por él dentro de la misma categoría |
| `description_source` | string(32), nullable | `tmdb`, `steam`, `wikipedia_es`, `wikidata` o `manual` (IGDB solo tiene sinopsis en inglés y nunca se guarda) |
| `description_url` | string, nullable | Obligatoria si el texto viene de Wikipedia (CC BY-SA) |
| `last_synced_at` | timestampTz, nullable | Última sincronización con el proveedor principal |

- `description` pasa a nullable. "Sin descripción" es válido, pero la ficha nunca llega al umbral de la cola y los destacados (tope de `ItemQualityScorer`, F1-04), y nunca se rellena con plantillas.
- `CREATE INDEX items_original_title_trgm_idx ON items USING GIN (original_title gin_trgm_ops);`
- Índice `(category_id, status, released_at)`.

**`create_item_sources_table`:**

| Columna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `item_id` | uuid FK → `items`, `cascadeOnDelete` | |
| `provider` | string(32) | `tmdb`, `igdb`, `steam` (secundaria, solo para la descripción), `wikidata` (secundaria, o **principal** en destinos, F4-02) o `wikipedia_es` |
| `external_id` | string(64) | TMDB: `movie:603` / `tv:1399` (sus IDs se solapan) |
| `url` | string, nullable | Ficha pública en la fuente |
| `is_primary` | boolean, default false | El proveedor que manda; uno por ítem |
| `last_synced_at` | timestampTz, nullable | |
| timestamps | | |

Índices: único `(provider, external_id)` e `(item_id)`.

**`add_catalog_fields_to_categories_table`:**

| Columna | Tipo | Notas |
|---|---|---|
| `attribute_schema` | jsonb, default `'[]'` | Formato más abajo |
| `is_active` | boolean, default true | Las inactivas no salen en la portada ni en la API pública |
| `sort_order` | smallInteger, default 0 | |
| `icon` | string(32), nullable | Icono lucide (`clapperboard`, `gamepad-2`, `map-pin`) para los *placeholders* |

**Formato de `attribute_schema`.** Tipos posibles: `string`, `integer`, `minutes`, `date`, `tags` (array de strings), `coordinates` (`{lat, lng}`) y `url`.

```json
[
  { "key": "year",      "label": "Año",       "type": "integer", "filterable": true,  "show_in_card": true },
  { "key": "genres",    "label": "Géneros",   "type": "tags",    "filterable": true,  "show_in_card": true },
  { "key": "runtime",   "label": "Duración",  "type": "minutes", "filterable": false, "show_in_card": false },
  { "key": "directors", "label": "Dirección", "type": "tags",    "filterable": false, "show_in_card": false }
]
```

**`attribute_schema` de `peliculas` y `series`** (acordado en la F1-03; lo siembra la F1-10 desde las constantes `ATTRIBUTE_SCHEMA` de `TmdbMovieProvider` y `TmdbTvProvider`).

`peliculas`:

| key | label | type | filterable | show_in_card |
|---|---|---|---|---|
| `year` | Año | `integer` | sí | sí |
| `genres` | Géneros | `tags` | sí | sí |
| `runtime` | Duración | `minutes` | no | no |
| `directors` | Dirección | `tags` | no | no |
| `countries` | Países | `tags` | no | no |
| `original_language` | Idioma original | `string` | no | no |

`series`:

| key | label | type | filterable | show_in_card |
|---|---|---|---|---|
| `year` | Año | `integer` | sí | sí |
| `genres` | Géneros | `tags` | sí | sí |
| `seasons` | Temporadas | `integer` | no | no |
| `episodes` | Episodios | `integer` | no | no |
| `creators` | Creación | `tags` | no | no |
| `networks` | Cadenas | `tags` | no | no |
| `status` | Estado | `string` | no | no |

`videojuegos` (F4-01; lo siembra `CategorySeeder` desde `IgdbGameProvider::ATTRIBUTE_SCHEMA`, y la migración `activate_videojuegos_category` lo pone en las bases ya sembradas):

| key | label | type | filterable | show_in_card |
|---|---|---|---|---|
| `year` | Año | `integer` | sí | sí |
| `genres` | Géneros | `tags` | sí | sí |
| `platforms` | Plataformas | `tags` | sí | no |
| `developers` | Desarrollo | `tags` | no | no |

**`activate_videojuegos_category`** (F4-01, `2026_11_04_000000`): migración de datos que pone `is_active = true` y el `attribute_schema` anterior a `videojuegos` (producción la tenía inactiva y sin esquema desde la F1-10; el panel de administración no permite activarla). Si la categoría no existe (`migrate:fresh`, donde la crea el seeder ya activa) no hace nada. El `down()` la devuelve a inactiva y con `attribute_schema = []`.

**`items.images`:** cada entrada añade `role` (`poster`/`banner`), `license` (`"© titular de derechos"`, `"CC BY-SA 4.0"`, `"Dominio público"`…), `author` (nullable) y `source_url`. No necesita migración.

### Fase 2 (F2-01, F2-07, F2-09)

**`extend_votes_type_check`** (hecha en la F2-01; antes, verifica el nombre con `SELECT conname FROM pg_constraint WHERE conrelid = 'votes'::regclass;`):

```sql
ALTER TABLE votes DROP CONSTRAINT votes_type_check;
ALTER TABLE votes ADD CONSTRAINT votes_type_check
  CHECK (type IN ('vote', 'skip', 'want', 'not_interested'));
```

- `down()`: convierte `want` y `not_interested` en `skip` y restaura la restricción original.
- Índice `(user_id, type)`.
- Semántica:
  - `vote` = lo conozco y lo valoro (`score` obligatorio);
  - `want` = me interesa (*wishlist*);
  - `not_interested` = no me interesa;
  - `skip` = valor heredado que la API normaliza a `not_interested`.

**`profiles.interests`:** jsonb, default `'[]'`, con slugs de categoría. Hecha en la F2-07 (migración `2026_10_30_100000_add_interests_to_profiles_table`).

**Listas** (se crearon en la F2-09 para las editoriales, con las migraciones `2026_10_31_000000_create_lists_table` y `2026_10_31_000100_create_list_items_table`; `is_ranked` lleva `default(false)` aunque aquí no se indicaba; la F3-01 añadió `lists.hidden_by_moderation_at` con `2026_11_01_200000_add_hidden_by_moderation_at_to_lists_table` y creó `list_likes` con `2026_11_01_200100_create_list_likes_table`):

| Tabla | Columnas |
|---|---|
| `lists` | `id` uuid, `user_id` FK (cascade), `title` string(120), `slug` único, `description` text nullable, `visibility` (`public`/`unlisted`/`private`, default `private`), `is_ranked` bool, `is_editorial` bool default false, `cover_item_id` FK nullable (`nullOnDelete`), `items_count` int default 0, `likes_count` int default 0, `hidden_by_moderation_at` timestamp nullable (F3-01), timestamps, borrado suave. Índices `(user_id)` e `(is_editorial, visibility)` |
| `list_items` | `id` uuid, `list_id` FK (cascade), `item_id` FK (cascade), `position` int, `note` string(280) nullable, timestamps. Único `(list_id, item_id)`; índice `(list_id, position)` |
| `list_likes` | `user_id` FK (cascade), `list_id` FK (cascade), `created_at`. PK `(user_id, list_id)`; índice `(list_id)`. Migración `2026_11_01_200100_create_list_likes_table` (F3-01) |

### Fase 3 (F3-01 a F3-08)

- `item_comments.parent_id` (hecha en la F3-02 con la migración `2026_11_01_000000_add_parent_and_vote_to_item_comments_table`): uuid nullable, FK a la propia tabla (`cascadeOnDelete`). Un solo nivel, validado en el Form Request.
- `item_comments.vote_id` (hecha en la F3-02, misma migración): uuid nullable, FK a `votes` (`nullOnDelete`). Si tiene valor, el comentario es una reseña. Máximo una por usuario e ítem: validado en el Form Request y garantizado por el índice parcial único `item_comments_review_unique` sobre `(item_id, user_id) WHERE vote_id IS NOT NULL AND deleted_at IS NULL`.
- `reports` (hecha en la F3-04 con la migración `2026_10_31_200000_create_reports_table`):
  - `id` uuid, `reporter_id` FK nullable (`nullOnDelete`);
  - `reportable_type` (string, FQCN: `App\Models\ItemComment`, `UserList` o `User`, sin *morph map*), `reportable_id` (uuid), con `uuidMorphs('reportable')`;
  - `reason`: `spam`/`odio`/`acoso`/`ilegal`/`spoiler`/`otro`; `details` text nullable;
  - `status`: `pending`/`resolved`/`dismissed`, default `pending`;
  - `resolved_by` FK nullable, `resolution_note` text nullable, `resolved_at` nullable, timestamps;
  - índices `(status, created_at)` y `(reportable_type, reportable_id)`;
  - índice parcial único `reports_pending_unique` sobre `(reporter_id, reportable_type, reportable_id) WHERE status = 'pending'` (un usuario no repite un reporte pendiente del mismo contenido).
- `notifications`: la tabla estándar (`sail artisan make:notifications-table`) con `uuidMorphs('notifiable')` en lugar de `morphs` (los usuarios son UUID; con `morphs` el `notifiable_id` sería bigint y el INSERT fallaría). Hecha en la F3-05.
- `users.username`: string(30), único y nullable. Hecha en la F3-03 con la migración `2026_11_02_000000_add_username_to_users_table`.
- `profiles.is_public`: bool, default **false**. Hecha en la F3-03 con la migración `2026_11_02_000100_add_is_public_to_profiles_table`.
- `item_comments.user_id` nullable (hecha en la F3-08 con la migración `2026_11_03_000000_make_user_id_nullable_on_item_comments_table`): solo cambia la nulabilidad, la FK `item_comments_user_id_foreign` (`cascadeOnDelete`) se conserva. Un comentario con `user_id` nulo es un comentario anonimizado al borrar la cuenta de su autor (texto `[comentario eliminado]`). Su `down()` borra los comentarios anonimizados (y sus respuestas, por la FK `parent_id`) antes de volver a `NOT NULL`.
- `users.privacy_accepted_at`: timestampTz, nullable (hecha en la F3-07 con la migración `2026_11_01_100000_add_privacy_accepted_at_to_users_table`; se rellena en el registro).

### Fase 4

Sin cambios de esquema: cada categoría nueva solo aporta su `attribute_schema`.

## 4. Descartado (no crear)

| Tabla | Motivo |
|---|---|
| `item_translations` | Solo hay español: basta con `name` + `original_title` |
| `item_media` | `images` en jsonb con las claves de licencia es suficiente |
| `item_stats` | Con este tráfico, el bloqueo de fila es breve |
| `user_items` | Rompería unos 30 archivos; ampliar `votes.type` consigue lo mismo |
| `tags`, `tag_provider_map`, `item_tag` | `attributes` jsonb con índice GIN; géneros traducidos con un mapa en `config/catalog.php` |
| `item_relations`, `item_revisions`, `pairwise_votes`, `item_similarities`, `follows`, `badges`, `sync_runs` | Aplazados. Para seguir las sincronizaciones bastan los logs y `failed_jobs` |
