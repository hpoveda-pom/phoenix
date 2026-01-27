<?php
// Endpoint AJAX para obtener estadísticas de conexión (tablas, views, SPs)
if (isset($_GET['action']) && $_GET['action'] === 'get_connection_stats' && isset($_GET['id'])) {
    require_once('config.php');
    require_once('conn/phoenix.php');
    require_once('models/class_connections.php');
    
    $test_id = intval($_GET['id']);
    
    ob_clean();
    
    $stats = [
        'tables' => 0,
        'views' => 0,
        'sps' => 0,
        'error' => null
    ];
    
    try {
        // Obtener información de la conexión
        global $conn_phoenix;
        $result = $conn_phoenix->query("SELECT Connector, Hostname, Port, Username, Password, ServiceName, `Schema` FROM connections WHERE ConnectionId = $test_id");
        if ($result && $row = $result->fetch_assoc()) {
            $connector = strtolower(trim($row['Connector']));
            $conn = class_Connections($test_id);
            
            if ($conn) {
                if ($connector == 'mysqli' || $connector == 'mysqlissl' || $connector == 'mysql' || $connector == 'mariadb') {
                    // MySQL/MariaDB
                    $database = !empty($row['Schema']) ? $row['Schema'] : (!empty($row['ServiceName']) ? $row['ServiceName'] : '');
                    if ($database) {
                        // Tablas
                        $tables_result = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . $conn->real_escape_string($database) . "' AND table_type = 'BASE TABLE'");
                        if ($tables_result) {
                            $tables_row = $tables_result->fetch_assoc();
                            $stats['tables'] = intval($tables_row['count']);
                        }
                        
                        // Views
                        $views_result = $conn->query("SELECT COUNT(*) as count FROM information_schema.views WHERE table_schema = '" . $conn->real_escape_string($database) . "'");
                        if ($views_result) {
                            $views_row = $views_result->fetch_assoc();
                            $stats['views'] = intval($views_row['count']);
                        }
                        
                        // Stored Procedures
                        $sps_result = $conn->query("SELECT COUNT(*) as count FROM information_schema.routines WHERE routine_schema = '" . $conn->real_escape_string($database) . "' AND routine_type = 'PROCEDURE'");
                        if ($sps_result) {
                            $sps_row = $sps_result->fetch_assoc();
                            $stats['sps'] = intval($sps_row['count']);
                        }
                    }
                } elseif ($connector == 'clickhouse') {
                    // ClickHouse
                    $database = !empty($row['Schema']) ? $row['Schema'] : (!empty($row['ServiceName']) ? $row['ServiceName'] : 'default');
                    
                    require_once('models/class_connclickhouse.php');
                    
                    // Tablas
                    $tables_query = "SELECT count() as count FROM system.tables WHERE database = '" . addslashes($database) . "' AND engine NOT LIKE '%View%'";
                    $tables_result = class_clickhouse_query($conn, $tables_query, 'JSON', $error_info);
                    if ($tables_result !== false) {
                        // ClickHouse puede retornar directamente un array o con estructura {data: [...]}
                        if (isset($tables_result['data']) && !empty($tables_result['data'])) {
                            $stats['tables'] = intval($tables_result['data'][0]['count'] ?? 0);
                        } elseif (is_array($tables_result) && !empty($tables_result) && isset($tables_result[0]['count'])) {
                            $stats['tables'] = intval($tables_result[0]['count']);
                        }
                    }
                    
                    // Views (ClickHouse tiene views)
                    $views_query = "SELECT count() as count FROM system.tables WHERE database = '" . addslashes($database) . "' AND engine LIKE '%View%'";
                    $views_result = class_clickhouse_query($conn, $views_query, 'JSON', $error_info);
                    if ($views_result !== false) {
                        // ClickHouse puede retornar directamente un array o con estructura {data: [...]}
                        if (isset($views_result['data']) && !empty($views_result['data'])) {
                            $stats['views'] = intval($views_result['data'][0]['count'] ?? 0);
                        } elseif (is_array($views_result) && !empty($views_result) && isset($views_result[0]['count'])) {
                            $stats['views'] = intval($views_result[0]['count']);
                        }
                    }
                    
                    // ClickHouse no tiene stored procedures tradicionales
                    $stats['sps'] = 0;
                } elseif ($connector == 'sqlserver' || $connector == 'mssql') {
                    // SQL Server
                    $database = !empty($row['Schema']) ? $row['Schema'] : (!empty($row['ServiceName']) ? $row['ServiceName'] : '');
                    if ($database && $conn instanceof PDO) {
                        // Tablas
                        $tables_result = $conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_CATALOG = '" . str_replace("'", "''", $database) . "'");
                        if ($tables_result) {
                            $tables_row = $tables_result->fetch(PDO::FETCH_ASSOC);
                            $stats['tables'] = intval($tables_row['count'] ?? 0);
                        }
                        
                        // Views
                        $views_result = $conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_CATALOG = '" . str_replace("'", "''", $database) . "'");
                        if ($views_result) {
                            $views_row = $views_result->fetch(PDO::FETCH_ASSOC);
                            $stats['views'] = intval($views_row['count'] ?? 0);
                        }
                        
                        // Stored Procedures
                        $sps_result = $conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'PROCEDURE' AND ROUTINE_CATALOG = '" . str_replace("'", "''", $database) . "'");
                        if ($sps_result) {
                            $sps_row = $sps_result->fetch(PDO::FETCH_ASSOC);
                            $stats['sps'] = intval($sps_row['count'] ?? 0);
                        }
                    }
                }
            } else {
                $stats['error'] = 'No se pudo establecer la conexión';
            }
        }
    } catch (Exception $e) {
        $stats['error'] = $e->getMessage();
    }
    
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Endpoint AJAX para probar conexión - DEBE ir ANTES de header.php para evitar output
if (isset($_GET['action']) && $_GET['action'] === 'test_connection' && isset($_GET['id'])) {
    // Iniciar buffer de salida para capturar cualquier output no deseado
    ob_start();
    
    // Incluir solo lo necesario para la conexión (sin header.php que tiene HTML)
    // Necesitamos config.php para $row_config (igual que header.php)
    require_once('config.php');
    require_once('conn/phoenix.php');
    require_once('models/class_connections.php');
    
    $test_id = intval($_GET['id']);
    
    // Limpiar cualquier output previo que haya generado los includes
    ob_clean();
    
    // Inicializar array de errores si no existe
    if (!isset($GLOBALS['phoenix_errors_warnings'])) {
        $GLOBALS['phoenix_errors_warnings'] = [];
    }
    
    // Capturar errores en un buffer
    $error_message = '';
    $original_error_handler = set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error_message) {
        $error_message .= "[$errno] $errstr en $errfile:$errline\n";
        return true;
    });
    
    // Obtener información de la conexión primero
    global $conn_phoenix;
    $conn_info = null;
    if (isset($conn_phoenix) && $conn_phoenix instanceof mysqli) {
        $result = $conn_phoenix->query("SELECT Connector, Hostname, Port, Username, Password, `Schema`, ServiceName FROM connections WHERE ConnectionId = $test_id");
        if ($result && $row = $result->fetch_assoc()) {
            $conn_info = $row;
        }
    }
    
    // Medir tiempo de respuesta (ping)
    $ping_start = microtime(true);
    
    // Intentar conectar usando class_Connections
    $conn = class_Connections($test_id);
    
    // Verificar la conexión haciendo una prueba real
    $success = false;
    $ping_time = 0;
    if ($conn !== null && $conn !== false) {
        // Hacer una prueba real según el tipo de conexión
        if (is_object($conn)) {
            if (isset($conn->type) && $conn->type === 'clickhouse') {
                // Para ClickHouse, probar con una consulta simple
                require_once('models/class_connclickhouse.php');
                $test_result = class_clickhouse_query($conn, "SELECT 1", 'JSON', $test_error);
                $success = ($test_result !== false);
                if (!$success && $test_error) {
                    $error_message = "ClickHouse: " . $test_error;
                } else {
                    // Calcular ping después de la consulta exitosa
                    $ping_time = (microtime(true) - $ping_start) * 1000; // Convertir a milisegundos
                }
            } elseif ($conn instanceof mysqli) {
                // Para MySQL, probar con una consulta simple
                $test_result = $conn->query("SELECT 1");
                $success = ($test_result !== false);
                if (!$success) {
                    $error_message = "MySQL: " . $conn->error;
                } else {
                    // Calcular ping después de la consulta exitosa
                    $ping_time = (microtime(true) - $ping_start) * 1000; // Convertir a milisegundos
                }
            } elseif ($conn instanceof PDO) {
                // Para PDO (SQL Server, etc.), probar con una consulta simple
                try {
                    $test_result = $conn->query("SELECT 1");
                    $success = ($test_result !== false);
                    if (!$success) {
                        $error_info = $conn->errorInfo();
                        $error_message = "SQL Server: " . ($error_info[2] ?? 'Error desconocido');
                    } else {
                        // Calcular ping después de la consulta exitosa
                        $ping_time = (microtime(true) - $ping_start) * 1000; // Convertir a milisegundos
                    }
                } catch (PDOException $e) {
                    $success = false;
                    $error_message = "SQL Server: " . $e->getMessage();
                }
            } else {
                // Otro tipo de conexión, asumir que funciona si no es null
                $success = true;
                $ping_time = (microtime(true) - $ping_start) * 1000;
            }
        } else {
            // Si es un recurso u otro tipo, asumir que funciona
            $success = true;
            $ping_time = (microtime(true) - $ping_start) * 1000;
        }
    }
    
    // Si la conexión falló pero se intentó con conexión directa, también medir ping
    if (!$success && isset($test_conn)) {
        $ping_time = (microtime(true) - $ping_start) * 1000;
    }
    
    // Si class_Connections falló pero tenemos info de la conexión, intentar conexión directa
    if (!$success && $conn_info) {
        $hostname = trim($conn_info['Hostname'] ?: 'localhost');
        $port = trim($conn_info['Port'] ?: '');
        $username = trim($conn_info['Username'] ?: '');
        $password = $conn_info['Password'] ?: '';
        $database = trim($conn_info['Schema'] ?: ($conn_info['ServiceName'] ?: ''));
        $connector = strtolower(trim($conn_info['Connector']));
        
        if ($connector == 'mysqli' || $connector == 'mysqlissl' || $connector == 'mysql' || $connector == 'mariadb') {
            require_once('models/class_connmysqli.php');
            if (empty($port)) $port = '3306';
            
            // Si no hay database, intentar conectar sin especificar base de datos (solo para probar)
            if (empty($database)) {
                $test_conn = @new mysqli($hostname, $username, $password, '', intval($port));
            } else {
                $test_conn = class_connMysqli($hostname, $port, $username, $password, $database);
            }
            
            if ($test_conn && !$test_conn->connect_error) {
                // Probar con una consulta simple
                $test_result = $test_conn->query("SELECT 1");
                if ($test_result !== false) {
                    $success = true;
                    $error_message = ''; // Limpiar el error si la conexión funciona
                    $ping_time = (microtime(true) - $ping_start) * 1000; // Calcular ping
                } else {
                    $error_message = "MySQL: " . $test_conn->error;
                }
                @$test_conn->close();
            } elseif ($test_conn && $test_conn->connect_error) {
                $error_message = "MySQL Connect Error: " . $test_conn->connect_error;
            }
        } elseif ($connector == 'sqlserver' || $connector == 'mssql') {
            require_once('models/class_connsqlserver.php');
            if (empty($port)) $port = '1433';
            
            if (!empty($database)) {
                $test_conn = class_connSqlServer($hostname, $port, $username, $password, $database);
                if ($test_conn && $test_conn instanceof PDO) {
                    try {
                        $test_result = $test_conn->query("SELECT 1");
                        if ($test_result !== false) {
                            $success = true;
                            $error_message = '';
                            $ping_time = (microtime(true) - $ping_start) * 1000; // Calcular ping
                        }
                    } catch (PDOException $e) {
                        $error_message = "SQL Server: " . $e->getMessage();
                    }
                } else {
                    $error_message = "SQL Server: No se pudo establecer la conexión";
                }
            } else {
                $error_message = "SQL Server: Base de datos no especificada";
            }
        }
    }
    
    // Calcular ping final si aún no se calculó y la conexión fue exitosa
    if ($success && $ping_time == 0) {
        $ping_time = (microtime(true) - $ping_start) * 1000;
    }
    
    // Si aún falló, obtener el mensaje de error más detallado
    if (!$success && empty($error_message)) {
        // Verificar si hay mensajes de error en GLOBALS
        if (isset($GLOBALS['phoenix_errors_warnings']) && !empty($GLOBALS['phoenix_errors_warnings'])) {
            $last_error = end($GLOBALS['phoenix_errors_warnings']);
            $error_message = isset($last_error['message']) ? $last_error['message'] : 'Error desconocido';
        } else if ($conn_info) {
            $error_message = "No se pudo establecer conexión. Verifique: Hostname: " . ($conn_info['Hostname'] ?: 'vacío') . 
                            ", Puerto: " . ($conn_info['Port'] ?: 'vacío') . 
                            ", Usuario: " . ($conn_info['Username'] ?: 'vacío') . 
                            ", Schema: " . ($conn_info['Schema'] ?: 'vacío') .
                            ", Connector: " . ($conn_info['Connector'] ?: 'N/A');
        } else {
            $error_message = "Error: No se pudo obtener información de la conexión.";
        }
    }
    
    // Restaurar error handler
    if ($original_error_handler) {
        restore_error_handler();
    }
    
    // Limpiar cualquier output adicional
    ob_clean();
    
    // Enviar JSON limpio
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success, 
        'message' => $success ? 'Conexión exitosa' : $error_message,
        'ping' => $success ? round($ping_time, 2) : null // Ping en milisegundos
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Terminar buffer y salir
    ob_end_flush();
    exit;
}

