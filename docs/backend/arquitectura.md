# Backend: arquitectura

Stack: Laravel 12, PHP 8.5 (mínimo 8.4 en `composer.json`), PostgreSQL 18, Redis, Sanctum (tokens Bearer), spatie/laravel-permission e intervention/image v3. Las rutas son relativas a `Backend/`.

## 1. Capas

| Capa | Ubicación | Responsabilidad |
|---|---|---|
| Rutas | `routes/api.php`, `routes/auth.php`, `routes/console.php` (comandos y *schedule*) | Middleware: `auth:sanctum`, `verified`, `not_banned`, `admin` (alias en `bootstrap/app.php`) |
| Controller | `app/Http/Controllers/<Dominio>/` | HTTP de entrada y salida; sin lógica de negocio |
| Form Request | `app/Http/Requests/<Dominio>/` | Validación y `authorize()` con *policies*; mensajes en español |
| Action | `app/Actions/` (en la raíz, sin subcarpetas) | Un caso de uso de escritura transaccional con `execute(...)`: `EmitVoteAction`, `ChangeVoteAction`, `DeleteVoteAction`, `ReviewProposalAction` |
| Service | `app/Services/` | Lógica reutilizable: `KudosService`, `NextCategoryItemService`, `ItemStatsService`, `ItemQualityScorer`, `ModerationAuditLogger`, `Media/*` |
| Model | `app/Models/` | Relaciones, *scopes* (`scopeApplyFilters`, `scopeApplySorting`, `scopeAdminApply*`) y *casts* |
| Resource | `app/Http/Resources/` | Toda respuesta JSON |
| Policy | `app/Policies/` | Autorización por modelo |
| Job | `app/Jobs/` | `ProcessProposalImagesJob`, `ProcessUserAvatarJob`, `RecalculateItemStatsJob`, `RecalculateItemQualityJob` (cola `catalog`) |
| Support | `app/Support/` | Utilidades sin estado: `Like` (escapa los comodines de `LIKE`/`ILIKE`, F0-15) |
| Catálogo (F1) | `app/Catalog/` | Proveedores, enriquecimiento e importación: ver [../integraciones/pipeline-catalogo.md](../integraciones/pipeline-catalogo.md) |

- Todos los modelos usan UUID (`HasUuids`).
- Formato de error (`bootstrap/app.php`): `{"error": {"code", "message", "details?"}}`, con los códigos `unauthenticated`, `forbidden`, `not_found`, `route_not_found`, `validation_error`, `conflict` e `invalid_signature`.
- **Verificación de email:** `config('auth.verification.enabled')` (`AUTH_EMAIL_VERIFICATION`, por defecto `false`). Si está apagada, el registro marca la cuenta como verificada. El middleware `verified` siempre comprueba `hasVerifiedEmail()`.

## 2. Mapa por dominio

**Votos**
- `app/Models/Vote.php`: `TYPE_VOTE='vote'` y `TYPE_SKIP='skip'`; `score` es un float (`decimal(4,1)`) y se valida con `multiple_of:0.5`.
- Actions `EmitVoteAction`, `ChangeVoteAction` y `DeleteVoteAction`. Las tres recalculan con `ItemStatsService::recalculate()` (`COUNT`/`AVG` de `type = 'vote'`, con `Item::lockForUpdate()`), que también calcula `bayes_score`. El servicio tiene además `bayesScore()`, `categoryMean()` (C, cacheada 1 h en `category-mean:{id}`) y `refreshCategoryMean()`. `ChangeVoteAction` guarda la nota como float. Un `skip` no recalcula en `EmitVoteAction`. `EmitVoteAction` bloquea el ítem (`Item::withTrashed()->lockForUpdate()`) al **principio** de la transacción, antes de comprobar el voto existente y de insertarlo: el `INSERT` en `votes` toma un `KEY SHARE` sobre el ítem por la FK y, si el `FOR UPDATE` de `recalculate()` llegara después, dos votos simultáneos al mismo ítem podrían bloquearse mutuamente (F0-16).
- `app/Http/Requests/Votes/`: `StoreVoteRequest`, `UpdateVoteRequest`, `DeleteVoteRequest`, `ListMyVotesRequest` y `GetNextCategoryItemRequest`.
  - `StoreVoteRequest` rechaza con 422 los ítems borrados (`exists` con `withoutTrashed()`), tanto en `vote` como en `skip`, incluso si ya había una interacción (antes daba 200 idempotente); los inactivos dan 403 por `VotePolicy::create()` en `authorize()` (F0-21).
