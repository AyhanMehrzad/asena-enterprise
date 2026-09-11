#!/usr/bin/env bash
# ==============================================================================
# ASENA Enterprise - Automated Database Backup Engine
# Version: 1.0.0
# Description: Dumps MySQL database using InnoDB single-transaction, compresses
#              with gzip, and applies automatic 30-day retention pruning.
# Compatible with Linux cron jobs:
#   0 2 * * * /bin/bash /opt/lampp/htdocs/asena/asena-enterprise/scripts/backup.sh >> /var/log/asena_backup.log 2>&1
# ==============================================================================

set -eo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
BACKUP_DIR="${ROOT_DIR}/database/backups"
ENV_FILE="${ROOT_DIR}/.env"

echo "----------------------------------------------------------------------"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting ASENA Database Backup..."

# 1. Ensure backup directory exists
mkdir -p "${BACKUP_DIR}"

# 2. Extract database credentials from .env
if [[ ! -f "${ENV_FILE}" ]]; then
    echo "[ERROR] .env file not found at ${ENV_FILE}" >&2
    exit 1
fi

get_env_val() {
    local key="$1"
    local val
    val=$(grep -E "^${key}=" "${ENV_FILE}" | head -n 1 | cut -d '=' -f2- | tr -d '"' | tr -d "'" | tr -d '\r' || true)
    echo "${val}"
}

DB_HOST=$(get_env_val "DB_HOST")
DB_NAME=$(get_env_val "DB_NAME")
DB_USER=$(get_env_val "DB_USER")
DB_PASS=$(get_env_val "DB_PASS")

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="${DB_NAME:-asena_premium}"
DB_USER="${DB_USER:-root}"

echo "[INFO] Target Host: ${DB_HOST} | Database: ${DB_NAME} | User: ${DB_USER}"

# 3. Detect mysqldump binary
MYSQLDUMP_BIN=""
if [[ -x "/opt/lampp/bin/mysqldump" ]]; then
    MYSQLDUMP_BIN="/opt/lampp/bin/mysqldump"
elif command -v mysqldump >/dev/null 2>&1; then
    MYSQLDUMP_BIN="$(command -v mysqldump)"
else
    echo "[ERROR] mysqldump executable could not be found." >&2
    exit 1
fi

echo "[INFO] Using mysqldump binary: ${MYSQLDUMP_BIN}"

# 4. Generate timestamped archive name
TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
BACKUP_FILE="${BACKUP_DIR}/asena_${DB_NAME}_${TIMESTAMP}.sql.gz"

# 5. Build mysqldump argument array safely
DUMP_ARGS=(
    "-h" "${DB_HOST}"
    "-u" "${DB_USER}"
    "--default-character-set=utf8mb4"
    "--single-transaction"
    "--quick"
    "--triggers"
)

if [[ -n "${DB_PASS}" ]]; then
    DUMP_ARGS+=("-p${DB_PASS}")
fi

DUMP_ARGS+=("${DB_NAME}")

# 6. Execute dump and compress directly to gzip stream
echo "[INFO] Exporting and compressing database tables..."
"${MYSQLDUMP_BIN}" "${DUMP_ARGS[@]}" | gzip -9 > "${BACKUP_FILE}"

FILE_SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
echo "[SUCCESS] Backup created successfully: ${BACKUP_FILE} (Size: ${FILE_SIZE})"

# 7. Automated Pruning / Retention Policy (Default: 30 Days)
RETENTION_DAYS=30
echo "[INFO] Cleaning up archives older than ${RETENTION_DAYS} days..."
DELETED_COUNT=0
while IFS= read -r old_file; do
    if [[ -n "${old_file}" ]]; then
        rm -f "${old_file}"
        echo "[PRUNED] Removed old backup: $(basename "${old_file}")"
        DELETED_COUNT=$((DELETED_COUNT + 1))
    fi
done < <(find "${BACKUP_DIR}" -type f -name "asena_${DB_NAME}_*.sql.gz" -mtime +"${RETENTION_DAYS}" 2>/dev/null || true)

echo "[INFO] Retention check complete. Pruned ${DELETED_COUNT} old archive(s)."
echo "[$(date '+%Y-%m-%d %H:%M:%S')] ASENA Backup completed successfully."
echo "----------------------------------------------------------------------"
exit 0
