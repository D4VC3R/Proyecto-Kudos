# Entorno local en Linux con Docker

**Responsable:** infraestructura/entorno. Este documento es la referencia para cualquier agente que necesite ejecutar código.

## 1. Requisitos

- Linux con **Docker Engine** y el plugin **Docker Compose v2**. `docker compose version` debe responder.
- El usuario debe pertenecer al grupo `docker`, para no tener que usar `sudo`.
- `git`.
- **No hace falta PHP, Composer ni Node en el host.** Todo se ejecuta en contenedores.

## 2. Estructura

```
Proyecto-Kudos/
├── Backend/    Laravel 12 · PHP 8.5 · PostgreSQL 18 · Redis   → Laravel Sail (Backend/compose.yml)
├── Frontend/   React 18 · Vite 6 · Tailwind 3 · pnpm          → docker compose (Frontend/compose.yml)
├── deploy/     Nginx del host y backups del VPS (solo producción)
└── docs/       Esta documentación
```

## 3. Puesta en marcha del backend (primera vez)

```bash
cd Proyecto-Kudos/Backend
cp .env.example .env

# 1. Instalar las dependencias de Composer con un contenedor temporal (no hace falta PHP en el host)
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs

# 2. Alias cómodo para Sail (añádelo a ~/.bashrc)
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'

# 3. Levantar los servicios: kudos-app (PHP 8.5), pgsql, redis, mailpit
sail up -d            # la primera vez construye la imagen docker/8.5: tarda unos minutos

# 4. Clave de la aplicación y base de datos
sail artisan key:generate
sail artisan migrate
```

Notas:
- **`WWWUSER` y `WWWGROUP`** valen 1000 en `.env.example`. Si tu UID/GID es otro (`id -u`, `id -g`), cámbialos en `.env` antes de `sail up`.
- **Sembrado:**
  - `sail artisan db:seed` siembra el catálogo **antiguo**: unos 4.400 ítems descargando imágenes, así que tarda bastante.
  - **Para trabajar en la Fase 0 no hace falta**: los tests crean sus propios datos con factories.
  - Desde la Fase 1 el sembrado cambia (ver [../roadmap/fase-1-catalogo-vivo.md](../roadmap/fase-1-catalogo-vivo.md)).
- **URLs:**
  - API: `http://localhost:8095/api`
  - Mailpit: `http://localhost:8025`
  - PostgreSQL: `localhost:5432` (usuario `sail`, contraseña `password`, base de datos `kudos_db`)
- **Colas:** en local `QUEUE_CONNECTION=sync`, así que los jobs se ejecutan en la misma petición. Para probar colas reales, pon `QUEUE_CONNECTION=database` y ejecuta `sail artisan queue:work --queue=default,catalog`.
- **Stack de producción local:** en la máquina del desarrollador puede estar levantado `compose.prod.yml` (contenedores `kudos-*`, nginx en `127.0.0.1:8001`). No choca con Sail (no publica 5432 ni 8095), pero no lo confundas: los comandos de desarrollo van siempre con `sail`.
- **Tareas programadas:** para probarlas, `sail artisan schedule:list` y `sail artisan schedule:work`. Para lanzar una sola vez: `sail artisan schedule:run`.

## 4. Base de datos de tests (PostgreSQL)

**Los tests nunca se ejecutan sobre SQLite**: las migraciones usan `CREATE EXTENSION pg_trgm`, índices GIN y `jsonb`.

**La tarea F0-01 deja esto configurado** de la siguiente forma:
- `phpunit.xml` apunta a `DB_CONNECTION=pgsql` y `DB_DATABASE=testing`.
- `compose.yml` monta `docker/pgsql/create-testing-database.sql` en `/docker-entrypoint-initdb.d/`. Ese script **solo se ejecuta al crear el volumen por primera vez**.

**Si el volumen `backend_sail-pgsql` ya existía** (es el caso en la máquina del desarrollador), crea la base de datos de tests a mano, una sola vez:

```bash
sail exec pgsql psql -U sail -d kudos_db -c "CREATE DATABASE testing;"
```

**Ejecutar los tests:**

```bash
sail test                         # toda la batería
sail test --filter=VoteTest       # un archivo o un test concreto
sail test --stop-on-failure
```

> **Seguridad:** `RefreshDatabase` borra la base de datos en la que corre. La clase base `tests/TestCase.php` (tarea F0-01) **aborta si la base de datos no se llama `testing`**. No quites esa protección.

## 5. Frontend

```bash
cd Proyecto-Kudos/Frontend
cp .env.example .env              # VITE_API_URL=http://localhost:8095/api
docker compose up --build         # Vite en http://localhost:5174
```

- **CORS:** el backend debe permitir el origen. `FRONTEND_URL` en `Backend/.env` ya incluye `http://localhost:5174`.
- **Comprobar que compila** (parte de la definición de hecho):

  ```bash
  docker compose run --rm kudos-frontend pnpm build
  ```

- **Añadir una dependencia:** `docker compose run --rm kudos-frontend pnpm add <paquete>@<versión exacta>`. Después reconstruye con `docker compose up --build`, porque `node_modules` vive en un volumen.
- **No hay linter ni tests de frontend configurados.** La verificación es `pnpm build` más una prueba manual en el navegador.

## 6. Comandos frecuentes

| Para… | Comando (desde `Backend/`) |
|---|---|
| Ver los logs | `sail logs -f kudos-app` o `sail artisan pail` |
| Entrar en el contenedor | `sail shell` |
| Abrir una consola de PostgreSQL | `sail psql` |
| Ejecutar Tinker | `sail artisan tinker` |
| Reset completo de la base de datos de desarrollo | `sail artisan migrate:fresh --seed` |
| Auditar los kudos | `sail artisan kudos:audit-consistency` |
| Listar las rutas | `sail artisan route:list --path=api` |
| Parar los servicios | `sail down` (y `sail down -v` para borrar también los volúmenes) |

## 7. Problemas frecuentes

| Síntoma | Causa y solución |
|---|---|
| `permission denied` en `storage/` o `bootstrap/cache` | `WWWUSER`/`WWWGROUP` no coinciden con tu UID/GID. Corrígelos y ejecuta `sail build --no-cache && sail up -d` |
| `database "testing" does not exist` | Crea la base de datos de tests (§4) |
| `could not find driver` al ejecutar los tests | Estás usando PHP del host. Usa `sail test` |
| `extension "pg_trgm" is not available` | Revisa que el servicio sea `postgres:18-alpine`, que la incluye |
| El frontend recibe un error de CORS | Revisa `FRONTEND_URL` en `Backend/.env` y ejecuta `sail artisan config:clear` |
| El puerto 5432 u 8095 está ocupado | Cambia `FORWARD_DB_PORT` o `APP_PORT` en `.env` |
