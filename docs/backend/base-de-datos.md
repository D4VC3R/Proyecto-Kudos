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
| `users` | `name` (no único), `email`, `email_verified_at`, `total_kudos`, `creations_accepted`, rachas, `is_banned`, `banned_until`, `ban_reason`, `banned_by` |
| `profiles` | 1:1 con `users`: `avatar`, `biography`, `social_links` (jsonb), `city`, `birthdate` |
| `categories` | `name` (único), `description` (NOT NULL), `slug` (único, clave de ruta), `image`, `attribute_schema` (jsonb, default `'[]'`), `is_active` (default true), `sort_order` (smallInteger, default 0), `icon` (string(32), nullable) |
| `items` | `name`, `description` (text, **nullable** desde la F1-01), `images` (jsonb), `status` (`active`/`inactive`), `vote_avg` (float), `vote_count`, `bayes_score` (float, default 0), `quality_score` (smallInteger, default 0), `creator_id` (nullable), `category_id`, borrado suave. Desde la F1-01: `slug` (único, nullable), `original_title`, `released_at` (date), `attributes` (jsonb, default `'{}'`), `popularity` (float, default 0), `wikidata_id` (string(16), único, nullable), `description_source`, `description_url` y `last_synced_at` (timestampTz). Índices: `(status, vote_avg, vote_count)`, `(category_id, status)`, `(category_id, status, bayes_score)`, `(category_id, status, quality_score)`, `(category_id, status, released_at)`, únicos `items_slug_unique` e `items_wikidata_id_unique`, y GIN: trigram `items_name_trgm_idx(name)`, trigram `items_original_title_trgm_idx(original_title)` y `items_attributes_gin_idx(attributes)` |
| `item_sources` | `item_id` (FK, cascade), `provider`, `external_id`, `url`, `is_primary`, `last_synced_at`, timestamps. Único `(provider, external_id)` e índice `(item_id)` |
| `votes` | `type` (`vote`/`skip`, CHECK `votes_type_check`), `score` decimal(4,1) nullable, `user_id`, `item_id`. Único `votes_user_item_unique(user_id, item_id)`; índice `(item_id, type)` |
| `item_comments` | `content`, `is_hidden`, `hidden_reason`, `hidden_by`, borrado suave |
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
| `quality_score` | smallInteger, default 0 | 0–100; reglas en la F0-05; la calcula `ItemQualityScorer` |

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
| `description_source` | string(32), nullable | `tmdb`, `igdb`, `rawg`, `wikipedia_es`, `wikidata` o `manual` |
| `description_url` | string, nullable | Obligatoria si el texto viene de Wikipedia (CC BY-SA) |
| `last_synced_at` | timestampTz, nullable | Última sincronización con el proveedor principal |

- `description` pasa a nullable. "Sin descripción" es válido: baja `quality_score` y nunca se rellena con plantillas.
- `CREATE INDEX items_original_title_trgm_idx ON items USING GIN (original_title gin_trgm_ops);`
- Índice `(category_id, status, released_at)`.

**`create_item_sources_table`:**

| Columna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `item_id` | uuid FK → `items`, `cascadeOnDelete` | |
| `provider` | string(32) | `tmdb`, `igdb`, `rawg`, `wikidata` o `wikipedia_es` |
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

**`items.images`:** cada entrada añade `license` (`"© titular de derechos"`, `"CC BY-SA 4.0"`, `"Dominio público"`…), `author` (nullable) y `source_url`. No necesita migración.

### Fase 2 (F2-01, F2-07, F2-09)

**`extend_votes_type_check`** (antes, verifica el nombre con `SELECT conname FROM pg_constraint WHERE conrelid = 'votes'::regclass;`):

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

**`profiles.interests`:** jsonb, default `'[]'`, con slugs de categoría.

**Listas** (se crean en la F2 para las editoriales):

| Tabla | Columnas |
|---|---|
| `lists` | `id` uuid, `user_id` FK (cascade), `title` string(120), `slug` único, `description` text nullable, `visibility` (`public`/`unlisted`/`private`, default `private`), `is_ranked` bool, `is_editorial` bool default false, `cover_item_id` FK nullable (`nullOnDelete`), `items_count` int default 0, `likes_count` int default 0, timestamps, borrado suave. Índices `(user_id)` e `(is_editorial, visibility)` |
| `list_items` | `id` uuid, `list_id` FK (cascade), `item_id` FK (cascade), `position` int, `note` string(280) nullable, timestamps. Único `(list_id, item_id)`; índice `(list_id, position)` |
| `list_likes` | **F3.** `user_id` FK (cascade), `list_id` FK (cascade), `created_at`. PK `(user_id, list_id)` |

### Fase 3 (F3-01 a F3-07)

- `item_comments.parent_id`: uuid nullable, FK a la propia tabla (`cascadeOnDelete`). Un solo nivel, validado en el Form Request.
- `item_comments.vote_id`: uuid nullable, FK a `votes` (`nullOnDelete`). Si tiene valor, el comentario es una reseña. Máximo una por usuario e ítem.
- `reports`:
  - `id` uuid, `reporter_id` FK nullable (`nullOnDelete`);
  - `reportable_type`, `reportable_id` (uuid);
  - `reason`: `spam`/`odio`/`acoso`/`ilegal`/`spoiler`/`otro`; `details` text nullable;
  - `status`: `pending`/`resolved`/`dismissed`, default `pending`;
  - `resolved_by` FK nullable, `resolution_note` text nullable, `resolved_at` nullable, timestamps;
  - índices `(status, created_at)` y `(reportable_type, reportable_id)`.
- `notifications`: la tabla estándar (`sail artisan make:notifications-table`).
- `users.username`: string(30), único y nullable.
- `profiles.is_public`: bool, default **false**.
- `users.privacy_accepted_at`: timestampTz, nullable.

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
