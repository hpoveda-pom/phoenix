<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../conn/phoenix.php');
require_once(__DIR__ . '/../models/class_recordset.php');
require_once(__DIR__ . '/../models/class_querymysqli.php');
require_once(__DIR__ . '/../models/class_connmysqli.php');
require_once(__DIR__ . '/../models/class_reportparams.php');
require_once(__DIR__ . '/../models/class_lastexecution.php');
require_once(__DIR__ . '/../models/class_fieldformat.php');
require_once(__DIR__ . '/../models/class_fieldalias.php');
require_once(__DIR__ . '/../models/class_masking.php');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

$widget_id = isset($input['widget_id']) ? intval($input['widget_id']) : 0;
$dashboard_id = isset($input['dashboard_id']) ? intval($input['dashboard_id']) : 0;

if ($widget_id <= 0 || $dashboard_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'IDs inválidos']);
    exit;
}

// Protección contra abuso: verificar cooldown (mínimo 2 segundos entre refrescos)
$cooldown_key = 'widget_refresh_' . $widget_id;
$last_refresh = isset($_SESSION[$cooldown_key]) ? $_SESSION[$cooldown_key] : 0;
$current_time = time();
$cooldown_seconds = 2; // Mínimo 2 segundos entre refrescos

if (($current_time - $last_refresh) < $cooldown_seconds) {
    $remaining = $cooldown_seconds - ($current_time - $last_refresh);
    echo json_encode([
        'success' => false, 
        'message' => 'Por favor espera ' . $remaining . ' segundo(s) antes de refrescar nuevamente.',
        'cooldown' => $remaining
    ]);
    exit;
}

// Actualizar timestamp del último refresh
$_SESSION[$cooldown_key] = $current_time;

try {
    // Obtener información del widget
    $widget_info = ReportParams::getReportInfo($widget_id, false);
    
    if (!$widget_info || $widget_info['ParentId'] != $dashboard_id) {
        echo json_encode(['success' => false, 'message' => 'Widget no encontrado o no pertenece al dashboard']);
        exit;
    }
    
    // Ejecutar query del widget y medir tiempo
    $filter_results = [];
    $groupby_results = [];
    $Limit = null;
    
    $execution_start = microtime(true);
    $array_reports = class_Recordset(
        $widget_info['ConnectionId'], 
        $widget_info['Query'], 
        $filter_results, 
        $groupby_results, 
        $Limit
    );
    $execution_time = microtime(true) - $execution_start;
    
    if (isset($array_reports['error']) && !empty($array_reports['error'])) {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar query: ' . $array_reports['error']]);
        exit;
    }
    
    // Formatear datos para respuesta
    $formatted_data = [
        'headers' => [],
        'rows' => [],
        'totals' => []
    ];
    
    // Headers
    foreach ($array_reports['headers'] as $header) {
        $formatted_data['headers'][] = [
            'name' => $header,
            'alias' => getFieldAlias($header),
            'format' => fieldFormat($header)
        ];
    }
    
    // Calcular totales si es necesario
    $total_values = [];
    foreach ($array_reports['headers'] as $header) {
        if (!isset($total_values[$header])) {
            $total_values[$header] = 0;
        }
    }
    
    // Filas
    foreach ($array_reports['data'] as $row) {
        $formatted_row = [];
        foreach ($array_reports['headers'] as $header) {
            $valor_dato = $row[$header];
            $field_format = fieldFormat($header, $valor_dato);
            
            if ($field_format['total']) {
                $total_values[$header] += $valor_dato;
            }
            
            $valor_dato = $field_format['value'];
            $valor_dato = maskedData($header, $valor_dato, $widget_info['UsersId'], $widget_info['ReportsId']);
            
            $formatted_row[] = [
                'value' => $valor_dato,
                'format' => $field_format
            ];
        }
        $formatted_data['rows'][] = $formatted_row;
    }
    
    // Totales
    if ($widget_info['TotalAxisX']) {
        foreach ($array_reports['headers'] as $header) {
            $field_format = fieldFormat($header);
            if ($field_format['total']) {
                $total_format = fieldFormat($header, $total_values[$header]);
                $formatted_data['totals'][] = [
                    'value' => $total_format['value'],
                    'format' => $total_format
                ];
            } else {
                $formatted_data['totals'][] = null;
            }
        }
    }
    
    // Obtener última ejecución
    $row_sync = class_getLastExecution($widget_info['LastExecution'] ?? null);
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_data,
        'lastExecution' => date('Y-m-d H:i:s'),
        'lastExecutionTimestamp' => time(),
        'executionTime' => $execution_time,
        'totalRows' => count($formatted_data['rows'])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
