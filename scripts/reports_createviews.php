<?php
require_once('../config.php');
require_once('../models/class_querymysqli.php');
require_once('../models/class_querymysqliexe.php');
require_once('../models/class_connections.php');
require_once('../models/class_connmysqli.php');
require_once('../models/class_recordset.php');

$qry_reports = "
SELECT
    b.Title AS Category,
    c.FullName AS Owner,
    a.CreatedDate,
    a.LastUpdated,
    a.ReportsId,
    a.Title,
    a.Description,
    CONCAT(
        'CREATE OR REPLACE VIEW `', 
        LEFT(REPLACE(CONCAT(a.ReportsId, '_', a.Title), ' ', '_'), 64), 
        '` AS ', 
        a.`Query`
    ) AS Query,
    a.`Query` AS sql_file_content
FROM reports a
INNER JOIN category b ON b.CategoryId = a.CategoryId
INNER JOIN users c ON c.UsersId = a.UsersId
WHERE a.`Status` = 1 AND a.ConnectionId = 8 AND a.TypeId = 1";

$reports = class_Recordset(1, $qry_reports, null, null, null);
$array_reports = $reports['data'];

$total_rows = 0;

if (!empty($array_reports)) {
    echo "Total Rows: " . ($total_rows = count($array_reports)) . "\n";

    // Verifica si la carpeta "query" existe, si no, la crea
    $query_folder = '../query/';
    if (!is_dir($query_folder)) {
        mkdir($query_folder, 0777, true);
    }

    foreach ($array_reports as $row_reports) {

        echo $row_reports['ReportsId'] . ". " . $row_reports['Title'] . ": ";

        try {

            $sql_file_content = null;
            $sql_file_content .= "/*"."\n";
            $sql_file_content .= "* Phoenix SQL File Generator". "\n";
            $sql_file_content .= "* Reporte: ".$row_reports['ReportsId'].". ".$row_reports['Title']. "\n";
            $sql_file_content .= "* Descripción: ".$row_reports['Description']. "\n";
            $sql_file_content .= "* Categoría: ".$row_reports['Category']. "\n";
            $sql_file_content .= "* Dueño: ".$row_reports['Owner']. "\n";
            $sql_file_content .= "* Creado: ".$row_reports['CreatedDate']. "\n";
            $sql_file_content .= "* Modificado: ".$row_reports['LastUpdated']. "\n";
            $sql_file_content .= "*/"."\n";
            $sql_file_content .= $row_reports['sql_file_content'];

            // Ejecuta el query para crear la vista
            $create_views = class_queryMysqliExe(8, $row_reports['Query']);
            echo $create_views . "\n"; // Muestra el resultado de la consulta ejecutada.

            // Guarda la consulta en un archivo .sql
            //$filename = $query_folder . $row_reports['ReportsId'] . "_" . preg_replace('/[^A-Za-z0-9_]/', '_', $row_reports['Title']) . ".sql";
            $filename = $query_folder . $row_reports['ReportsId'] . ". " . $row_reports['Title'] . ".sql";
            file_put_contents($filename, $sql_file_content);

            echo "Archivo guardado: " . $filename . "\n";
        } catch (Exception $e) {
            // Si ocurre un error, muestra un mensaje y continúa con el siguiente reporte
            echo "Error: " . $e->getMessage() . "<br>";
        }
    }
} else {
    echo "No se encontraron registros.\n";
}
?>
