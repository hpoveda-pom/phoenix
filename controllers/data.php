<?php
// Obtener el directorio base del proyecto
$base_dir = dirname(__DIR__);

require_once($base_dir . '/functions.php');
require_once($base_dir . '/models/class_recordset.php');
require_once($base_dir . '/models/class_connections.php');
require_once($base_dir . '/models/class_exportexcel.php');
require_once($base_dir . '/models/class_exportjson.php');
require_once($base_dir . '/models/class_exportcsv.php');
require_once($base_dir . '/models/class_queryoci.php');
require_once($base_dir . '/models/class_querymysqli.php');
require_once($base_dir . '/models/class_connoci.php');
require_once($base_dir . '/models/class_connmysqli.php');
require_once($base_dir . '/models/class_filterremove.php');
require_once($base_dir . '/models/class_tipodato.php');
require_once($base_dir . '/models/class_accesslog.php');
require_once($base_dir . '/models/class_querymysqliexe.php');
require_once($base_dir . '/models/class_pipeline.php');
require_once($base_dir . '/models/class_namingconvention.php');
require_once($base_dir . '/models/class_lastexecution.php');
require_once($base_dir . '/models/class_ociformat.php');
require_once($base_dir . '/models/class_fieldalias.php');

//php ini settings
require_once($base_dir . '/config.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_time_limit(0);
date_default_timezone_set($row_config['time_zone']);
ini_set('memory_limit', $row_config['memory_limit']);

$exec_timestart = microtime(true);

//global vars
$action = 'json';
if (isset($_GET['action'])) {
  $action = $_GET['action'];
}

$file_path = null;
if (isset($_GET['file_path'])) {
  $file_path = $_GET['file_path'];
}

$ReportsId = null;
if (isset($_GET['Id'])) {
  $ReportsId = $_GET['Id'];
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
  if ($Filter['field']) {
    $array_filters[] = array(
      'filter' => array($Filter['field'] => $Filter['keyword']),
      'operator' => $Filter['operator']
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
if ($ReportsId) {
  $query_reports_info = "
  SELECT a.*, b.Title AS Category, c.FullName, d.Connector AS conn_connector, d.Schema AS conn_schema, d.Title AS conn_title,
  e.ConnSource, e.TableSource, e.Status AS PipelineStatus,e.SchemaCreate, e.TableCreate, e.TableTruncate,e.TimeStamp,e.RecordsAlert
  FROM reports a 
  INNER JOIN category b ON b.CategoryId = a.CategoryId 
  INNER JOIN users c ON c.UsersId = a.UsersId 
  INNER JOIN connections d ON d.ConnectionId = a.ConnectionId 
  LEFT JOIN pipelines e ON e.ReportsId = a.ReportsId
  WHERE a.Status = 1 AND b.Status = 1 AND a.ReportsId = ".$ReportsId." ORDER BY `Order` ASC
  ";
  $reports_info = class_Recordset(1, $query_reports_info, null, null, 1);
  if (isset($reports_info['data'][0])) {
    $row_reports_info = $reports_info['data'][0];
  } else {
    if ($action != "datatables") {
      echo '<div class="alert alert-subtle-danger" role="alert">Error, no ha seleccionado un reporte válido!</div>';
      exit;
    }
  }
}else{
  if ($action != "datatables") {
    echo '<div class="alert alert-subtle-danger" role="alert">Error, no ha seleccionado un reporte válido!</div>';
    exit;
  }
}


$LogType = 'Reporte';

//export to csv
if ($action == "csv") {
  $row_reports_info['Category'];
  $csv_title  = $row_reports_info['ReportsId'].". ".$row_reports_info['Title'];
  $csv_data   = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null);
  
  //custom save csv path
  $csv_path = "csv/";
  if ($row_reports_info['Category']) {
    $csv_path = "csv/".$row_reports_info['Category']."/";
    if ($file_path) {
      $csv_path = $file_path.$row_reports_info['Category']."/";
    }
  }
  
  $csv_redirect = false;
  $csv_date     = false;
  $csv_prefix   = false;
  $csv_head     = false;
  $csv = class_exportCSV($csv_data['headers'], $csv_data['data'], $csv_title, $csv_path, $csv_redirect, $csv_date, $csv_prefix, $csv_head);

  if ($csv['filepath']) {
    $fault_code = 2;
    $fault_msg = "Error al generar el csv";
  }

  $LogType = 'csv';
}

//export to excel
if ($action == "excel") {
  $excel_title  = $row_reports_info['ReportsId'].". ".$row_reports_info['Title'];
  $excel_data   = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null);
  class_exportExcel($excel_data['headers'], $excel_data['data'], $excel_title);
  $LogType = 'Excel';
}

//export to json
if ($action == "json") {
  $json_title  = "report_".$row_reports_info['ReportsId'];
  $json_data   = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null);
  class_exportJSON($json_data['data'], $json_title, 0);
  $LogType = 'json';
}

//Get API Rest
if ($action == "api") {
  $json_title  = "report_".$row_reports_info['ReportsId'];
  $json_data   = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null);
  $LogType = 'json';


  $new_headers = [];
  foreach ($json_data['headers'] as $key_headers => $row_headers) {
    $new_headers[$row_headers] = getFieldAlias($row_headers);
  }


  // Función para cambiar las claves del array según los alias
  function mapHeaders($json_data, $new_headers) {
      $result = [];
      
      // Eliminar posibles espacios o saltos de línea en los alias
      $new_headers = array_map('trim', $new_headers);
      
      foreach ($json_data as $row) {
          $mapped_row = [];
          foreach ($row as $key => $value) {
              $mapped_key = isset($new_headers[$key]) ? $new_headers[$key] : $key;
              $mapped_row[$mapped_key] = $value;
          }
          $result[] = $mapped_row;
      }
      
      return $result;
  }

  // Obtener el resultado con los alias de los headers
  $mapped_data = mapHeaders($json_data['data'], $new_headers);

  header('Content-Type: application/json');
  echo json_encode($mapped_data, JSON_PRETTY_PRINT);
}

