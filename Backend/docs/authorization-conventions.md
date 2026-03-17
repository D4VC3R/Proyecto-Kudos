# Convencion de autorizacion API

## Regla base

1. **`FormRequest::authorize()`** decide permisos HTTP (403).
2. **`Policy`** contiene reglas actor-recurso (`create`, `update`, `delete`, etc.).
3. **`Service/Action`** contiene reglas de dominio y estado (422), nunca permisos.
4. **Controller** orquesta entrada/salida; no repite `Gate::authorize()` si ya hay `FormRequest`.

## Semantica de errores

- `401`: no autenticado (`auth:sanctum`).
- `403`: autenticado pero sin permiso (middleware/authorize/policy).
- `422`: payload invalido o transicion de negocio invalida.

## Patron por endpoint

- Endpoints con body/query relevante: crear `FormRequest` dedicado con `authorize()` + `rules()`.
- Endpoints sin body (delete/listados admin): usar request liviano con `authorize()` y reglas minimas.
- Listados con filtros: validar query params en request y pasar solo `validated()` a query/service.

