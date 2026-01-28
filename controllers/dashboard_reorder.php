<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../conn/phoenix.php');
require_once(__DIR__ . '/../models/class_recordset.php');
require_once(__DIR__ . '/../models/class_querymysqli.php');
require_once(__DIR__ . '/../models/class_connmysqli.php');
require_once(__DIR__ . '/../models/class_reportparams.php');

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

if (!isset($input['widgets']) || !is_array($input['widgets'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$widgets = $input['widgets'];
$dashboard_id = isset($input['dashboard_id']) ? intval($input['dashboard_id']) : 0;

if ($dashboard_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de dashboard inválido']);
    exit;
}

// Verificar que el usuario tenga permisos (opcional, puedes agregar validación de sesión aquí)
// $UsersId = isset($_SESSION['UsersId']) ? $_SESSION['UsersId'] : 0;

try {
    // Actualizar el orden de cada widget
    $conn_phoenix = class_connMysqli(1);
    
    foreach ($widgets as $order => $widget_id) {
        $widget_id = intval($widget_id);
        $order = intval($order) + 1; // El orden empieza en 1
        
        if ($widget_id > 0) {
            $sql = "UPDATE reports SET `Order` = ? WHERE ReportsId = ? AND ParentId = ? AND TypeId = 1";
            $stmt = $conn_phoenix->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('iii', $order, $widget_id, $dashboard_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    // Limpiar cache del reporte
    if (class_exists('ReportParams')) {
        ReportParams::clearCache($dashboard_id);
    }
    
    echo json_encode(['success' => true, 'message' => 'Orden actualizado correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
