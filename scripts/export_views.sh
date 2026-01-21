#!/bin/bash

DB_NAME="pipelines"
USER="phoenix"
PASSWORD="Solid256!"

# Obtener la lista de vistas y guardarlas en un archivo
MYSQL_PWD="$PASSWORD" mysql -u "$USER" -N -e "SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA='$DB_NAME';" > views_list.txt

# Crear directorio para los archivos SQL
mkdir -p views_sql

# Leer cada vista y exportarla a un archivo
while IFS= read -r view; do
    # Obtener la definición del VIEW
    CREATE_VIEW=$(MYSQL_PWD="$PASSWORD" mysql -u "$USER" -N -e "SHOW CREATE VIEW $DB_NAME.$view;" | sed 's/\r//g')

    # Extraer todo después de " AS " hasta el final
    QUERY=$(echo "$CREATE_VIEW" | awk '/ AS /{found=1} found')

    # Guardar en un archivo .sql
    echo "$QUERY;" > "views_sql/$view.sql"

    echo "Exportado: $view.sql"
done < views_list.txt

echo "Todas las vistas han sido exportadas en views_sql/"
