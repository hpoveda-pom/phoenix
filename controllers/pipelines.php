<?php
$base_dir = dirname(__DIR__);
if (!isset($conn_phoenix)) {
  require_once($base_dir . '/config.php');
  require_once($base_dir . '/conn/phoenix.php');
}
require_once($base_dir . '/models/class_recordset.php');
require_once($base_dir . '/models/class_connections.php');
require_once($base_dir . '/models/class_querymysqli.php');
require_once($base_dir . '/models/class_connmysqli.php');
require_once($base_dir . '/models/class_reportparams.php');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$pipeline_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';

// Mensajes flash (después de redirect)
if (isset($_SESSION['pipeline_flash'])) {
    $message = $_SESSION['pipeline_flash']['msg'];
    $message_type = $_SESSION['pipeline_flash']['type'] ?? 'info';
    unset($_SESSION['pipeline_flash']);
}

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_post = $_POST['action'] ?? '';
    
    if ($action_post === 'save_pipeline') {
        try {
        $reports_id = intval($_POST['reports_id'] ?? 0);
        $conn_source = intval($_POST['conn_source'] ?? 0);
        $table_source = trim($_POST['table_source'] ?? '');
        $schema_source = trim($_POST['schema_source'] ?? '');
        $schema_create = intval($_POST['schema_create'] ?? 0);
        $table_create = intval($_POST['table_create'] ?? 1);
        $table_truncate = intval($_POST['table_truncate'] ?? 1);
        $time_stamp = intval($_POST['time_stamp'] ?? 0);
        $records_alert = isset($_POST['records_alert']) && $_POST['records_alert'] !== '' ? intval($_POST['records_alert']) : 0;
        $description = trim($_POST['description'] ?? '');
        $status = intval($_POST['status'] ?? 1);
        $pipeline_id_post = intval($_POST['pipeline_id'] ?? 0);
        
        if (!isset($conn_phoenix) || !($conn_phoenix instanceof mysqli)) {
            $message = 'Error: No hay conexión a la base de datos Phoenix. Verifica la configuración.';
            $message_type = 'danger';
            $action = $pipeline_id_post > 0 ? 'edit' : 'add';
        } elseif ($reports_id <= 0 || $conn_source <= 0) {
            $message = 'El reporte y la conexión destino son obligatorios';
            $message_type = 'danger';
            $action = $pipeline_id_post > 0 ? 'edit' : 'add';
        } else {
            if ($pipeline_id_post > 0) {
                // Actualizar pipeline existente
                $records_alert_upd = $records_alert ?: 0;
                $sql = "UPDATE pipelines SET ReportsId = ?, ConnSource = ?, SchemaSource = NULLIF(?, ''), TableSource = NULLIF(?, ''), SchemaCreate = ?, TableCreate = ?, TableTruncate = ?, TimeStamp = ?, RecordsAlert = NULLIF(?, 0), Description = NULLIF(?, ''), Status = ? WHERE PipelinesId = ?";
                $stmt = $conn_phoenix->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('iissiiiiisii', $reports_id, $conn_source, $schema_source, $table_source, $schema_create, $table_create, $table_truncate, $time_stamp, $records_alert_upd, $description, $status, $pipeline_id_post);
                    if ($stmt->execute()) {
                        ReportParams::clearCache($reports_id);
                        $_SESSION['pipeline_flash'] = ['msg' => 'Pipeline actualizado exitosamente', 'type' => 'success'];
                        while (ob_get_level()) ob_end_clean();
                        header('Location: pipelines.php');
                        exit;
                    } else {
                        $message = 'Error al actualizar: ' . $stmt->error;
                        $message_type = 'danger';
                        $action = 'edit';
                        $pipeline_id = $pipeline_id_post;
                    }
                    $stmt->close();
                } else {
                    $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                    $action = 'edit';
                    $pipeline_id = $pipeline_id_post;
                }
            } else {
                // Crear nuevo pipeline
                $sql = "INSERT INTO pipelines (ReportsId, ConnSource, SchemaSource, TableSource, SchemaCreate, TableCreate, TableTruncate, TimeStamp, RecordsAlert, Description, Status) VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?, ?, ?, NULLIF(?, 0), NULLIF(?, ''), ?)";
                $stmt = $conn_phoenix->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('iissiiiiisi', $reports_id, $conn_source, $schema_source, $table_source, $schema_create, $table_create, $table_truncate, $time_stamp, $records_alert, $description, $status);
                    if ($stmt->execute()) {
                        $new_pipeline_id = $conn_phoenix->insert_id;
                        // Vincular reporte con este pipeline
                        $conn_phoenix->query("UPDATE reports SET PipelinesId = " . intval($new_pipeline_id) . " WHERE ReportsId = " . intval($reports_id));
                        ReportParams::clearCache($reports_id);
                        $_SESSION['pipeline_flash'] = [
                            'msg' => 'Pipeline creado (ID ' . $new_pipeline_id . '). Haz clic en "Ejecutar" para cargar los datos al destino.',
                            'type' => 'success'
                        ];
                        while (ob_get_level()) ob_end_clean();
                        header('Location: pipelines.php');
                        exit;
                    } else {
                        $message = 'Error al crear: ' . $stmt->error;
                        $message_type = 'danger';
                        $action = 'add';
                    }
                    $stmt->close();
                } else {
                    $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                    $action = 'add';
                }
            }
        }
        } catch (Throwable $e) {
            $message = 'Error al guardar: ' . htmlspecialchars($e->getMessage()) . ' (línea ' . $e->getLine() . ')';
            $message_type = 'danger';
            $action = 'add';
            $pipeline_id_post = intval($_POST['pipeline_id'] ?? 0);
            if ($pipeline_id_post > 0) {
                $action = 'edit';
                $pipeline_id = $pipeline_id_post;
            }
        }
    }
}

