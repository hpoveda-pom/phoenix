<?php
function class_ociFormatColumns($array_headers, $Query, $ConnectionId){

	$conn = class_Connections($ConnectionId);

	if (!$conn) {
	    $e = oci_error();
	    echo "Error de conexión: " . $e['message'];
	    exit;
	}

	// Obtener todas las tablas involucradas
	$sql_tables = "
	    SELECT DISTINCT TABLE_NAME
	    FROM ALL_TAB_COLUMNS
	    WHERE LOWER(TABLE_NAME) IN (
	        SELECT REGEXP_SUBSTR(LOWER(:query), '[A-Za-z0-9_]+', 1, LEVEL) AS table_name
	        FROM DUAL
	        CONNECT BY REGEXP_SUBSTR(LOWER(:query), '[A-Za-z0-9_]+', 1, LEVEL) IS NOT NULL
	    )
	";

	$stid_tables = oci_parse($conn, $sql_tables);
	oci_bind_by_name($stid_tables, ':query', $Query);
	oci_execute($stid_tables);

	// Separar por coma para la cláusula IN (con sanitización)
	$sql_in_headers = implode("','", array_map('addslashes', $array_headers));
	$sql_in_headers = "'$sql_in_headers'"; // Agregar comillas

	// Almacenar resultados en un array de columnas
	$columns = [];

	while ($row = oci_fetch_assoc($stid_tables)) {

	    $table_name = $row['TABLE_NAME'];
	    
	    // Obtener las columnas de la tabla
	    $sql_columns = "
	        SELECT DISTINCT COLUMN_NAME, DATA_TYPE, DATA_LENGTH
	        FROM USER_TAB_COLUMNS
	        INNER JOIN USER_OBJECTS ON USER_TAB_COLUMNS.TABLE_NAME = USER_OBJECTS.OBJECT_NAME
	        WHERE USER_TAB_COLUMNS.COLUMN_NAME IN($sql_in_headers)
	        AND USER_TAB_COLUMNS.TABLE_NAME = :table_name
	    ";
	    
	    $stid_columns = oci_parse($conn, $sql_columns);
		oci_bind_by_name($stid_columns, ':table_name', $table_name);
	    oci_execute($stid_columns);

		//echo "<hr><b>".$row['TABLE_NAME']."</b><br>";


	    // Almacenar columnas en el array
	    while ($column = oci_fetch_assoc($stid_columns)) {

	    	//echo $table_name." - ".$column['COLUMN_NAME']." - ".$column['DATA_TYPE']." - ".$column['DATA_LENGTH']."<br>";

	    	//Compatibilidad con MySQL para el destino
			$column_converted = class_dbConvertFieldType($column['DATA_TYPE'], $column['DATA_LENGTH']);

	        $arr_header[$column['COLUMN_NAME']] = [
	            'COLUMN_NAME' 	=> $column['COLUMN_NAME'],
	            'DATA_TYPE' 	=> $column_converted['DATA_TYPE'],
	            'DATA_LENGTH' 	=> $column_converted['DATA_LENGTH']
	        ];
	    }
	}

	// Cerrar la conexión
	oci_free_statement($stid_tables);
	oci_free_statement($stid_columns);
	oci_close($conn);

	$results = [];
	foreach ($array_headers as $key_headers => $row_headers) {

		$results[] = $arr_header[$row_headers];
		
	}
	

	return $results;
}