//Pipelines
if ($action == "pipeline") {

  $msg = null;

    
    $msg .= "[ID: ".$row_reports_info['ReportsId']."]";

  if ($row_reports_info['PipelineStatus']) {

    $pipeline_title = $row_reports_info['ReportsId'].'. '.$row_reports_info['Title'];

    $pipeline_data  = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null);

      $exec_timeend = microtime(true);
      $ExecTime = $exec_timeend - $exec_timestart;
      $ExecTime = number_format($ExecTime, 2);
      $msg .= "[Consulta SQL ejecutada en: (".$ExecTime."s)]";
      $exec_timestart = microtime(true);
      //exit;

    $pipeline_conn = 8;
    if ($row_reports_info['ConnSource']) {
      $pipeline_conn = $row_reports_info['ConnSource'];
    }

    $pipeline_source = class_namingConvention($pipeline_title, 'snake_case');
    if ($row_reports_info['TableSource']) {
      $pipeline_source = $row_reports_info['TableSource'];
    }

    // Actualzia inicio ejecución
    class_initExecution($row_reports_info['ReportsId']);

    if(0){
      $arr_headers  = class_ociFormatColumns($pipeline_data['headers'], $row_reports_info['Query'], $row_reports_info['ConnectionId']);
    }else{

      foreach ($pipeline_data['headers'] as $key_headers => $row_headers) {
        $arr_headers[] = array(
          'COLUMN_NAME' => $row_headers,
          'DATA_TYPE'   => 'VARCHAR',
          'DATA_LENGTH' => 255,
        );
      }

    }

    //Generate no-structurate files (csv)
    $csv_redirect = false;
    $csv_date     = false;
    $csv_prefix   = false;
    $csv_head     = false;
    $csv_path     = "data/csv/pipelines/";
    $csv = class_exportCSV($pipeline_data['headers'], $pipeline_data['data'], $pipeline_title, $csv_path, $csv_redirect, $csv_date, $csv_prefix, $csv_head);

    $exec_timeend = microtime(true);
    $ExecTime = $exec_timeend - $exec_timestart;
    $ExecTime = number_format($ExecTime, 2);
    $msg .= "[CSV guardado en: (".$ExecTime."s)]";
    $exec_timestart = microtime(true);

    //pipeline execution
    class_pipeline($arr_headers,
      $pipeline_data['data'],
      $pipeline_source,
      $pipeline_conn,
      $row_reports_info['SchemaCreate'],
      $row_reports_info['TableCreate'],
      $row_reports_info['TableTruncate'],
      $row_reports_info['TimeStamp'],
      $row_reports_info['RecordsAlert']
    );

    $exec_timeend = microtime(true);
    $ExecTime = $exec_timeend - $exec_timestart;
    $ExecTime = number_format($ExecTime, 2);
    $msg .= "[Insertado en: (".$ExecTime."s)]";
    $exec_timestart = microtime(true);

    // Actualzia ultima ejecución
    class_lastExecution($row_reports_info['ReportsId']);

    $LogType = 'Pipeline';
  }else{
    $msg .= "[Error: El reporte ".$row_reports_info['ReportsId']." no se encuentra definido como pipeline o está inactivo.]";
  }

  $msg .= "[".$pipeline_source."]";

  echo "[".date("Y-m-d H:i:s")."]".$msg."\n";
}

