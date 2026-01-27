<?php
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
    
    // Intentar conectar
    $conn = class_Connections($test_id);
    $success = ($conn !== null && $conn !== false);
    
    // Si falló, obtener el mensaje de error más detallado
    if (!$success) {
        // Verificar si hay mensajes de error en GLOBALS
        if (isset($GLOBALS['phoenix_errors_warnings']) && !empty($GLOBALS['phoenix_errors_warnings'])) {
            $last_error = end($GLOBALS['phoenix_errors_warnings']);
            $error_message = isset($last_error['message']) ? $last_error['message'] : 'Error desconocido';
        } else if (empty($error_message)) {
            // Intentar obtener información de la conexión para ver qué falló
            global $conn_phoenix;
            if (isset($conn_phoenix) && $conn_phoenix instanceof mysqli) {
                $result = $conn_phoenix->query("SELECT Title, Connector, Hostname, Port, Username FROM connections WHERE ConnectionId = $test_id");
                if ($result && $row = $result->fetch_assoc()) {
                    $error_message = "No se pudo establecer conexión. Verifique: Hostname: " . ($row['Hostname'] ?: 'vacío') . 
                                    ", Puerto: " . ($row['Port'] ?: 'vacío') . 
                                    ", Usuario: " . ($row['Username'] ?: 'vacío') . 
                                    ", Connector: " . ($row['Connector'] ?: 'N/A');
                } else {
                    $error_message = "Error al establecer conexión. Verifique la configuración.";
                }
            } else {
                $error_message = "Error: No se pudo conectar a la base de datos Phoenix para verificar la configuración.";
            }
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
        'message' => $success ? 'Conexión exitosa' : $error_message
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Terminar buffer y salir
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
        <!-- Lista -->
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Connector</th>
                <th>Hostname</th>
                <th>Puerto</th>
                <th>Usuario</th>
                <th>Schema</th>
                <th>Conexión</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($connections)): ?>
              <tr>
                <td colspan="10" class="text-center text-muted">
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
</script>

<?php require_once('footer.php'); ?>
