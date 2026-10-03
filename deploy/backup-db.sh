#!/usr/bin/env bash
# Copia de seguridad de la base de datos de Kudos (PostgreSQL en Docker).
#
# Pensado para cron del usuario que despliega (no root), por ejemplo a diario a las 03:30:
#   30 3 * * * $HOME/apps/kudos/deploy/backup-db.sh >> $HOME/backups/kudos/backup.log 2>&1
#
# Variables opcionales: BACKUP_DIR (por defecto ~/backups/kudos) y RETENTION_DAYS (por defecto 7).
set -euo pipefail

REPO_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_DIR="${BACKUP_DIR:-$HOME/backups/kudos}"
RETENTION_DAYS="${RETENTION_DAYS:-7}"

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

file="$BACKUP_DIR/kudos-$(date +%Y%m%d-%H%M%S).dump"
# Si algo falla a mitad, no dejar un volcado incompleto que parezca válido.
trap 'rm -f "$file.tmp"' EXIT

cd "$REPO_DIR/Backend"

# pg_dump se ejecuta DENTRO del contenedor: misma versión que el servidor y sin exponer el puerto 5432.
# Las credenciales son las del propio contenedor (POSTGRES_USER/POSTGRES_DB).
# -Fc (formato custom): ya va comprimido y pg_restore puede restaurar tablas sueltas.
docker compose -f compose.prod.yml --env-file .env.production exec -T postgres \
    sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$file.tmp"

mv "$file.tmp" "$file"
chmod 600 "$file"

# Rotación: borra los volcados con más de RETENTION_DAYS días.
find "$BACKUP_DIR" -name 'kudos-*.dump' -mtime +"$RETENTION_DAYS" -delete

echo "$(date -Is) OK $file ($(du -h "$file" | cut -f1))"
