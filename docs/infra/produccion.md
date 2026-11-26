# Producción

**Los agentes no ejecutan nada en producción (R10).** Preparan los cambios en el repositorio y redactan *runbooks* que ejecuta el humano. La guía de despliegue existente está en [../../deploy/README.md](../../deploy/README.md).

## 1. Situación actual

- VPS (OVH) de 4 GB compartido; Kudos dispone de ~2–2,5 GB, 2 vCores y 40 GB de disco.
- Frontend en Vercel (`kudos.dcerdan.es`); API en el VPS (`api.kudos.dcerdan.es`).
- La función `Frontend/api/meta.js` (F2-10) lee `VITE_API_URL` y `VITE_STORAGE_URL` en tiempo de ejecución: deben estar definidas en los entornos *Production* y *Preview* de Vercel.
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
  - los jobs del catálogo declaran `$tries`, `$backoff` y `$timeout` (120 s), que prevalecen sobre las opciones del comando; `retry_after` de la cola es 180 (F5-07);
  - 512M.
- **Comentario de presupuesto en la cabecera:** postgres 512M + app 1G + worker 512M + scheduler 128M + redis 128M + nginx 64M ≈ 2,3 GB. Si el humano dice que no cabe, se propone bajar `app` a 768M y `pm.max_children` (`docker/prod/`) en proporción, pero **se consulta**, no se decide.
- Nada de LibreTranslate ni de otros servicios pesados.

## 3. Variables de entorno nuevas

Van vacías en `Backend/.env.example` y `Backend/.env.production.example`, y se leen en `config/catalog.php` y `config/kudos.php`.

| Variable | Fase | Uso |
|---|---|---|
| `CATALOG_USER_AGENT` | 1 | User-Agent identificativo (Wikimedia, OSM). Por ejemplo, `KudosBot/1.0 (+https://kudos.dcerdan.es; contacto@dominio)` |
| `TMDB_API_TOKEN` | 1 | Token v4 (Bearer); lo da de alta el humano |
| `CATALOG_IMPORT_LIMIT` | 1 | Máximo por categoría en `catalog:fetch-new` (50 en local, 300 en producción) |
| `LEGAL_CONTACT_EMAIL` | 3 | Correo de contacto legal (se lee en `config/kudos.php`): se publica en `GET /api/legal` y se muestra en `/aviso-legal` y `/privacidad` (F3-07), y es la vía de reclamación que reciben los autores cuando se oculta o borra su contenido (F3-04). **Lo elige el humano**: debe ser un buzón que reciba correo. Vacío, el aviso al autor no incluye la vía de reclamación |
| `IGDB_CLIENT_ID`, `IGDB_CLIENT_SECRET` | 4 | Twitch/IGDB (proveedor de videojuegos, F4-00). La Steam Store API no lleva clave |

## 4. Copia de seguridad de imágenes (F0-11, hecha)

`deploy/backup-storage.sh` hace un `tar.gz` del volumen `kudos_storage-public` en `~/backups/kudos`, con la misma rotación que los volcados (`RETENTION_DAYS`, 7 días). El `tar` corre en un contenedor efímero de `kudos-api:latest` (`--pull never`, `--network none`, volumen `:ro`, `--entrypoint tar`). Antes de copiar comprueba que el volumen existe, y después que el gzip está entero. La instalación, la línea de cron (03:45, después de `backup-db.sh`) y la restauración están en `deploy/README.md` §8; en el VPS lo instala el humano.

## 5. *Runbook*: paso a Kudos 2.0 (fases 0–4; lo ejecuta el humano en la F5-16)

Ningún agente lo ejecuta (R10). Funde en un solo procedimiento el reset de producción y la activación de videojuegos y destinos. Los datos actuales son de prueba, y eso está confirmado: `migrate:fresh` los borra y, con ellos, cierra todas las sesiones y revoca los tokens.

**Ventana:** fuera de 02:30–06:00 UTC (tareas programadas) y de las copias del host (03:30 y 03:45, hora del host). Conviene empezar pronto: destinos ocupa al worker más de 1 hora.

