#!/usr/bin/env bash
# Lanza visual-check.mjs en la imagen de Playwright para no instalar nada en el host.
# Uso (desde cualquier carpeta): Frontend/scripts/visual-check.sh --routes / --widths 360 [--auth] [--out DIR]
# Detalle de los argumentos en Frontend/README.md y en la cabecera de visual-check.mjs.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
IMAGE="${VISUAL_CHECK_IMAGE:-mcr.microsoft.com/playwright/mcp:latest}"

OUT=""
ARGS=()
MOUNTS=()
while [ "$#" -gt 0 ]; do
  case "$1" in
    --out)
      OUT="${2:-}"
      shift 2 || shift
      ;;
    --out=*)
      OUT="${1#--out=}"
      shift
      ;;
    --routes | --routes=*)
      if [ "$1" = "--routes" ]; then
        ROUTES="${2:-}"
        [ "$#" -ge 2 ] && shift 2 || shift
      else
        ROUTES="${1#--routes=}"
        shift
      fi
      # Solo un archivo .json se monta en el contenedor; una lista (o "/") pasa tal cual.
      if [ -f "$ROUTES" ] && [[ "$ROUTES" == *.json ]]; then
        MOUNTS+=(-v "$(cd "$(dirname "$ROUTES")" && pwd)/$(basename "$ROUTES"):/input/routes.json:ro")
        ROUTES="/input/routes.json"
      fi
      ARGS+=(--routes "$ROUTES")
      ;;
    *)
      ARGS+=("$1")
      shift
      ;;
  esac
done

OUT="${OUT:-$REPO_ROOT/.claude/ciclo/visual-check}"
mkdir -p "$OUT"
OUT="$(cd "$OUT" && pwd)"

# Las credenciales salen de Backend/.env (las del seeder local) salvo que ya vengan en el entorno. Se pasan al
# contenedor por nombre para que no aparezcan en `ps`.
read_env() {
  grep -E "^$1=" "$REPO_ROOT/Backend/.env" | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'\$//" || true
}
if [ -f "$REPO_ROOT/Backend/.env" ]; then
  if [ -z "${KUDOS_VISUAL_PASSWORD:-}" ]; then
    KUDOS_VISUAL_PASSWORD="$(read_env SEED_ADMIN_PASSWORD)"
  fi
  if [ -z "${KUDOS_VISUAL_EMAIL:-}" ]; then
    KUDOS_VISUAL_EMAIL="$(read_env SEED_ADMIN_EMAIL)"
  fi
fi
export KUDOS_VISUAL_EMAIL="${KUDOS_VISUAL_EMAIL:-}"
export KUDOS_VISUAL_PASSWORD="${KUDOS_VISUAL_PASSWORD:-}"

exec docker run --rm --network host --shm-size=1g --user "$(id -u):$(id -g)" -e HOME=/tmp \
  -e KUDOS_VISUAL_EMAIL -e KUDOS_VISUAL_PASSWORD \
  -v "$SCRIPT_DIR/visual-check.mjs:/scripts/visual-check.mjs:ro" -v "$OUT:/out" ${MOUNTS[@]+"${MOUNTS[@]}"} \
  --entrypoint sh "$IMAGE" -c 'cd /tmp && cp /scripts/visual-check.mjs . && ln -s /app/node_modules node_modules && exec node visual-check.mjs "$@"' sh \
  --out /out ${ARGS[@]+"${ARGS[@]}"}
