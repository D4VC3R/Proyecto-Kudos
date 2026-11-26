# Contrato JSON de la API

Este documento define el contrato de respuesta para consumo de frontend.

La fuente de verdad son `routes/*.php` y `bootstrap/app.php`. La lista completa de rutas se obtiene con `./vendor/bin/sail artisan route:list --path=api` (desde `Backend/`).

## 1) Respuestas exitosas

### Mutaciones (`POST`, `PUT`, `PATCH`, `DELETE`)

```json
{
  "message": "Texto descriptivo en español",
  "data": {},
  "meta": {}
}
```

- `message`: obligatorio.
- `data`: opcional (no aplica en algunas eliminaciones).
- `meta`: opcional (flags, totales, estado idempotente, etc.).

### Listados

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 0
  },
  "links": {
    "first": null,
    "last": null,
    "prev": null,
    "next": null
  }
}
```

- `meta` y `links` son opcionales según endpoint.

### Sin contenido

Cuando no hay recurso que devolver por diseño de flujo, se usa `204 No Content` sin body.

## 2) Respuestas de error

```json
{
  "error": {
    "code": "validation_error",
    "message": "La solicitud contiene errores de validación.",
    "details": {
      "campo": ["Mensaje en español"]
    }
  }
}
```

Campos:
- `error.code`: identificador técnico estable.
- `error.message`: mensaje legible en español.
- `error.details`: opcional (normalmente en validación).

Códigos usados:

| HTTP | `code` | Cuándo |
|---|---|---|
| 401 | `unauthenticated` | Falta el token o no es válido. |
| 403 | `forbidden` | El usuario no tiene permiso para la acción. |
| 403 | `invalid_signature` | La URL firmada (por ejemplo, la de verificar el email) no es válida o ha caducado. |
| 403 | `invalid_hash` | El hash de `verify-email` no corresponde al usuario. |
| 404 | `not_found` | El recurso no existe: un modelo que no se encuentra (con un mensaje por modelo) o una *policy* que lo oculta con `Response::denyAsNotFound`. |
| 404 | `route_not_found` | Solo cuando la ruta no existe. |
| 405 | `method_not_allowed` | El método HTTP no está permitido en esa ruta. |
| 409 | `conflict` | Conflicto con el estado actual del recurso. |
| 422 | `validation_error` | La petición no pasa la validación (`details` por campo). |
| 429 | `too_many_requests` | Límite de peticiones superado; la respuesta trae la cabecera `Retry-After`. |
| 500 | `server_error` | Error inesperado; `details` solo se incluye en los entornos `local` y `testing`. |
| 503 | `catalog_unavailable` | El catálogo externo no está disponible (búsqueda e importación). |

## 3) Reglas de votación

### `POST /api/votes`

- Crea una interacción. `type` es `vote`, `want` o `not_interested`; el valor heredado `skip` se acepta y se guarda como `not_interested`.
- `score` es obligatorio con `vote` (de 0 a 10, en pasos de 0,5) y está prohibido con `want` y `not_interested`.
- Es idempotente por (`user_id`, `item_id`):
  - si la interacción ya existe, responde `200` sin mutar.
  - si se crea nueva, responde `201`.
- Un ítem borrado (*soft delete*) responde `422` `validation_error` con `details.item_id` ("El ítem no existe o ya no está disponible."), aunque ya hubiera una interacción. Un ítem inactivo responde `403` `forbidden`.

`meta` trae `total_kudos`, `kudos_awarded`, `vote_type`, `was_existing`, `idempotent_hit` y `reason` (`already_voted`, `already_wanted` o `already_skipped`, este último para `not_interested` y `skip`; `null` si se crea).

Ejemplo `200` idempotente (con `skip` heredado):

```json
{
  "message": "La interacción ya estaba registrada para este item.",
  "data": {
    "id": "...",
    "item_id": "...",
    "type": "not_interested",
    "score": null
  },
  "meta": {
    "total_kudos": 0,
    "kudos_awarded": 0,
    "vote_type": "not_interested",
    "was_existing": true,
    "idempotent_hit": true,
    "reason": "already_skipped"
  }
}
```

### `PUT /api/votes/{vote}`

- `type` es opcional: si falta, se mantiene el tipo actual (`vote`, `want` o `not_interested`; `skip` se acepta y se guarda como `not_interested`, también en los votos heredados).
- De `vote` a `vote`: cambia la nota (`score` opcional; si no llega, se conserva) y recalcula la media, sin kudos.
- De `want`, `not_interested` o `skip` a `vote`: hace falta `type: "vote"` y la nota (`score`, obligatoria); la guarda y puede dar kudos. Enviar solo `score` sobre un `want` responde `422`.
- Entre `want` y `not_interested` no hay nota ni efectos (`score` está prohibido).
- De `vote` a otro tipo se rechaza con `422`: hay que borrar el voto y crearlo de nuevo.
- Si el ítem está borrado o inactivo, responde `422` con `details.vote`.
- `meta`: `kudos_awarded`, `total_kudos` y `vote_type`.

### `GET /api/votes/my-votes`

- Requiere autenticación.
- Lista las interacciones del usuario autenticado con paginación.
- Filtros soportados: `type` (`vote`, `want`, `not_interested` o `skip`), `category_slug`, `search` y `per_page` (de 1 a 100, 15 por defecto).
- Orden: `created_at` descendente y, en empate, `id` descendente.

Respuesta `200`:

```json
{
  "data": [
    {
      "id": "...",
      "type": "vote",
      "score": 8,
      "item_id": "...",
      "voted_at": "2026-03-17T12:00:00Z",
      "item": {
        "id": "...",
        "name": "..."
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": null
  }
}
```

## 4) Siguiente item de votación

### `GET /api/categories/{category}/next-item`

- Requiere autenticación.
- Excluye los ítems con los que el usuario ya ha interactuado (cualquier tipo: `vote`, `want`, `not_interested` o `skip`).
- Usa una cola aleatoria cacheada por usuario y categoría para mantener rotación estable.
- Si no quedan candidatos, responde `204`.

Respuesta `200`:

```json
{
  "data": {
    "id": "...",
    "name": "..."
  },
  "meta": {
    "remaining": 3
  }
}
```

## 5) Verificación de email (API JSON)

### `GET /api/verify-email/{id}/{hash}`

- Requiere firma válida (`signed`, máx. 6 por minuto); no requiere sesión.
- Respuesta JSON (sin redirect).
- Errores: `403` `invalid_signature` y `403` `invalid_hash`.

Respuesta `200` (ya verificado):

```json
{
  "message": "El email ya estaba verificado.",
  "data": {
    "status": "already-verified"
  }
}
```

Respuesta `200` (verificado ahora):

```json
{
  "message": "Tu cuenta ha sido verificada correctamente.",
  "data": {
    "status": "verified"
  }
}
```

## 6) Grupos de rutas

Las rutas de `routes/api.php` se agrupan por el acceso que exigen:

- **Públicas** (`auth.optional`): con token, los recursos conocen al usuario; sin él, es un invitado. Por ejemplo, `categories`, `items` y `home`.
- **Autenticadas** (`auth:sanctum`, `verified`, `not_banned`): requieren sesión, email verificado y que el usuario no esté baneado. Por ejemplo, `votes`, `profile` y `catalog`.
- **Administración** (las anteriores más `admin`): por ejemplo, `admin/items`, `admin/users` y `admin/reports`.

`routes/auth.php` reúne el registro, el inicio y cierre de sesión, la recuperación de contraseña y la verificación del email.

## 7) Cambios respecto a la API anterior

- El 404 de un modelo que no existe pasa de `route_not_found` a `not_found`.
- 429, 405 y 500 responden con el formato `{"error"}`.
- `POST /api/forgot-password` responde siempre `200`, exista o no el correo.
- Se ha quitado `GET /api/items/my-items`.
- `images` ya no se acepta en las peticiones de escritura de ítems (creación, edición de administración) ni en la edición de propuestas.
- `POST /api/proposals` puede traer `meta.images_failed: true` si no se pudieron procesar las imágenes.
