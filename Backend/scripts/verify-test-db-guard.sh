#!/usr/bin/env bash
# Comprueba que la protección de tests/TestCase.php aborta si los tests apuntan a otra base de datos.
#
# No toca kudos_db: crea una base de datos señuelo con una tabla `items` y 3 filas, ejecuta los tests
# forzando esa base de datos y comprueba que abortan con el mensaje de protección y que el señuelo
# sigue intacto. Al terminar borra el señuelo y la configuración temporal.
#
# Uso (desde cualquier carpeta, con Sail levantado): Backend/scripts/verify-test-db-guard.sh
set -euo pipefail

cd "$(dirname "$0")/.."

SAIL=./vendor/bin/sail
CANARY=kudos_guard_canary
CONFIG=phpunit.guard.xml
EXPECTED="Los tests deben ejecutarse contra la base de datos 'testing', no '${CANARY}'."

psql_admin() {
    "$SAIL" exec -T pgsql sh -c "psql -v ON_ERROR_STOP=1 -U \"\$POSTGRES_USER\" -d \"\$POSTGRES_DB\" -tAc \"$1\""
}

psql_canary() {
    "$SAIL" exec -T pgsql sh -c "psql -v ON_ERROR_STOP=1 -U \"\$POSTGRES_USER\" -d ${CANARY} -tAc \"$1\""
}

cleanup() {
    rm -f "$CONFIG"
    psql_admin "DROP DATABASE IF EXISTS ${CANARY}" >/dev/null || true
}
trap cleanup EXIT

psql_admin "DROP DATABASE IF EXISTS ${CANARY}" >/dev/null 2>&1
psql_admin "CREATE DATABASE ${CANARY}" >/dev/null
psql_canary "CREATE TABLE items (id int); INSERT INTO items VALUES (1), (2), (3);" >/dev/null
before=$(psql_canary "SELECT count(*) FROM items")

sed "s|<env name=\"DB_DATABASE\" value=\"testing\" force=\"true\"/>|<env name=\"DB_DATABASE\" value=\"${CANARY}\" force=\"true\"/>|" phpunit.xml > "$CONFIG"
grep -q "value=\"${CANARY}\"" "$CONFIG" || { echo "FALLO: no se ha podido forzar DB_DATABASE en ${CONFIG}"; exit 1; }

set +e
output=$("$SAIL" test --configuration="$CONFIG" 2>&1)
status=$?
set -e

after=$(psql_canary "SELECT count(*) FROM items" 2>/dev/null || echo "tabla borrada")

echo "Filas en ${CANARY}.items: antes=${before} después=${after}"

if [[ $status -eq 0 ]]; then
    echo "FALLO: los tests han pasado contra ${CANARY}; la protección no ha actuado."
    exit 1
fi
if ! grep -qF "$EXPECTED" <<<"$output"; then
    echo "FALLO: los tests han fallado, pero sin el mensaje de protección. Salida:"
    echo "$output" | tail -20
    exit 1
fi
if [[ "$before" != "$after" ]]; then
    echo "FALLO: el señuelo ha cambiado; la protección ha llegado tarde."
    exit 1
fi

echo "OK: los tests abortan con \"${EXPECTED}\" y el señuelo sigue intacto."