**1. Variables.** En `Backend/.env.production` (§3): `TMDB_API_TOKEN`, `IGDB_CLIENT_ID`, `IGDB_CLIENT_SECRET`, `CATALOG_USER_AGENT` (con un correo real), `CATALOG_IMPORT_LIMIT=300`, `LEGAL_CONTACT_EMAIL` (un buzón que reciba correo), `SEED_ADMIN_NAME`, `SEED_ADMIN_EMAIL` y `SEED_ADMIN_PASSWORD` (sin ellas, `UserSeeder` falla). Comprueba que no falta ninguna, sin mostrar valores, desde `~/apps/kudos/Backend` (no debe salir ninguna línea con `<`):

```bash
diff <(grep -o '^[A-Z_]*=' .env.production.example | sort) <(grep -o '^[A-Z_]*=' .env.production | sort)
```

En Vercel, `VITE_API_URL` y `VITE_STORAGE_URL` en *Production* y *Preview* (§1 y `deploy/README.md` §9).

**2. Pausar el despliegue de Vercel.** En el proyecto: Settings → Environments → Production, desactiva "Auto-assign Custom Production Domains". Así el *push* a `main` construye el despliegue, pero no lo asigna a `kudos.dcerdan.es` hasta que lo promuevas. No sirve el "Ignored Build Step" del panel, porque lo anula el `ignoreCommand` de `Frontend/vercel.json`. Para comprobarlo, antes del *push* anota el *bundle* servido:

```bash
curl -s https://kudos.dcerdan.es/ | grep -o 'assets/index-[^"]*\.js'
```

Tras el *push*, el despliegue nuevo aparece en Deployments sin el dominio asignado, y el mismo `curl` devuelve el mismo nombre.

**3. Copias.** En el VPS, y comprueba que las dos imprimen "OK":

```bash
~/apps/kudos/deploy/backup-db.sh
~/apps/kudos/deploy/backup-storage.sh
```

**4. *Push* a `main`.** Lo hace el humano desde su equipo, tras fusionar `rediseno-fase-0` en `main` (F5-16).

**5. Despliegue.** En el VPS: `cd ~/apps/kudos && ./deploy.sh` (*pull*, *build*, `migrate --force` y `up -d`). Si falla en `migrate` por los datos heredados, la imagen ya está construida: sigue con el paso 6 y, al acabar el paso 7, ejecuta `dc up -d --remove-orphans`.

**6. Reset.**

```bash
cd ~/apps/kudos/Backend
dc() { docker compose -f compose.prod.yml --env-file .env.production "$@"; }

# Parar worker y scheduler: que ningún job ni tarea programada se ejecute mientras se rehace el esquema
dc stop worker scheduler
# Esquema limpio: 4 categorías activas sin portada, permisos, roles y el admin.
# Sin usuarios de prueba, ítems, propuestas ni votos (esos solo se siembran en local)
dc run --rm app php artisan migrate:fresh --seed --force
# Vaciar las imágenes. categories/* incluye las portadas sembradas categories/{slug}/seed/ y las carpetas
# de las categorías retiradas, porque ninguna fila las referencia ya
dc run --rm app sh -c 'cd storage/app/public && rm -rf items/* proposals/* avatars/* categories/*'
```

**7. Caché y arranque.** Las colas de votación (6 h) guardan IDs de ítems que ya no existen:

```bash
dc run --rm app php artisan cache:clear
dc start worker scheduler
```

**8. Importación inicial.** El primer comando (películas, series y videojuegos) solo encola; `destinos` no entra en `--all` y hace su listado en el propio proceso del comando:

```bash
dc run --rm app php artisan catalog:fetch-new --all --limit=300
dc run --rm app php artisan catalog:fetch-new --category=destinos --limit=3000
dc logs -f --tail=100 worker                        # Ctrl+C solo deja de mirar
```

- Duración: el trabajo lo hace el worker y lo limita Wikimedia (150 peticiones por minuto). Steam va a 1 petición cada 2 s, así que 300 juegos tardan al menos 10 minutos. El listado de destinos (unas 200 consultas SPARQL, una por país, con una pausa entre ellas) tarda unos 25 minutos; encola unos 2.100 jobs, y el worker tarda más de 1 hora en procesarlos.
- Si al final sale "no se pudieron listar las ciudades de algunos países de destinos", vuelve a lanzar el mismo comando: reimporta sin duplicar.
- Los fallos se reintentan con `dc exec app php artisan queue:retry all`.

