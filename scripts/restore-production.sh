#!/bin/sh
set -eu

if [ "${CONFIRM_RESTORE:-}" != "RESTORE_NORTHLINK" ]; then
  echo "Restauración detenida: defina CONFIRM_RESTORE=RESTORE_NORTHLINK." >&2
  exit 2
fi

if [ "$#" -ne 1 ] || [ ! -f "$1" ]; then
  echo "Uso: restore-production.sh /ruta/respaldo.dump" >&2
  exit 2
fi

pg_restore --list "$1" >/dev/null
pg_restore --clean --if-exists --no-owner --no-acl --exit-on-error --dbname="${PGDATABASE:?PGDATABASE requerido}" "$1"
