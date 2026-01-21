#!/bin/bash 

# Definir variables
USER="phoenix"
PASSWORD="Solid256!"
BACKUP_DIR="/opt/backup"
RETENTION_DAYS=7  # Número de días que deseas mantener los respaldos
DATE=$(date +"%Y/%m/%d")
YEAR=$(date +"%Y")
MONTH=$(date +"%m")
DAY=$(date +"%d")

# Crear las carpetas si no existen
mkdir -p "$BACKUP_DIR/$YEAR/$MONTH/$DAY"

# Eliminar respaldos antiguos que superen los días de retención
find "$BACKUP_DIR" -type f -name "*.gz" -mtime +$RETENTION_DAYS -exec rm -f {} \;
echo "Respaldos antiguos eliminados (más de $RETENTION_DAYS días)."

# Listado de bases de datos a respaldar
DATABASES=("phoenix" "datawerehouse" "pipelines")

# Hacer el backup de cada base de datos
for DB in "${DATABASES[@]}"; do
    BACKUP_FILE="$BACKUP_DIR/$YEAR/$MONTH/$DAY/$DB.sql.gz"

    mysqldump --skip-lock-tables --set-gtid-purged=OFF --column-statistics=0 --no-tablespaces --single-transaction --force -u "$USER" -p"$PASSWORD" "$DB" | gzip > "$BACKUP_FILE"

    if [ $? -eq 0 ]; then
        echo "Respaldo de $DB guardado en $BACKUP_FILE"
    else
        echo "Error al respaldar la base de datos $DB" >&2
    fi
done
