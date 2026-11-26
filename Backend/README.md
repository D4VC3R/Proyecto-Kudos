<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# ⚙️ Proyecto Kudos - Backend

<img src="https://skillicons.dev/icons?i=laravel,php,postgres,redis,docker" alt="Backend Stack" />

> Backend API de Kudos construido con Laravel 12 (PHP 8.5) sobre PostgreSQL 18 y Redis. Gestiona la autenticación por token (Sanctum), autorización por roles (Spatie), verificación de email, votación, creación de ítems, el catálogo importado de fuentes abiertas, listas, reportes y notificaciones, el sistema de puntos (Kudos) y el panel de administración con moderación y baneos.

## Arquitectura

Cada petición sigue el mismo camino: `Controller` -> `Form Request` / `Policy` -> `Action` o `Service` -> `Resource`.

- El **Controller** solo gestiona la entrada y la salida HTTP.
- El **Form Request** valida y autoriza (con *policies*); el **Action** es una escritura transaccional y el **Service**, lógica reutilizable.
- Toda respuesta JSON pasa por un **Resource**. No hay repositorios: Eloquent se usa directamente en Actions y Services.

El detalle (capas, patrones y trampas conocidas) está en [../docs/backend/arquitectura.md](../docs/backend/arquitectura.md).

## Estructura

```text
app/
  Actions/               # escrituras transaccionales (EmitVoteAction, ReviewProposalAction…)
  Catalog/               # proveedores, enriquecimiento e importación del catálogo
  Console/Commands/      # comandos de Artisan (con la subcarpeta Catalog/)
  Contracts/             # interfaces (almacenamiento y procesado de imágenes)
  Http/                  # Controllers (por dominio), Middleware, Requests y Resources
  Jobs/                  # trabajos en cola (con la subcarpeta Catalog/)
  Models/                # entidades Eloquent
  Notifications/         # notificaciones de autenticación y en la aplicación
  Policies/              # autorización por modelo
  Providers/             # configuración de servicios de Laravel
  Rules/                 # reglas de validación propias
  Services/              # lógica reutilizable (Media/ para imágenes)
  Support/               # utilidades sin estado
routes/
  api.php                # rutas de la API (públicas, autenticadas y de administración)
  auth.php               # registro, sesión, contraseña y verificación de email
  console.php            # kudos:audit-consistency y tareas programadas
```

## Dominios

| Dominio | Piezas clave |
|---|---|
| Votos | `EmitVoteAction`, `ChangeVoteAction`, `DeleteVoteAction`, `VoteController`, `ItemStatsService` |
| Kudos | `KudosService`, `config/kudos.php`, tabla `kudos_transactions` (*ledger*) |
| Ítems y categorías | `Item`, `Category`, `ItemController`, `CategoryController`, `NextCategoryItemService`, `ItemQualityScorer` |
| Catálogo | `app/Catalog/`, `ImportCatalogItemOnDemandAction`, `Jobs/Catalog/`, `Console/Commands/Catalog/` |
| Listas | `UserList`, `AddItemToListAction`, `OwnListController`, `HomeFeedService` |
| Comentarios y reportes | `CreateItemCommentAction`, `ModerateCommentAction`, `CreateReportAction`, `ResolveReportAction` |
| Notificaciones | `InAppNotification`, `NotificationController`, `NotifyWishlistReleasesJob` |
| Cuenta y perfil | `ProfileController`, `PublicProfileService`, `DeleteAccountAction`, `AuthenticatedSessionController` |
| Administración | `Admin/*Controller`, `ReviewProposalAction`, `ModerationAuditLogger` |

El mapa completo por dominio está en el §2 de [../docs/backend/arquitectura.md](../docs/backend/arquitectura.md); el flujo del catálogo, en [../docs/integraciones/pipeline-catalogo.md](../docs/integraciones/pipeline-catalogo.md).

## Kudos

El sistema es idempotente y se apoya en el *ledger* `kudos_transactions` (`action_key` único); `users.total_kudos` es un caché. Las reglas están en `config/kudos.php` y la escritura, en `KudosService`. Los kudos se dan por:

- el primer voto de cada ítem (`voting.reward`, con rendimiento decreciente a partir de `threshold` y un tope diario `daily_cap`);
- una propuesta aceptada;
- la racha de login diario.

## Comandos de Artisan

| Comando | Qué hace |
|---|---|
| `catalog:fetch-new` | Encola la importación de novedades y populares del catálogo en la cola `catalog` |
| `catalog:import` | Encola la importación (o el refresco) de un ítem concreto del catálogo |
| `catalog:refresh` | Encola el refresco de los ítems del catálogo sincronizados hace más de N días |
| `catalog:purge-stale` | Encola la desactivación y limpieza de los ítems de TMDB sin refrescar en más de 180 días |
| `items:recalculate-stats` | Encola el recálculo de `vote_count`, `vote_avg` y `bayes_score` de los ítems |
| `items:recalculate-quality` | Encola el recálculo de `quality_score` de los ítems |
| `wishlist:notify-releases` | Avisa a los usuarios de los estrenos de los ítems de su wishlist |
| `kudos:metrics` | Muestra las métricas de uso (usuarios activos, votos por día, cobertura de valoraciones y listas) |
| `kudos:audit-consistency` | Audita y reconcilia `users.total_kudos` con `kudos_transactions` (`--fix` para reconciliar) |

## Entorno local (Docker)

Todo se ejecuta con Laravel Sail, desde `Backend/` (sin alias, con `./vendor/bin/sail`). El paso a paso está en [../docs/infra/entorno-local.md](../docs/infra/entorno-local.md).

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan storage:link
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan kudos:audit-consistency   # audita users.total_kudos frente al ledger (--fix para reconciliar)
```

`migrate:fresh --seed` siembra las categorías, los roles y los usuarios (los 50 de prueba, solo en local), pero no el catálogo: se importa con `catalog:fetch-new` (secuencia completa en entorno-local §3).

## Tests

Los tests son de PHPUnit y se ejecutan siempre con Sail, contra la base de datos PostgreSQL `testing`: nunca SQLite ni la base de datos de desarrollo.

```bash
./vendor/bin/sail up -d
./vendor/bin/sail test                          # todos los tests
./vendor/bin/sail test --filter=EmitVoteTest    # un archivo o un test
```

- `scripts/verify-test-db-guard.sh` (desde `Backend/`) comprueba la protección de `tests/TestCase.php`; ejecútalo si cambias `TestCase.php`, `phpunit.xml` o los traits de base de datos.
- Más detalle en [../docs/backend/testing.md](../docs/backend/testing.md).

## Contrato de la API

- Contrato JSON de respuestas y errores: [docs/api-contract.md](docs/api-contract.md).
- Lista de rutas: `./vendor/bin/sail artisan route:list --path=api`.
- Índice de la documentación: [../docs/README.md](../docs/README.md).
