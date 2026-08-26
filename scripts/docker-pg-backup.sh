#!/bin/bash
set -euo pipefail

# 1. Autodiscover script directory (resolves symlinks if any exist)
SCRIPT_DIR="$(cd "$(dirname "$(realpath "${BASH_SOURCE[0]}")")" && pwd)"

# 2. Derive project root (one level up from /scripts) and backup target dir
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${PROJECT_ROOT}/pgsql-backups"

# 3. Configuration
CONTAINER_NAME="postgres"
POSTGRES_USER="postgres"
DATE=$(date +%Y%m%d_%H%M%S)

# 4. Ensure destination folder exists
mkdir -p "$BACKUP_DIR"

# 5. Execute dump inside container and output directly to target folder
docker exec -t "$CONTAINER_NAME" pg_dumpall -U "$POSTGRES_USER" | gzip > "${BACKUP_DIR}/pg_all_dbs_${DATE}.sql.gz"

# 6. Retention policy: Remove backups older than 3 days
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +3 -exec rm -f {} \;
