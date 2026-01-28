<?php
require_once('models/class_recordset.php');
require_once('models/class_connections.php');
require_once('models/class_exportexcel.php');
require_once('models/class_queryoci.php');
require_once('models/class_querymysqli.php');
require_once('models/class_querymysqlissl.php');
require_once('models/class_queryclickhouse.php');
require_once('models/class_querysqlserver.php');
require_once('models/class_connoci.php');
require_once('models/class_connmysqli.php');
require_once('models/class_connmysqlissl.php');
require_once('models/class_connclickhouse.php');
require_once('models/class_connsqlserver.php');
require_once('models/class_filterremove.php');
require_once('models/class_tipodato.php');
require_once('models/class_accesslog.php');
require_once('models/class_querymysqliexe.php');
require_once('models/class_lastexecution.php');
require_once('models/class_masking.php');
require_once('models/class_fieldalias.php');
require_once('models/class_tooltips.php');
require_once('models/class_cruds.php');
require_once('models/class_fieldformat.php');
require_once('models/class_reportparams.php');

//CRUD
$form_id = null;
if (isset($_POST['form_id'])) {
  $form_id = $_POST['form_id'];
}

//global vars
$action = null;
if (isset($_GET['action'])) {
  $action = $_GET['action'];
}

if (isset($_POST['action'])) {
  $action = $_POST['action'];
}

$Id = null;
if (isset($_GET['Id'])) {
  $Id = $_GET['Id'];
}

$Limit = 10;
if (isset($_GET['Limit'])) {
  $Limit = $_GET['Limit'];
}

if (isset($_GET['unset'])) {
  foreach ($_GET['unset'] as $key_unset => $row_unset) {
    class_filterRemove($_GET, $key_unset, $row_unset);
  }
}

// Procesar todos los parámetros usando la clase centralizada
$params = ReportParams::processAll(array(
    'Filter' => isset($_GET['Filter']) ? $_GET['Filter'] : null,
    'filter_selected' => isset($_GET['filter_selected']) ? $_GET['filter_selected'] : array(),
    'GroupBy' => isset($_GET['GroupBy']) ? $_GET['GroupBy'] : null,
    'groupby_selected' => isset($_GET['groupby_selected']) ? $_GET['groupby_selected'] : array(),
    'SumBy' => isset($_GET['SumBy']) ? $_GET['SumBy'] : null,
    'sumby_selected' => isset($_GET['sumby_selected']) ? $_GET['sumby_selected'] : array(),
    'OrderBy' => isset($_GET['OrderBy']) ? $_GET['OrderBy'] : null,
    'orderby_selected' => isset($_GET['orderby_selected']) ? $_GET['orderby_selected'] : array()
));

$filter_results = $params['filter_results'];
$groupby_results = $params['groupby_results'];
$sumby_results = $params['sumby_results'];
$orderby_results = $params['orderby_results'];

//limit list
$array_limit = array(
  '10'    => 10,
  '25'    => 25,
  '50'    => 50,
  '100'   => 100,
  '150'   => 150,
  '200'   => 200,
  '500'   => 500,
  '1000'  => 1000,
  '2000'  => 2000,
  '5000'  => 5000,
  '10000' => 10000,
  '15000' => 15000,
  '20000' => 20000,
  '25000' => 25000
);

//Order By list
$array_orderby = array(
  'ASC'    => 'Ascendente',
  'DESC' => 'Descendente'
);

// Obtener información del reporte usando la clase centralizada (con cache)
$row_reports_info = ReportParams::getReportInfo($Id, false);

if (!$row_reports_info) {
  echo '<div class="alert alert-subtle-danger" role="alert">Error, no ha seleccionado un reporte válido!</div>';
  exit;
}

if (isset($row_reports_info['error']) && !empty($row_reports_info['error'])) {
  echo '<div class="alert alert-subtle-danger" role="alert">Error al consultar el reporte: ' . htmlspecialchars($row_reports_info['error']) . '</div>';
  exit;
}

