import json
import cx_Oracle
import mysql.connector
from kafka import KafkaConsumer

# Configuración de conexión de Kafka
KAFKA_BROKER = 'localhost:9092'  # Dirección de tu broker de Kafka
KAFKA_TOPIC = 'oracle_changes'  # El tema de Kafka donde llegan los cambios

# Configuración de Oracle
ORACLE_USER = 'sinu'
ORACLE_PASSWORD = 'sinu'
ORACLE_DSN = '10.20.1.120:1521/SISPROD_PDB1.SUB08061516441.SISVNETWORK.ORACLEVCN.COM'

# Configuración de MySQL
MYSQL_HOST = 'localhost'
MYSQL_USER = 'root'
MYSQL_PASSWORD = 'Solid256!'
MYSQL_DB = 'db_sis'

# Nombre de la tabla a usar
TABLE_NAME = 'SRC_MAT_PENSUM'

# Conexión a Oracle
def get_oracle_connection():
    dsn = cx_Oracle.makedsn('10.20.1.120', '1521', service_name='SISPROD_PDB1.SUB08061516441.SISVNETWORK.ORACLEVCN.COM')
    return cx_Oracle.connect(ORACLE_USER, ORACLE_PASSWORD, dsn)

# Conexión a MySQL
def get_mysql_connection():
    return mysql.connector.connect(
        host=MYSQL_HOST,
        user=MYSQL_USER,
        password=MYSQL_PASSWORD,
        database=MYSQL_DB
    )

# Función para obtener las columnas de la tabla de Oracle
def get_oracle_columns(table_name):
    oracle_conn = get_oracle_connection()
    oracle_cursor = oracle_conn.cursor()

    # Obtener las columnas de la tabla de Oracle
    oracle_cursor.execute(f"SELECT column_name FROM all_tab_columns WHERE table_name = '{table_name.upper()}'")
    columns = [row[0] for row in oracle_cursor.fetchall()]

    # Cerrar conexión de Oracle
    oracle_cursor.close()
    oracle_conn.close()

    return columns

# Función para obtener la clave primaria de la tabla de Oracle
def get_primary_key_column(table_name):
    oracle_conn = get_oracle_connection()
    oracle_cursor = oracle_conn.cursor()

    # Obtener la clave primaria de la tabla de Oracle
    oracle_cursor.execute(f"""
        SELECT acc.column_name
        FROM all_cons_columns acc
        JOIN all_constraints ac
            ON acc.constraint_name = ac.constraint_name
        WHERE acc.table_name = '{table_name.upper()}'
        AND ac.constraint_type = 'P'
    """)
    primary_key_column = oracle_cursor.fetchone()

    # Cerrar conexión de Oracle
    oracle_cursor.close()
    oracle_conn.close()

    return primary_key_column[0] if primary_key_column else None

# Función para realizar la carga inicial desde Oracle a MySQL
def initial_load():
    oracle_conn = get_oracle_connection()
    oracle_cursor = oracle_conn.cursor()

    # Obtener las columnas de la tabla de Oracle
    columns = get_oracle_columns(TABLE_NAME)

    # Obtener la clave primaria dinámica de la tabla
    primary_key_column = get_primary_key_column(TABLE_NAME)
    if not primary_key_column:
        print("Error: No se encontró la clave primaria para la tabla.")
        return

    # Realizar consulta para obtener todos los registros de la tabla de origen en Oracle
    select_query = f"SELECT {', '.join(columns)} FROM {TABLE_NAME}"
    oracle_cursor.execute(select_query)
    rows = oracle_cursor.fetchall()

    mysql_conn = get_mysql_connection()
    mysql_cursor = mysql_conn.cursor()

    # Verificar si la tabla está vacía antes de cargar
    mysql_cursor.execute(f"SELECT COUNT(1) FROM {TABLE_NAME}")
    if mysql_cursor.fetchone()[0] == 0:
        # Crear la consulta de inserción dinámica
        insert_query = f"INSERT INTO {TABLE_NAME} ({', '.join(columns)}) VALUES ({', '.join(['%s'] * len(columns))})"
        
        # Insertar los registros de Oracle en MySQL
        for row in rows:
            mysql_cursor.execute(insert_query, row)
            print(f"Insertado registro con {primary_key_column}: {row[columns.index(primary_key_column)]}")

        mysql_conn.commit()
        print("Carga inicial completada.")

    # Cerrar conexiones de Oracle y MySQL
    oracle_cursor.close()
    oracle_conn.close()
    mysql_cursor.close()
    mysql_conn.close()