- `app/Http/Controllers/Vote/`, `app/Policies/VotePolicy.php` y `app/Http/Resources/VoteResource.php`.
- `app/Services/NextCategoryItemService.php`: cola de 50 ítems, cacheada 6 h en `next-item-queue:{user}:{category}`. Excluye los ítems con `quality_score` por debajo de `catalog.min_quality_for_discovery` (`discoverable()`). Al servir cada ítem vuelve a filtrar el lote con los mismos criterios (activo, `discoverable()`, sin borrar y sin voto ni *skip* del usuario), así que no ofrece ítems votados desde la ficha ni ítems desactivados o empobrecidos después de generarse el lote (F0-17).

**Ítems y categorías**
- `app/Models/Item.php`:
  - `applyFilters`: `ilike` sobre `name` (el texto se escapa con `App\Support\Like::contains()`, F0-15; igual en `adminApplyFilters` de Item, Proposal y User y en `Vote::applyFilters`; toda búsqueda nueva debe usar `Like::contains()`); acepta `discoverable` (`scopeDiscoverable`, umbral `catalog.min_quality_for_discovery`);
  - el hook `saving` (`booted()`) recalcula `quality_score` con `ItemQualityScorer` si cambian `images`, `description`, `released_at` o `attributes`;
  - `applySorting`: `vote_avg|recent|name|random`; con `vote_avg` ordena por `bayes_score desc`, `vote_count desc` (y `id`);
  - `adminApplyFilters` y `adminApplySorting`;
  - `STATUS_ACTIVE` y `STATUS_INACTIVE`.
- `app/Models/Category.php`: `getRankingPaginator()` ordena por `bayes_score`, `vote_count` y `name`; la clave de ruta es `slug`.
- `app/Http/Controllers/Item/ItemController.php`: `index` usa `ListItemsRequest` (que valida `search` con `min:2`, F0-09), la categoría es opcional y ya hace *eager loading* de `category`.
- Resources: `ItemListResource`, `ItemDetailResource`, `ItemMinimalResource`, `CategoryResource`, `CategoryRankingResource` y `CategoryWithItemsResource`. `user_vote` (`id`, `score`, `voted_at`) y `can_vote` (false si existe cualquier voto) salen de la relación `userVote`.
- `items.creator_id` es nullable: los ítems importados no tienen creador.

**Kudos**
- `app/Services/KudosService.php`: `processDailyLogin(User)`, `processFirstTimeVote(User, itemId)` y `processProposalAccepted(User, proposalId)`. Todos pasan por `awardIfFirst`, que garantiza la idempotencia con un `action_key` único en `kudos_transactions`.
- `config/kudos.php`: `voting.*` (recompensa, rendimiento decreciente y tope diario; es lo que usa `calculateVoteReward`), `rewards.*` (propuesta y rachas) y `reasons`. `rewards.vote_first_time_item` es una clave muerta. La sección `ranking.*` (`bayes_min_votes`, `category_mean_fallback`) ajusta el ranking bayesiano.
- `kudos:audit-consistency [--fix]` es un *closure* en `routes/console.php`.
- `items:recalculate-stats {--category=slug} {--chunk=200}` (`app/Console/Commands/RecalculateItemStatsCommand.php`) primero refresca C de las categorías afectadas (`refreshCategoryMean`) y después encola un `RecalculateItemStatsJob` por bloque en la cola `catalog`. Está programado a diario a las 03:00 en `routes/console.php`.
- `items:recalculate-quality {--category=slug} {--chunk=200}` (`app/Console/Commands/RecalculateItemQualityCommand.php`) encola un `RecalculateItemQualityJob` por bloque en la cola `catalog`; el job escribe con `DB::table` (sin eventos ni `updated_at`). Programado los lunes a las 03:30.
- `config/catalog.php`: `min_quality_for_discovery` (40), `template_description_patterns` y `reserved_slugs` (F0-09: slugs de categoría prohibidos por coincidir con rutas de primer nivel del frontend; se valida con `App\Rules\NotReservedSlug` en `StoreCategoryRequest` y `UpdateCategoryRequest`); la F1-02 lo amplía.

**Propuestas y moderación**
- `app/Actions/ReviewProposalAction.php` y `app/Http/Controllers/Proposal/ProposalController.php`. No existe un `ProposalService`.
- `app/Services/ModerationAuditLogger.php`: canal de log `moderation`.
- `app/Models/ItemComment.php`: `is_hidden`, `hidden_reason` y `hidden_by`.

