<?php
require_once('../config.php');
require_once('../restrict.php');
require_once('../functions.php');
require_once('../conn/phoenix.php');
require_once('../models/class_recordset.php');
require_once('../models/class_querymysqli.php');
require_once('../models/class_connmysqli.php');
require_once('../models/class_reportparams.php');

header('Content-Type: application/json');

// Verificar que el usuario sea administrador
if (!isset($UsersType) || $UsersType != 1) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action !== 'update_widget') {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

$widget_id = intval($_POST['widget_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$query = trim($_POST['query'] ?? '');
$layout_grid_class = trim($_POST['layout_grid_class'] ?? 'col');
$order = intval($_POST['order'] ?? 0);
$status = intval($_POST['status'] ?? 1);
$connection_id = intval($_POST['connection_id'] ?? 0);

if ($widget_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de widget no válido']);
    exit;
}

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'El título es obligatorio']);
    exit;
}

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'La consulta SQL es obligatoria']);
    exit;
}

// Verificar que el widget existe y es de tipo widget (TypeId = 1)
$sql_check = "SELECT ReportsId, TypeId, ParentId FROM reports WHERE ReportsId = ?";
$stmt_check = $conn_phoenix->prepare($sql_check);
if ($stmt_check) {
    $stmt_check->bind_param('i', $widget_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $widget_data = $result_check->fetch_assoc();
    $stmt_check->close();
    
    if (!$widget_data) {
        echo json_encode(['success' => false, 'message' => 'Widget no encontrado']);
        exit;
    }
    
    if ($widget_data['TypeId'] != 1) {
        echo json_encode(['success' => false, 'message' => 'El ID especificado no corresponde a un widget']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error al verificar el widget: ' . $conn_phoenix->error]);
    exit;
}

// Actualizar el widget
$sql = "UPDATE reports SET 
        Title = ?, 
        Description = ?, 
        Query = ?, 
        LayoutGridClass = ?, 
        `Order` = ?, 
        Status = ?, 
        ConnectionId = ?,
        UserUpdated = ? 
        WHERE ReportsId = ? AND TypeId = 1";

$stmt = $conn_phoenix->prepare($sql);
if ($stmt) {
    $stmt->bind_param('ssssiiiii', 
        $title, 
        $description, 
        $query, 
        $layout_grid_class, 
        $order, 
        $status, 
        $connection_id,
        $UsersId,
        $widget_id
    );
    
    if ($stmt->execute()) {
        // Limpiar caché del widget
        if (class_exists('ReportParams')) {
            ReportParams::clearCache($widget_id);
            // También limpiar caché del dashboard padre si existe
            if (isset($widget_data['ParentId']) && $widget_data['ParentId'] > 0) {
                ReportParams::clearCache($widget_data['ParentId']);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Widget actualizado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . $conn_phoenix->error]);
}
