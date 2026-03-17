# Contrato JSON de la API

Este documento define el contrato de respuesta para consumo de frontend.

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
- `401` -> `unauthenticated`
- `403` -> `forbidden`
- `404` -> `not_found` / `route_not_found`
- `422` -> `validation_error`

## 3) Reglas de votación

### `POST /api/votes`

- Crea interacción `vote` o `skip`.
- Es idempotente por (`user_id`, `item_id`):
  - si la interacción ya existe, responde `200` sin mutar.
  - si se crea nueva, responde `201`.

Ejemplo `200` idempotente:

```json
{
  "message": "La interacción ya estaba registrada para este item.",
  "data": {
    "id": "...",
    "item_id": "...",
    "type": "skip",
    "score": null
  },
  "meta": {
    "idempotent_hit": true,
    "was_existing": true,
    "reason": "already_skipped",
    "vote_type": "skip",
    "total_kudos": 0
  }
}
```

### `PUT /api/votes/{vote}`

- Solo permite editar la puntuación (`score`) de votos tipo `vote`.
- No cambia `type`.

### `GET /api/votes/my-votes`

- Requiere autenticación.
- Lista las interacciones del usuario autenticado con paginación.
- Filtros soportados: `type`, `category_id`, `search`, `per_page`.

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
- Excluye items ya interactuados por el usuario (`vote` y `skip`).
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

- Requiere autenticación y firma válida.
- Respuesta JSON (sin redirect).

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
  "message": "Email verificado correctamente.",
  "data": {
    "status": "verified"
  }
}
```

