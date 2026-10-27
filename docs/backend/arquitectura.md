# Backend: arquitectura, convenciones y trampas conocidas

**Responsable:** backend. Stack: Laravel 12, PHP 8.5 (8.4 como mínimo según `composer.json`), PostgreSQL 18, Redis, Sanctum (tokens Bearer), spatie/laravel-permission e intervention/image v3.

> El `README.md` raíz dice "MySQL", pero la base de datos real es **PostgreSQL**. La tarea F0-12 corrige el README.

## 1. Capas

| Capa | Ubicación | Responsabilidad |
|---|---|---|
| Rutas | `routes/api.php`, `routes/auth.php`, `routes/console.php` (comandos y, desde la F0, *schedule*) | Middleware: `auth:sanctum`, `verified`, `not_banned`, `admin` |
| Controller | `app/Http/Controllers/<Dominio>/` | Entrada y salida HTTP. Delega en Actions y Services. Sin lógica de negocio |
| Form Request | `app/Http/Requests/<Dominio>/` | Validación y `authorize()`, que usa las *policies*. Mensajes en español |
| Action | `app/Actions/` | **Un caso de uso de escritura transaccional**, con `execute(...)`. Ejemplos: `EmitVoteAction`, `ReviewProposalAction` (ambas en la raíz de `app/Actions/`) |
| Service | `app/Services/` | Lógica reutilizable u orquestación: `KudosService`, `NextCategoryItemService`, `ModerationAuditLogger`, `Media/*` |
| Model | `app/Models/` | Eloquent: relaciones, *scopes* (`scopeApplyFilters`, `scopeApplySorting`, `scopeAdminApply*`) y *casts* |
| Resource | `app/Http/Resources/` | Toda respuesta JSON pasa por un Resource |
| Policy | `app/Policies/` | Autorización por modelo |
| Job | `app/Jobs/` | Procesado de imágenes en segundo plano (`ProcessProposalImagesJob`, `ProcessUserAvatarJob`) |

Todos los modelos usan **claves UUID** (`HasUuids`). Los errores JSON tienen un formato único definido en `bootstrap/app.php`:

```json
{ "error": { "code": "validation_error", "message": "…", "details": { } } }
```

Los códigos son `unauthenticated`, `forbidden`, `not_found`, `route_not_found`, `validation_error`, `conflict` e `invalid_signature`.

**Módulo nuevo (Fase 1):** `app/Catalog/` contiene proveedores externos, enriquecimiento e importación. Ver [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md).

## 2. Mapa de archivos por dominio

### Votos
- `app/Models/Vote.php`, con los tipos `TYPE_VOTE='vote'` y `TYPE_SKIP='skip'`.
- `app/Actions/EmitVoteAction.php`, `ChangeVoteAction.php` y `DeleteVoteAction.php`.
- `app/Http/Requests/Votes/*`: `StoreVoteRequest`, `UpdateVoteRequest`, `DeleteVoteRequest`, `ListMyVotesRequest` y `GetNextCategoryItemRequest`.
- `app/Http/Controllers/Vote/`, `app/Policies/VotePolicy.php` y `app/Http/Resources/VoteResource.php`.
- `app/Services/NextCategoryItemService.php`: la cola de votación, cacheada 6 h por usuario y categoría.

### Ítems y categorías
- `app/Models/Item.php`: los *scopes* `applyFilters` (búsqueda con `ilike` sobre `name`), `applySorting` (`vote_avg|recent|name|random`), `adminApplyFilters` y `adminApplySorting`.
- `app/Models/Category.php`: `getRankingPaginator()` ordena por `vote_avg` y `vote_count`. La clave de ruta es `slug`.
- `app/Http/Controllers/Item/ItemController.php` (`index` usa `ListItemsRequest`; la categoría es **opcional**, así que sirve para la búsqueda global) y `app/Http/Controllers/Category/`.
- Resources: `ItemListResource`, `ItemDetailResource`, `ItemMinimalResource`, `CategoryResource`, `CategoryRankingResource` y `CategoryWithItemsResource`.

### Kudos (gamificación)
- `app/Services/KudosService.php` tiene tres métodos públicos:
  - `processDailyLogin(User)`;
  - `processFirstTimeVote(User, itemId)`;
  - `processProposalAccepted(User, proposalId)`.

  Todos pasan por `awardIfFirst`, que garantiza la **idempotencia** mediante un `action_key` único en `kudos_transactions`.
- `config/kudos.php` define las recompensas, el rendimiento decreciente, el tope diario y la racha de login.
- `routes/console.php` contiene `kudos:audit-consistency [--fix]`.

### Propuestas y moderación
- `app/Actions/ReviewProposalAction.php` (no existe un `ProposalService`; la lógica está en la Action y en `ProposalController`).
- `app/Services/ModerationAuditLogger.php`, que escribe en el canal de log `moderation`.
- Comentarios: `app/Models/ItemComment.php`, con `is_hidden`, `hidden_reason` y `hidden_by`.

