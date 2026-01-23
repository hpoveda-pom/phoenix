<?php
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
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($connections)): ?>
              <tr>
                <td colspan="9" class="text-center text-muted">
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

<?php require_once('footer.php'); ?>
