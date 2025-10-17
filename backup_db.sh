#!/bin/bash

# Database Backup Script for PHP Store
# Usage: ./backup_db.sh [backup_name]

BACKUP_NAME=${1:-"backup_$(date +%Y%m%d_%H%M%S)"}
BACKUP_FILE="database_backups/${BACKUP_NAME}.sql"

# Create backups directory if it doesn't exist
mkdir -p database_backups

echo "Creating database backup: ${BACKUP_FILE}"

# Create the backup
docker exec phpstore_mysql mysqldump -u root -proot phpstore > "${BACKUP_FILE}"

if [ $? -eq 0 ]; then
    echo "✅ Backup created successfully: ${BACKUP_FILE}"
    echo "📁 Database files are now stored in: ./data/mysql/"
    echo "💾 Backup files are stored in: ./database_backups/"
else
    echo "❌ Backup failed!"
    exit 1
fi



