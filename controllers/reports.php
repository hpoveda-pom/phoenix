<?php
require_once('models/class_recordset.php');
require_once('models/class_connections.php');
require_once('models/class_exportexcel.php');
require_once('models/class_queryoci.php');
require_once('models/class_querymysqli.php');
require_once('models/class_querymysqlissl.php');
require_once('models/class_queryclickhouse.php');
require_once('models/class_connoci.php');
require_once('models/class_connmysqli.php');
require_once('models/class_connmysqlissl.php');
require_once('models/class_connclickhouse.php');
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

$GroupBy = null;
if (isset($_GET['GroupBy'])) {
  $GroupBy = $_GET['GroupBy'];
}

$OrderBy = null;
if (isset($_GET['OrderBy'])) {
  $OrderBy = $_GET['OrderBy'];
}

$Filter = null;
if (isset($_GET['Filter'])) {
  $Filter = $_GET['Filter'];
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

$filter_selected = array();
if (isset($_GET['filter_selected'])) {
  $filter_selected = $_GET['filter_selected'];
}

$array_filters = $filter_selected;
if (is_array($Filter)) {
  // Manejar arrays indexados numéricamente: Filter[0][field], Filter[1][field], etc.
  if (isset($Filter[0]) && is_array($Filter[0])) {
    // Es un array indexado: Filter[0][field], Filter[1][field], etc.
    foreach ($Filter as $filter_index => $filter_item) {
      if (is_array($filter_item) && isset($filter_item['field']) && !empty($filter_item['field'])) {
        $array_filters[] = array(
          'filter' => array($filter_item['field'] => isset($filter_item['keyword']) ? $filter_item['keyword'] : ''),
          'operator' => isset($filter_item['operator']) ? $filter_item['operator'] : '='
        );
      }
    }
  } elseif (isset($Filter['field']) && !empty($Filter['field'])) {
    // Formato antiguo: Filter[field] (sin índice numérico)
    $array_filters[] = array(
      'filter' => array($Filter['field'] => isset($Filter['keyword']) ? $Filter['keyword'] : ''),
      'operator' => isset($Filter['operator']) ? $Filter['operator'] : '='
    );
  }
}

$filter_results = array();
if (is_array($array_filters) && !empty($array_filters)) {
  foreach ($array_filters as $key_filters => $row_filters) {
    if (is_array($row_filters)) {
      foreach ($row_filters['filter'] as $filter_key => $filter_value) {
        $filter_results[] = array(
          'key' => $filter_key,
          'operator' => $row_filters['operator'],
          'value' => $filter_value,
        );
      }
    }
  }
}

// Debug breve: solo filtros finales
if (!isset($GLOBALS['debug_filters'])) {
  $GLOBALS['debug_filters'] = [];
}
$GLOBALS['debug_filters'][] = "=== REPORTS.PHP (CARGA INICIAL) ===";
$GLOBALS['debug_filters'][] = "filter_results: " . json_encode($filter_results);

//Group by selected
$groupby_selected = array();
if (isset($_GET['groupby_selected'])) {
  $groupby_selected = $_GET['groupby_selected'];
}

$array_groupby = $groupby_selected;
if (is_array($GroupBy)) {
  if ($GroupBy['field']) {
    $array_groupby[] = array(
      'GroupBy' => $GroupBy['field'],
    );
  }
}

$groupby_results = array();
if (is_array($array_groupby) && !empty($array_groupby)) {
  foreach ($array_groupby as $key_groupby => $row_groupby) {
    if (is_array($row_groupby)) {
      foreach ($row_groupby as $groupby_key => $groupby_value) {
        $groupby_results[] = array(
          'key' => $groupby_key,
          'value' => $groupby_value,
        );
      }
    }
  }
}

//Sum by selected
$SumBy = null;
if (isset($_GET['SumBy'])) {
  $SumBy = $_GET['SumBy'];
}

$sumby_selected = array();
if (isset($_GET['sumby_selected'])) {
  $sumby_selected = $_GET['sumby_selected'];
}

$array_sumby = $sumby_selected;
if (is_array($SumBy)) {
  if ($SumBy['field']) {
    $array_sumby[] = array(
      'SumBy' => $SumBy['field'],
    );
  }
}

$sumby_results = array();
if (is_array($array_sumby) && !empty($array_sumby)) {
  foreach ($array_sumby as $key_sumby => $row_sumby) {
    if (is_array($row_sumby)) {
      // Si tiene la clave 'SumBy', usar ese valor directamente como nombre de campo
      if (isset($row_sumby['SumBy']) && !empty($row_sumby['SumBy'])) {
        $sumby_results[] = array(
          'key' => 'field',
          'value' => $row_sumby['SumBy'],
        );
      } else {
        // Si no, procesar como antes
        foreach ($row_sumby as $sumby_key => $sumby_value) {
          if ($sumby_key !== 'SumBy' || !empty($sumby_value)) {
            $sumby_results[] = array(
              'key' => $sumby_key,
              'value' => $sumby_value,
            );
          }
        }
      }
    }
  }
}

//OrderBy by selected
$orderby_selected = array();
if (isset($_GET['orderby_selected'])) {
  $orderby_selected = $_GET['orderby_selected'];
}

$array_orderby = $orderby_selected;
if (is_array($OrderBy)) {
  if ($OrderBy['field']) {
    $array_orderby[] = array(
      'OrderBy' => array($OrderBy['field'] => $OrderBy['operator']),
    );
  }
}

$orderby_results = array();
if (is_array($array_orderby) && !empty($array_orderby)) {
  foreach ($array_orderby as $key_orderby => $row_orderby) {
    if (is_array($row_orderby)) {
      foreach ($row_orderby['OrderBy'] as $orderby_key => $orderby_value) {
        $orderby_results[] = array(
          'key' => $orderby_key,
          'value' => $orderby_value,
        );
      }
    }
  }
}

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

if ($Id) {
  $query_reports_info = "
  SELECT a.*, b.Title AS Category, c.FullName, d.Connector AS conn_connector, d.Schema AS conn_schema, d.Title AS conn_title,

  CASE
  WHEN e.LastExecution IS NOT NULL THEN e.LastExecution
  WHEN f.LastExecution IS NOT NULL THEN f.LastExecution
  ELSE NULL
  END AS LastExecution,

  CASE
  WHEN e.SyncStatus IS NOT NULL THEN e.SyncStatus
  WHEN f.SyncStatus IS NOT NULL THEN f.SyncStatus
  ELSE NULL
  END AS SyncStatus,
  g.FullName AS UserUpdatedName

  FROM reports a
  INNER JOIN category b ON b.CategoryId = a.CategoryId 
  INNER JOIN users c ON c.UsersId = a.UsersId 
  INNER JOIN connections d ON d.ConnectionId = a.ConnectionId

  LEFT JOIN pipelines e ON e.ReportsId = a.ReportsId
  LEFT JOIN pipelines f ON f.PipelinesId = a.PipelinesId
  LEFT JOIN users g ON g.UsersId = a.UserUpdated 

  WHERE a.ReportsId = ".$Id."
  ORDER BY `Order` ASC
  ";
  $reports_info = class_Recordset(1, $query_reports_info, null, null, 1);

  // Verificar si hay un error en la consulta
  if (isset($reports_info['error']) && !empty($reports_info['error'])) {
    echo '<div class="alert alert-subtle-danger" role="alert">Error al consultar el reporte: ' . htmlspecialchars($reports_info['error']) . '</div>';
    exit;
  }

  if (isset($reports_info['info']['total_rows']) && $reports_info['info']['total_rows'] > 0 && isset($reports_info['data'][0])) {
    $row_reports_info = $reports_info['data'][0];
  } else {
    $error_msg = 'Error, no ha seleccionado un reporte válido!';
    if (isset($reports_info['error']) && !empty($reports_info['error'])) {
      $error_msg .= '<br>Detalle: ' . htmlspecialchars($reports_info['error']);
    } elseif (isset($reports_info['msg_error']) && !empty($reports_info['msg_error'])) {
      $error_msg .= '<br>Detalle: ' . htmlspecialchars($reports_info['msg_error']);
    }
    echo '<div class="alert alert-subtle-danger" role="alert">' . $error_msg . '</div>';
    exit;
  }
  
}else{
  echo '<div class="alert alert-subtle-danger" role="alert">Error, no ha seleccionado un reporte válido!</div>';
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

// Inicializar array de debug global antes de cualquier llamada
if (!isset($GLOBALS['debug_info'])) {
  $GLOBALS['debug_info'] = [];
}

//reports recordset
if (isset($row_reports_info['TypeId']) && $row_reports_info['TypeId']==1) {

  // Debug breve: antes de ejecutar
  if (!isset($GLOBALS['debug_filters'])) {
    $GLOBALS['debug_filters'] = [];
  }
  $GLOBALS['debug_filters'][] = "ANTES class_Recordset:";
  $GLOBALS['debug_filters'][] = "  Query: " . $row_reports_info['Query'];
  $GLOBALS['debug_filters'][] = "  filter_results: " . json_encode($filter_results);

  // Capturar tiempo de inicio de ejecución
  $query_execution_start = microtime(true);
  
  $array_headers  = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], null, null, 1, null, null, $sumby_results);
  
  // Debug: después de headers
  if (!isset($GLOBALS['debug_filters'])) {
    $GLOBALS['debug_filters'] = [];
  }
  $GLOBALS['debug_filters'][] = "DESPUÉS headers (sin filtros): " . (isset($array_headers['info']['total_rows']) ? $array_headers['info']['total_rows'] : 'N/A') . " filas";
  
  $array_reports  = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, $Limit, null, null, $sumby_results);
  
  // Debug: después de reports
  $GLOBALS['debug_filters'][] = "DESPUÉS reports (con filtros): " . (isset($array_reports['info']['total_rows']) ? $array_reports['info']['total_rows'] : 'N/A') . " filas totales, " . (isset($array_reports['data']) ? count($array_reports['data']) : 0) . " filas en página";
  
  // Calcular tiempo de ejecución
  $query_execution_end = microtime(true);
  $query_execution_time = $query_execution_end - $query_execution_start;
  $query_execution_time_formatted = number_format($query_execution_time, 3) . ' segundos';
  
  // Capturar información de debug de class_Connections
  $debug_info = isset($GLOBALS['debug_info']) ? $GLOBALS['debug_info'] : [];
  
  // Agregar información adicional de debug
  if (isset($array_reports['error'])) {
    $debug_info[] = "Error en class_Recordset: " . $array_reports['error'];
  }
  if (isset($array_headers['error'])) {
    $debug_info[] = "Error en class_Recordset (headers): " . $array_headers['error'];
  }
  
  // Guardar de vuelta en GLOBALS
  $GLOBALS['debug_info'] = $debug_info;
  
  // Inicializar array_info con valores por defecto si no existe
  if (isset($array_reports['info']) && is_array($array_reports['info'])) {
    $array_info = $array_reports['info'];
  } else {
    $array_info = ['total_rows' => 0, 'page_rows' => 0];
  }
  
  // Asegurar que array_headers tenga la estructura correcta
  if (!isset($array_headers['headers']) || !is_array($array_headers['headers'])) {
    $array_headers['headers'] = [];
  }
  
  // Asegurar que array_reports tenga la estructura correcta
  if (!isset($array_reports['data']) || !is_array($array_reports['data'])) {
    $array_reports['data'] = [];
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