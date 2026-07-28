#!/usr/bin/env bash
#===============================================================================
# Importa datos transaccionales pesados desde ecm_db/ mediante psql COPY.
#
# USO:
#   1. Configurar DATABASE_URL o pasar variables manualmente:
#      export DATABASE_URL="postgresql://user:pass@host:5432/dbname"
#      bash database/data/import_production_data.sh
#
#   2. O usando .env del proyecto:
#      bash database/data/import_production_data.sh
#
# REQUISITOS:
#   - psql (cliente PostgreSQL)
#   - Las tablas deben existir (php artisan migrate ejecutado)
#   - Ejecutar DESPUES de: php artisan db:seed --class=ImportProductionDataSeeder
#===============================================================================

set -euo pipefail

# --- Leer config desde .env si existe ---
if [ -f .env ]; then
    export "$(grep '^DB_' .env | xargs)"
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-opsisdb}"
DB_USERNAME="${DB_USERNAME:-ecmadmin}"
DB_PASSWORD="${DB_PASSWORD:-}"

ECM_DIR="database/data/ecm_db"

PGARGS="-h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE"
if [ -n "$DB_PASSWORD" ]; then
    export PGPASSWORD="$DB_PASSWORD"
fi

echo "=== Importando datos transaccionales pesados ==="
echo "BD: $DB_DATABASE en $DB_HOST:$DB_PORT"
echo ""

import_csv() {
    local table="$1"
    local file

    file=$(ls "$ECM_DIR/${table}_"*.csv 2>/dev/null | head -1)

    if [ -z "$file" ]; then
        echo "  [skip] $table: archivo no encontrado"
        return
    fi

    local rows
    rows=$(($(wc -l < "$file") - 1))
    local size
    size=$(du -h "$file" | cut -f1)

    echo "  [truncate] $table..."
    psql $PGARGS -c "TRUNCATE $table;" 2>/dev/null || true

    echo "  [copy] $table: ${rows} filas (${size})..."
    psql $PGARGS -c "\copy $table FROM '$file' WITH (FORMAT CSV, HEADER true);"

    echo "  [ok]   $table importada"
}

# --- Tablas transaccionales pesadas ---
import_csv "call_records"
import_csv "agent_call_performance"
import_csv "agent_state_transitions"
import_csv "chat_records"

echo ""
echo "=== Ajustando secuencias ==="
psql $PGARGS -c "
SELECT setval('call_records_id_seq', COALESCE((SELECT MAX(id) FROM call_records), 1));
SELECT setval('agent_call_performance_id_seq', COALESCE((SELECT MAX(id) FROM agent_call_performance), 1));
SELECT setval('agent_state_transitions_id_seq', COALESCE((SELECT MAX(id) FROM agent_state_transitions), 1));
SELECT setval('chat_records_id_seq', COALESCE((SELECT MAX(id) FROM chat_records), 1));
"

echo ""
echo "=== Importacion completada ==="
