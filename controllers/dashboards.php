<?php
require_once('models/class_recordset.php');
require_once('models/class_connections.php');
require_once('models/class_querymysqli.php');
require_once('models/class_connmysqli.php');
require_once('models/class_reportparams.php');

// Variables globales
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$dashboard_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$widget_id = isset($_GET['widget_id']) ? intval($_GET['widget_id']) : 0;

$message = '';
$message_type = '';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_post = $_POST['action'] ?? '';
    
    // Crear o actualizar Dashboard
    if ($action_post === 'save_dashboard') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $order = intval($_POST['order'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        $dashboard_id_post = intval($_POST['dashboard_id'] ?? 0);
        
        if (empty($title)) {
            $message = 'El título es obligatorio';
            $message_type = 'danger';
        } else {
            if ($dashboard_id_post > 0) {
                // Actualizar dashboard
                $sql = "UPDATE reports SET Title = ?, Description = ?, CategoryId = ?, `Order` = ?, Status = ?, UserUpdated = ? WHERE ReportsId = ? AND TypeId = 2";
                $stmt = $conn_phoenix->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssiiiii', $title, $description, $category_id, $order, $status, $UsersId, $dashboard_id_post);
                    if ($stmt->execute()) {
                        $message = 'Dashboard actualizado exitosamente';
                        $message_type = 'success';
                        $action = 'list';
                        $dashboard_id = $dashboard_id_post;
                    } else {
                        $message = 'Error al actualizar: ' . $stmt->error;
                        $message_type = 'danger';
                    }
                    $stmt->close();
                } else {
                    $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                }
            } else {
                // Crear nuevo dashboard
                $sql = "INSERT INTO reports (Title, Description, CategoryId, `Order`, TypeId, UsersId, ConnectionId, Query, Status, ParentId) VALUES (?, ?, ?, ?, 2, ?, 0, '', ?, 0)";
                $stmt = $conn_phoenix->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssiiii', $title, $description, $category_id, $order, $UsersId, $status);
                    if ($stmt->execute()) {
                        $dashboard_id = $conn_phoenix->insert_id;
                        $message = 'Dashboard creado exitosamente';
                        $message_type = 'success';
                        $action = 'edit';
                    } else {
                        $message = 'Error al crear: ' . $stmt->error;
                        $message_type = 'danger';
                    }
                    $stmt->close();
                } else {
                    $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                }
            }
        }
    }
    
    // Crear o actualizar Widget
    elseif ($action_post === 'save_widget') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parent_id = intval($_POST['parent_id'] ?? 0);
        $connection_id = intval($_POST['connection_id'] ?? 0);
        $query = trim($_POST['query'] ?? '');
        $order = intval($_POST['order'] ?? 0);
        $layout_grid_class = trim($_POST['layout_grid_class'] ?? 'col');
        $total_axis_x = intval($_POST['total_axis_x'] ?? 0);
        $total_axis_y = intval($_POST['total_axis_y'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        $widget_id_post = intval($_POST['widget_id'] ?? 0);
        
        if (empty($title) || $parent_id == 0) {
            $message = 'El título y el dashboard padre son obligatorios';
            $message_type = 'danger';
        } else {
            if ($widget_id_post > 0) {
                // Actualizar widget
                $sql = "UPDATE reports SET Title = ?, Description = ?, ConnectionId = ?, Query = ?, `Order` = ?, LayoutGridClass = ?, TotalAxisX = ?, TotalAxisY = ?, Status = ?, UserUpdated = ? WHERE ReportsId = ? AND TypeId = 1 AND ParentId = ?";
                $stmt = $conn_phoenix->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssisissiiiii', $title, $description, $connection_id, $query, $order, $layout_grid_class, $total_axis_x, $total_axis_y, $status, $UsersId, $widget_id_post, $parent_id);
                    if ($stmt->execute()) {
                        // Limpiar el caché del reporte si se actualizó el query
                        if (!empty($query)) {
                            ReportParams::clearCache($widget_id_post);
                        }
                        $message = 'Widget actualizado exitosamente';
                        $message_type = 'success';
                        $action = 'widgets';
                        $dashboard_id = $parent_id;
                    } else {
                        $message = 'Error al actualizar: ' . $stmt->error;
                        $message_type = 'danger';
                    }
                    $stmt->close();
                } else {
                    $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                }
            } else {
                // Crear nuevo widget
                $sql = "INSERT INTO reports (Title, Description, CategoryId, `Order`, TypeId, UsersId, ConnectionId, Query, Status, ParentId, LayoutGridClass, TotalAxisX, TotalAxisY) VALUES (?, ?, 0, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn_phoenix->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssiiissiiii', $title, $description, $order, $UsersId, $connection_id, $query, $status, $parent_id, $layout_grid_class, $total_axis_x, $total_axis_y);
                    if ($stmt->execute()) {
                        $message = 'Widget creado exitosamente';
                        $message_type = 'success';
                        $action = 'widgets';
                        $dashboard_id = $parent_id;
                    } else {
                        $message = 'Error al crear: ' . $stmt->error;
                        $message_type = 'danger';
                    }
                    $stmt->close();
                } else {
                    $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                }
            }
        }
    }
    
    // Eliminar Dashboard
    elseif ($action_post === 'delete_dashboard') {
        $dashboard_id_delete = intval($_POST['dashboard_id'] ?? 0);
        
        if ($dashboard_id_delete > 0) {
            // Primero eliminar todos los widgets hijos
            $sql_delete_widgets = "DELETE FROM reports WHERE ParentId = ? AND TypeId = 1";
            $stmt_widgets = $conn_phoenix->prepare($sql_delete_widgets);
            if ($stmt_widgets) {
                $stmt_widgets->bind_param('i', $dashboard_id_delete);
                $stmt_widgets->execute();
                $stmt_widgets->close();
            }
            
            // Luego eliminar el dashboard
            $sql = "DELETE FROM reports WHERE ReportsId = ? AND TypeId = 2";
            $stmt = $conn_phoenix->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $dashboard_id_delete);
                if ($stmt->execute()) {
                    $message = 'Dashboard eliminado exitosamente';
                    $message_type = 'success';
                    $action = 'list';
                } else {
                    $message = 'Error al eliminar: ' . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
    }
    
    // Eliminar Widget
    elseif ($action_post === 'delete_widget') {
        $widget_id_delete = intval($_POST['widget_id'] ?? 0);
        $parent_id_delete = intval($_POST['parent_id'] ?? 0);
        
        if ($widget_id_delete > 0) {
            $sql = "DELETE FROM reports WHERE ReportsId = ? AND TypeId = 1 AND ParentId = ?";
            $stmt = $conn_phoenix->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ii', $widget_id_delete, $parent_id_delete);
                if ($stmt->execute()) {
                    $message = 'Widget eliminado exitosamente';
                    $message_type = 'success';
                    $action = 'widgets';
                    $dashboard_id = $parent_id_delete;
                } else {
                    $message = 'Error al eliminar: ' . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
    }
}

// Obtener datos del dashboard si estamos editando
$dashboard_data = null;
if ($dashboard_id > 0 && ($action === 'edit' || $action === 'widgets')) {
    $query_dashboard = "SELECT * FROM reports WHERE ReportsId = ? AND TypeId = 2";
    $stmt = $conn_phoenix->prepare($query_dashboard);
    if ($stmt) {
        $stmt->bind_param('i', $dashboard_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $dashboard_data = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Obtener datos del widget si estamos editando
$widget_data = null;
if ($widget_id > 0 && $action === 'edit_widget') {
    $query_widget = "SELECT * FROM reports WHERE ReportsId = ? AND TypeId = 1";
    $stmt = $conn_phoenix->prepare($query_widget);
    if ($stmt) {
        $stmt->bind_param('i', $widget_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $widget_data = $result->fetch_assoc();
            $dashboard_id = $widget_data['ParentId'];
        }
        $stmt->close();
    }
}

// Obtener listas para los formularios
// Categorías
$query_categories = "SELECT CategoryId, Title FROM category WHERE Status = 1 AND ParentId IS NULL ORDER BY Title ASC";
$categories_result = class_Recordset(1, $query_categories, null, null, null);
$categories = $categories_result['data'] ?? [];

// Conexiones
$query_connections = "SELECT ConnectionId, Title FROM connections WHERE Status = 1 ORDER BY Title ASC";
$connections_result = class_Recordset(1, $query_connections, null, null, null);
$connections = $connections_result['data'] ?? [];

// Dashboards (para seleccionar padre)
$query_dashboards = "SELECT ReportsId, Title FROM reports WHERE Status = 1 AND TypeId = 2 ORDER BY Title ASC";
$dashboards_result = class_Recordset(1, $query_dashboards, null, null, null);
$dashboards = $dashboards_result['data'] ?? [];

// Widgets del dashboard
$widgets = [];
if ($dashboard_id > 0) {
    $query_widgets = "SELECT * FROM reports WHERE ParentId = ? AND TypeId = 1 ORDER BY `Order` ASC";
    $stmt = $conn_phoenix->prepare($query_widgets);
    if ($stmt) {
        $stmt->bind_param('i', $dashboard_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $widgets[] = $row;
        }
        $stmt->close();
    }
}

// Layout Grid Classes
$layout_grid_classes = [
    'Predeterminado' => 'col',
    '25%' => 'col-md-3',
    '33%' => 'col-md-4',
    '50%' => 'col-md-6',
    '66%' => 'col-md-8',
    '75%' => 'col-md-9',
    '100%' => 'col-md-12',
    'Auto' => 'col-auto',
];

// Estados
$status_options = [
    'Activo' => 1,
    'Inactivo' => 0,
    'Mantenimiento' => 2,
];

// Variables disponibles para las vistas
// $action, $dashboard_id, $widget_id, $message, $message_type
// $dashboard_data, $widget_data
// $categories, $connections, $dashboards, $widgets
// $layout_grid_classes, $status_options
?>
