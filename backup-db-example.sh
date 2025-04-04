#!/bin/bash

ENV_PATH="/path/to/your/deployment/.env"
if [ ! -f "$ENV_PATH" ]; then
  echo ".env file not found at $ENV_PATH"
  exit 1
fi

export $(grep -v '^#' "$ENV_PATH" | xargs)

USER=$DB_USERNAME
PASSWORD=$DB_PASSWORD
DATABASE=$DB_DATABASE
HOST=$DB_HOST
BACKUP_DIR="/path/to/your/deployment/backup/directory"
DATE=$(date +"%Y-%m-%d-%H-%M")
FILE="$BACKUP_DIR/db-backup-$DATE.sql"

mkdir -p "$BACKUP_DIR"

mysqldump -u"$USER" -p"$PASSWORD" -h "$HOST" "$DATABASE" > "$FILE"
gzip "$FILE"

find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +30 -delete

echo "✅ Backup created in: $FILE.gz"