// Endpoint AJAX para obtener lista de tablas o views
if (isset($_GET['action']) && $_GET['action'] === 'get_tables_views' && isset($_GET['id']) && isset($_GET['type'])) {
    require_once('config.php');
    require_once('conn/phoenix.php');
    require_once('models/class_connections.php');
    
    $connection_id = intval($_GET['id']);
    $type = $_GET['type']; // 'tables' o 'views'
    
    ob_clean();
    
    $items = [];
    $error = null;
    
    try {
        global $conn_phoenix;
        $result = $conn_phoenix->query("SELECT Connector, Hostname, Port, Username, Password, ServiceName, `Schema` FROM connections WHERE ConnectionId = $connection_id");
        if ($result && $row = $result->fetch_assoc()) {
            $connector = strtolower(trim($row['Connector']));
            $conn = class_Connections($connection_id);
            
            if ($conn) {
                if ($connector == 'mysqli' || $connector == 'mysqlissl' || $connector == 'mysql' || $connector == 'mariadb') {
                    $database = !empty($row['Schema']) ? $row['Schema'] : (!empty($row['ServiceName']) ? $row['ServiceName'] : '');
                    if ($database) {
                        if ($type === 'tables') {
                            $query = "SELECT table_name as name FROM information_schema.tables WHERE table_schema = '" . $conn->real_escape_string($database) . "' AND table_type = 'BASE TABLE' ORDER BY table_name";
                        } else {
                            $query = "SELECT table_name as name FROM information_schema.views WHERE table_schema = '" . $conn->real_escape_string($database) . "' ORDER BY table_name";
                        }
                        $result_items = $conn->query($query);
                        if ($result_items) {
                            while ($item_row = $result_items->fetch_assoc()) {
                                $items[] = $item_row['name'];
                            }
                        }
                    }
                } elseif ($connector == 'clickhouse') {
                    $database = !empty($row['Schema']) ? $row['Schema'] : (!empty($row['ServiceName']) ? $row['ServiceName'] : 'default');
                    require_once('models/class_connclickhouse.php');
                    
                    if ($type === 'tables') {
                        $query = "SELECT name FROM system.tables WHERE database = '" . addslashes($database) . "' AND engine NOT LIKE '%View%' ORDER BY name";
                    } else {
                        $query = "SELECT name FROM system.tables WHERE database = '" . addslashes($database) . "' AND engine LIKE '%View%' ORDER BY name";
                    }
                    $result_items = class_clickhouse_query($conn, $query, 'JSON', $error_info);
                    if ($result_items !== false) {
                        if (isset($result_items['data']) && !empty($result_items['data'])) {
                            foreach ($result_items['data'] as $item) {
                                $items[] = $item['name'];
                            }
                        } elseif (is_array($result_items) && !empty($result_items)) {
                            foreach ($result_items as $item) {
                                if (isset($item['name'])) {
                                    $items[] = $item['name'];
                                }
                            }
                        }
                    }
                } elseif ($connector == 'sqlserver' || $connector == 'mssql') {
                    $database = !empty($row['Schema']) ? $row['Schema'] : (!empty($row['ServiceName']) ? $row['ServiceName'] : '');
                    if ($database && $conn instanceof PDO) {
                        if ($type === 'tables') {
                            $query = "SELECT TABLE_NAME as name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_CATALOG = '" . str_replace("'", "''", $database) . "' ORDER BY TABLE_NAME";
                        } else {
                            $query = "SELECT TABLE_NAME as name FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_CATALOG = '" . str_replace("'", "''", $database) . "' ORDER BY TABLE_NAME";
                        }
                        $result_items = $conn->query($query);
                        if ($result_items) {
                            while ($item_row = $result_items->fetch(PDO::FETCH_ASSOC)) {
                                $items[] = $item_row['name'];
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $error === null,
        'items' => $items,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    ob_end_flush();
    exit;
}

// Endpoint AJAX para crear reportes desde tablas/views
if (isset($_POST['action']) && $_POST['action'] === 'create_reports_from_tables') {
    require_once('config.php');
    require_once('conn/phoenix.php');
    
    ob_clean();
    
    // Obtener UsersId de la sesión
    $UsersId = null;
    if (isset($_SESSION['UsersId'])) {
        $UsersId = intval($_SESSION['UsersId']);
    }
    
    if (empty($UsersId)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no autenticado',
            'created' => 0
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    }
    
    $connection_id = intval($_POST['connection_id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $tables_views = json_decode($_POST['tables_views'] ?? '[]', true);
    $type = $_POST['type'] ?? 'tables'; // 'tables' o 'views'
    
    $created = 0;
    $errors = [];
    
    if (empty($connection_id) || empty($category_id) || empty($tables_views)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos',
            'created' => 0
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    }
    
    // Obtener información de la conexión para construir el query correcto
    $conn_info_result = $conn_phoenix->query("SELECT Connector, `Schema`, ServiceName FROM connections WHERE ConnectionId = $connection_id");
    if (!$conn_info_result) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener información de la conexión: ' . $conn_phoenix->error,
            'created' => 0
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    }
    
    $conn_info = $conn_info_result->fetch_assoc();
    if (!$conn_info) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Conexión no encontrada',
            'created' => 0
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    }
    
    $connector = strtolower(trim($conn_info['Connector'] ?? ''));
    $database = !empty($conn_info['Schema']) ? $conn_info['Schema'] : (!empty($conn_info['ServiceName']) ? $conn_info['ServiceName'] : '');
    
    foreach ($tables_views as $table_view_name) {
        $table_view_name = trim($table_view_name);
        if (empty($table_view_name)) continue;
        
        // Escapar nombre de tabla/view para evitar SQL injection
        $escaped_table_name = str_replace("`", "``", $table_view_name);
        $escaped_database = str_replace("`", "``", $database);
        
        // Construir query según el tipo de conexión
        $query = "SELECT * FROM ";
        if ($connector == 'clickhouse') {
            $query .= "`" . $escaped_database . "`.`" . $escaped_table_name . "`";
        } else {
            $query .= "`" . $escaped_table_name . "`";
        }
        
        // Insertar reporte
        $title = $table_view_name;
        $description = "Reporte generado automáticamente desde " . ($type === 'tables' ? 'tabla' : 'vista') . ": " . $table_view_name;
        $order = 0;
        $type_id = 1; // Tipo: Reporte
        $version = '';
        $layout_grid_class = '';
        $periodic = '';
        $convention_status = 1;
        $masking_status = 1;
        $status = 1;
        
        $sql = "INSERT INTO reports (Title, Description, CategoryId, `Order`, TypeId, UsersId, ConnectionId, Query, Version, LayoutGridClass, Periodic, ConventionStatus, MaskingStatus, Status, ParentId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = $conn_phoenix->prepare($sql);
        if ($stmt === false) {
            $errors[] = "Error al preparar consulta para $table_view_name: " . $conn_phoenix->error;
        } else {
            $stmt->bind_param('ssiiiiissssiii', $title, $description, $category_id, $order, $type_id, $UsersId, $connection_id, $query, $version, $layout_grid_class, $periodic, $convention_status, $masking_status, $status);
            if ($stmt->execute()) {
                $created++;
            } else {
                $errors[] = "Error al crear reporte para $table_view_name: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $created > 0,
        'created' => $created,
        'total' => count($tables_views),
        'errors' => $errors,
        'message' => $created > 0 ? "Se crearon $created de " . count($tables_views) . " reportes exitosamente." : "No se pudo crear ningún reporte."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    ob_end_flush();
    exit;
}

require_once('header.php');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action_post = $_POST['action'];
        
        if ($action_post === 'save_connection') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $connector = trim($_POST['connector'] ?? 'mysqli');
            $hostname = trim($_POST['hostname'] ?? '');
            $port = trim($_POST['port'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $service_name = trim($_POST['service_name'] ?? '');
            $schema = trim($_POST['schema'] ?? '');
            $status = intval($_POST['status'] ?? 1);
            $connection_id = intval($_POST['connection_id'] ?? 0);
            
            if (empty($title)) {
                $message = 'El título es obligatorio';
                $message_type = 'danger';
            } else {
                if ($connection_id > 0) {
                    // Actualizar - si la contraseña está vacía, no actualizarla
                    // Schema es palabra reservada, usar backticks
                    if (empty($password)) {
                        $sql = "UPDATE connections SET Title = ?, Description = ?, Connector = ?, Hostname = ?, Port = ?, Username = ?, ServiceName = ?, `Schema` = ?, Status = ? WHERE ConnectionId = ?";
                        $stmt = $conn_phoenix->prepare($sql);
                        if ($stmt === false) {
                            $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                            $message_type = 'danger';
                        } else {
                            $stmt->bind_param('ssssssssii', $title, $description, $connector, $hostname, $port, $username, $service_name, $schema, $status, $connection_id);
                        }
                    } else {
                        $sql = "UPDATE connections SET Title = ?, Description = ?, Connector = ?, Hostname = ?, Port = ?, Username = ?, Password = ?, ServiceName = ?, `Schema` = ?, Status = ? WHERE ConnectionId = ?";
                        $stmt = $conn_phoenix->prepare($sql);
                        if ($stmt === false) {
                            $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                            $message_type = 'danger';
                        } else {
                            $stmt->bind_param('sssssssssii', $title, $description, $connector, $hostname, $port, $username, $password, $service_name, $schema, $status, $connection_id);
                        }
                    }
                } else {
                    // Insertar - Schema es palabra reservada, usar backticks
                    $sql = "INSERT INTO connections (Title, Description, Connector, Hostname, Port, Username, Password, ServiceName, `Schema`, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn_phoenix->prepare($sql);
                    if ($stmt === false) {
                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                        $message_type = 'danger';
                    } else {
                        $stmt->bind_param('sssssssssi', $title, $description, $connector, $hostname, $port, $username, $password, $service_name, $schema, $status);
                    }
                }
                
                if ($stmt && $stmt->execute()) {
                    $message = $connection_id > 0 ? 'Conexión actualizada exitosamente' : 'Conexión creada exitosamente';
                    $message_type = 'success';
                    $action = 'list';
                } else {
                    $message = 'Error al guardar: ' . ($stmt ? $stmt->error : $conn_phoenix->error);
                    $message_type = 'danger';
                }
                if ($stmt) {
                    $stmt->close();
                }
            }
        }
        
        elseif ($action_post === 'delete') {
            $delete_id = intval($_POST['delete_id'] ?? 0);
            
            if ($delete_id > 0) {
                // Verificar si tiene reportes asociados
                $check = $conn_phoenix->query("SELECT COUNT(*) as count FROM reports WHERE ConnectionId = $delete_id");
                if ($check) {
                    $row = $check->fetch_assoc();
                    if ($row['count'] > 0) {
                        $message = 'No se puede eliminar: la conexión tiene reportes asociados';
                        $message_type = 'danger';
                    } else {
                        $sql = "DELETE FROM connections WHERE ConnectionId = ?";
                        $stmt = $conn_phoenix->prepare($sql);
                        if ($stmt === false) {
                            $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                            $message_type = 'danger';
                        } else {
                            $stmt->bind_param('i', $delete_id);
                            if ($stmt->execute()) {
                                $message = 'Conexión eliminada exitosamente';
                                $message_type = 'success';
                            } else {
                                $message = 'Error al eliminar: ' . $stmt->error;
                                $message_type = 'danger';
                            }
                            $stmt->close();
                        }
                    }
                } else {
                    $message = 'Error al verificar reportes asociados: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Obtener datos para edición
$edit_data = null;
if ($action === 'edit' && $id > 0) {
    $result = $conn_phoenix->query("SELECT * FROM connections WHERE ConnectionId = $id");
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

// Obtener lista de conexiones
$connections = [];
$result = $conn_phoenix->query("SELECT * FROM connections ORDER BY Title ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $connections[] = $row;
    }
}

// Obtener categorías para el modal
$categories = [];
$categories_result = $conn_phoenix->query("SELECT CategoryId, Title, ParentId FROM category WHERE IdType = 1 AND Status = 1 ORDER BY Title ASC");
if ($categories_result) {
    while ($cat_row = $categories_result->fetch_assoc()) {
        $categories[] = $cat_row;
    }
}
?>

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="mb-0">Configuración de Conexiones</h5>
          </div>
        </div>
      </div>
      <div class="card-body">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($action === 'list' || $action === 'add' || $action === 'edit'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">Conexiones de Base de Datos</h6>
          <?php if ($action === 'list'): ?>
          <a href="connections_config.php?action=add" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Agregar Conexión
          </a>
          <?php endif; ?>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulario -->
        <form method="POST" action="connections_config.php">
          <input type="hidden" name="action" value="save_connection">
          <?php if ($edit_data): ?>
          <input type="hidden" name="connection_id" value="<?php echo $edit_data['ConnectionId']; ?>">
          <?php endif; ?>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Título <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Title']) : ''; ?>" required>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Connector</label>
              <select class="form-select" name="connector">
                <option value="mysqli" <?php echo (!$edit_data || $edit_data['Connector'] == 'mysqli') ? 'selected' : ''; ?>>MySQLi</option>
                <option value="mysqlissl" <?php echo ($edit_data && $edit_data['Connector'] == 'mysqlissl') ? 'selected' : ''; ?>>MySQLi con SSL</option>
                <option value="sqlserver" <?php echo ($edit_data && ($edit_data['Connector'] == 'sqlserver' || $edit_data['Connector'] == 'mssql')) ? 'selected' : ''; ?>>SQL Server</option>
                <option value="oci" <?php echo ($edit_data && $edit_data['Connector'] == 'oci') ? 'selected' : ''; ?>>Oracle (OCI)</option>
                <option value="pdo" <?php echo ($edit_data && $edit_data['Connector'] == 'pdo') ? 'selected' : ''; ?>>PDO</option>
                <option value="clickhouse" <?php echo ($edit_data && $edit_data['Connector'] == 'clickhouse') ? 'selected' : ''; ?>>ClickHouse</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Hostname</label>
              <input type="text" class="form-control" name="hostname" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Hostname']) : ''; ?>" placeholder="localhost">
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Puerto</label>
              <input type="text" class="form-control" name="port" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Port']) : ''; ?>" placeholder="3306">
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Usuario</label>
              <input type="text" class="form-control" name="username" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Username']) : ''; ?>">
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Contraseña</label>
              <input type="password" class="form-control" name="password" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Password']) : ''; ?>" placeholder="<?php echo $edit_data ? 'Dejar vacío para mantener la actual' : ''; ?>">
              <?php if ($edit_data): ?>
              <small class="text-muted">Dejar vacío para mantener la contraseña actual</small>
              <?php endif; ?>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Service Name (Oracle)</label>
              <input type="text" class="form-control" name="service_name" value="<?php echo $edit_data ? htmlspecialchars($edit_data['ServiceName']) : ''; ?>" placeholder="Opcional para Oracle">
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Schema / Base de Datos</label>
              <input type="text" class="form-control" name="schema" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Schema']) : ''; ?>" placeholder="Nombre de la base de datos">
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" name="status">
                <option value="1" <?php echo (!$edit_data || $edit_data['Status'] == 1) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo ($edit_data && $edit_data['Status'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
              </select>
            </div>
            
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" name="description" rows="3"><?php echo $edit_data ? htmlspecialchars($edit_data['Description']) : ''; ?></textarea>
            </div>
            
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="connections_config.php" class="btn btn-secondary">Cancelar</a>
            </div>
          </div>
        </form>
        <?php else: ?>
        <!-- Lista con buscador y paginación -->
        <div class="table-responsive">
          <table class="table table-hover" id="connectionsTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Connector</th>
                <th>Hostname</th>
                <th>Puerto</th>
                <th>Usuario</th>
                <th>Schema</th>
                <th>Tablas</th>
                <th>Views</th>
                <th>SPs</th>
                <th>Conexión</th>
                <th>Ping</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($connections)): ?>
              <tr>
                <td colspan="14" class="text-center text-muted">
                  No hay conexiones registradas
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($connections as $conn): ?>
              <tr>
                <td><?php echo $conn['ConnectionId']; ?></td>
                <td><?php echo htmlspecialchars($conn['Title']); ?></td>
                <td><?php echo htmlspecialchars($conn['Connector'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($conn['Hostname'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($conn['Port'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($conn['Username'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($conn['Schema'] ?? 'N/A'); ?></td>
                <td>
                  <span class="connection-stats-tables" data-connection-id="<?php echo $conn['ConnectionId']; ?>" title="Cargando..." style="cursor: pointer;">
                    <span class="spinner-border spinner-border-sm text-secondary" style="width: 12px; height: 12px;" role="status"></span>
                  </span>
                </td>
                <td>
                  <span class="connection-stats-views" data-connection-id="<?php echo $conn['ConnectionId']; ?>" title="Cargando..." style="cursor: pointer;">
                    <span class="spinner-border spinner-border-sm text-secondary" style="width: 12px; height: 12px;" role="status"></span>
                  </span>
                </td>
                <td>
                  <span class="connection-stats-sps" data-connection-id="<?php echo $conn['ConnectionId']; ?>" title="Cargando...">
                    <span class="spinner-border spinner-border-sm text-secondary" style="width: 12px; height: 12px;" role="status"></span>
                  </span>
                </td>
                <td>
                  <div class="connection-status-wrapper" style="position: relative; display: inline-block;">
                    <span class="connection-status" data-connection-id="<?php echo $conn['ConnectionId']; ?>" title="Probando conexión...">
                      <span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span>
                    </span>
                    <div class="connection-error-tooltip" style="display: none; position: absolute; background: #1f2937; color: #fff; padding: 12px; border-radius: 6px; z-index: 10000; max-width: 500px; min-width: 300px; font-size: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.5); bottom: calc(100% + 10px); left: 50%; transform: translateX(-50%); white-space: pre-wrap; word-wrap: break-word; border: 1px solid #374151;">
                      <div style="margin-bottom: 10px; font-weight: bold; border-bottom: 1px solid #4b5563; padding-bottom: 6px; color: #fca5a5;">⚠️ Error de Conexión</div>
                      <div class="error-message-text" style="margin-bottom: 10px; line-height: 1.5; max-height: 200px; overflow-y: auto; color: #e5e7eb;"></div>
                      <div style="border-top: 1px solid #4b5563; padding-top: 8px; text-align: right;">
                        <button class="btn btn-sm btn-outline-light copy-error-btn" style="font-size: 11px; padding: 4px 12px; border-color: #6b7280;">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                          Copiar Error
                        </button>
                      </div>
                      <div style="position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid #1f2937;"></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="connection-ping-display" data-connection-id="<?php echo $conn['ConnectionId']; ?>" style="font-size: 11px; color: #6c757d;">
                    <span class="spinner-border spinner-border-sm text-secondary" style="width: 10px; height: 10px;" role="status"></span>
                  </span>
                </td>
                <td>
                  <span class="badge bg-<?php echo ($conn['Status'] == 1) ? 'success' : 'secondary'; ?>">
                    <?php echo ($conn['Status'] == 1) ? 'Activo' : 'Inactivo'; ?>
                  </span>
                </td>
                <td>
                  <a href="connections_config.php?action=edit&id=<?php echo $conn['ConnectionId']; ?>" class="btn btn-sm btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </a>
                  <form method="POST" action="connections_config.php" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar esta conexión?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="delete_id" value="<?php echo $conn['ConnectionId']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cargar estadísticas de todas las conexiones
    const statsElements = document.querySelectorAll('.connection-stats-tables, .connection-stats-views, .connection-stats-sps');
    const connectionIds = new Set();
    statsElements.forEach(function(el) {
        connectionIds.add(el.getAttribute('data-connection-id'));
    });
    
    // Cargar estadísticas para cada conexión
    connectionIds.forEach(function(connectionId) {
        fetch('connections_config.php?action=get_connection_stats&id=' + connectionId)
            .then(response => response.json())
            .then(data => {
                // Actualizar tablas
                const tablesEl = document.querySelector('.connection-stats-tables[data-connection-id="' + connectionId + '"]');
                if (tablesEl) {
                    if (data.error) {
                        tablesEl.innerHTML = '<span class="text-muted" title="Error: ' + data.error + '">-</span>';
                    } else {
                        tablesEl.innerHTML = '<span class="badge bg-info clickable-badge" data-connection-id="' + connectionId + '" data-type="tables" title="Click para crear reportes desde tablas" style="cursor: pointer;">' + data.tables + '</span>';
                    }
                }
                
                // Actualizar views
                const viewsEl = document.querySelector('.connection-stats-views[data-connection-id="' + connectionId + '"]');
                if (viewsEl) {
                    if (data.error) {
                        viewsEl.innerHTML = '<span class="text-muted" title="Error: ' + data.error + '">-</span>';
                    } else {
                        viewsEl.innerHTML = '<span class="badge bg-secondary clickable-badge" data-connection-id="' + connectionId + '" data-type="views" title="Click para crear reportes desde views" style="cursor: pointer;">' + data.views + '</span>';
                    }
                }
                
                // Actualizar SPs
                const spsEl = document.querySelector('.connection-stats-sps[data-connection-id="' + connectionId + '"]');
                if (spsEl) {
                    if (data.error) {
                        spsEl.innerHTML = '<span class="text-muted" title="Error: ' + data.error + '">-</span>';
                    } else {
                        spsEl.innerHTML = '<span class="badge bg-warning text-dark" title="Stored Procedures">' + data.sps + '</span>';
                    }
                }
            })
            .catch(error => {
                // Error en la petición
                const tablesEl = document.querySelector('.connection-stats-tables[data-connection-id="' + connectionId + '"]');
                const viewsEl = document.querySelector('.connection-stats-views[data-connection-id="' + connectionId + '"]');
                const spsEl = document.querySelector('.connection-stats-sps[data-connection-id="' + connectionId + '"]');
                
                if (tablesEl) tablesEl.innerHTML = '<span class="text-muted" title="Error al cargar">-</span>';
                if (viewsEl) viewsEl.innerHTML = '<span class="text-muted" title="Error al cargar">-</span>';
                if (spsEl) spsEl.innerHTML = '<span class="text-muted" title="Error al cargar">-</span>';
            });
    });
    
    // Probar todas las conexiones al cargar la página
    const connectionStatuses = document.querySelectorAll('.connection-status');
    
    connectionStatuses.forEach(function(statusEl) {
        const connectionId = statusEl.getAttribute('data-connection-id');
        const wrapper = statusEl.closest('.connection-status-wrapper');
        const tooltip = wrapper ? wrapper.querySelector('.connection-error-tooltip') : null;
        const errorText = tooltip ? tooltip.querySelector('.error-message-text') : null;
        const copyBtn = tooltip ? tooltip.querySelector('.copy-error-btn') : null;
        
        let tooltipTimeout = null;
        let isTooltipVisible = false;
        
        // Función para mostrar tooltip
        function showTooltip() {
            if (tooltipTimeout) {
                clearTimeout(tooltipTimeout);
            }
            tooltip.style.display = 'block';
            isTooltipVisible = true;
        }
        
        // Función para ocultar tooltip con delay
        function hideTooltip() {
            if (tooltipTimeout) {
                clearTimeout(tooltipTimeout);
            }
            tooltipTimeout = setTimeout(function() {
                if (!isTooltipVisible) {
                    tooltip.style.display = 'none';
                }
            }, 200); // Pequeño delay para permitir movimiento del mouse
        }
        
        // Hacer petición AJAX para probar la conexión
        fetch('connections_config.php?action=test_connection&id=' + connectionId)
            .then(response => response.json())
            .then(data => {
                // Limpiar el contenido
                statusEl.innerHTML = '';
                
                if (data.success) {
                    // Bolita verde
                    statusEl.innerHTML = '<span class="badge bg-success" style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; padding: 0; cursor: pointer;" title="Conexión exitosa"></span>';
                    
                    // Mostrar ping si está disponible
                    if (data.ping !== null && data.ping !== undefined) {
                        const pingDisplay = document.querySelector('.connection-ping-display[data-connection-id="' + connectionId + '"]');
                        if (pingDisplay) {
                            const pingMs = parseFloat(data.ping);
                            let pingColor = '#28a745'; // Verde por defecto
                            let pingText = pingMs.toFixed(0) + 'ms';
                            
                            if (pingMs > 500) {
                                pingColor = '#dc3545'; // Rojo si es muy lento
                            } else if (pingMs > 200) {
                                pingColor = '#ffc107'; // Amarillo si es lento
                            }
                            
                            pingDisplay.innerHTML = '<span style="color: ' + pingColor + '; font-weight: 500;">' + pingText + '</span>';
                            pingDisplay.title = 'Tiempo de respuesta: ' + pingMs.toFixed(2) + 'ms';
                        }
                    }
                } else {
                    // Bolita roja con tooltip
                    const errorMsg = data.message || 'Error desconocido';
                    statusEl.innerHTML = '<span class="badge bg-danger connection-error-dot" style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; padding: 0; cursor: pointer;" title="Error de conexión - Pase el mouse para ver detalles"></span>';
                    
                    // Configurar tooltip
                    if (tooltip && errorText) {
                        // Establecer el mensaje de error
                        errorText.textContent = errorMsg;
                        
                        // Mostrar tooltip al pasar el mouse sobre la bolita roja
                        const errorDot = statusEl.querySelector('.connection-error-dot');
                        if (errorDot) {
                            errorDot.addEventListener('mouseenter', function() {
                                showTooltip();
                            });
                            
                            errorDot.addEventListener('mouseleave', function(e) {
                                // Verificar si el mouse se está moviendo hacia el tooltip
                                const relatedTarget = e.relatedTarget;
                                if (!relatedTarget || !tooltip.contains(relatedTarget)) {
                                    isTooltipVisible = false;
                                    hideTooltip();
                                }
                            });
                            
                            // Mantener tooltip visible cuando el mouse está sobre él
                            tooltip.addEventListener('mouseenter', function() {
                                isTooltipVisible = true;
                                if (tooltipTimeout) {
                                    clearTimeout(tooltipTimeout);
                                }
                            });
                            
                            tooltip.addEventListener('mouseleave', function() {
                                isTooltipVisible = false;
                                hideTooltip();
                            });
                        }
                        
                        // Botón copiar
                        if (copyBtn) {
                            copyBtn.addEventListener('click', function(e) {
                                e.stopPropagation();
                                e.preventDefault();
                                navigator.clipboard.writeText(errorMsg).then(function() {
                                    const originalText = copyBtn.innerHTML;
                                    copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Copiado!';
                                    copyBtn.style.color = '#4ade80';
                                    setTimeout(function() {
                                        copyBtn.innerHTML = originalText;
                                        copyBtn.style.color = '';
                                    }, 2000);
                                }).catch(function(err) {
                                    alert('Error al copiar: ' + err);
                                });
                            });
                        }
                    }
                }
            })
            .catch(error => {
                // Error en la petición - bolita roja
                statusEl.innerHTML = '<span class="badge bg-danger connection-error-dot" style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; padding: 0; cursor: pointer;" title="Error al probar conexión"></span>';
                
                // Mostrar "-" en ping si hay error
                const pingDisplayError = document.querySelector('.connection-ping-display[data-connection-id="' + connectionId + '"]');
                if (pingDisplayError) {
                    pingDisplayError.innerHTML = '<span style="color: #dc3545;">-</span>';
                    pingDisplayError.title = 'Error al medir ping';
                }
                
                if (tooltip && errorText) {
                    const errorMsg = 'Error al probar conexión: ' + error.message;
                    errorText.textContent = errorMsg;
                    
                    let tooltipTimeout = null;
                    let isTooltipVisible = false;
                    
                    function showTooltip() {
                        if (tooltipTimeout) {
                            clearTimeout(tooltipTimeout);
                        }
                        tooltip.style.display = 'block';
                        isTooltipVisible = true;
                    }
                    
                    function hideTooltip() {
                        if (tooltipTimeout) {
                            clearTimeout(tooltipTimeout);
                        }
                        tooltipTimeout = setTimeout(function() {
                            if (!isTooltipVisible) {
                                tooltip.style.display = 'none';
                            }
                        }, 200);
                    }
                    
                    const errorDot = statusEl.querySelector('.connection-error-dot');
                    if (errorDot) {
                        errorDot.addEventListener('mouseenter', function() {
                            showTooltip();
                        });
                        
                        errorDot.addEventListener('mouseleave', function(e) {
                            const relatedTarget = e.relatedTarget;
                            if (!relatedTarget || !tooltip.contains(relatedTarget)) {
                                isTooltipVisible = false;
                                hideTooltip();
                            }
                        });
                        
                        tooltip.addEventListener('mouseenter', function() {
                            isTooltipVisible = true;
                            if (tooltipTimeout) {
                                clearTimeout(tooltipTimeout);
                            }
                        });
                        
                        tooltip.addEventListener('mouseleave', function() {
                            isTooltipVisible = false;
                            hideTooltip();
                        });
                    }
                    
                    if (copyBtn) {
                        copyBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            e.preventDefault();
                            navigator.clipboard.writeText(errorMsg).then(function() {
                                const originalText = copyBtn.innerHTML;
                                copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Copiado!';
                                copyBtn.style.color = '#4ade80';
                                setTimeout(function() {
                                    copyBtn.innerHTML = originalText;
                                    copyBtn.style.color = '';
                                }, 2000);
                            });
                        });
                    }
                }
            });
    });
});

// Inicializar DataTables para la tabla de conexiones
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        var connectionsTable = $('#connectionsTable').DataTable({
            "language": {
                "decimal": ",",
                "emptyTable": "No hay conexiones registradas",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ".",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": activar para ordenar la columna de forma ascendente",
                    "sortDescending": ": activar para ordenar la columna de forma descendente"
                }
            },
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            "order": [[1, "asc"]], // Ordenar por título por defecto
            "responsive": false,
            "scrollX": false,
            "autoWidth": true,
            "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            "columnDefs": [
                {
                    "targets": [7, 8, 9, 10, 11, 13], // Columnas de estadísticas, conexión, ping y acciones
                    "orderable": false,
                    "searchable": false
                }
            ]
        });
    });
    
    // Manejar clics en badges de tablas/views (usando event delegation)
    document.addEventListener('click', function(e) {
        const badge = e.target.closest('.clickable-badge');
        if (badge) {
            const connectionId = badge.getAttribute('data-connection-id');
            const type = badge.getAttribute('data-type'); // 'tables' o 'views'
            
            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('tablesViewsModal'));
            document.getElementById('modalConnectionId').value = connectionId;
            document.getElementById('modalType').value = type;
            document.getElementById('modalTitle').textContent = type === 'tables' ? 'Crear Reportes desde Tablas' : 'Crear Reportes desde Views';
            document.getElementById('tablesViewsList').innerHTML = '<div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span> Cargando...</div>';
            
            // Cargar tablas/views
            fetch('connections_config.php?action=get_tables_views&id=' + connectionId + '&type=' + type)
                .then(response => response.json())
                .then(data => {
                    const listContainer = document.getElementById('tablesViewsList');
                    if (data.success && data.items && data.items.length > 0) {
                        let html = '<div class="mb-3"><button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">Seleccionar Todos</button> <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">Deseleccionar Todos</button></div>';
                        html += '<div style="max-height: 400px; overflow-y: auto;">';
                        data.items.forEach(function(item) {
                            html += '<div class="form-check mb-2">';
                            html += '<input class="form-check-input table-view-checkbox" type="checkbox" value="' + item.replace(/"/g, '&quot;') + '" id="item_' + item.replace(/[^a-zA-Z0-9]/g, '_') + '">';
                            html += '<label class="form-check-label" for="item_' + item.replace(/[^a-zA-Z0-9]/g, '_') + '">' + item + '</label>';
                            html += '</div>';
                        });
                        html += '</div>';
                        listContainer.innerHTML = html;
                        
                        // Manejar select all/deselect all
                        document.getElementById('selectAllBtn').addEventListener('click', function() {
                            document.querySelectorAll('.table-view-checkbox').forEach(function(cb) {
                                cb.checked = true;
                            });
                        });
                        document.getElementById('deselectAllBtn').addEventListener('click', function() {
                            document.querySelectorAll('.table-view-checkbox').forEach(function(cb) {
                                cb.checked = false;
                            });
                        });
                    } else {
                        listContainer.innerHTML = '<div class="alert alert-warning">No se encontraron ' + (type === 'tables' ? 'tablas' : 'views') + ' o hubo un error al cargarlas.</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('tablesViewsList').innerHTML = '<div class="alert alert-danger">Error al cargar las ' + (type === 'tables' ? 'tablas' : 'views') + '.</div>';
                });
            
            modal.show();
        }
    });
    
    // Manejar creación de reportes (usando event delegation)
    document.addEventListener('click', function(e) {
        // Verificar si el click fue en el botón o en un elemento dentro del botón
        const btn = e.target.closest('#createReportsBtn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Botón crear reportes clickeado');
            
            const connectionId = document.getElementById('modalConnectionId').value;
            const type = document.getElementById('modalType').value;
            const categoryId = document.getElementById('modalCategoryId').value;
            const selectedItems = [];
            
            document.querySelectorAll('.table-view-checkbox:checked').forEach(function(cb) {
                selectedItems.push(cb.value);
            });
            
            console.log('Datos:', { connectionId, type, categoryId, selectedItems });
            
            if (!categoryId) {
                alert('Por favor seleccione una categoría');
                return;
            }
            
            if (selectedItems.length === 0) {
                alert('Por favor seleccione al menos una ' + (type === 'tables' ? 'tabla' : 'view'));
                return;
            }
            
            // Deshabilitar botón y mostrar loading
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';
            
            // Crear reportes
            const formData = new FormData();
            formData.append('action', 'create_reports_from_tables');
            formData.append('connection_id', connectionId);
            formData.append('category_id', categoryId);
            formData.append('type', type);
            formData.append('tables_views', JSON.stringify(selectedItems));
            
            console.log('Enviando petición...');
            
            fetch('connections_config.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Respuesta recibida:', response.status);
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.text().then(text => {
                    console.log('Respuesta texto:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Error al parsear JSON:', e, 'Texto:', text);
                        throw new Error('Respuesta no válida del servidor');
                    }
                });
            })
            .then(data => {
                console.log('Datos recibidos:', data);
                if (data.success) {
                    alert('Se crearon ' + data.created + ' de ' + data.total + ' reportes exitosamente.');
                    if (data.errors && data.errors.length > 0) {
                        console.warn('Errores:', data.errors);
                    }
                    // Cerrar modal y recargar página
                    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('tablesViewsModal'));
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    window.location.reload();
                } else {
                    alert('Error al crear los reportes: ' + (data.message || 'Verifique la consola para más detalles.'));
                    console.error(data);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(error => {
                alert('Error al crear los reportes: ' + error.message);
                console.error('Error completo:', error);
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }
    });
}
</script>

<!-- Modal para crear reportes desde tablas/views -->
<div class="modal fade" id="tablesViewsModal" tabindex="-1" aria-labelledby="tablesViewsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Crear Reportes desde Tablas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modalConnectionId" value="">
        <input type="hidden" id="modalType" value="">
        
        <div class="mb-3">
          <label class="form-label">Categoría <span class="text-danger">*</span></label>
          <select class="form-select" id="modalCategoryId" required>
            <option value="">-- Seleccionar Categoría --</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['CategoryId']; ?>">
              <?php echo htmlspecialchars($cat['Title']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Seleccionar <?php echo 'Tablas/Views'; ?>:</label>
          <div id="tablesViewsList">
            <!-- Se cargará dinámicamente -->
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="createReportsBtn">Crear Reportes</button>
      </div>
    </div>
  </div>
</div>

<?php require_once('footer.php'); ?>