### Media
- `app/Services/Media/RemoteImageDownloader.php`: `downloadToTemp(url)` valida la URL (anti-SSRF), el MIME y el tamaño. `isRemoteUrl()`.
- `app/Services/Media/MediaManager.php`: `processItemVariants(absTempPath, targetDir, ?filename)` genera las variantes `thumb` y `banner`. También `processAvatar` y `processCategory`.
- `database/seeders/Trait/DownloadsSeedImages.php` muestra cómo combinar los dos anteriores. **Es la referencia para los proveedores del catálogo**, pero no se reutiliza el trait como tal: es de seeders.

**Formato del campo `items.images`** (jsonb, array):

```json
[{ "disk": "public", "alt": "…", "order": 0,
   "variants": { "thumb": "items/…/thumb.webp", "banner": "items/…/banner.webp" },
   "meta": { } }]
```

Desde la Fase 1 cada entrada añade `"license"`, `"author"` y `"source_url"` (ver [base-de-datos.md](base-de-datos.md)).

## 3. Trampas y bugs conocidos

| # | Problema | Dónde | Tarea que lo resuelve |
|---|---|---|---|
| 1 | **Las notas de medio punto se truncan.** `EmitVoteAction` hace `(int) $voteData['score']` al actualizar la media. `ChangeVoteAction` hace `(int)` sobre la nota nueva y la antigua, y **guarda la truncada** (7.5 pasa a 7). `DeleteVoteAction` usa el float. Las medias derivan | `app/Actions/*Vote*` | F0-03 |
| 2 | La media se mantiene de forma incremental (`avg*count ± score`), lo que acumula error de redondeo | Las mismas | F0-03: recálculo con `AVG(score)` |
| 3 | Un `skip` es **permanente**. `UpdateVoteRequest` solo permite editar si `type === 'vote'`, y `EmitVoteAction` devuelve el voto existente sin modificarlo | `UpdateVoteRequest::withValidator`, `EmitVoteAction::execute` | F2-02 |
| 4 | En PostgreSQL, `$table->enum()` crea un `varchar` con una restricción `CHECK` (`votes_type_check`). Para añadir valores hay que **borrar y recrear la restricción** con SQL | Migración de `votes` | F2-01 |
| 5 | `CheckMinimumKudos` existe pero **no está registrado** en `bootstrap/app.php` | `app/Http/Middleware/` | Aplazado: no usar |
| 6 | `Vote::kudosTransactions()` es código muerto: las transacciones de voto referencian a `Item`, no a `Vote` | `app/Models/Vote.php` | No tocar salvo que moleste |
| 7 | **No hay tests**: no existe la carpeta `tests/`, aunque el README dice lo contrario. `phpunit.xml` usa SQLite en memoria, que no puede ejecutar las migraciones | `phpunit.xml` | F0-01 |
| 8 | **No hay scheduler** en producción | `compose.prod.yml` | F0-10 |
| 9 | `users.name` **no es único**. No sirve como identificador público de perfil | Migración de `users` | F3-03 |
| 10 | La búsqueda usa `ilike '%…%'`, apoyada en el índice GIN trigram `items_name_trgm_idx`. Un campo de búsqueda nuevo (por ejemplo `original_title`) necesita su propio índice trigram | `Item`, `Vote::scopeApplyFilters` | F1-01 |
| 11 | El workflow `.github/workflows/docs.yml` publica phpDocumentor en `Backend/docs/api`. **No confundir** con esta carpeta `docs/` raíz | — | — |

## 4. Patrones que se deben seguir

**Escritura concurrente sobre un ítem:**
- se hace dentro de `DB::transaction` con `Item::lockForUpdate()`, como las Actions actuales;
- las transacciones deben ser cortas.

**Kudos:**
- se otorgan siempre a través de `KudosService`, nunca escribiendo en `users.total_kudos` directamente;
- cada recompensa nueva necesita un `reason` en `config/kudos.php` y un `action_key` determinista.

**Parámetros ajustables:**
- van en `config/` (`config/kudos.php`, y el nuevo `config/catalog.php`);
- llevan un comentario explicativo.

**Comandos de consola:**
- los pequeños, como *closures* en `routes/console.php` (patrón actual);
- si crecen, como clases en `app/Console/Commands/` (Laravel 12 las descubre automáticamente);
- **los comandos del catálogo van como clases.**

**Tareas programadas:**
- se registran en `routes/console.php` con `Schedule::command(...)`;
- llevan `->withoutOverlapping()` y `->onOneServer()` cuando aplique.

**Mantener el contrato JSON:**
- si cambia la columna que hay detrás de un campo (por ejemplo, el orden por `bayes_score`), el Resource sigue devolviendo los mismos campos que espera el frontend;
- los campos nuevos se **añaden**, no se renombran.
