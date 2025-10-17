#!/bin/bash

# Database Restore Script for PHP Store
# Usage: ./restore_db.sh <backup_file>

if [ $# -eq 0 ]; then
    echo "Usage: ./restore_db.sh <backup_file>"
    echo "Available backups:"
    ls -la database_backups/*.sql 2>/dev/null || echo "No backups found in database_backups/"
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo "Restoring database from: $BACKUP_FILE"

# Restore the backup
docker exec -i phpstore_mysql mysql -u root -proot phpstore < "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ Database restored successfully from: $BACKUP_FILE"
else
    echo "❌ Restore failed!"
    exit 1
fi



