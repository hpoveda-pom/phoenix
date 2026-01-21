<?php
function class_queryMysqliExe($ConnectionId, $Query){
    $conn = class_Connections($ConnectionId);
    $result = $conn->query($Query);
    
    if ($result === TRUE) {
        $affectedRows = $conn->affected_rows; // Obtener el número de filas afectadas
        $output = "Query executed successfully. Affected rows: " . $affectedRows;
    } else {
        $output = "Error executing query: " . $conn->error;
    }

    $conn->close();

    return $output; // Devuelve el resultado de la ejecución
}
