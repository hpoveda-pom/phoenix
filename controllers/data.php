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
require_once($base_dir . '/models/class_querymysqlissl.php');
require_once($base_dir . '/models/class_queryclickhouse.php');
require_once($base_dir . '/models/class_querysqlserver.php');
require_once($base_dir . '/models/class_connoci.php');
require_once($base_dir . '/models/class_connmysqli.php');
require_once($base_dir . '/models/class_connmysqlissl.php');
require_once($base_dir . '/models/class_connclickhouse.php');
require_once($base_dir . '/models/class_connsqlserver.php');
require_once($base_dir . '/models/class_filterremove.php');
require_once($base_dir . '/models/class_tipodato.php');
require_once($base_dir . '/models/class_accesslog.php');
require_once($base_dir . '/models/class_querymysqliexe.php');
require_once($base_dir . '/models/class_pipeline.php');
require_once($base_dir . '/models/class_namingconvention.php');
require_once($base_dir . '/models/class_lastexecution.php');
require_once($base_dir . '/models/class_ociformat.php');
require_once($base_dir . '/models/class_fieldalias.php');
require_once($base_dir . '/models/class_reportparams.php');

//php ini settings
require_once($base_dir . '/config.php');
require_once($base_dir . '/conn/phoenix.php'); // Necesario para que class_Connections pueda leer la tabla connections

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
$PipelinesId = isset($_GET['PipelinesId']) ? intval($_GET['PipelinesId']) : 0;
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

// Obtener información del reporte
$row_reports_info = null;

// action=pipeline con PipelinesId: cargar TODO directo desde pipelines+reports (evita problemas de join/cache)
if ($action === 'pipeline' && $PipelinesId > 0) {
  global $conn_phoenix;
  $q = "SELECT p.ConnSource, p.TableSource, p.SchemaCreate, p.TableCreate, p.TableTruncate, p.TimeStamp, p.RecordsAlert,
        r.ReportsId, r.Title, r.ConnectionId, r.Query, r.CategoryId,
        b.Title AS Category, c.FullName, d.Connector AS conn_connector, d.Schema AS conn_schema, d.Title AS conn_title
        FROM pipelines p
        INNER JOIN reports r ON r.ReportsId = p.ReportsId
        LEFT JOIN category b ON b.CategoryId = r.CategoryId
        LEFT JOIN users c ON c.UsersId = r.UsersId
        LEFT JOIN connections d ON d.ConnectionId = r.ConnectionId
        WHERE p.PipelinesId = " . intval($PipelinesId) . " AND p.Status = 1 AND r.Status = 1";
  $res = isset($conn_phoenix) ? $conn_phoenix->query($q) : null;
  if ($res && $res->num_rows > 0) {
    $row_reports_info = $res->fetch_assoc();
    $row_reports_info['PipelineStatus'] = 1;
    $ReportsId = $row_reports_info['ReportsId'];
  } else {
    $r = class_Recordset(1, $q, null, null, 1);
    if (!empty($r['data'][0])) {
      $row_reports_info = $r['data'][0];
      $row_reports_info['PipelineStatus'] = 1;
      $ReportsId = $row_reports_info['ReportsId'];
    }
  }
}

// Si no se cargó por PipelinesId, usar getReportInfo normal
if (!$row_reports_info) {
  if ($PipelinesId > 0 && $action === 'pipeline') {
    $q2 = "SELECT ReportsId FROM pipelines WHERE PipelinesId = " . intval($PipelinesId);
    $r2 = class_Recordset(1, $q2, null, null, 1);
    if (!empty($r2['data'][0]['ReportsId'])) {
      $ReportsId = intval($r2['data'][0]['ReportsId']);
    }
  }
  $row_reports_info = ReportParams::getReportInfo($ReportsId, true);
}

if (!$row_reports_info) {
  if ($action == "pipeline") {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Pipeline - Error</title>';
    echo '<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#eee;} .err{color:#f87171;} pre{background:#16213e;padding:15px;}</style></head><body><pre>';
    echo '<span class="err">[ERROR] No se encontró pipeline o reporte.</span>'."\n\n";
    echo "Usaste: Id=".htmlspecialchars($_GET['Id'] ?? '-').", PipelinesId=".htmlspecialchars($_GET['PipelinesId'] ?? '-')."\n\n";
    if ($PipelinesId > 0) {
      echo "El PipelinesId=".$PipelinesId." no existe, está inactivo, o su ReportsId no tiene reporte válido.\n";
      echo "Ejecuta en MySQL: SELECT * FROM pipelines WHERE PipelinesId=".$PipelinesId.";\n";
    }
    echo '</pre></body></html>';
    exit;
  }
  if ($action != "datatables") {
    echo '<div class="alert alert-subtle-danger" role="alert">Error, no ha seleccionado un reporte válido!</div>';
    exit;
  }
  // Para datatables, lanzar excepción que será capturada
  if ($action == "datatables") {
    throw new Exception('No se pudo obtener información del reporte');
  }
}

