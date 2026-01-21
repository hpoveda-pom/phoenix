<?php
require_once('../models/class_recordset.php');
require_once('../models/class_connections.php');
require_once('../models/class_querymysqli.php');
require_once('../models/class_connmysqli.php');

// Verificar si hay una búsqueda
$search_query = isset($_GET['query']) ? $_GET['query'] : '';

// Reports Menu Recordset
$array_reports = [];
if ($search_query) {
    $query_reports = "
    SELECT 
    a.ReportsId,
    a.Title,
    a.Description,
    a.Order, 
    b.Title AS Category, 
    c.FullName
    FROM reports a 
    INNER JOIN category b ON b.CategoryId = a.CategoryId
    INNER JOIN users c ON c.UsersId = a.UsersId
    WHERE a.Status = 1 AND b.IdType = 1 AND b.Status = 1";

    if ($search_query !== '') {
        $query_reports .= " AND (a.Title LIKE '%" . $search_query . "%' OR a.Description LIKE '%" . $search_query . "%' OR a.ReportsId = '" . $search_query . "' OR b.Title LIKE '%" . $search_query . "%' OR c.FullName LIKE '%" . $search_query . "%')";
    }

    $query_reports .= " ORDER BY a.ReportsId DESC";

    $results_reports = class_Recordset(1, $query_reports, null, null, 10);

    //echo "<pre>";print_r($results_reports);exit;

    if ($results_reports['data']) {

            foreach ($results_reports['data'] as $key_reports => $row_reports) {
                $array_reports[] = $row_reports;
            }

    }
}

echo json_encode($array_reports); // Devuelve los resultados en formato JSON
?>
