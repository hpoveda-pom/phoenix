##
# Este script se conecta a la base de datos de SIS y copia algunas tablas y las coloca en db_sis de phoenix
##
import cx_Oracle
import mysql.connector
import time  # Importar el módulo time para medir el tiempo de ejecución

# Configuración de Oracle
ORACLE_USER = 'sinu'
ORACLE_PASSWORD = 'sinu'
ORACLE_DSN = '10.20.1.120:1521/SISPROD_PDB1.SUB08061516441.SISVNETWORK.ORACLEVCN.COM'

# Configuración de MySQL
MYSQL_HOST = 'localhost'
MYSQL_USER = 'root'
MYSQL_PASSWORD = 'Solid256!'
MYSQL_DB = 'db_sis'
BATCH_SIZE = 1000

# Lista de tablas a procesar
TABLES = [
    'SRC_MAT_PENSUM',
    'SRC_NOT_GRUPO',
    'SRC_PENSUM',
    'SRC_PERIODO',
    'SRC_REQ_MATERIA',
    'SRC_SEDE',
    'SRC_TEM_MATRICULA',
    'SRC_UNI_ACADEMICA',
    'BAS_ADI_TERCERO',
    'BAS_DEPENDENCIA',
    'BAS_GEOPOLITICA',
    'BAS_TERCERO',
    'FIN_TARIFA',
    'SRC_ALUM_PERIODO',
    'SRC_ALUM_PROGRAMA',
    'SRC_BLOQUE',
    'SRC_CORR_MATERIA',
    'SRC_ENC_LIQUIDACION',
    'SRC_ENC_LIQUIDACION_ANT',
    'SRC_ENC_MATRICULA',
    'SRC_EQU_MATERIA',
    'SRC_FORMULARIO',
    'SRC_GENERICA',
    'SRC_GRUPO',
    'SRC_HIS_ACADEMICA',
    'SRC_VIS_EQUIVALENCIA',
    'SRC_VIS_GRUPO_OFE_EQUI_WEB',
    'SRC_VIS_HIS_ACADEMICA',
    'SRC_VIS_MAT_PENSUM',
    'SRC_SEMAFORO',
    'BAS_VIS_DOCENTE',
    'BAS_VIS_NUC_FAMILIAR',
    'EXP_VIS_MAT_NOT_ESTUDIANTE',
    'EXP_VIS_OFERTA_GRUPOS',
    'SEG_VIS_PERFIL_TERCERO_WEB',
    'SEG_VIS_TER_MENU_EMP_GWT'
]

def get_oracle_connection():
    dsn = cx_Oracle.makedsn('10.20.1.120', '1521', service_name='SISPROD_PDB1.SUB08061516441.SISVNETWORK.ORACLEVCN.COM')
    return cx_Oracle.connect(ORACLE_USER, ORACLE_PASSWORD, dsn)

def get_mysql_connection():
    return mysql.connector.connect(host=MYSQL_HOST, user=MYSQL_USER, password=MYSQL_PASSWORD, database=MYSQL_DB)

def get_oracle_columns(table_name):
    """Obtiene las columnas de la tabla en Oracle."""
    with get_oracle_connection() as conn:
        with conn.cursor() as cursor:
            cursor.execute(f"SELECT column_name FROM all_tab_columns WHERE table_name = '{table_name.upper()}'")
            return [row[0] for row in cursor.fetchall()]

def is_table(table_name):
    """Verifica si la entidad es una tabla y no una vista."""
    with get_oracle_connection() as conn:
        with conn.cursor() as cursor:
            cursor.execute(f"SELECT OBJECT_TYPE FROM ALL_OBJECTS WHERE OBJECT_NAME = '{table_name.upper()}'")
            row = cursor.fetchone()
            return row and row[0] == 'TABLE'

