import pymysql
import sys
import os
import glob

# Verificar si se pasó un archivo o patrón de archivos como argumento
if len(sys.argv) != 2:
    print("[ERROR] Uso: python3 importar_mysql.py <archivo.sql> o <patron/*.sql>")
    sys.exit(1)

dump_pattern = sys.argv[1]  # Obtener el archivo o patrón de archivos desde la línea de comandos

# Verificar si el patrón de archivo coincide con archivos existentes
files = glob.glob(dump_pattern)
if not files:
    print(f"[ERROR] No se encontraron archivos que coincidan con el patrón '{dump_pattern}'.")
    sys.exit(1)

# Configuración de conexión a MySQL
mysql_user = 'root'
mysql_password = 'Solid256!'
mysql_host = 'localhost'
mysql_database = 'db_sis'

# Conectar a MySQL
try:
    connection = pymysql.connect(
        host=mysql_host,
        user=mysql_user,
        password=mysql_password,
        database=mysql_database
    )
    cursor = connection.cursor()
    print(f"[INFO] Conexión a MySQL establecida correctamente.")
except Exception as e:
    print(f"[ERROR] No se pudo conectar a MySQL: {e}")
    sys.exit(1)

# Procesar cada archivo .sql encontrado
for dump_file in files:
    print(f"[INFO] Procesando archivo: {dump_file}")
    
    # Leer el archivo y convertirlo para MySQL
    with open(dump_file, "r", encoding="utf-8") as f:
        sql_script = f.read()

    # Reemplazos para compatibilidad MySQL (en caso de necesidad futura)
    sql_script = sql_script.replace("DECIMAL(18,6)", "DECIMAL(18,6)") \
                           .replace("DATETIME", "DATETIME") \
                           .replace("TEXT", "TEXT")

    # Ejecutar el script SQL en MySQL
    statements = sql_script.split(";\n")  # Separar comandos SQL
    for statement in statements:
        if statement.strip():  # Evitar líneas vacías
            try:
                cursor.execute(statement)
                connection.commit()
            except Exception as e:
                print(f"[ERROR] No se pudo ejecutar: {statement[:50]}... Error: {e}")

print(f"[INFO] Migración de archivos a MySQL completada con éxito.")
cursor.close()
connection.close()
