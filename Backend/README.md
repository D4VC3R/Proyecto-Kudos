<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# ⚙️ Proyecto Kudos - Backend

<img src="https://skillicons.dev/icons?i=laravel,php,postgres,redis,docker" alt="Backend Stack" />

> Backend API de Kudos construido con Laravel 12 (PHP 8.5) sobre PostgreSQL 18 y Redis. Gestiona la autenticación por token (Sanctum), autorización por roles (Spatie), verificación de email, votación, creación de ítems, el sistema de puntos (Kudos) y el panel de administración con moderación y baneos.

## Objetivo de arquitectura

Este backend sigue una arquitectura adaptada orientada a aislar la lógica de dominio y mantener los controladores limpios, basada en Patrón MVC potenciado con Actions y Services:

- `Controller` -> Mismo propósito que en MVC estándar (entrada/salida HTTP, validación inicial mediante Form Requests, retorno de respuestas/recursos).
- `Action` -> Caso de uso concreto (ej. EmitirVoto, RevisarPropuesta), encapsulando la lógica de una sola acción transaccional de escritura.
- `Service` -> Lógica de negocio reutilizable, orquestación, reglas complejas o centralización (ej. KudosService, ModerationAuditLogger).
- `Model` -> Entidades Eloquent (acceso a base de datos, relaciones y scopes).

Esto permite evitar controladores grandes, reducir la carga cognitiva al leer el flujo de una petición y centralizar mejor las reglas de la aplicación.
No se utilizan Repositorios ni Query objects; Eloquent y los Models se usan de forma directa en las Actions y Services.

## Estructura principal

```text
app/
  Actions/               # operaciones transaccionales únicas (ej: EmitVoteAction, ReviewProposalAction)
  Console/Commands/      # comandos de Artisan (items:recalculate-stats, items:recalculate-quality)
  Contracts/Media/       # interfaces de almacenamiento y procesado de imágenes
  Http/
    Controllers/         # capa HTTP, agrupada por dominio (Admin, Auth, Category, Item, Proposal, User, Vote)
    Requests/            # validación de entrada
    Middleware/          # políticas transversales (admin, verified, not_banned)
    Resources/           # serialización y formato de respuestas JSON
  Jobs/                  # trabajos en cola (imágenes, recálculo de medias y de calidad)
  Models/                # entidades Eloquent
  Notifications/         # notificaciones de autenticación
  Policies/              # autorización detallada por modelo
  Providers/             # configuración de servicios de Laravel
  Rules/                 # reglas de validación propias
  Services/              # dominio/orquestación y reglas de negocio reutilizables (Media/ para imágenes)
routes/
  api.php                # rutas de la api (públicas, autenticadas y admin)
  auth.php               # login/register/logout/verificación
  console.php            # comandos de consola y tareas programadas
```

## Módulos de negocio

### 0) Comentarios

- Comentarios de usuarios en items con moderación admin (`hide` / `unhide`).

Piezas clave:
- `app/Models/ItemComment.php`
- `app/Http/Controllers/Item/ItemCommentController.php`

### 1) Autenticación y sesiones

- Login por token con Sanctum (`Bearer`).
- Verificación de email en rutas protegidas, con interruptor `AUTH_EMAIL_VERIFICATION` (apagada por defecto: las cuentas se crean ya verificadas y el registro devuelve `requires_email_verification: false`).
- Bloqueo de cuentas baneadas en login y en rutas autenticadas.
- Cierre de sesión individual y global (revocación de tokens).