// Inicializar Query si no existe
if (!isset($row_reports_info['Query'])) {
  $row_reports_info['Query'] = '';
}
if (isset($_POST['Query'])) {
  $row_reports_info['Query'] = $_POST['Query'];
}

//ACTION - Sent to email
if ($action == "email") {

echo "email";

exit;
}

//ACTION - Export to Excel
$LogType = 'Reporte';
if ($action == "excel") {
  $excel_title  = $row_reports_info['ReportsId'].". ".$row_reports_info['Title'];
  $excel_data   = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null, null, null, $sumby_results);

  $excel_data_masked = [];

  foreach ($excel_data['data'] as $key => $row) {

    $array_masked = [];
    foreach ($excel_data['headers'] as $key_headers => $row_headers) {
      $valor_dato = $row[$row_headers];
      $array_masked[$row_headers] = maskedData($row_headers,$valor_dato,$row_reports_info['UsersId'],$row_reports_info['ReportsId']);
    }

    $excel_data_masked[] = $array_masked;

  }

$new_headers = [];
foreach ($excel_data['headers'] as $key_headers => $row_headers) {
  $new_headers[] = getFieldAlias($row_headers);
}

  $tmp_path = "tmp/"; // temporaly save files
  $download_redirect = true;
  class_exportExcel($new_headers, $excel_data_masked, $excel_title, $tmp_path, $download_redirect);
  $LogType = 'Excel';
}

//reports recordset
if (isset($row_reports_info['TypeId']) && $row_reports_info['TypeId']==1) {
  // Por defecto usamos DataTables para reportes tipo 1
  // Solo obtenemos headers, los datos los obtendrá DataTables desde data.php
  // Esto evita procesamiento duplicado
  
  // Capturar tiempo de inicio de ejecución
  $query_execution_start = microtime(true);
  
  // IMPORTANTE: Cuando hay GroupBy o SumBy, los headers cambian (se agregan campos calculados como "Cantidad")
  // Por eso ejecutamos el query con esos parámetros para obtener los headers correctos
  // Usamos Limit=1 para evitar overload (solo necesitamos la estructura, no todos los datos)
  
  $array_headers = null;
  
  // Si hay GroupBy o SumBy, obtener headers con esos parámetros aplicados (headers incluyen campos calculados)
  if (!empty($groupby_results) || !empty($sumby_results)) {
    $array_headers = class_Recordset(
      $row_reports_info['ConnectionId'], 
      $row_reports_info['Query'], 
      $filter_results,  // Aplicar filtros
      $groupby_results,  // Aplicar groupby para obtener headers con campos calculados (Cantidad, etc.)
      1,  // Solo 1 fila para obtener headers (evita overload)
      null, 
      null, 
      $sumby_results  // Aplicar sumby para obtener headers con campos calculados
    );
  }
  
  // Si no se obtuvieron headers (o no había GroupBy/SumBy), obtener estructura base
  if (!isset($array_headers['headers']) || empty($array_headers['headers'])) {
    $array_headers = class_Recordset(
      $row_reports_info['ConnectionId'], 
      $row_reports_info['Query'], 
      $filter_results,  // Aplicar filtros si existen
      null,  // Sin groupby para obtener estructura base
      1,  // Solo 1 fila para obtener headers (evita overload)
      null, 
      null, 
      null  // Sin sumby para obtener estructura base
    );
  }
  
  // Si aún no hay headers, intentar sin filtros para obtener la estructura base
  if (!isset($array_headers['headers']) || empty($array_headers['headers'])) {
    $array_headers = class_Recordset(
      $row_reports_info['ConnectionId'], 
      $row_reports_info['Query'], 
      null,  // Sin filtros
      null,  // Sin groupby
      1,  // Solo 1 fila para obtener headers (evita overload)
      null, 
      null, 
      null  // Sin sumby
    );
  }
  
  // Si aún no hay headers, intentar obtenerlos desde data.php haciendo una petición inicial
  // Esto puede pasar si la consulta no devuelve ningún resultado y no hay forma de obtener la estructura
  if (!isset($array_headers['headers']) || empty($array_headers['headers'])) {
    // Hacer una consulta mínima solo para obtener estructura
    // Usar LIMIT 0 si es posible, o simplemente ejecutar la consulta base
    $temp_query = $row_reports_info['Query'];
    // Intentar agregar LIMIT 0 al final si no existe
    if (stripos($temp_query, 'LIMIT') === false) {
      $temp_query = rtrim(trim($temp_query), ';') . ' LIMIT 0';
    }
    $array_headers = class_Recordset(
      $row_reports_info['ConnectionId'], 
      $temp_query, 
      null, 
      null, 
      0,  // 0 filas, solo estructura
      null, 
      null, 
      null
    );
  }
  
  // Calcular tiempo de ejecución
  $query_execution_end = microtime(true);
  $query_execution_time = $query_execution_end - $query_execution_start;
  $query_execution_time_formatted = number_format($query_execution_time, 3) . ' segundos';
  
  // Inicializar array_reports - DataTables obtendrá los datos desde data.php
  $array_reports = array(
    'headers' => isset($array_headers['headers']) && is_array($array_headers['headers']) ? $array_headers['headers'] : [],
    'data' => [], // Vacío, DataTables lo llenará
    'info' => array('total_rows' => 0, 'page_rows' => 0)
  );
  
  // Si hay error en la consulta de headers, preservarlo
  if (isset($array_headers['error']) && !empty($array_headers['error'])) {
    $array_reports['error'] = $array_headers['error'];
  }
  
  // Inicializar array_info con valores por defecto
  $array_info = array('total_rows' => 0, 'page_rows' => 0);
  
  // Asegurar que array_headers tenga la estructura correcta
  if (!isset($array_headers['headers']) || !is_array($array_headers['headers'])) {
    $array_headers['headers'] = [];
  }
}

