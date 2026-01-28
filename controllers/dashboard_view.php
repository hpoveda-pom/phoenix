<?php
require_once('models/class_recordset.php');
require_once('models/class_connections.php');
require_once('models/class_querymysqli.php');
require_once('models/class_connmysqli.php');
require_once('models/class_lastexecution.php');
require_once('models/class_fieldformat.php');
require_once('models/class_fieldalias.php');
require_once('models/class_masking.php');
require_once('models/class_reportparams.php');

// Variables globales
$Id = null;
if (isset($_GET['Id'])) {
  $Id = $_GET['Id'];
}

$filter_results = [];
$groupby_results = [];
$Limit = null;

// Procesar parámetros de filtros si existen
if (isset($_GET['Filter']) || isset($_GET['filter_selected'])) {
    $params = ReportParams::processAll(array(
        'Filter' => isset($_GET['Filter']) ? $_GET['Filter'] : null,
        'filter_selected' => isset($_GET['filter_selected']) ? $_GET['filter_selected'] : array(),
        'GroupBy' => isset($_GET['GroupBy']) ? $_GET['GroupBy'] : null,
        'groupby_selected' => isset($_GET['groupby_selected']) ? $_GET['groupby_selected'] : array(),
    ));
    $filter_results = $params['filter_results'];
    $groupby_results = $params['groupby_results'];
}

// Obtener información del dashboard
$row_reports_info = ReportParams::getReportInfo($Id, false);

if (!$row_reports_info) {
  $error_message = 'Error, no ha seleccionado un dashboard válido!';
  $error = true;
} elseif (isset($row_reports_info['error']) && !empty($row_reports_info['error'])) {
  $error_message = 'Error al consultar el dashboard: ' . htmlspecialchars($row_reports_info['error']);
  $error = true;
} else {
  $error = false;
  
  // Verificar que sea un dashboard (TypeId = 2)
  if (!isset($row_reports_info['TypeId']) || $row_reports_info['TypeId'] != 2) {
    $error_message = 'El ID especificado no corresponde a un dashboard.';
    $error = true;
  } else {
    // Obtener widgets del dashboard
    $query_parent = "
    SELECT 
    a.*,

    CASE
    WHEN b.LastExecution IS NOT NULL THEN b.LastExecution
    WHEN c.LastExecution IS NOT NULL THEN c.LastExecution
    ELSE NULL
    END AS LastExecution,

    CASE
    WHEN b.SyncStatus IS NOT NULL THEN b.SyncStatus
    WHEN c.SyncStatus IS NOT NULL THEN c.SyncStatus
    ELSE NULL
    END AS SyncStatus

    FROM reports a
    LEFT JOIN pipelines b ON b.ReportsId = a.ReportsId
    LEFT JOIN pipelines c ON c.PipelinesId = a.PipelinesId
    WHERE 
    a.ParentId = ".$row_reports_info['ReportsId']." 
    AND a.Status = 1 
    ORDER BY a.Order ASC
    ";
    $array_parent = class_Recordset(1, $query_parent, null, null, NULL);
    $array_info = $array_parent['info'];
  }
}

// Validar permisos si el usuario es tipo 3
$cod_error = 0;
$msg_error = null;

if ($UsersType == 3) {
    if ($UsersId != $row_reports_info['UsersId']) {
        $cod_error = 1;
        $msg_error = "Sin Permisos";
    }
}

if ($cod_error) {
    $error_message = $msg_error;
    $error = true;
}

?>
