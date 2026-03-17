# API Kudos - Documentacion completa

## 1. Resumen

API REST para una plataforma de votaciones multitematica gamificada.

Funciones principales:
- Gestion de categorias e items.
- Flujo de votacion con `vote` y `skip`.
- Recompensas de kudos y ranking de usuarios.
- Propuestas de items por usuarios y moderacion admin.
- Administracion de usuarios, items y propuestas.

Base URL sugerida (entorno local):
- `http://localhost:<APP_PORT>/api`

---

## 2. Autenticacion y autorizacion

### 2.1 Autenticacion
- Token Bearer con Sanctum.
- Header requerido en rutas protegidas:
  - `Authorization: Bearer <token>`

### 2.2 Middlewares principales
- `auth:sanctum`: requiere usuario autenticado.
- `verified`: requiere email verificado.
- `not_banned`: bloquea usuarios suspendidos.
- `admin`: requiere rol administrador.

### 2.3 Autorizacion
- Se aplica con `FormRequest::authorize()` + Policies.
- Permisos por recurso en policies (`ItemPolicy`, `VotePolicy`, `ProposalPolicy`, etc.).

---

## 3. Contrato de respuestas

Referencia formal:
- `docs/api-contract.md`

### 3.1 Exito (mutaciones)
```json
{
  "message": "Texto descriptivo",
  "data": {},
  "meta": {}
}
```