**9. Comprobaciones** (los resultados se anotan en la ficha de la F5-16):
- `curl -s https://api.kudos.dcerdan.es/api/categories` devuelve 4 categorías, en este orden: `peliculas`, `series`, `videojuegos` y `destinos`. Todas con `image: null` y `attribute_schema`, y `destinos` con `has_catalog_provider: false`. Para verlo de un vistazo (`Controller::respondData` envuelve la lista en `data`): `| python3 -c 'import json,sys; [print(c["slug"], c["image"]) for c in json.load(sys.stdin)["data"]]'`.
- `dc exec app php artisan schedule:list` muestra 7 tareas (la cifra sale de `grep -c "Schedule::" Backend/routes/console.php`): `sanctum:prune-expired --hours=24` (02:30), `items:recalculate-stats` (03:00), `items:recalculate-quality` (lunes, 03:30), `catalog:fetch-new --all` (04:00), `catalog:refresh` (05:00), `catalog:purge-stale` (domingos, 05:30) y `wishlist:notify-releases` (06:00).
- La cola está vacía y sin fallos: `dc exec app php artisan queue:failed` y `dc exec postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "SELECT queue, count(*) FROM jobs GROUP BY queue;"'`.
- Recuento por categoría (el umbral 40 es `catalog.min_quality_for_discovery`):
  `dc exec postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "SELECT c.slug, count(i.id) AS items, count(i.description) AS con_descripcion, count(*) FILTER (WHERE i.quality_score >= 40) AS sobre_umbral FROM categories c LEFT JOIN items i ON i.category_id = c.id GROUP BY c.slug ORDER BY c.slug;"'`
- Descripción por fuente (`steam` con `?l=english` en la URL es el inglés de respaldo):
  `dc exec postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "SELECT c.slug, COALESCE(i.description_source, '"'"'sin descripcion'"'"') AS fuente, count(*) FROM items i JOIN categories c ON c.id = i.category_id GROUP BY 1, 2 ORDER BY 1, 3 DESC;"'`
- El volumen del log de moderación existe: `docker volume ls | grep kudos_storage-logs`.

**10. Reanudar Vercel.** En Deployments, el último despliegue de *Production* de `main` → ⋯ → Promote, y vuelve a activar "Auto-assign Custom Production Domains". Comprobaciones: el `curl` del paso 2 devuelve otro nombre de *bundle*; en `https://kudos.dcerdan.es` salen las 4 categorías y las fichas muestran las atribuciones; la pestaña de red llama a `api.kudos.dcerdan.es` y la consola no muestra errores de CORS.

**Ruta de las imágenes:** la raíz del disco `public` es el volumen `kudos_storage-public`, montado en `/var/www/html/storage/app/public` en `app`, `worker` y `scheduler` (en `nginx`, solo lectura); el `WORKDIR` de la imagen `app` es `/var/www/html`, así que `storage/app/public/…` dentro de `dc run --rm app sh -c '…'` es correcto. Dentro hay `items/{categoría}/{id}/` (de propuestas aceptadas y del importador), `proposals/` y `avatars/`. `categories/` queda vacía: ya no hay portadas de categoría.

**Logs:** `storage-logs` es un volumen con nombre del ancla `x-laravel` (`app`, `worker` y `scheduler`), así que sobrevive a `deploy.sh`, a `migrate:fresh` y al paso 6. En él están `moderation/` (30 días) y `security/` (90 días); `LOG_CHANNEL=stderr` solo afecta al canal por defecto. Este *runbook* no lo borra.

**Si algo sale mal:** restaurar el volcado y las imágenes como en `deploy/README.md` §8 (restaurar las imágenes no borra los archivos nuevos). Mientras no se promueva el despliegue, el dominio sigue sirviendo el frontend anterior.

`VoteSeeder` se niega a ejecutarse fuera de local (falla con "VoteSeeder solo se ejecuta en local…"), y `UserSeeder` solo crea los 50 usuarios de prueba en local: en producción la base queda con el admin como único usuario.