// Obtener lista de pipelines con información del reporte y conexión destino
$query_pipelines = "
    SELECT 
        a.PipelinesId,
        a.ReportsId,
        a.Description,
        a.ConnSource,
        a.TableSource,
        a.SchemaCreate,
        a.TableCreate,
        a.TableTruncate,
        a.TimeStamp,
        a.RecordsAlert,
        a.LastExecution,
        a.Status,
        b.Title AS ReportTitle,
        b.ConnectionId AS ReportConnectionId,
        c.Title AS ConnDestTitle,
        d.Title AS CategoryTitle
    FROM pipelines a
    INNER JOIN reports b ON b.ReportsId = a.ReportsId
    LEFT JOIN connections c ON c.ConnectionId = a.ConnSource
    LEFT JOIN category d ON d.CategoryId = b.CategoryId
    WHERE a.Status = 1 AND b.Status = 1
    ORDER BY b.Title ASC
";
$list_result = class_Recordset(1, $query_pipelines, null, null, null);
$pipelines_list = $list_result['data'] ?? [];

// Para formulario add/edit: reportes (solo TypeId=1 con Query y ConnectionId) y conexiones
$reports_for_pipeline = [];
$connections_list = [];
$pipeline_data = null;

if ($action === 'add' || $action === 'edit') {
    $qry_reports = "SELECT a.ReportsId, a.Title, a.ConnectionId, a.Query, b.Title AS ConnSourceTitle 
        FROM reports a 
        LEFT JOIN connections b ON b.ConnectionId = a.ConnectionId 
        WHERE a.TypeId = 1 AND a.Status = 1 AND a.ConnectionId > 0 AND (a.Query IS NOT NULL AND a.Query != '')
        ORDER BY a.Title ASC";
    $lst_reports = class_Recordset(1, $qry_reports, null, null, null);
    $reports_for_pipeline = $lst_reports['data'] ?? [];

    $qry_conn = "SELECT ConnectionId, Title, Connector FROM connections WHERE Status = 1 ORDER BY Title ASC";
    $lst_conn = class_Recordset(1, $qry_conn, null, null, null);
    $connections_list = $lst_conn['data'] ?? [];

    if ($action === 'edit' && $pipeline_id > 0) {
        $qry_one = "SELECT * FROM pipelines WHERE PipelinesId = " . intval($pipeline_id);
        $lst_one = class_Recordset(1, $qry_one, null, null, 1);
        if (!empty($lst_one['data'][0])) {
            $pipeline_data = $lst_one['data'][0];
        } else {
            $action = 'list';
            $message = 'Pipeline no encontrado';
            $message_type = 'warning';
        }
    }
}
