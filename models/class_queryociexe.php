<?php
function class_queryOciExe($ConnectionId, $Query){
    // Obtener la conexión
    $conn = class_Connections($ConnectionId);

    // Preparar y ejecutar la consulta
    $countStmt  = oci_parse($conn, $Query);
    $result     = oci_execute($countStmt);

    if ($result) {
        // Obtener el número de filas afectadas
        $affectedRows = oci_num_rows($countStmt);
        $output = "Query executed successfully. Affected rows: " . $affectedRows;
    } else {
        // Error en la ejecución de la consulta
        $output = "Error executing query: " . oci_error($countStmt)['message'];
    }

    // Liberar el statement y cerrar la conexión
    oci_free_statement($countStmt);
    oci_close($conn);

    return $output; // Devuelve el resultado de la ejecución
}
?>
