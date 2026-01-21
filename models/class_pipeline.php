<?php
function class_pipeline($array_headers, $array_data, $source_table, $source_conn, $schema_create, $table_create, $table_truncate, $time_stamp, $RecordsAlert) {
    
    // Conectar a la base de datos
    $conn = class_Connections($source_conn);
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    //reintentar conexión
    if (!$conn->ping()) {
        echo "La conexión se perdió, reconectando...\n";
        $conn = class_Connections($source_conn);
        if ($conn->connect_error) {
            die("Re-conexión fallida: " . $conn->connect_error);
        }
    }


    if (empty($array_headers)) {
        die("No hay headers.\n");
    }
    if (empty($array_data)) {
        die("No hay registros.\n");
    }

    // Crear tabla temporal
    $temp_table_name = $source_table . "_temp";
    $create_temp_table_sql = "CREATE TEMPORARY TABLE `$temp_table_name` (";

    foreach ($array_headers as $row_header) {
        $new_data_type = $row_header['DATA_TYPE'];
        if (!empty($row_header['DATA_LENGTH'])) {
            $new_data_type .= "(" . $row_header['DATA_LENGTH'] . ")";
        }
        $create_temp_table_sql .= "`" . $row_header['COLUMN_NAME'] . "` " . $new_data_type . ",";
    }
    
    if ($time_stamp) {
        $create_temp_table_sql .= "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ";
        $create_temp_table_sql .= "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    } else {
        $create_temp_table_sql = rtrim($create_temp_table_sql, ",");
    }
    $create_temp_table_sql .= ")";

    if (!$conn->query($create_temp_table_sql)) {
        die("Error al crear la tabla temporal: " . $conn->error . "\n");
    }

    // Insertar datos en la tabla temporal en lotes
    $batch_size = 1000;
    $chunks = array_chunk($array_data, $batch_size);
    $date_now = date('Y-m-d H:i:s');

    foreach ($chunks as $chunk) {
        $values_list = [];
        foreach ($chunk as $row) {
            $values = array_map(function ($value) use ($conn) {
                return ($value === null || strtolower($value) === 'null') ? 'NULL' : "'" . $conn->real_escape_string($value) . "'";
            }, array_values($row));
            
            if ($time_stamp) {
                $values[] = "'$date_now'";
                $values[] = "'$date_now'";
            }
            $values_list[] = "(" . implode(",", $values) . ")";
        }
        
        $columns = implode(",", array_keys($chunk[0]));
        if ($time_stamp) {
            $columns .= ", created_at, updated_at";
        }
        $insert_temp_sql = "INSERT INTO `$temp_table_name` ($columns) VALUES " . implode(",", $values_list);
        
        if (!$conn->query($insert_temp_sql)) {
            die("Error al insertar datos en la tabla temporal: " . $conn->error . "\n");
        }
    }

    // Verificar la existencia de la tabla destino
    $result = $conn->query("SHOW TABLES LIKE '$source_table'");
    if ($result->num_rows == 0 && $table_create) {
        $create_table_sql = "CREATE TABLE `$source_table` (";
        $columns_sql = [];

        foreach ($array_headers as $row_header) {
            $new_data_type = $row_header['DATA_TYPE'];
            if (!empty($row_header['DATA_LENGTH'])) {
                $new_data_type .= "(" . $row_header['DATA_LENGTH'] . ")";
            }
            $columns_sql[] = "`" . $row_header['COLUMN_NAME'] . "` " . $new_data_type;
        }
        
        if ($time_stamp) {
            $columns_sql[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            $columns_sql[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        }

        $create_table_sql .= implode(",", $columns_sql) . ")";
        if (!$conn->query($create_table_sql)) {
            die("Error al crear la tabla destino: " . $conn->error . "\n");
        }
    }

    // Sincronizar columnas de la tabla destino con la tabla temporal
    $result = $conn->query("SHOW COLUMNS FROM `$source_table`");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }

    foreach ($array_headers as $row_header) {
        if (!in_array($row_header['COLUMN_NAME'], $existing_columns)) {
            $new_data_type = $row_header['DATA_TYPE'];
            if (!empty($row_header['DATA_LENGTH'])) {
                $new_data_type .= "(" . $row_header['DATA_LENGTH'] . ")";
            }
            $alter_sql = "ALTER TABLE `$source_table` ADD COLUMN `" . $row_header['COLUMN_NAME'] . "` " . $new_data_type;
            if (!$conn->query($alter_sql)) {
                die("Error al agregar columna '" . $row_header['COLUMN_NAME'] . "': " . $conn->error . "\n");
            }
        }
    }

    // Truncar tabla destino si es necesario
    if ($table_truncate) {
        if (!$conn->query("TRUNCATE TABLE `$source_table`")) {
            die("Error al truncar la tabla: " . $conn->error . "\n");
        }
    }

    // Mover los datos de la tabla temporal a la tabla destino
    $columns = implode(",", array_map(function ($header) {
        return "`" . $header['COLUMN_NAME'] . "`";
    }, $array_headers));
    if ($time_stamp) {
        $columns .= ", created_at, updated_at";
    }
    
    $move_data_sql = "INSERT INTO `$source_table` ($columns) SELECT $columns FROM `$temp_table_name`";
    if (!$conn->query($move_data_sql)) {
        die("Error al mover datos a la tabla destino: " . $conn->error . "\n");
    }

    // Cerrar conexión
    $conn->close();
}