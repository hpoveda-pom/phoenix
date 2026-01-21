"""
Script para migrar múltiples tablas de Oracle a MySQL con medición de tiempo.

REQUISITOS (Instalación de dependencias en Python):
1. Instalar paquetes necesarios con pip:
   pip3 install cx_Oracle pandas sqlalchemy pymysql  

2. Instalar Oracle Instant Client (requerido para cx_Oracle):
   - Descargar desde: https://www.oracle.com/database/technologies/instant-client.html  
   - Configurar en Linux:
     sudo mkdir -p /opt/oracle && sudo mv instantclient_* /opt/oracle/instantclient  
     sudo sh -c "echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf"  
     sudo ldconfig  
"""

import cx_Oracle
import pandas as pd
import time
from sqlalchemy import create_engine

# Datos de conexión a Oracle
oracle_user = 'sinu'
oracle_password = 'sinu'
oracle_host = '10.20.1.120'
oracle_port = '1521'
oracle_service_name = 'SISPROD_PDB1.SUB08061516441.SISVNETWORK.ORACLEVCN.COM'

# Datos de conexión a MySQL
mysql_user = 'root'
mysql_password = 'Solid256!'
mysql_host = 'localhost'
mysql_database = 'db_sis'

# Lista de tablas a migrar
tables_to_migrate = [
    "FIN_TARIFA",
    "SRC_PENSUM",
    #"SRC_ALUM_PROGRAMA"
]

# Crear conexiones a Oracle y MySQL con SQLAlchemy
oracle_connection_string = f"oracle+cx_oracle://{oracle_user}:{oracle_password}@{oracle_host}:{oracle_port}/?service_name={oracle_service_name}"
oracle_engine = create_engine(oracle_connection_string)

mysql_connection_string = f"mysql+pymysql://{mysql_user}:{mysql_password}@{mysql_host}/{mysql_database}"
mysql_engine = create_engine(mysql_connection_string)

for table in tables_to_migrate:
    try:
        start_time = time.time()  # Tiempo de inicio
        df = pd.read_sql(f"SELECT * FROM {table}", oracle_engine)  # Leer datos de Oracle
        if not df.empty:
            df.to_sql(table.lower(), con=mysql_engine, if_exists='replace', index=False)  # Insertar en MySQL
            elapsed_time = time.time() - start_time  # Calcular duración
            print(f"[INFO] {table} migrada en {elapsed_time/60:.2f} min.")
        else:
            print(f"[WARNING] La tabla {table} está vacía. No se migró.")
    except Exception as e:
        print(f"[ERROR] Ocurrió un problema al migrar {table}: {e}")

# Cerrar conexiones
oracle_engine.dispose()
mysql_engine.dispose()
print("[INFO] Conexiones cerradas.")