//parent recordsets
if (isset($row_reports_info['TypeId']) && $row_reports_info['TypeId']==2) {
  // Capturar tiempo de inicio de ejecución
  $query_execution_start = microtime(true);
  
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
  
  // Calcular tiempo de ejecución
  $query_execution_end = microtime(true);
  $query_execution_time = $query_execution_end - $query_execution_start;
  $query_execution_time_formatted = number_format($query_execution_time, 3) . ' segundos';

}

// Validamos si el tipo de usuario es 3 y el ID del usuario logueado es diferente al del reporte
$cod_error = 0;
$msg_error = null;

if ($UsersType == 3) { // Verificamos si el tipo de usuario es 3
    if ($UsersId != $row_reports_info['UsersId']) { // Verificamos si el usuario no es el mismo que el del reporte
        $cod_error = 1;
        $msg_error = "Sin Permisos";
        $Response = $msg_error;
    }
}

if (!$cod_error) {
  // Inicializar $array_info si no está definido
  if (!isset($array_info)) {
    $array_info = ['total_rows' => 0, 'page_rows' => 0];
  }

  if (is_array($array_info) && isset($array_info['total_rows'])) {
      $Response = "OK;ROWS:" . $array_info['total_rows'];
  } else {
      $Response = "ERROR;ROWS 0";
  }

  //VIEWS - Reports
  if (isset($row_reports_info['TypeId']) && $row_reports_info['TypeId']==1) {
    require_once('views/reports_filtersbox.php');
    require_once('views/reports_breadcrumb.php');
    require_once('views/reports_results.php');
  }

  //VIEWS - Dashboard
  if (isset($row_reports_info['TypeId']) && $row_reports_info['TypeId']==2) {
    require_once('views/reports_breadcrumb.php');
    require_once('views/reports_dashboard.php');
  }

}else{
      echo '<div class="alert alert-outline-danger d-flex align-items-center mt-5" role="alert">
        <span class="fas fa-times-circle text-danger fs-5 me-3"></span>
        <p class="mb-0 flex-1">'.$msg_error.'</p>
      </div>';
}

class_accessLog($Id, $UsersId, $LogType, $exec_timestart, $Response);
?>