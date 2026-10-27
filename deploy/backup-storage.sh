#!/usr/bin/env bash
# Copia de seguridad de las imágenes de Kudos: volumen Docker kudos_storage-public (avatares,
# imágenes de ítems y de propuestas).
#
# Pensado para cron del usuario que despliega (no root), por ejemplo a diario a las 03:45, después
# del volcado de la base de datos (backup-db.sh, 03:30):
#   45 3 * * * $HOME/apps/kudos/deploy/backup-storage.sh >> $HOME/backups/kudos/backup.log 2>&1
#
# Variables opcionales: BACKUP_DIR (por defecto ~/backups/kudos), RETENTION_DAYS (por defecto 7),
# STORAGE_VOLUME (por defecto kudos_storage-public) y BACKUP_IMAGE (por defecto kudos-api:latest).
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-$HOME/backups/kudos}"
RETENTION_DAYS="${RETENTION_DAYS:-7}"
STORAGE_VOLUME="${STORAGE_VOLUME:-kudos_storage-public}"
BACKUP_IMAGE="${BACKUP_IMAGE:-kudos-api:latest}"

# Sin esta comprobación, "docker run -v" crearía un volumen vacío con ese nombre y la copia
# terminaría "bien" pero sin ninguna imagen.
if ! docker volume inspect "$STORAGE_VOLUME" > /dev/null 2>&1; then
    echo "$(date -Is) ERROR no existe el volumen $STORAGE_VOLUME" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

file="$BACKUP_DIR/kudos-storage-$(date +%Y%m%d-%H%M%S).tar.gz"
# Si algo falla a mitad, no dejar una copia incompleta que parezca válida.
trap 'rm -f "$file.tmp"' EXIT

# tar se ejecuta en un contenedor efímero de la imagen de la API, que ya está en el VPS y tiene el
# mismo usuario www-data que escribe las imágenes:
#   - el volumen se monta en solo lectura y sin red, y no hace falta que el stack esté levantado;
#   - --pull never: la imagen se construye en local; nunca se descarga una "kudos-api" de un registro;
#   - --entrypoint tar salta el entrypoint de Laravel (cachea la configuración y necesita el .env);
#   - la salida va por stdout, así que el fichero es del usuario del cron y no de root.
# Las imágenes ya van comprimidas y gzip apenas reduce, pero permite comprobar que la copia está entera.
docker run --rm --pull never --network none \
    -v "$STORAGE_VOLUME":/data:ro \
    --entrypoint tar \
    "$BACKUP_IMAGE" -czf - -C /data . > "$file.tmp"

# Comprueba que el gzip está entero antes de darlo por bueno.
gzip -t "$file.tmp"

mv "$file.tmp" "$file"
chmod 600 "$file"

# Rotación: borra las copias de imágenes con más de RETENTION_DAYS días (los .dump los rota backup-db.sh).
find "$BACKUP_DIR" -name 'kudos-storage-*.tar.gz' -mtime +"$RETENTION_DAYS" -delete

echo "$(date -Is) OK $file ($(du -h "$file" | cut -f1))"
