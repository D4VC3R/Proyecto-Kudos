#!/usr/bin/env bash
# Despliegue de la API de Kudos en el VPS. Uso, desde la raíz del repositorio:
#
#   ./deploy.sh            git pull + build + migraciones + arranque
#   SKIP_PULL=1 ./deploy.sh  igual pero sin git pull (para probar en local)
#
# El frontend no se despliega aquí: Vercel lo construye solo al hacer push a main.

# -e: parar en el primer error, en vez de seguir y dejar el despliegue a medias.
# -u: error si se usa una variable sin definir. -o pipefail: un fallo dentro de una tubería cuenta.
set -euo pipefail

cd "$(dirname "$0")/Backend"

if [[ ! -f .env.production ]]; then
    echo "Falta Backend/.env.production (cópialo de .env.production.example y rellénalo)." >&2
    exit 1
fi

# Todas las órdenes de Compose usan el fichero y las variables de producción.
dc() { docker compose -f compose.prod.yml --env-file .env.production "$@"; }

if [[ "${SKIP_PULL:-0}" != "1" ]]; then
    echo "==> Actualizando código"
    # --ff-only: si alguien editó algo en el servidor, falla en vez de mezclar cambios en silencio.
    git pull --ff-only origin main
fi

echo "==> Construyendo imágenes"
dc build

echo "==> Aplicando migraciones"
# Con el código nuevo y ANTES de reemplazar los contenedores: el esquema ya está listo cuando
# la versión nueva empieza a atender peticiones. --force evita la confirmación de producción.
dc run --rm app php artisan migrate --force

echo "==> Arrancando servicios"
# Solo recrea los contenedores cuya imagen o configuración ha cambiado.
# La caché de config/rutas la genera el entrypoint de cada contenedor al arrancar.
dc up -d --remove-orphans

echo "==> Estado"
dc ps

# Borra las imágenes antiguas que han quedado sin etiqueta tras el build (disco de 40 GB).
docker image prune -f >/dev/null
