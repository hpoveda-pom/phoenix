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
        
        if ($action_post === 'save_access') {
            $users_id = !empty($_POST['users_id']) ? intval($_POST['users_id']) : null;
            $reports_id = !empty($_POST['reports_id']) ? intval($_POST['reports_id']) : null;
            $level = !empty($_POST['level']) ? intval($_POST['level']) : null;
            $access_id = intval($_POST['access_id'] ?? 0);
            
            if (empty($users_id) || empty($reports_id)) {
                $message = 'El usuario y el reporte son obligatorios';
                $message_type = 'danger';
            } else {
                // Verificar si ya existe un acceso para este usuario y reporte (excepto si es el mismo registro)
                $check_sql = "SELECT AccessId FROM access WHERE UsersId = ? AND ReportsId = ? AND AccessId != ?";
                $check_stmt = $conn_phoenix->prepare($check_sql);
                $check_stmt->bind_param('iii', $users_id, $reports_id, $access_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = 'Ya existe un acceso asignado para este usuario y reporte';
                    $message_type = 'danger';
                    $check_stmt->close();
                } else {
                    $check_stmt->close();
                    
                    if ($access_id > 0) {
                        // Actualizar
                        $sql = "UPDATE access SET UsersId = ?, ReportsId = ?, Level = ? WHERE AccessId = ?";
                        $stmt = $conn_phoenix->prepare($sql);
                        if ($stmt === false) {
                            $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                            $message_type = 'danger';
                        } else {
                            $stmt->bind_param('iiii', $users_id, $reports_id, $level, $access_id);
                        }
                    } else {
                        // Insertar
                        $sql = "INSERT INTO access (UsersId, ReportsId, Level) VALUES (?, ?, ?)";
                        $stmt = $conn_phoenix->prepare($sql);
                        if ($stmt === false) {
                            $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                            $message_type = 'danger';
                        } else {
                            $stmt->bind_param('iii', $users_id, $reports_id, $level);
                        }
                    }
                    
                    if ($stmt && $stmt->execute()) {
                        $message = $access_id > 0 ? 'Acceso actualizado exitosamente' : 'Acceso creado exitosamente';
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
        }
        
        elseif ($action_post === 'delete') {
            $delete_id = intval($_POST['delete_id'] ?? 0);
            
            if ($delete_id > 0) {
                $sql = "DELETE FROM access WHERE AccessId = ?";
                $stmt = $conn_phoenix->prepare($sql);
                if ($stmt === false) {
                    $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                } else {
                    $stmt->bind_param('i', $delete_id);
                    if ($stmt->execute()) {
                        $message = 'Acceso eliminado exitosamente';
                        $message_type = 'success';
                    } else {
                        $message = 'Error al eliminar: ' . $stmt->error;
                        $message_type = 'danger';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Obtener datos para edición
$edit_data = null;
if ($action === 'edit' && $id > 0) {
    $result = $conn_phoenix->query("SELECT * FROM access WHERE AccessId = $id");
    if ($result && $result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

// Obtener lista de usuarios para el dropdown
$users = [];
$users_result = $conn_phoenix->query("SELECT UsersId, Username, FullName FROM users WHERE Status = 1 ORDER BY FullName ASC, Username ASC");
if ($users_result) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Obtener lista de reportes para el dropdown
$reports = [];
$reports_result = $conn_phoenix->query("SELECT ReportsId, Title FROM reports WHERE Status = 1 ORDER BY Title ASC");
if ($reports_result) {
    while ($row = $reports_result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Obtener lista de accesos
$access_records = [];
$result = $conn_phoenix->query("SELECT a.*, 
    u.Username AS UserName, 
    u.FullName AS UserFullName,
    r.Title AS ReportTitle
    FROM access a 
    LEFT JOIN users u ON u.UsersId = a.UsersId 
    LEFT JOIN reports r ON r.ReportsId = a.ReportsId 
    ORDER BY a.LastModify DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $access_records[] = $row;
    }
}
?>

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="mb-0">Configuración de Accesos a Reportes</h5>
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
          <h6 class="mb-0">Asignación de Accesos a Reportes</h6>
          <p class="text-muted small mb-0">Asignar reportes a usuarios que no son dueños</p>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
          <?php if ($action === 'list'): ?>
          <a href="access_config.php?action=add" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Agregar Acceso
          </a>
          <?php endif; ?>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulario -->
        <form method="POST" action="access_config.php">
          <input type="hidden" name="action" value="save_access">
          <?php if ($edit_data): ?>
          <input type="hidden" name="access_id" value="<?php echo $edit_data['AccessId']; ?>">
          <?php endif; ?>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Usuario <span class="text-danger">*</span></label>
              <select class="form-select" name="users_id" required>
                <option value="">-- Seleccionar Usuario --</option>
                <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['UsersId']; ?>" <?php echo ($edit_data && $edit_data['UsersId'] == $user['UsersId']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($user['FullName'] ? $user['FullName'] . ' (' . $user['Username'] . ')' : $user['Username']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Reporte <span class="text-danger">*</span></label>
              <select class="form-select" name="reports_id" required>
                <option value="">-- Seleccionar Reporte --</option>
                <?php foreach ($reports as $report): ?>
                <option value="<?php echo $report['ReportsId']; ?>" <?php echo ($edit_data && $edit_data['ReportsId'] == $report['ReportsId']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($report['Title']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Nivel de Acceso</label>
              <select class="form-select" name="level">
                <option value="">-- Sin nivel específico --</option>
                <option value="1" <?php echo ($edit_data && $edit_data['Level'] == 1) ? 'selected' : ''; ?>>Nivel 1 - Lectura</option>
                <option value="2" <?php echo ($edit_data && $edit_data['Level'] == 2) ? 'selected' : ''; ?>>Nivel 2 - Lectura y Exportación</option>
                <option value="3" <?php echo ($edit_data && $edit_data['Level'] == 3) ? 'selected' : ''; ?>>Nivel 3 - Lectura, Exportación y Filtros</option>
                <option value="4" <?php echo ($edit_data && $edit_data['Level'] == 4) ? 'selected' : ''; ?>>Nivel 4 - Acceso Completo</option>
              </select>
              <small class="text-muted">Define los permisos del usuario sobre el reporte</small>
            </div>
            
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="access_config.php" class="btn btn-secondary">Cancelar</a>
            </div>
          </div>
        </form>
        <?php else: ?>
        <!-- Lista con buscador y paginación -->
        <div class="table-responsive">
          <table class="table table-hover" id="accessTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Reporte</th>
                <th>Nivel</th>
                <th>Última Modificación</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($access_records)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted">
                  No hay accesos asignados
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($access_records as $acc): ?>
              <tr>
                <td><?php echo $acc['AccessId']; ?></td>
                <td>
                  <strong><?php echo $acc['UserFullName'] ? htmlspecialchars($acc['UserFullName']) : ($acc['UserName'] ? htmlspecialchars($acc['UserName']) : '<span class="text-muted">-</span>'); ?></strong>
                  <?php if ($acc['UserName']): ?>
                  <br><small class="text-muted"><?php echo htmlspecialchars($acc['UserName']); ?></small>
                  <?php endif; ?>
                </td>
                <td><?php echo $acc['ReportTitle'] ? htmlspecialchars($acc['ReportTitle']) : '<span class="text-muted">-</span>'; ?></td>
                <td>
                  <?php if ($acc['Level']): ?>
                    <?php
                    $level_labels = [1 => 'Lectura', 2 => 'Lectura y Exportación', 3 => 'Lectura, Exportación y Filtros', 4 => 'Acceso Completo'];
                    $level_colors = [1 => 'info', 2 => 'primary', 3 => 'warning', 4 => 'success'];
                    $level = $acc['Level'];
                    ?>
                    <span class="badge bg-<?php echo $level_colors[$level] ?? 'secondary'; ?>">
                      Nivel <?php echo $level . ' - ' . ($level_labels[$level] ?? 'N/A'); ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td><?php echo $acc['LastModify'] ? date('Y-m-d H:i', strtotime($acc['LastModify'])) : '<span class="text-muted">-</span>'; ?></td>
                <td>
                  <a href="access_config.php?action=edit&id=<?php echo $acc['AccessId']; ?>" class="btn btn-sm btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </a>
                  <form method="POST" action="access_config.php" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este acceso?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="delete_id" value="<?php echo $acc['AccessId']; ?>">
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
    // Esperar a que jQuery esté disponible
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no está disponible');
        return;
    }
    
    // Inicializar DataTables
    var table = jQuery('#accessTable').DataTable({
        "language": {
            "decimal": ",",
            "emptyTable": "No hay accesos asignados",
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
        "order": [[4, "desc"]], // Ordenar por última modificación descendente por defecto
        "responsive": false,
        "scrollX": false,
        "autoWidth": true,
        "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        "columnDefs": [
            {
                "targets": [5], // Columna de acciones
                "orderable": false,
                "searchable": false
            }
        ]
    });
});
</script>

<?php require_once('footer.php'); ?>