if (isset($row_reports_info['error']) && !empty($row_reports_info['error'])) {
  if ($action != "datatables") {
    echo '<div class="alert alert-subtle-danger" role="alert">Error al consultar el reporte: ' . htmlspecialchars($row_reports_info['error']) . '</div>';
    exit;
  }
  if ($action == "datatables") {
    throw new Exception('Error al consultar el reporte: ' . $row_reports_info['error']);
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
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Ejecución Pipeline</title>';
  echo '<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#eee;} .ok{color:#4ade80;} .err{color:#f87171;} pre{background:#16213e;padding:15px;border-radius:8px;}</style></head><body><h3>Pipeline - Reporte ID '.intval($ReportsId).'</h3><pre>';

  $msg = null;

  if (empty($ReportsId)) {
    echo '<span class="err">[ERROR] No se especificó Id de reporte. Usa: data.php?action=pipeline&Id=XXX</span>';
    echo '</pre></body></html>';
    exit;
  }

  $msg .= "[ID: ".$row_reports_info['ReportsId']."]";

  if (!isset($row_reports_info['PipelineStatus']) || !$row_reports_info['PipelineStatus']) {
    echo '<span class="err">[ERROR] No se encontró pipeline activo.</span>'."\n\n";
    echo "URL recibida: Id=".htmlspecialchars($_GET['Id'] ?? '-').", PipelinesId=".htmlspecialchars($_GET['PipelinesId'] ?? '-')."\n\n";
    echo "Prueba con: data.php?action=pipeline&PipelinesId=1\n";
    echo "O verifica en la base de datos: SELECT PipelinesId, ReportsId, Status FROM pipelines;";
    echo '</pre></body></html>';
    exit;
  }

  if ($row_reports_info['PipelineStatus']) {

    $pipeline_title = $row_reports_info['ReportsId'].'. '.$row_reports_info['Title'];

    $pipeline_data  = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null);

    if (!empty($pipeline_data['msg_error'])) {
      echo '<span class="err">[ERROR] Consulta SQL: '.htmlspecialchars($pipeline_data['msg_error']).'</span>';
      echo '</pre></body></html>';
      exit;
    }
    if (empty($pipeline_data['headers'])) {
      echo '<span class="err">[ERROR] La consulta no devolvió columnas. Revisa el reporte.</span>';
      echo '</pre></body></html>';
      exit;
    }
    if (empty($pipeline_data['data'])) {
      echo '<span class="err">[AVISO] La consulta devolvió 0 registros. No hay datos para insertar.</span>'."\n";
      echo 'Ejecuta el reporte para verificar que la consulta SQL devuelve datos.';
      echo '</pre></body></html>';
      exit;
    }

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
  }

  $msg .= "[".$pipeline_source."]";
  echo '<span class="ok">['.date("Y-m-d H:i:s").']</span> '.htmlspecialchars($msg);
  echo '</pre></body></html>';
  exit;
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
  // Capturar errores en un buffer
  $error_buffer = '';
  set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error_buffer) {
    $error_buffer .= "[$errno] $errstr en $errfile:$errline\n";
    return true;
  });
  
  // Limpiar cualquier salida previa
  while (ob_get_level()) {
    ob_end_clean();
  }
  
  header('Content-Type: application/json; charset=utf-8');
  
  try {
    // Inicializar variables si no existen
    if (!isset($filter_results)) {
      $filter_results = [];
    }
    if (!isset($groupby_results)) {
      $groupby_results = [];
    }
    if (!isset($sumby_results)) {
      $sumby_results = [];
    }
    
    // Obtener parámetros de DataTables
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $search_value = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    
    // Obtener parámetros de ordenamiento de DataTables
    $order_by = null;
    if (isset($_GET['order']) && is_array($_GET['order']) && !empty($_GET['order'])) {
        // Primero necesitamos obtener los headers para mapear el índice de columna al nombre
        // Hacer una consulta rápida para obtener los headers (sin paginación para ser más rápido)
        $temp_headers = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null, 0, 1, $sumby_results);
        $headers = isset($temp_headers['headers']) && is_array($temp_headers['headers']) ? $temp_headers['headers'] : [];
        
        if (!empty($headers)) {
            // Detectar el tipo de conector para usar el formato correcto de nombres de columnas
            $connector_type = null;
            global $conn_phoenix;
            if (isset($conn_phoenix) && $conn_phoenix instanceof mysqli && !$conn_phoenix->connect_error) {
                $stmt = $conn_phoenix->prepare("SELECT Connector FROM connections WHERE ConnectionId = ? AND Status = 1");
                if ($stmt) {
                    $stmt->bind_param('i', $row_reports_info['ConnectionId']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result && $row = $result->fetch_assoc()) {
                        $connector_type = isset($row['Connector']) ? strtolower(trim($row['Connector'])) : null;
                    }
                    $stmt->close();
                }
            }
            
            $order_parts = [];
            foreach ($_GET['order'] as $order_item) {
                if (isset($order_item['column']) && isset($order_item['dir'])) {
                    $column_index = intval($order_item['column']);
                    $direction = strtoupper($order_item['dir']) == 'DESC' ? 'DESC' : 'ASC';
                    
                    // Mapear el índice de columna al nombre del header
                    if (isset($headers[$column_index])) {
                        $column_name = $headers[$column_index];
                        
                        // Escapar el nombre de columna según el conector
                        if ($connector_type == 'sqlserver' || $connector_type == 'mssql') {
                            // SQL Server usa corchetes
                            $order_parts[] = "[" . str_replace(']', ']]', $column_name) . "] " . $direction;
                        } else {
                            // MySQL, ClickHouse, etc. usan backticks
                            $order_parts[] = "`" . str_replace('`', '``', $column_name) . "` " . $direction;
                        }
                    }
                }
            }
            if (!empty($order_parts)) {
                $order_by = implode(', ', $order_parts);
                // Debug: Log del ORDER BY construido
                if (!isset($GLOBALS['debug_filters'])) {
                    $GLOBALS['debug_filters'] = [];
                }
                $GLOBALS['debug_filters'][] = "ORDER BY construido: " . $order_by;
            }
        }
    }
    
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
    
    // Ejecutar consulta con paginación y ordenamiento
    // (especialmente importante cuando hay GroupBy o SumBy, ya que los headers cambian)
    $array_reports = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null, $start, $length, $sumby_results, $order_by);
    
    // Obtener headers de la consulta ejecutada (que ya incluye GroupBy/SumBy si aplica)
    $array_headers = $array_reports;
    
    // Si hay datos pero no headers, obtenerlos del primer registro
    if ((!isset($array_headers['headers']) || empty($array_headers['headers'])) && isset($array_headers['data']) && is_array($array_headers['data']) && !empty($array_headers['data'])) {
      $first_row = $array_headers['data'][0];
      if (is_array($first_row)) {
        $array_headers['headers'] = array_keys($first_row);
      }
    }
    
    // Si no hay headers en la respuesta, intentar obtenerlos de una consulta sin paginación
    if (!isset($array_headers['headers']) || empty($array_headers['headers'])) {
      $array_headers = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null, 0, 1, $sumby_results);
      if (isset($array_headers['data']) && is_array($array_headers['data']) && !empty($array_headers['data'])) {
        $first_row = $array_headers['data'][0];
        if (is_array($first_row)) {
          $array_headers['headers'] = array_keys($first_row);
        }
      }
    }
    
    if (!isset($array_headers['headers'])) {
      throw new Exception('No se pudieron obtener los headers del reporte: ' . (isset($array_headers['error']) ? $array_headers['error'] : 'Desconocido'));
    }
    
    if (!is_array($array_headers['headers']) || empty($array_headers['headers'])) {
      if (isset($array_headers['data']) && is_array($array_headers['data']) && !empty($array_headers['data'])) {
        $first_row = $array_headers['data'][0];
        if (is_array($first_row)) {
          $array_headers['headers'] = array_keys($first_row);
        }
      }
      if (empty($array_headers['headers'])) {
        // Construir mensaje de error más detallado
        $error_detail = 'Los headers están vacíos';
        if (isset($array_headers['error']) && !empty($array_headers['error'])) {
          $error_detail .= '. Error: ' . $array_headers['error'];
        }
        if (isset($array_reports['error']) && !empty($array_reports['error'])) {
          $error_detail .= '. Error en consulta: ' . $array_reports['error'];
        }
        throw new Exception($error_detail);
      }
    }
    
    // Preparar datos para DataTables
    $data = [];
    if (isset($array_reports['data']) && is_array($array_reports['data']) && !empty($array_reports['data'])) {
      foreach ($array_reports['data'] as $row_index => $row) {
        $row_data = [];
        foreach ($array_headers['headers'] as $header) {
          $value = isset($row[$header]) ? $row[$header] : '';
          
          // Formatear "Cantidad" con separadores de miles (formato español: punto para decimales, coma para miles)
          // Si hay GroupBy, hacer la columna "Cantidad" clickeable para drill-down
          $is_cantidad_clickable = false;
          if (strtolower($header) === 'cantidad' && is_numeric($value)) {
            $formatted_value = number_format((float)$value, 0, '.', ',');
            
            // Si hay groupby_results, hacer el valor clickeable con los datos de agrupación
            if (!empty($groupby_results) && is_array($groupby_results)) {
              // Construir objeto con los valores de los campos agrupados
              $group_values = array();
              foreach ($groupby_results as $groupby_item) {
                $group_field = isset($groupby_item['value']) ? $groupby_item['value'] : (isset($groupby_item['key']) ? $groupby_item['key'] : '');
                if (!empty($group_field) && isset($row[$group_field])) {
                  $group_values[$group_field] = $row[$group_field];
                }
              }
              
              // Si hay valores de agrupación, hacer el valor clickeable
              if (!empty($group_values)) {
                // Codificar los valores de agrupación en JSON para el atributo data
                $group_values_json = json_encode($group_values, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);
                $value = '<span class="cantidad-clickable" style="cursor: pointer; color: #0d6efd; text-decoration: underline;" data-group-values=\'' . htmlspecialchars($group_values_json, ENT_QUOTES, 'UTF-8') . '\'>' . htmlspecialchars($formatted_value) . '</span>';
                $is_cantidad_clickable = true;
              } else {
                $value = $formatted_value;
              }
            } else {
              $value = $formatted_value;
            }
          }
          
          // Manejar campos Suma (*) o Suma_*: si es NULL, 0 (cuando todos eran NULL), o no numérico, mostrar N/A
          $header_lower = strtolower($header);
          if (strpos($header_lower, 'suma (') === 0 || strpos($header_lower, 'suma_') === 0) {
            // Si el valor es NULL, vacío, o no numérico, mostrar N/A
            if (is_null($value) || $value === '' || $value === false || !is_numeric($value)) {
              $value = 'N/A';
            } else {
              $float_value = (float)$value;
              // Si el valor es 0, podría ser que todos los valores originales eran NULL
              // Pero por ahora mostramos 0 si es numérico
              // Formatear con separador de miles
              $value = number_format($float_value, 2, '.', ',');
            }
          }
          
          // Aplicar masking si está habilitado (solo si no es HTML clickeable)
          if (!$is_cantidad_clickable && isset($row_reports_info['MaskingStatus']) && $row_reports_info['MaskingStatus'] && function_exists('maskedData')) {
            $value = maskedData($header, $value, $row_reports_info['UsersId'], $row_reports_info['ReportsId']);
          }
          
          // Limpiar el valor para evitar problemas con JSON (solo si no es HTML)
          if (!$is_cantidad_clickable) {
            if (is_null($value)) {
              $value = '';
            } elseif (is_bool($value)) {
              $value = $value ? '1' : '0';
            } elseif (is_array($value) || is_object($value)) {
              $value = json_encode($value);
            } else {
              $value = (string)$value;
            }
          }
          $row_data[] = $value;
        }
        $data[] = $row_data;
      }
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
    }
    
    // Agregar información de debug si está disponible (solo en desarrollo)
    if (isset($GLOBALS['debug_filters']) && !empty($GLOBALS['debug_filters'])) {
      $response['debug'] = $GLOBALS['debug_filters'];
    }
    
    // Validar JSON antes de enviar
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json_response === false) {
      $json_error = json_last_error_msg();
      throw new Exception('Error al codificar JSON: ' . $json_error);
    }
    
    echo $json_response;
    
  } catch (Exception $e) {
    $error_response = [
      'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
      'recordsTotal' => 0,
      'recordsFiltered' => 0,
      'data' => [],
      'error' => $e->getMessage()
    ];
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  } catch (Error $e) {
    $error_response = [
      'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
      'recordsTotal' => 0,
      'recordsFiltered' => 0,
      'data' => [],
      'error' => 'Error: ' . $e->getMessage()
    ];
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }
  
  restore_error_handler();
  
  exit;
}
?>