**Media**
- `app/Services/Media/RemoteImageDownloader.php`: `downloadToTemp(url)` valida la URL (anti-SSRF con `gethostbyname()`), el MIME y el tamaño; también `isRemoteUrl()`.
- `app/Services/Media/MediaManager.php`: `processItemVariants(absTempPath, targetDir, ?filename)` genera las variantes `thumb` y `banner`; también `processAvatar` y `processCategory`.
- `database/seeders/Trait/DownloadsSeedImages.php` es el **patrón de referencia** para combinar los dos anteriores. No se reutiliza el trait: es de seeders.

`items.images` (jsonb, array). Desde la F1, cada entrada añade `license`, `author` y `source_url`:

```json
[{ "disk": "public", "alt": "…", "order": 0,
   "variants": { "thumb": "items/…/thumb.webp", "banner": "items/…/banner.webp" },
   "meta": { } }]
```

## 3. Trampas conocidas

| # | Problema | Dónde | Lo resuelve |
|---|---|---|---|
| 1 | Las notas de medio punto se truncan. `EmitVoteAction` hace `(int)` sobre la nota al actualizar la media. `ChangeVoteAction` hace `(int)` sobre la nota nueva y la antigua, y **guarda la truncada**. `DeleteVoteAction` usa el float | `app/Actions/*Vote*` | F0-03 (resuelta) |
| 2 | Las medias son incrementales (`avg*count ± score`) y acumulan error | Las mismas | F0-03 (resuelta) |
| 3 | `skip` es permanente: `UpdateVoteRequest::withValidator` solo permite editar si `type === 'vote'`, y `EmitVoteAction` devuelve el voto existente sin tocarlo | — | F2-02 |
| 4 | `$table->enum()` en PostgreSQL crea un `varchar` + CHECK (`votes_type_check`). Para añadir valores hay que borrar y recrear la restricción con SQL | Migración de `votes` | F2-01 |
| 5 | `CheckMinimumKudos` existe pero no está registrado | `app/Http/Middleware/` | Aplazado: no usar |
| 6 | `Vote::kudosTransactions()` es código muerto | `app/Models/Vote.php` | No tocar |
| 7 | `phpunit.xml` usa SQLite en memoria, que no puede ejecutar las migraciones | `phpunit.xml` | F0-01 |
| 8 | No hay scheduler en producción | `compose.prod.yml` | F0-10 (resuelta) |
| 9 | `users.name` no es único | Migración de `users` | F3-03 (`username`) |
| 10 | La búsqueda (`ilike '%…%'`) depende del índice GIN trigram `items_name_trgm_idx`. Cada campo de búsqueda nuevo necesita su propio índice trigram | `Item`, `Vote::scopeApplyFilters` | F1-01 |
| 11 | `DatabaseSeeder` usa `WithoutModelEvents`: los hooks de `Item` (`saving`, `creating`) no se ejecutan al sembrar | `database/seeders/` | Se tiene en cuenta en F0-05 y F1-01 |
| 12 | `.github/workflows/docs.yml` publica phpDocumentor en `Backend/docs/api`. No tiene nada que ver con `docs/` | — | — |

## 4. Patrones

- **Escritura sobre un ítem:** dentro de `DB::transaction`, con `Item::lockForUpdate()` como **primera** consulta (antes de insertar filas que referencien el ítem por FK, como `votes`) y transacciones cortas.
- **Kudos:** siempre a través de `KudosService`, nunca escribiendo `users.total_kudos` directamente. Cada recompensa nueva necesita un `reason` en `config/kudos.php` y un `action_key` determinista.
- **Comandos:** los pequeños, como *closures* en `routes/console.php`; los grandes, como clases en `app/Console/Commands/` (se descubren solas; la carpeta ya existe, con `RecalculateItemStatsCommand`). **Los del catálogo son clases.**
- **Tareas programadas:** `Schedule::command(...)` o `Schedule::job(...)` en `routes/console.php`, con `withoutOverlapping()` (y `onOneServer()` cuando aplique). Se ejecutan en el contenedor `scheduler` (128M): **solo encolan**, el trabajo pesado va en jobs ([../infra/produccion.md](../infra/produccion.md) §2).
- **Contrato JSON:** si cambia la columna que hay detrás de un campo (por ejemplo, ordenar por `bayes_score`), el Resource sigue devolviendo los mismos campos (R7).
