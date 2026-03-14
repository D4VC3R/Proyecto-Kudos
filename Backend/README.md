<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Proyecto Kudos - Backend

Backend API de Kudos construido con Laravel, autenticación por token (Sanctum), verificación de email, votación y creación de ítems, sistema de puntos (kudos) y panel de administración con moderación y baneos.

## Objetivo de arquitectura

Este backend sigue una arquitectura orientada a casos de uso para mantener responsabilidades claras:

- `Controller` -> entrada/salida HTTP (auth, validación, respuesta)
- `Action/Query` -> caso de uso concreto (escritura/lectura)
- `Service` -> reglas de negocio y orquestación
- `Repository` -> acceso a datos (Eloquent/DB)

Esto permite evitar controladores grandes, reducir duplicación y aislar la lógica de dominio.

## Estructura principal

```text
app/
  Http/
    Controllers/         # capa HTTP
    Requests/            # validación de entrada
    Middleware/          # políticas transversales (admin, verified, not_banned)
  Actions/               # casos de uso de escritura
    Admin/
      Users/
      Items/
      Proposals/
    Items/
    Votes/
    Categories/
  Queries/               # casos de uso de lectura
    Admin/
      Users/
      Items/
      Proposals/
    Items/
    Categories/
  Services/              # dominio/orquestación
  Repositories/          # persistencia
  Models/                # entidades Eloquent
routes/
  api.php                # rutas públicas, autenticadas y admin
  auth.php               # login/register/logout/verificación
```

## Módulos de negocio

### 1) Autenticación y sesiones

- Login por token con Sanctum (`Bearer`).
- Verificación de email obligatoria en rutas protegidas.
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
- `app/Services/KudosRules.php`
- `app/Services/DailyLoginKudosService.php`
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
- `app/Services/ProposalService.php`
- `app/Actions/Admin/Proposals/ReviewProposalAction.php`
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

Piezas clave:
- `app/Http/Controllers/AdminUserController.php`
- `app/Http/Controllers/AdminItemController.php`
- `app/Http/Controllers/ProposalController.php` (bloque admin)

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

## Persistencia y consistencia

- `users.total_kudos` funciona como cache agregada.
- `kudos_transactions` es fuente de verdad del historial de puntos.
- Reconciliación disponible por comando de auditoría.

## Comandos útiles

### Auditoría de kudos

```bash
php artisan kudos:audit-consistency
```

### Reconciliación automática de kudos

```bash
php artisan kudos:audit-consistency --fix
```

### Entorno local (Docker)

```bash
php artisan migrate:fresh --seed
php artisan route:list
```

## Convención de desarrollo del proyecto

Para nuevas features:

1. Crear `Request` para validación.
2. Crear `Action` (escritura) o `Query` (lectura).
3. Reutilizar/mover reglas de negocio a `Service`.
4. Reutilizar/mover acceso a datos a `Repository`.
5. Dejar el controlador solo para gestionar respuestas HTTP.

