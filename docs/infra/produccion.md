# Producción

**Los agentes no ejecutan nada en producción (R10).** Preparan los cambios en el repositorio y redactan *runbooks* que ejecuta el humano. La guía de despliegue existente está en [../../deploy/README.md](../../deploy/README.md).

## 1. Situación actual

- VPS (OVH) de 4 GB compartido; Kudos dispone de ~2–2,5 GB, 2 vCores y 40 GB de disco.
- Frontend en Vercel (`kudos.dcerdan.es`); API en el VPS (`api.kudos.dcerdan.es`).
- `Backend/compose.prod.yml` (proyecto `kudos`; ancla `x-laravel: &laravel` compartida por `app`, `worker` y `scheduler`):

| Servicio | Función | Memoria |
|---|---|---|
| `nginx` | Servidor web interno | 64M |
| `app` | PHP-FPM (`memory_limit` 512M, hasta 5 procesos) | 1G |
| `worker` | `queue:work --queue=default,catalog` (cola en la **base de datos**: `QUEUE_CONNECTION=database`) | 512M |
| `scheduler` | `schedule:work` (solo encola) | 128M |
| `postgres` | Base de datos | 512M |
| `redis` | Caché y sesiones; `maxmemory 64mb`, `allkeys-lru` | 128M |

- **Problemas:**
  - ~~no hay scheduler~~ (resuelto en F0-10);
  - ~~el worker (256M) se queda corto para jobs de imagen con 512M de `memory_limit`~~ (resuelto en F0-10: 512M);
  - ~~`deploy/backup-db.sh` no copia el volumen de imágenes~~ (resuelto en F0-11: `deploy/backup-storage.sh`).
- **Redis con LRU:** la caché de Wikidata y Wikipedia (7 días, F1-04) puede expulsarse. Es aceptable, porque solo es caché.

## 2. Cambios en `compose.prod.yml` (F0-10)

- **Servicio `scheduler` nuevo:**
  - `<<: *laravel`;
  - `command: ["php", "artisan", "schedule:work"]`;
  - 128M y `stop_grace_period: 30s`.

  `schedule:work` ejecuta cada tarea **dentro de este contenedor**, así que las tareas programadas solo encolan jobs (`Schedule::job(...)`, o comandos que recorren IDs y despachan). El trabajo va al worker.
- **Worker:**
  - `command: ["php", "artisan", "queue:work", "--queue=default,catalog", "--tries=3", "--backoff=60", "--max-time=3600"]`. `default` (correos, avatares) tiene prioridad sobre `catalog` (importaciones y recálculos);
  - 512M.
- **Comentario de presupuesto en la cabecera:** postgres 512M + app 1G + worker 512M + scheduler 128M + redis 128M + nginx 64M ≈ 2,3 GB. Si el humano dice que no cabe, se propone bajar `app` a 768M y `pm.max_children` (`docker/prod/`) en proporción, pero **se consulta**, no se decide.
- Nada de LibreTranslate ni de otros servicios pesados.

## 3. Variables de entorno nuevas

Van vacías en `Backend/.env.example` y `Backend/.env.production.example`, y se leen en `config/catalog.php`.

| Variable | Fase | Uso |
|---|---|---|
| `CATALOG_USER_AGENT` | 1 | User-Agent identificativo (Wikimedia, OSM). Por ejemplo, `KudosBot/1.0 (+https://kudos.dcerdan.es; contacto@dominio)` |
| `TMDB_API_TOKEN` | 1 | Token v4 (Bearer); lo da de alta el humano |
| `CATALOG_IMPORT_LIMIT` | 1 | Máximo por categoría en `catalog:fetch-new` (50 en local, 300 en producción) |
| `IGDB_CLIENT_ID`, `IGDB_CLIENT_SECRET` | 4 | Twitch/IGDB, si se elige IGDB |
| `RAWG_API_KEY` | 4 | Si se elige RAWG |

## 4. Copia de seguridad de imágenes (F0-11, hecha)

`deploy/backup-storage.sh` hace un `tar.gz` del volumen `kudos_storage-public` en `~/backups/kudos`, con la misma rotación que los volcados (`RETENTION_DAYS`, 7 días). El `tar` corre en un contenedor efímero de `kudos-api:latest` (`--pull never`, `--network none`, volumen `:ro`, `--entrypoint tar`). Antes de copiar comprueba que el volumen existe, y después que el gzip está entero. La instalación, la línea de cron (03:45, después de `backup-db.sh`) y la restauración están en `deploy/README.md` §8; en el VPS lo instala el humano.

## 5. *Runbook*: reset de producción (F1-10 lo revisa, F1-11 lo ejecuta el humano)

Los datos actuales son de prueba, y eso está confirmado.

```bash
# En el VPS
cd ~/apps/kudos && git pull                       # main con la Fase 1 fusionada
~/apps/kudos/deploy/backup-db.sh                  # copia por si acaso
~/apps/kudos/deploy/backup-storage.sh             # imágenes, antes de vaciarlas en el paso 2
./deploy.sh                                       # imagen nueva y migraciones

cd Backend
# 1. Esquema limpio, categorías, roles y admin (el seeder nuevo NO importa el catálogo)
docker compose -f compose.prod.yml --env-file .env.production run --rm app php artisan migrate:fresh --seed --force
# 2. Vaciar las imágenes del catálogo antiguo (la F1-10 confirma la ruta)
docker compose -f compose.prod.yml --env-file .env.production run --rm app sh -c 'rm -rf storage/app/public/items/*'
# 3. Importación inicial (larga)
nohup docker compose -f compose.prod.yml --env-file .env.production run --rm -T app \
  php artisan catalog:fetch-new --all --limit=300 > ~/kudos-import.log 2>&1 &
tail -f ~/kudos-import.log
```

**Comprobaciones:**
- `GET https://api.kudos.dcerdan.es/api/categories` devuelve solo las categorías vivas.
- `docker logs kudos-scheduler-1` muestra las tareas programadas.
- Como `fetch-new` solo encola, el worker tiene que ir vaciando la cola: `docker logs -f kudos-worker-1`.