Piezas clave:
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Middleware/EnsureEmailIsVerified.php`
- `app/Http/Middleware/EnsureUserIsNotBanned.php`

### 2) Sistema de kudos

Sistema idempotente con ledger de transacciones.

- Tabla `kudos_transactions` con `action_key` único.
- Reglas centralizadas en `config/kudos.php`.
- Escritura centralizada en `KudosService`.
- Motivos actuales:
  - primer voto por item
  - propuesta aceptada
  - login diario en racha (10/25/50/100/200)

Piezas clave:
- `app/Services/KudosService.php`
- `config/kudos.php`

### 3) Propuestas y moderación

- Usuario crea propuestas (`pending`).
- Admin revisa: `accepted`, `rejected` o `changes_requested`.
- Si se acepta:
  - se crea item
  - se otorgan kudos al creador
  - se incrementa `creations_accepted`
- Auditoría de moderación por logs.

Piezas clave:
- `app/Actions/ReviewProposalAction.php`
- `app/Http/Controllers/Proposal/ProposalController.php`
- `app/Services/ModerationAuditLogger.php`

### 4) Administración

Incluye gestión de usuarios, items y propuestas:

- **Usuarios**
  - listado con filtros (`role`, `is_banned`, `ban_state`, `search`)
  - ban temporal/permanente y unban
  - revocación de todas las sesiones
- **Items**
  - listado admin con filtros
  - edición completa
  - moderación de estado (`active/inactive`) con motivo
- **Propuestas**
  - listado admin histórico con filtros
  - listado pending
  - review
- **Comentarios**
  - ocultar / restaurar comentarios de items

Piezas clave:
- `app/Http/Controllers/Admin/AdminUserController.php`
- `app/Http/Controllers/Admin/AdminItemController.php`
- `app/Http/Controllers/Proposal/ProposalController.php` (bloque admin)

## Estándares de Arquitectura y Patrones Implementados

### 1. Form Requests (Validación)
Toda validación de datos de entrada debe realizarse mediante **Form Requests** (`app/Http/Requests`).
- Los Controladores no deben contener reglas de validación en los métodos directamente.
- Los Request capturan datos, validan y pueden aplicar conversiones antes de inyectarse al método.
- Si la validación falla, se retorna automáticamente un estándar de error `422 Unprocessable Entity`.

### 2. API Resources (Serialización)
Nunca se deben retornar Modelos Eloquent o arrays de datos en crudo desde el Controller. Todo debe transformarse usando **API Resources** (`app/Http/Resources`).
- Mutan la data subyacente para no revelar ids internos irrelevantes, fechas en formatos incorrectos o campos sensibles.
- Manejan el "lazy loading" y carga de relaciones anidadas condicionalmente (`whenLoaded`).
- Permiten extender de manera nativa objetos de respuesta con la llave `meta`.

### 3. Autorización (Roles y Policies)
La seguridad a nivel de métodos y accesos combina dos tecnologías:
- **Spatie Laravel Permission:** Estandariza la autorización a alto nivel a través de roles (`admin`, `user`). Aplicado principalmente en grupos de Middleware (ej: un perfil sin verificar no accede o `admin` para administración general).
- **Laravel Policies (`app/Policies`):** Reglas pormenorizadas por recurso. Operaciones sobre un `Item`, `Proposal` o un `Vote` pasan por métodos como `$this->authorize('update', $proposal)` dentro del controlador. Aportan una capa extra de seguridad para asegurar de que un creador solo puede editar lo suyo, mientras que un admin tiene poder global.

### 4. Logging Transaccional y Auditoría
Cualquier manipulación de estado clave se registra internamente.
- **ModerationAuditLogger**: Toda aprobación o rechazo en ítems y comentarios, así como subidas de propuestas pasa a un track controlable para trazar las responsabilidades si existe vandalismo.
- Acciones como transacciones de **Kudos** mantienen un registro en modo libro mayor ("ledger") donde un identificador unívoco previene dar puntos doblemente a un usuario.

### 5. Estándar de Respuestas API (Contract)
Nuestra API responde bajo un formato JSON estrictamente predecible (Documentado internamente en detalle en `docs/api-contract.md`).  
Por norma general las respuestas de mutación o éxito simple estructuran:
```json
{
  "message": "Texto descriptivo de éxito",
  "data": { },
  "meta": { }
}
```
En caso de respuestas con error, el contrato engloba bajo una propiedad estandarizada:
```json
{
  "error": {
    "code": "validation_error",
    "message": "La solicitud contiene errores.",
    "details": { "campo": ["Mensaje de error"] }
  }
}
```

## Middleware y seguridad

Middlewares relevantes:

- `auth:sanctum` -> autenticación por token
- `verified` -> email verificado
- `not_banned` -> bloquea usuarios suspendidos
- `admin` -> rol administrador

Grupos de rutas:
- Públicas (`categories`, `items` list)
- Autenticadas (`profile`, `votes`, `proposals` de usuario, `my-items`)
- Admin (`admin/users`, `admin/items`, `admin/proposals`, categorías admin)

## Consistencia de Kudos y Seeders

- `users.total_kudos` es un caché acumulado.
- `kudos_transactions` funciona como ledger (fuente de la verdad).

### Seed de items desde snapshots locales

`ItemSeeder` carga directamente JSONs en `database/seed-data/`.

Categorías cubiertas por snapshots locales:

- videojuegos
- películas
- series
- países
- ciudades
- politicos
- álbumes musicales
- artistas musicales
- libros

### Entorno local (Docker)

Todo se ejecuta con Laravel Sail, desde `Backend/` (sin alias, con `./vendor/bin/sail`). El paso a paso está en [../docs/infra/entorno-local.md](../docs/infra/entorno-local.md).

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan storage:link
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan kudos:audit-consistency   # audita users.total_kudos frente al ledger (--fix para reconciliar)
```

`migrate:fresh --seed` siembra el catálogo antiguo y descarga imágenes, así que tarda (entorno-local §3).

### Tests

Los tests son de PHPUnit y se ejecutan siempre con Sail, contra la base de datos PostgreSQL `testing`: nunca SQLite ni la base de datos de desarrollo.

```bash
./vendor/bin/sail up -d
./vendor/bin/sail test                          # todos los tests
./vendor/bin/sail test --filter=EmitVoteTest    # un archivo o un test
```

- `scripts/verify-test-db-guard.sh` (desde `Backend/`) comprueba la protección de `tests/TestCase.php`; ejecútalo si cambias `TestCase.php`, `phpunit.xml` o los traits de base de datos.
- Más detalle en [../docs/backend/testing.md](../docs/backend/testing.md).

## Convención de desarrollo del proyecto

Para el desarrollo de nuevas funcionalidades:

1. **Rutas:** Registrar en `api.php` o `auth.php` según contexto.
2. **Validación:** Crear un `Request` (`make:request`) específico.
3. **Controlador:** Crear un `Controller` (`make:controller`). Su trabajo debe limitarse a delegar datos extraídos y retornar un `Resource`.
4. **Lógica de Ejecución Transaccional:** Crear un `Action` si la petición requiere manejar transacciones complejas o varias mutaciones en base de datos.
5. **Lógica de Dominio Reutilizable:** Centralizar las validaciones o herramientas comunes de modelo de datos en un `Service`.
6. **Respuesta:** Crear y retornar siempre un `Resource` (`make:resource`) o `ResourceCollection`. No devuelvas arrays fijos manualmente.

## Documentación de contrato API

- Contrato JSON de respuestas y errores: `docs/api-contract.md`
- Referencia completa de endpoints y flujos: `docs/api-reference.md`
- Documentación del rediseño (Kudos 2.0): [../docs/README.md](../docs/README.md)
