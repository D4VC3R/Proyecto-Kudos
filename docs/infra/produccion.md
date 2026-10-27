# Producción: cambios de infraestructura del rediseño

**Responsable:** infraestructura. **Los agentes NO ejecutan nada en producción.** Preparan los cambios en el repositorio y redactan los *runbooks*; el humano los ejecuta en el VPS.

La guía de despliegue existente está en [../../deploy/README.md](../../deploy/README.md).

## 1. Situación actual

- **Servidor:** VPS de 4 GB compartido con otros proyectos. Kudos dispone de unos 2–2,5 GB. Tiene 2 vCores y 40 GB de disco.
- **Stack** (`Backend/compose.prod.yml`):

  | Servicio | Función | Límite de memoria |
  |---|---|---|
  | `nginx` | Servidor web interno | 64M |
  | `app` | PHP-FPM | 1G |
  | `worker` | `queue:work` | 256M |
  | `postgres` | Base de datos | 512M |
  | `redis` | Caché y sesiones (`maxmemory 64mb`, `allkeys-lru`; la cola **no** va en Redis, va en la base de datos) | 128M |

- **Frontend:** está en **Vercel**, en `kudos.dcerdan.es`. La API está en el VPS, en `api.kudos.dcerdan.es`.
- **Carencias detectadas:**
  - **No hay scheduler:** ni servicio `schedule:work` ni cron que llame a `schedule:run`.
  - **El worker se queda corto:** tiene 256M, pero `memory_limit` de PHP es 512M y los jobs de imagen pueden necesitarlos.
  - **Las imágenes no tienen copia de seguridad:** `deploy/backup-db.sh` solo vuelca PostgreSQL, no el volumen `storage-public`.

## 2. Cambios en `compose.prod.yml` (tarea F0-10)

- **Servicio `scheduler` nuevo:**
  - misma imagen y configuración que `worker` (ancla `*laravel`);
  - `command: ["php", "artisan", "schedule:work"]`;
  - límite de memoria de 128M. **`schedule:work` ejecuta cada comando programado dentro de este contenedor**, así que los comandos programados deben limitarse a encolar jobs (`Schedule::job(...)` o un comando que solo recorra IDs y despache). El trabajo pesado va al worker;
  - `stop_grace_period: 30s`.
- **Worker:**
  - `command: ["php", "artisan", "queue:work", "--queue=default,catalog", "--tries=3", "--backoff=60", "--max-time=3600"]`. La cola `default` (correos, avatares) tiene prioridad sobre `catalog` (importaciones);
  - límite de memoria de **512M**.
- **Actualizar el comentario de presupuesto de memoria** de la cabecera: postgres 512M + app 1G + worker 512M + scheduler 128M + redis 128M + nginx 64M ≈ **2,3 GB**.
  - Si el humano indica que no cabe, se baja `app` a 768M y `pm.max_children` en `docker/prod/` en proporción. Esto se consulta, no se decide por cuenta propia.
- **Nada de LibreTranslate ni de otros servicios pesados.**

## 3. Variables de entorno nuevas

Se añaden a `Backend/.env.example`, a `Backend/.env.production.example` y a `config/catalog.php`.

| Variable | Fase | Uso |
|---|---|---|
| `CATALOG_USER_AGENT` | 1 | User-Agent identificativo para Wikimedia, MusicBrainz y OSM. Ejemplo: `KudosBot/1.0 (+https://kudos.dcerdan.es; contacto@dominio)` |
| `TMDB_API_TOKEN` | 1 | Token de lectura v4 (Bearer) de TMDB. Lo da de alta el humano en themoviedb.org |
| `CATALOG_IMPORT_LIMIT` | 1 | Máximo de ítems por categoría en `catalog:fetch-new` (por defecto 50 en local, 300 en producción) |
| `IGDB_CLIENT_ID`, `IGDB_CLIENT_SECRET` | 4 | Credenciales de Twitch para IGDB, si se elige IGDB |
| `RAWG_API_KEY` | 4 | Solo si se elige RAWG en vez de IGDB |

Los secretos **nunca** se suben al repositorio. En los `.example` van vacíos.

## 4. Copias de seguridad de imágenes (tarea F0-11)

`deploy/backup-db.sh` vuelca solo la base de datos. Hay que añadir un script `deploy/backup-storage.sh`, o ampliar el existente, que:
- haga un `tar` del volumen `kudos_storage-public` con la misma rotación que los volcados de la base de datos;
- se documente en `deploy/README.md`, junto con la línea de cron que debe añadir el humano.

Tras el reset de la Fase 1, todas las imágenes se pueden volver a descargar de las fuentes, así que esta tarea es de prioridad baja. Aun así conviene tenerla antes de abrir listas y avatares a usuarios reales.

## 5. *Runbook*: reset de producción (Fase 1, tarea F1-10)

**Lo ejecuta el humano.** Los datos actuales son de prueba, y eso está confirmado.

```bash
# En el VPS
cd ~/apps/kudos && git pull                       # rama con la Fase 1 ya fusionada en main
~/apps/kudos/deploy/backup-db.sh                  # copia de seguridad por si acaso
./deploy.sh                                       # construye la imagen y aplica las migraciones nuevas

cd Backend
# 1. Esquema limpio, más categorías, roles y admin (el seeder nuevo NO importa el catálogo)
docker compose -f compose.prod.yml --env-file .env.production run --rm app php artisan migrate:fresh --seed --force

# 2. Vaciar las imágenes del catálogo antiguo (la ruta exacta la confirma la tarea F1-10)
docker compose -f compose.prod.yml --env-file .env.production run --rm app sh -c 'rm -rf storage/app/public/items/*'

# 3. Importación inicial (larga; se lanza con nohup como la siembra original)
nohup docker compose -f compose.prod.yml --env-file .env.production run --rm -T app \
  php artisan catalog:fetch-new --all --limit=300 > ~/kudos-import.log 2>&1 &
tail -f ~/kudos-import.log
```

Comprobaciones posteriores:
- `GET https://api.kudos.dcerdan.es/api/categories` devuelve solo las categorías vivas.
- Los logs del contenedor `scheduler` muestran las tareas programadas: `docker logs kudos-scheduler-1`.