# Procesar los cambios de Kafka en lotes de 10
def process_changes():
    # Crear un consumidor de Kafka
    consumer = KafkaConsumer(KAFKA_TOPIC, 
                             group_id='oracle_group',
                             bootstrap_servers=[KAFKA_BROKER],
                             value_deserializer=lambda m: json.loads(m.decode('utf-8')))
    
    # Conectar a MySQL
    mysql_conn = get_mysql_connection()
    mysql_cursor = mysql_conn.cursor()

    print("Esperando mensajes de Kafka...")

    batch_size = 10
    batch = []  # Almacenar los cambios en un lote

    for message in consumer:
        change = message.value  # El mensaje contiene los datos modificados

        # Verificar la estructura de los mensajes
        print(f"Mensaje recibido: {change}")

        operation = change.get('operation')
        data = change.get('data')

        # Obtener la clave primaria dinámica
        primary_key_column = get_primary_key_column(TABLE_NAME)
        if not primary_key_column:
            print("Error: No se encontró la clave primaria para la tabla.")
            continue

        primary_key_value = data.get(primary_key_column)

        # Obtener las columnas de la tabla de Oracle
        columns = get_oracle_columns(TABLE_NAME)

        # Preparar la operación
        if operation == 'update' or operation == 'insert':
            # Verificar si el registro ya existe en MySQL
            mysql_cursor.execute(f"SELECT COUNT(1) FROM {TABLE_NAME} WHERE {primary_key_column} = %s", (primary_key_value,))
            count = mysql_cursor.fetchone()[0]

            # Crear la consulta de actualización dinámica
            update_query = f"UPDATE {TABLE_NAME} SET {', '.join([f'{col} = %s' for col in columns if col != primary_key_column])} WHERE {primary_key_column} = %s"
            
            if count > 0:
                # Si existe, agregar a la lista para actualizar
                update_values = [data[col] for col in columns if col != primary_key_column] + [primary_key_value]
                batch.append(('update', update_query, update_values))
                print(f"Preparado para actualizar registro {primary_key_column}: {primary_key_value}")
            else:
                # Si no existe, agregar a la lista para insertar
                insert_query = f"INSERT INTO {TABLE_NAME} ({', '.join(columns)}) VALUES ({', '.join(['%s'] * len(columns))})"
                insert_values = [data[col] for col in columns]
                batch.append(('insert', insert_query, insert_values))
                print(f"Preparado para insertar nuevo registro {primary_key_column}: {primary_key_value}")

        elif operation == 'delete':
            # Si la operación es eliminar, agregar a la lista para borrar
            delete_query = f"DELETE FROM {TABLE_NAME} WHERE {primary_key_column} = %s"
            batch.append(('delete', delete_query, (primary_key_value,)))
            print(f"Preparado para eliminar registro {primary_key_column}: {primary_key_value}")

        # Procesar el lote cada vez que tenga 10 elementos
        if len(batch) >= batch_size:
            for operation_type, query, values in batch:
                if operation_type == 'update' or operation_type == 'insert':
                    mysql_cursor.execute(query, values)
                elif operation_type == 'delete':
                    mysql_cursor.execute(query, values)

            mysql_conn.commit()
            print(f"Lote de {batch_size} cambios procesados.")
            batch.clear()  # Limpiar el lote después de procesarlo

    # Cerrar las conexiones
    mysql_cursor.close()
    mysql_conn.close()

# Función principal que ejecuta el flujo de procesamiento
if __name__ == "__main__":
    # Cargar los datos iniciales desde Oracle a MySQL
    initial_load()

    # Luego de la carga inicial, empezar a procesar los cambios de Kafka en lotes de 10
    process_changes()