//show HTML report
if ($action == "report") {

  $array_headers  = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], null, null, 1);
  $array_reports  = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, $Limit);
  $array_info     = $array_reports['info'];
  $data           = $array_reports['data'];

}

// DataTables server-side processing
if ($action == "datatables") {
  // Limpiar el log de debug anterior y solo mantener errores
  $previous_debug = isset($GLOBALS['debug_info']) ? $GLOBALS['debug_info'] : [];
  $error_messages = [];
  foreach ($previous_debug as $msg) {
    if (stripos($msg, 'error') !== false || stripos($msg, '✗') !== false || stripos($msg, 'exception') !== false) {
      $error_messages[] = $msg;
    }
  }
  
  // Inicializar array de debug limpio
  $GLOBALS['debug_info'] = $error_messages;
  $GLOBALS['debug_detailed'] = false; // Desactivar debug detallado para reducir logs
  $debug_info = &$GLOBALS['debug_info'];
  $debug_info[] = "=== DataTables Request ===";
  $debug_info[] = "GET params: " . json_encode($_GET);
  
  // Capturar errores en un buffer
  $error_buffer = '';
  set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error_buffer, &$debug_info) {
    $error_buffer .= "[$errno] $errstr en $errfile:$errline\n";
    $debug_info[] = "PHP Error: [$errno] $errstr";
    return true;
  });
  
  // Limpiar cualquier salida previa
  while (ob_get_level()) {
    ob_end_clean();
  }
  
  header('Content-Type: application/json; charset=utf-8');
  
  try {
    $debug_info[] = "Iniciando procesamiento DataTables";
    
    // Inicializar variables si no existen
    if (!isset($filter_results)) {
      $filter_results = [];
      $debug_info[] = "filter_results inicializado como array vacío";
    }
    if (!isset($groupby_results)) {
      $groupby_results = [];
      $debug_info[] = "groupby_results inicializado como array vacío";
    }
    
    // Obtener parámetros de DataTables
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $search_value = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    
    $debug_info[] = "Parámetros: draw=$draw, start=$start, length=$length";
    
    // Validar que exista la información del reporte
    if (!isset($row_reports_info)) {
      throw new Exception('$row_reports_info no está definido');
    }
    if (!isset($row_reports_info['ConnectionId'])) {
      throw new Exception('ConnectionId no está definido en $row_reports_info');
    }
    if (!isset($row_reports_info['Query'])) {
      throw new Exception('Query no está definido en $row_reports_info');
    }
    
    $debug_info[] = "Reporte ID: " . (isset($row_reports_info['ReportsId']) ? $row_reports_info['ReportsId'] : 'N/A');
    $debug_info[] = "ConnectionId: " . $row_reports_info['ConnectionId'];
    
    // Obtener headers primero (sin paginación)
    $debug_info[] = "Obteniendo headers...";
    $array_headers = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], null, null, 1);
    
    if (!isset($array_headers['headers'])) {
      $debug_info[] = "ERROR: array_headers['headers'] no está definido";
      $debug_info[] = "array_headers keys: " . implode(', ', array_keys($array_headers));
      if (isset($array_headers['error'])) {
        $debug_info[] = "Error en headers: " . $array_headers['error'];
      }
      throw new Exception('No se pudieron obtener los headers del reporte: ' . (isset($array_headers['error']) ? $array_headers['error'] : 'Desconocido'));
    }
    
    if (!is_array($array_headers['headers']) || empty($array_headers['headers'])) {
      $debug_info[] = "ERROR: array_headers['headers'] está vacío o no es array";
      throw new Exception('Los headers están vacíos');
    }
    
    $debug_info[] = "Headers obtenidos: " . count($array_headers['headers']) . " columnas";
    
    // Ejecutar consulta con paginación
    $debug_info[] = "Ejecutando consulta con paginación (start=$start, length=$length)...";
    $array_reports = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null, $start, $length);
    
    if (isset($array_reports['error'])) {
      $debug_info[] = "ERROR en consulta: " . $array_reports['error'];
    }
    
    // Preparar datos para DataTables
    $data = [];
    if (isset($array_reports['data']) && is_array($array_reports['data'])) {
      $debug_info[] = "Procesando " . count($array_reports['data']) . " filas de datos";
      foreach ($array_reports['data'] as $row_index => $row) {
        $row_data = [];
        foreach ($array_headers['headers'] as $header) {
          $value = isset($row[$header]) ? $row[$header] : '';
          // Aplicar masking si está habilitado
          if (isset($row_reports_info['MaskingStatus']) && $row_reports_info['MaskingStatus'] && function_exists('maskedData')) {
            $value = maskedData($header, $value, $row_reports_info['UsersId'], $row_reports_info['ReportsId']);
          }
          // Limpiar el valor para evitar problemas con JSON
          if (is_null($value)) {
            $value = '';
          } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
          } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value);
          } else {
            $value = (string)$value;
          }
          $row_data[] = $value;
        }
        $data[] = $row_data;
      }
    } else {
      $debug_info[] = "WARNING: array_reports['data'] no está definido o no es array";
    }
    
    // Respuesta para DataTables
    $response = [
      'draw' => $draw,
      'recordsTotal' => isset($array_reports['info']['total_rows']) ? intval($array_reports['info']['total_rows']) : 0,
      'recordsFiltered' => isset($array_reports['info']['total_rows']) ? intval($array_reports['info']['total_rows']) : 0,
      'data' => $data
    ];
    
    // Si hay error, agregarlo a la respuesta
    if (isset($array_reports['error']) && !empty($array_reports['error'])) {
      $response['error'] = $array_reports['error'];
      $debug_info[] = "Error agregado a respuesta: " . $array_reports['error'];
    }
    
    $debug_info[] = "Respuesta preparada: " . count($data) . " filas, " . $response['recordsTotal'] . " total";
    
    // Validar JSON antes de enviar
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json_response === false) {
      $json_error = json_last_error_msg();
      $debug_info[] = "ERROR al codificar JSON: " . $json_error;
      throw new Exception('Error al codificar JSON: ' . $json_error);
    }
    
    $debug_info[] = "JSON generado exitosamente (" . strlen($json_response) . " bytes)";
    
    echo $json_response;
    
  } catch (Exception $e) {
    $debug_info[] = "EXCEPCIÓN: " . $e->getMessage();
    $error_response = [
      'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
      'recordsTotal' => 0,
      'recordsFiltered' => 0,
      'data' => [],
      'error' => $e->getMessage(),
      'debug' => $debug_info
    ];
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  } catch (Error $e) {
    $debug_info[] = "ERROR FATAL: " . $e->getMessage();
    $error_response = [
      'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
      'recordsTotal' => 0,
      'recordsFiltered' => 0,
      'data' => [],
      'error' => 'Error: ' . $e->getMessage(),
      'debug' => $debug_info
    ];
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }
  
  restore_error_handler();
  
  exit;
}
?>