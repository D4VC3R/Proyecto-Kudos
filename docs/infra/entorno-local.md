# Entorno local (Linux + Docker)

## 1. Requisitos

Docker Engine con Compose v2, un usuario en el grupo `docker` y `git`. **No hace falta PHP, Composer ni Node en el host.**

## 2. Estructura

```
Proyecto-Kudos/
├── Backend/    Laravel 12 · PHP 8.5 · PostgreSQL 18 · Redis  → Sail (Backend/compose.yml)
├── Frontend/   React 18 · Vite 6 · Tailwind 3 · pnpm         → docker compose (Frontend/compose.yml)
├── deploy/     Nginx del host y backups del VPS (solo producción)
└── docs/
```

## 3. Backend

Primera vez, desde `Backend/`:

```bash
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
  laravelsail/php84-composer:latest composer install --ignore-platform-reqs
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'   # añádelo a ~/.bashrc
sail up -d                  # kudos-app (PHP 8.5, imagen docker/8.5), pgsql, redis, mailpit
sail artisan key:generate
sail artisan migrate
```

- `WWWUSER`/`WWWGROUP` = 1000 en `.env.example`. Si `id -u`/`id -g` dan otro valor, cámbialos antes de `sail up`.
- `APP_SERVICE=kudos-app` en `.env`: el servicio de la app no se llama `laravel.test` (el nombre que Sail usa por defecto). Sin esa variable, `sail artisan` y `sail test` fallan con `service "laravel.test" is not running`.
- **Sembrado.** `sail artisan db:seed` siembra el catálogo **antiguo** (~4.400 ítems, descargando imágenes: tarda mucho). En la Fase 0 no hace falta: los tests usan factories. Desde la F1-10, la secuencia es:
  ```bash
  sail artisan migrate:fresh --seed
  sail artisan catalog:fetch-new --all --limit=50
  sail artisan db:seed --class=VoteSeeder
  ```
- **URLs:**
  - API: `http://localhost:8095/api`;
  - Mailpit: `http://localhost:8025`;
  - PostgreSQL: `localhost:5432`, base de datos `kudos_db`, con el `DB_USERNAME`/`DB_PASSWORD` de tu `.env`.
- **Colas:** `QUEUE_CONNECTION=sync` en local, así que los jobs se ejecutan en la misma petición. Para probar colas reales: `QUEUE_CONNECTION=database` + `sail artisan queue:work --queue=default,catalog`.
- **Tareas programadas:** `sail artisan schedule:list`, `schedule:work` o `schedule:run`.
- **Verificación de email:** `AUTH_EMAIL_VERIFICATION=false` por defecto. Las cuentas nuevas nacen verificadas.
- **Stack de producción local:** puede estar levantado `compose.prod.yml` (contenedores `kudos-*`, nginx en `127.0.0.1:8001`). No choca con Sail, pero los comandos de desarrollo van **siempre** con `sail`.

## 4. Base de datos de tests

La configuración de `phpunit.xml` está en [../backend/testing.md](../backend/testing.md) §1.

- `compose.yml` monta `docker/pgsql/create-testing-database.sql` (F0-01), pero ese script **solo se ejecuta al crear el volumen**. Si el volumen `backend_sail-pgsql` ya existía, ejecútalo a mano una vez (es idempotente y usa el usuario con el que se creó el volumen):
  ```bash
  sail exec pgsql sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /docker-entrypoint-initdb.d/10-create-testing-database.sql'
  ```
- Ejecución:
  ```bash
  sail test                      # todo
  sail test --filter=VoteTest    # un archivo o un test
  sail test --stop-on-failure
  ```

## 5. Frontend

Desde `Frontend/`:

```bash
cp .env.example .env                                   # VITE_API_URL=http://localhost:8095/api
docker compose up --build                              # Vite en http://localhost:5174
docker compose run --rm kudos-frontend pnpm build      # comprobación de la definición de hecho
docker compose run --rm kudos-frontend pnpm add <paquete>@<versión-exacta>   # después, up --build (node_modules vive en un volumen)
```

CORS: `FRONTEND_URL` en `Backend/.env` ya incluye `http://localhost:5174`.

## 6. Comandos frecuentes (desde `Backend/`)

| Para… | Comando |
|---|---|
| Ver los logs | `sail logs -f kudos-app` o `sail artisan pail` |
| Abrir un *shell*, `psql` o Tinker | `sail shell`, `sail psql`, `sail artisan tinker` |
| Resetear la base de datos de desarrollo | `sail artisan migrate:fresh --seed` |
| Auditar los kudos | `sail artisan kudos:audit-consistency` |
| Listar las rutas | `sail artisan route:list --path=api` |
| Parar los servicios | `sail down` (`-v` borra también los volúmenes) |

## 7. Problemas frecuentes

| Síntoma | Solución |
|---|---|
| `permission denied` en `storage/` o `bootstrap/cache` | Ajusta `WWWUSER`/`WWWGROUP` y ejecuta `sail build --no-cache && sail up -d` |
| `database "testing" does not exist` | §4 |
| `could not find driver` en los tests | Estás usando el PHP del host: usa `sail test` |
| `extension "pg_trgm" is not available` | El servicio debe ser `postgres:18-alpine` |
| CORS en el frontend | Revisa `FRONTEND_URL` y ejecuta `sail artisan config:clear` |
| El puerto 5432 u 8095 está ocupado | Cambia `FORWARD_DB_PORT` o `APP_PORT` en `.env` |