### 3.2 Exito (listados)
```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
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

### 3.3 Error
```json
{
  "error": {
    "code": "validation_error",
    "message": "La solicitud contiene errores de validacion.",
    "details": {
      "campo": ["mensaje"]
    }
  }
}
```

Codigos globales:
- `401` `unauthenticated`
- `403` `forbidden`
- `404` `not_found` / `route_not_found`
- `422` `validation_error`

---

## 4. Modelo de dominio (alto nivel)

- `User`: autenticacion, rol, estado de baneo, kudos totales.
- `Profile`: datos de perfil del usuario.
- `Category`: agrupa items y propuestas.
- `Item`: recurso votable, con promedio y conteo de votos.
- `Vote`: interaccion de usuario sobre item (`vote` o `skip`).
- `Proposal`: propuesta de item enviada por usuarios.
- `KudosTransaction`: refleja al usuario y la accion por la que gana puntos.

---

## 5. Endpoints de autenticacion (`/api`)

### 5.1 Registro y login
- `POST /register`
- `POST /login`
- `POST /logout` (auth)
- `POST /logout-all` (auth)

### 5.2 Password reset
- `POST /forgot-password`
- `POST /reset-password`

### 5.3 Verificacion email (flujo JSON)
- `GET /verify-email/{id}/{hash}` (auth + signed)
- `POST /email/verification-notification` (auth)

---

## 6. Endpoints publicos (`/api`)

### 6.1 Categorias
- `GET /categories`
- `GET /categories/{category}`
- `GET /categories/{category}/ranking`

### 6.2 Items
- `GET /items`
  - filtros: `category_id`, `search`, `tag_ids[]`, `sort_by`, `sort_order`, `per_page`

### 6.3 Ranking de usuarios
- `GET /users/ranking`
  - top publico (10 por pagina)
  - si hay token valido en cabecera, incluye posicion/pagina personal

---

## 7. Endpoints autenticados (`auth + verified + not_banned`)

### 7.1 Perfil
- `GET /profile`
- `PUT /profile`

### 7.2 Votacion
- `GET /categories/{category}/next-item`
  - devuelve siguiente item elegible del usuario para la categoria.
  - excluye items ya interactuados (`vote` y `skip`).
  - devuelve `204` si no quedan candidatos.

- `POST /votes`
  - crea `vote` o `skip`.
  - idempotente por (`user_id`,`item_id`):
    - `201` si crea.
    - `200` si ya existia (no muta).
  - `meta.reason` cuando idempotente:
    - `already_voted`
    - `already_skipped`

- `GET /votes/my-votes`
  - historial paginado del usuario.
  - filtros: `type`, `category_id`, `search`, `per_page`.

- `PUT /votes/{vote}`
  - solo edita `score` de interacciones tipo `vote`.

- `DELETE /votes/{vote}`
  - elimina la interaccion propia.

### 7.3 Propuestas de usuario
- `POST /proposals`
- `GET /proposals/my-proposals`
- `GET /proposals/{proposal}`
- `PUT /proposals/{proposal}`
- `DELETE /proposals/{proposal}`

### 7.4 Items propios
- `GET /items/my-items`
- `GET /items/{item}`

---

## 8. Endpoints admin (`auth + verified + not_banned + admin`)

### 8.1 Categorias
- `POST /categories`
- `PUT /categories/{category}`
- `DELETE /categories/{category}`

### 8.2 Items
- `POST /items`
- `DELETE /items/{item}`

### 8.3 Admin items
- `GET /admin/items`
- `PUT /admin/items/{item}`
- `PATCH /admin/items/{item}/moderate`

### 8.4 Admin users
- `GET /admin/users`
- `PATCH /admin/users/{user}/ban`
- `PATCH /admin/users/{user}/unban`
- `POST /admin/users/{user}/sessions/revoke`

### 8.5 Admin proposals
- `GET /admin/proposals`
- `GET /admin/proposals/pending`
- `PATCH /admin/proposals/{proposal}/review`

---

## 9. Reglas de negocio criticas

### 9.1 Votos
- Un usuario tiene una sola interaccion por item.
- `vote` suma en `vote_count` y `vote_avg`.
- `skip` no afecta metricas del item.
- `POST /votes` no actualiza interaccion existente (idempotencia no mutante).
- `PUT /votes/{vote}` no cambia `type`; solo `score`.

### 9.2 Kudos
- Kudis por primer voto real de item (idempotente por `action_key`).
- Kudos por propuesta aceptada.
- Kudos por login diario con racha (segun configuracion).

### 9.3 Next item
- Solo items activos de la categoria.
- Excluye items ya `vote` o `skip` por el usuario.
- Orden aleatorio estable por cola cacheada con cursor por usuario/categoria.

### 9.4 Moderacion de propuestas
- Estados: `pending`, `accepted`, `rejected`, `changes_requested`.
- Al aceptar: se crea item + posible recompensa al creador.

---

## 10. Validaciones importantes (resumen)

### 10.1 `POST /votes`
- `item_id`: requerido, uuid, existente.
- `type`: `vote|skip`.
- `score`:
  - requerido si `type=vote`.
  - debe ser `null` si `type=skip`.

### 10.2 `PUT /votes/{vote}`
- `score`: requerido, entero `0..10`.

### 10.3 `GET /votes/my-votes`
- `type`: `vote|skip` (opcional).
- `category_id`: uuid existente (opcional).
- `search`: string (opcional).
- `per_page`: 1..100.

---

## 11. Flujos recomendados para frontend

### 11.1 Onboarding basico
1. `POST /register`
2. `POST /email/verification-notification`
3. `GET /verify-email/{id}/{hash}`
4. `POST /login`

### 11.2 Votacion por categoria
1. `GET /categories`
2. `GET /categories/{slug}/next-item`
3. `POST /votes` con `vote` o `skip`
4. Repetir `next-item` hasta `204`

### 11.3 Edicion de voto
1. `GET /votes/my-votes`
2. seleccionar voto tipo `vote`
3. `PUT /votes/{id}` con nuevo `score`

### 11.4 Ranking de usuarios
- `GET /users/ranking` (publico)
- opcionalmente con token para `my_position` y `my_page_data`

---

## 12. Observabilidad y auditoria

- Eventos de moderacion se registran en logs (`ModerationAuditLogger`).
- Errores de autorizacion/validacion y not found usan contrato uniforme en `bootstrap/app.php`.

---

## 13. Testing

Suites relevantes:
- `tests/Feature/Api/VoteAndRankingTest.php`
- `tests/Feature/Api/NextCategoryItemTest.php`
- `tests/Feature/Api/MyVotesTest.php`
- `tests/Feature/Auth/*`

Ejecucion (Docker):

```bash
cd /home/davcer/Escritorio/Proyecto/Proyecto-Kudos/Backend
docker compose -f compose.yml exec -T kudos-app php artisan test
```

---

## 14. Notas para integracion frontend

- Enviar siempre `Accept: application/json`.
- Manejar `204` explicitamente en next-item como fin de pool.
- Para `POST /votes`, si llega `idempotent_hit=true` no repetir accion de UI como si fuese alta nueva.
- Usar `error.code` para control de errores programatico y `error.message` para UI.

