import cx_Oracle
import os
from datetime import datetime
import time

# Configuración de conexión a Oracle
oracle_user = 'sinu'
oracle_password = 'sinu'
oracle_host = '10.20.1.120'
oracle_port = '1521'
oracle_service_name = 'SISPROD_PDB1.SUB08061516441.SISVNETWORK.ORACLEVCN.COM'

# Crear conexión
dsn = cx_Oracle.makedsn(oracle_host, oracle_port, service_name=oracle_service_name)
connection = cx_Oracle.connect(oracle_user, oracle_password, dsn)
cursor = connection.cursor()

# Lista de tablas a exportar
tables = [
    "SEG_VIS_PERFIL_TERCERO_WEB",
    "SEG_VIS_TER_MENU_EMP_GWT"

]  # Puedes automatizar esto si quieres

# Crear carpeta 'dumps/' si no existe
dump_folder = "/opt/dumps/sis"
os.makedirs(dump_folder, exist_ok=True)

# Activar o desactivar la exportación de datos
export_data = False  # Cambiar a False si solo quieres crear la estructura

start_time = time.time()  # Marcar el inicio del tiempo total de ejecución

for table in tables:
    try:
        table_start_time = time.time()  # Marcar el inicio del tiempo para cada tabla

        print(f"[INFO] Exportando {table}...")

        # Archivo de salida
        output_file = os.path.join(dump_folder, f"{table}.sql")

        with open(output_file, "w", encoding="utf-8") as f:
            # Obtener nombres y tipos de columnas, ahora ordenadas por column_id
            cursor.execute(f"SELECT column_name, data_type, data_length, data_precision, data_scale "
                           f"FROM all_tab_columns WHERE table_name = '{table.upper()}' ORDER BY column_id")
            columns = cursor.fetchall()

            # Escribir encabezado para MySQL
            f.write("SET FOREIGN_KEY_CHECKS=0;\n")  # Evita errores con claves foráneas
            f.write(f"DROP TABLE IF EXISTS `{table}`;\n")

            # Crear estructura de la tabla
            f.write(f"CREATE TABLE `{table}` (\n")
            
            column_definitions = []
            for col_name, col_type, col_length, col_precision, col_scale in columns:
                # Establecer el tipo de datos y las características de longitud y escala
                if col_type == 'NUMBER':
                    if col_precision is not None and col_scale is not None:
                        column_definitions.append(f"`{col_name}` DECIMAL({col_precision},{col_scale}) DEFAULT NULL")
                    else:
                        column_definitions.append(f"`{col_name}` DECIMAL(18,6) DEFAULT NULL")
                elif col_type == 'VARCHAR2' or col_type == 'CHAR':
                    if col_length > 255:  # Si el largo de la columna es mayor a 255, usar TEXT
                        column_definitions.append(f"`{col_name}` TEXT DEFAULT NULL")
                    else:
                        column_definitions.append(f"`{col_name}` VARCHAR({col_length}) DEFAULT NULL")
                elif col_type == 'CLOB':
                    column_definitions.append(f"`{col_name}` TEXT DEFAULT NULL")
                elif col_type == 'DATE' or col_type == 'TIMESTAMP':
                    column_definitions.append(f"`{col_name}` DATETIME DEFAULT NULL")
                else:
                    column_definitions.append(f"`{col_name}` TEXT DEFAULT NULL")

            f.write(",\n".join(column_definitions))
            f.write("\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n")

            # Solo exportar datos si el interruptor está activado
            if export_data:
                cursor.execute(f"SELECT * FROM {table}")
                insert_values = []  # Para almacenar los valores de inserción

                for row in cursor:
                    values = []
                    for idx, value in enumerate(row):
                        col_name = columns[idx][0]  # Obtener el nombre de la columna

                        # Verificación de tamaño para columnas críticas
                        if isinstance(value, str) and len(value) > 255:
                            print(f"[ERROR] El valor de la columna {col_name} es demasiado largo: {value}")
                            value = value[:255]  # Limitar longitud a 255 caracteres si es necesario

                        if isinstance(value, str):
                            values.append("'" + value.replace("'", "''") + "'")  # Escapar comillas simples
                        elif isinstance(value, (int, float)):
                            values.append(str(value))  # Dejar números tal cual
                        elif isinstance(value, datetime):  # Si es una fecha/hora
                            try:
                                values.append("'" + value.strftime('%Y-%m-%d %H:%M:%S') + "'")  # Convertir a string con formato MySQL
                            except Exception as e:
                                values.append("NULL")  # Si no es una fecha válida, asignar NULL
                                print(f"[WARNING] Valor no válido para fecha: {value}")
                        elif value is None:
                            values.append("NULL")
                        else:
                            values.append("'" + str(value) + "'")  # Otros tipos de datos

                    insert_values.append(f"({', '.join(values)})")

                # Escribir todas las inserciones de una vez para mejorar rendimiento
                insert_batch_size = 1000  # Tamaño de lote
                for i in range(0, len(insert_values), insert_batch_size):
                    f.write(f"INSERT INTO `{table}` VALUES\n")
                    f.write(",\n".join(insert_values[i:i + insert_batch_size]) + ";\n")

                f.write("SET FOREIGN_KEY_CHECKS=1;\n")  # Reactivar claves foráneas

        table_end_time = time.time()  # Marcar el final del tiempo para cada tabla
        table_elapsed_time = (table_end_time - table_start_time) / 60  # Convertir a minutos
        print(f"[INFO] {table} exportada correctamente a {output_file}.")
        print(f"[INFO] Tiempo de ejecución: {table_elapsed_time:.2f} minutos.")

    except Exception as e:
        print(f"[ERROR] No se pudo exportar {table}: {str(e)}")

# Calcular el tiempo total de ejecución
end_time = time.time()
total_elapsed_time = (end_time - start_time) / 60  # Convertir a minutos
print(f"[INFO] Dump completado. Archivos guardados en '{dump_folder}/'.")
print(f"[INFO] Tiempo total de ejecución: {total_elapsed_time:.2f} minutos.")

cursor.close()
connection.close()
