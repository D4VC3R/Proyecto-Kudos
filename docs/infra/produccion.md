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

## 5. *Runbook*: reset de producción (revisado en la F1-10; lo ejecuta el humano en la F1-11)

Los datos actuales son de prueba, y eso está confirmado. Ningún agente ejecuta este *runbook* (R10).

**Requisitos:**
- `main` con la Fase 1 fusionada (Vercel despliega el frontend al hacer *push* a `main`).
- En `Backend/.env.production`: `TMDB_API_TOKEN`, `CATALOG_USER_AGENT` (con un correo real) y `CATALOG_IMPORT_LIMIT=300` (§3, F1-00), además de `SEED_ADMIN_EMAIL` y `SEED_ADMIN_PASSWORD` (sin ellos, `UserSeeder` falla).
- Hacerlo fuera de 03:00–05:30 UTC (tareas programadas) y de las copias del host (03:30 y 03:45). `migrate:fresh` cierra todas las sesiones y revoca los tokens.

```bash
# En el VPS
cd ~/apps/kudos && git pull --ff-only origin main   # main con la Fase 1 fusionada
~/apps/kudos/deploy/backup-db.sh                    # copia por si acaso
~/apps/kudos/deploy/backup-storage.sh               # imágenes, antes de vaciarlas en el paso 3
./deploy.sh                                         # imagen nueva, migraciones y arranque

cd Backend
dc() { docker compose -f compose.prod.yml --env-file .env.production "$@"; }

# 1. Parar worker y scheduler: que ningún job ni tarea programada se ejecute mientras se rehace el esquema
dc stop worker scheduler
# 2. Esquema limpio: categorías (peliculas y series activas; videojuegos y destinos inactivas), permisos,
#    roles y el admin. Sin usuarios de prueba, ítems, propuestas ni votos (esos solo se siembran en local)
dc run --rm app php artisan migrate:fresh --seed --force
# 3. Vaciar las imágenes que ya no referencia ninguna fila: ítems, propuestas, avatares y portadas de las
#    categorías retiradas. NO borres categories/peliculas, series ni videojuegos: CategorySeeder reutiliza esas portadas
dc run --rm app sh -c 'cd storage/app/public && rm -rf items/* proposals/* avatars/* \
  categories/albumes-musicales categories/artistas-musicales categories/libros \
  categories/ciudades categories/politicos categories/paises'
# 4. Caché de Redis: las colas de votación (6 h) guardan IDs de ítems que ya no existen
dc run --rm app php artisan cache:clear
# 5. Volver a arrancar el worker y el scheduler
dc start worker scheduler
# 6. Importación inicial: el comando solo encola (QUEUE_CONNECTION=database) y acaba en segundos; la hace el worker
dc run --rm app php artisan catalog:fetch-new --all --limit=300
dc logs -f --tail=100 worker                        # Ctrl+C solo deja de mirar
```

**Ruta de las imágenes (confirmada en la F1-10):** la raíz del disco `public` es el volumen `kudos_storage-public`, montado en `/var/www/html/storage/app/public` en `app`, `worker` y `scheduler` (en `nginx`, solo lectura); el `WORKDIR` de la imagen `app` es `/var/www/html`, así que `storage/app/public/…` dentro de `dc run --rm app sh -c '…'` es correcto. Dentro hay `items/{categoría}/{id|seed}/` (del `ItemSeeder` heredado, de propuestas aceptadas y del importador nuevo), `proposals/`, `avatars/` y `categories/{slug}/seed/` (portadas, que no se borran).

**Duración:** el trabajo lo hace el worker: hasta 600 importaciones más sus descargas de imágenes. Es una estimación a partir de la F1-05 (20 películas en unos 27 s en local), limitada por Wikimedia (150/min), así que puede ir de 15 a 30 minutos o más en el VPS. Si hace falta, se reintenta con `dc exec app php artisan queue:retry all`.

**Comprobaciones** (los resultados se anotan en la ficha de la F1-11):
- `curl -s https://api.kudos.dcerdan.es/api/categories` devuelve solo `peliculas` y `series`, en ese orden, con `attribute_schema` e `icon`.
- `dc exec app php artisan schedule:list` muestra las cinco tareas.
- La cola está vacía y sin fallos: `dc exec app php artisan queue:failed` y `dc exec postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "SELECT queue, count(*) FROM jobs GROUP BY queue;"'`.
- Recuento por categoría (el umbral 40 es `catalog.min_quality_for_discovery`):
  `dc exec postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "SELECT c.slug, count(i.id) AS items, count(i.description) AS con_descripcion, count(*) FILTER (WHERE i.quality_score >= 40) AS sobre_umbral FROM categories c LEFT JOIN items i ON i.category_id = c.id GROUP BY c.slug ORDER BY c.slug;"'`
- En `https://kudos.dcerdan.es` salen solo Películas y Series, y una ficha muestra chips de atributos y la atribución de TMDB.

**Si algo sale mal:** restaurar el volcado y las imágenes como en `deploy/README.md` §8 (restaurar las imágenes no borra los archivos nuevos).

`VoteSeeder` se niega a ejecutarse fuera de local (falla con "VoteSeeder solo se ejecuta en local…"), y `UserSeeder` solo crea los 50 usuarios de prueba en local: en producción la base queda con el admin como único usuario.
