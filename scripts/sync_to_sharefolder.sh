#!/bin/bash

# Definir las carpetas de origen y destino
ORIGEN="/var/www/phoenix/data/csv/pipelines/"
DESTINO="/media/sf_Sharepoint/Byron Perez Rojas/Pipelines"

# Crear la carpeta destino si no existe
mkdir -p "$DESTINO"

# Copiar archivos
cp -r "$ORIGEN"/* "$DESTINO/"

# Mensaje de confirmación
echo "[$(date '+%Y-%m-%d %H:%M:%S')][Archivos copiados de $ORIGEN a $DESTINO]"

# Genera CSV de reportes específicos y los almacena en una carpeta
#/usr/bin/curl -s "http://localhost/data.php?action=csv&Id=579&file_path=$DESTINO/"

# Mensaje de confirmación
#echo "[$(date '+%Y-%m-%d %H:%M:%S')][Reportes generados y guardados en $DESTINO]"