def get_primary_key_column(table_name):
    """Obtiene la clave primaria de la tabla en Oracle si es una tabla física."""
    if not is_table(table_name):
        print(f"Advertencia: {table_name} es una vista, no se puede obtener clave primaria.")
        return None

    with get_oracle_connection() as conn:
        with conn.cursor() as cursor:
            cursor.execute(f"""
                SELECT acc.column_name
                FROM all_cons_columns acc
                JOIN all_constraints ac
                    ON acc.constraint_name = ac.constraint_name
                WHERE acc.table_name = '{table_name.upper()}'
                AND ac.constraint_type = 'P'
            """)
            row = cursor.fetchone()
            return row[0] if row else None

def get_last_inserted_primary_key(table_name, primary_key):
    """Obtiene el último valor de la clave primaria en MySQL."""
    with get_mysql_connection() as conn:
        with conn.cursor() as cursor:
            cursor.execute(f"SELECT MAX({primary_key}) FROM {table_name}")
            row = cursor.fetchone()
            return row[0] if row and row[0] else 0  # Si no hay datos, empezar desde 0

def initial_load(table_name):
    """Carga inicial por lotes desde Oracle a MySQL, evitando duplicados."""
    columns = get_oracle_columns(table_name)
    primary_key = get_primary_key_column(table_name)
    
    if not primary_key:
        print(f"Error: No se encontró la clave primaria para la tabla {table_name}.")
        return

    last_inserted_id = get_last_inserted_primary_key(table_name, primary_key)
    total_rows = 0
    start_time = time.time()  # Marca el inicio del tiempo de la tabla

    with get_oracle_connection() as oracle_conn, get_mysql_connection() as mysql_conn:
        with oracle_conn.cursor() as oracle_cursor, mysql_conn.cursor() as mysql_cursor:
            # Verifica si la clave primaria es un número
            oracle_cursor.execute(f"""
                SELECT DATA_TYPE
                FROM ALL_TAB_COLUMNS
                WHERE TABLE_NAME = '{table_name.upper()}' AND COLUMN_NAME = '{primary_key.upper()}'
            """)
            column_type = oracle_cursor.fetchone()[0]

            # Ajusta la consulta dependiendo del tipo de datos de la clave primaria
            if column_type == 'NUMBER':
                oracle_cursor.execute(f"""
                    SELECT {', '.join(columns)}
                    FROM {table_name}
                    WHERE {primary_key} > :last_id
                    ORDER BY {primary_key}
                    FETCH NEXT {BATCH_SIZE} ROWS ONLY
                """, {'last_id': last_inserted_id})
            else:
                oracle_cursor.execute(f"""
                    SELECT {', '.join(columns)}
                    FROM {table_name}
                    WHERE TO_CHAR({primary_key}) > :last_id
                    ORDER BY {primary_key}
                    FETCH NEXT {BATCH_SIZE} ROWS ONLY
                """, {'last_id': str(last_inserted_id)})  # Convierte a cadena si no es numérico
                
            rows = oracle_cursor.fetchall()
            if not rows:
                print(f"No hay más datos para insertar en la tabla {table_name}.")
                return  # Termina el proceso si no hay más datos

            # Insertar en MySQL
            insert_query = f"INSERT INTO {table_name} ({', '.join(columns)}) VALUES ({', '.join(['%s'] * len(columns))})"
            mysql_cursor.executemany(insert_query, rows)
            mysql_conn.commit()

            # Actualizar el último ID insertado
            last_inserted_id = rows[-1][0]  # La última clave primaria en este lote
            total_rows += len(rows)

    end_time = time.time()  # Marca el final del tiempo de la tabla
    table_time = (end_time - start_time) / 60  # Calcula el tiempo de la tabla en minutos
    print(f"{table_name}: {total_rows} registros procesados en {table_time:.2f} minutos.")

if __name__ == "__main__":
    start_time = time.time()  # Marca el inicio del tiempo total
    for table in TABLES:
        initial_load(table)
    end_time = time.time()  # Marca el final del tiempo total
    total_time = (end_time - start_time) / 60  # Calcula el tiempo total en minutos
    print(f"\nTiempo total de ejecución: {total_time:.2f} minutos.")
