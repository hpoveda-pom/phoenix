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
        
        if ($action_post === 'save_masking') {
            $reports_id = intval($_POST['reports_id'] ?? 0);
            $users_id = intval($_POST['users_id'] ?? 0);
            $owner_id = intval($_POST['owner_id'] ?? 0);
            $level = trim($_POST['level'] ?? '1');
            $expiration_date = !empty($_POST['expiration_date']) ? trim($_POST['expiration_date']) : null;
            $status = intval($_POST['status'] ?? 1);
            $masking_id = intval($_POST['masking_id'] ?? 0);
            
            if (empty($reports_id) || empty($users_id) || empty($owner_id)) {
                $message = 'El reporte, usuario y propietario son obligatorios';
                $message_type = 'danger';
            } else {
                if ($masking_id > 0) {
                    // Actualizar
                    if ($expiration_date) {
                        $sql = "UPDATE masking SET ReportsId = ?, UsersId = ?, OwnerId = ?, `Level` = ?, ExpirationDate = ?, Status = ? WHERE MaskingId = ?";
                        $stmt = $conn_phoenix->prepare($sql);
                        if ($stmt === false) {
                            $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                            $message_type = 'danger';
                        } else {
                            $stmt->bind_param('iiiissi', $reports_id, $users_id, $owner_id, $level, $expiration_date, $status, $masking_id);
                        }
                    } else {
                        $sql = "UPDATE masking SET ReportsId = ?, UsersId = ?, OwnerId = ?, `Level` = ?, ExpirationDate = NULL, Status = ? WHERE MaskingId = ?";
                        $stmt = $conn_phoenix->prepare($sql);
                        if ($stmt === false) {
                            $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                            $message_type = 'danger';
                        } else {
                            $stmt->bind_param('iiiisii', $reports_id, $users_id, $owner_id, $level, $status, $masking_id);
                        }
                    }
                } else {
                    // Insertar
                    if ($expiration_date) {
                        $sql = "INSERT INTO masking (ReportsId, UsersId, OwnerId, `Level`, ExpirationDate, Status) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn_phoenix->prepare($sql);
                        if ($stmt === false) {
                            $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                            $message_type = 'danger';
                        } else {
                            $stmt->bind_param('iiiissi', $reports_id, $users_id, $owner_id, $level, $expiration_date, $status);
                        }
                    } else {
                        $sql = "INSERT INTO masking (ReportsId, UsersId, OwnerId, `Level`, Status) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn_phoenix->prepare($sql);
                        if ($stmt === false) {
                            $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                            $message_type = 'danger';
                        } else {
                            $stmt->bind_param('iiiisi', $reports_id, $users_id, $owner_id, $level, $status);
                        }
                    }
                }
                
                if ($stmt && $stmt->execute()) {
                    $message = $masking_id > 0 ? 'Registro de enmascaramiento actualizado exitosamente' : 'Registro de enmascaramiento creado exitosamente';
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
                $sql = "DELETE FROM masking WHERE MaskingId = ?";
                $stmt = $conn_phoenix->prepare($sql);
                if ($stmt === false) {
                    $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                } else {
                    $stmt->bind_param('i', $delete_id);
                    if ($stmt->execute()) {
                        $message = 'Registro de enmascaramiento eliminado exitosamente';
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
    $result = $conn_phoenix->query("SELECT * FROM masking WHERE MaskingId = $id");
    if ($result && $result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
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

// Obtener lista de usuarios para el dropdown
$users = [];
$users_result = $conn_phoenix->query("SELECT UsersId, Username, FullName FROM users WHERE Status = 1 ORDER BY FullName ASC, Username ASC");
if ($users_result) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Obtener lista de registros de enmascaramiento
$masking_records = [];
$result = $conn_phoenix->query("SELECT m.*, r.Title AS ReportTitle, u.Username AS UserName, u.FullName AS UserFullName, o.Username AS OwnerName, o.FullName AS OwnerFullName FROM masking m LEFT JOIN reports r ON r.ReportsId = m.ReportsId LEFT JOIN users u ON u.UsersId = m.UsersId LEFT JOIN users o ON o.UsersId = m.OwnerId ORDER BY m.DateCreated DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $masking_records[] = $row;
    }
}
?>

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="mb-0">Configuración de Enmascaramiento de Datos</h5>
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
          <h6 class="mb-0">Registros de Enmascaramiento</h6>
          <?php if ($action === 'list'): ?>
          <a href="masking_config.php?action=add" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Agregar Registro
          </a>
          <?php endif; ?>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulario -->
        <form method="POST" action="masking_config.php">
          <input type="hidden" name="action" value="save_masking">
          <?php if ($edit_data): ?>
          <input type="hidden" name="masking_id" value="<?php echo $edit_data['MaskingId']; ?>">
          <?php endif; ?>
          
          <div class="row g-3">
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
              <label class="form-label">Propietario del Reporte <span class="text-danger">*</span></label>
              <select class="form-select" name="owner_id" required>
                <option value="">-- Seleccionar Propietario --</option>
                <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['UsersId']; ?>" <?php echo ($edit_data && $edit_data['OwnerId'] == $user['UsersId']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($user['FullName'] ? $user['FullName'] . ' (' . $user['Username'] . ')' : $user['Username']); ?>
                </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Usuario propietario del reporte</small>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Nivel de Enmascaramiento <span class="text-danger">*</span></label>
              <select class="form-select" name="level" required>
                <option value="1" <?php echo (!$edit_data || $edit_data['Level'] == '1') ? 'selected' : ''; ?>>1 - Público</option>
                <option value="2" <?php echo ($edit_data && $edit_data['Level'] == '2') ? 'selected' : ''; ?>>2 - Personal</option>
                <option value="3" <?php echo ($edit_data && $edit_data['Level'] == '3') ? 'selected' : ''; ?>>3 - Sensible</option>
                <option value="4" <?php echo ($edit_data && $edit_data['Level'] == '4') ? 'selected' : ''; ?>>4 - Confidencial</option>
              </select>
              <small class="text-muted">Según Ley N.° 8968 & PRODHAB</small>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Fecha de Expiración</label>
              <input type="datetime-local" class="form-control" name="expiration_date" value="<?php echo $edit_data && $edit_data['ExpirationDate'] ? date('Y-m-d\TH:i', strtotime($edit_data['ExpirationDate'])) : ''; ?>">
              <small class="text-muted">Opcional - Dejar vacío si no expira</small>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" name="status">
                <option value="1" <?php echo (!$edit_data || $edit_data['Status'] == 1) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo ($edit_data && $edit_data['Status'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
              </select>
            </div>
            
            <?php if ($edit_data): ?>
            <div class="col-md-6">
              <label class="form-label">Fecha de Creación</label>
              <input type="text" class="form-control" value="<?php echo $edit_data['DateCreated'] ? date('Y-m-d H:i:s', strtotime($edit_data['DateCreated'])) : '-'; ?>" readonly>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Fecha de Modificación</label>
              <input type="text" class="form-control" value="<?php echo $edit_data['DateModified'] ? date('Y-m-d H:i:s', strtotime($edit_data['DateModified'])) : '-'; ?>" readonly>
            </div>
            <?php endif; ?>
            
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="masking_config.php" class="btn btn-secondary">Cancelar</a>
            </div>
          </div>
        </form>
        <?php else: ?>
        <!-- Lista con buscador y paginación -->
        <div class="table-responsive">
          <table class="table table-hover" id="maskingTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Reporte</th>
                <th>Usuario</th>
                <th>Propietario</th>
                <th>Nivel</th>
                <th>Fecha Creación</th>
                <th>Fecha Modificación</th>
                <th>Fecha Expiración</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($masking_records)): ?>
              <tr>
                <td colspan="10" class="text-center text-muted">
                  No hay registros de enmascaramiento
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($masking_records as $mask): ?>
              <tr>
                <td><?php echo $mask['MaskingId']; ?></td>
                <td><?php echo $mask['ReportTitle'] ? htmlspecialchars($mask['ReportTitle']) : '<span class="text-muted">-</span>'; ?></td>
                <td><?php echo $mask['UserFullName'] ? htmlspecialchars($mask['UserFullName']) : ($mask['UserName'] ? htmlspecialchars($mask['UserName']) : '<span class="text-muted">-</span>'); ?></td>
                <td><?php echo $mask['OwnerFullName'] ? htmlspecialchars($mask['OwnerFullName']) : ($mask['OwnerName'] ? htmlspecialchars($mask['OwnerName']) : '<span class="text-muted">-</span>'); ?></td>
                <td>
                  <?php
                  $level_labels = ['1' => 'Público', '2' => 'Personal', '3' => 'Sensible', '4' => 'Confidencial'];
                  $level_colors = ['1' => 'success', '2' => 'info', '3' => 'warning', '4' => 'danger'];
                  $level = $mask['Level'] ?? '1';
                  ?>
                  <span class="badge bg-<?php echo $level_colors[$level] ?? 'secondary'; ?>">
                    <?php echo $level . ' - ' . ($level_labels[$level] ?? 'N/A'); ?>
                  </span>
                </td>
                <td><?php echo $mask['DateCreated'] ? date('Y-m-d H:i', strtotime($mask['DateCreated'])) : '<span class="text-muted">-</span>'; ?></td>
                <td><?php echo $mask['DateModified'] ? date('Y-m-d H:i', strtotime($mask['DateModified'])) : '<span class="text-muted">-</span>'; ?></td>
                <td>
                  <?php if ($mask['ExpirationDate']): ?>
                    <?php 
                    $exp_date = strtotime($mask['ExpirationDate']);
                    $now = time();
                    $is_expired = $exp_date < $now;
                    ?>
                    <span class="<?php echo $is_expired ? 'text-danger' : 'text-success'; ?>" title="<?php echo $is_expired ? 'Expirado' : 'Vigente'; ?>">
                      <?php echo date('Y-m-d H:i', $exp_date); ?>
                      <?php if ($is_expired): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-circle"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                      <?php endif; ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-<?php echo ($mask['Status'] == 1) ? 'success' : 'secondary'; ?>">
                    <?php echo ($mask['Status'] == 1) ? 'Activo' : 'Inactivo'; ?>
                  </span>
                </td>
                <td>
                  <a href="masking_config.php?action=edit&id=<?php echo $mask['MaskingId']; ?>" class="btn btn-sm btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </a>
                  <form method="POST" action="masking_config.php" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este registro de enmascaramiento?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="delete_id" value="<?php echo $mask['MaskingId']; ?>">
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
    var table = jQuery('#maskingTable').DataTable({
        "language": {
            "decimal": ",",
            "emptyTable": "No hay registros de enmascaramiento",
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
        "order": [[5, "desc"]], // Ordenar por fecha de creación descendente por defecto
        "responsive": false,
        "scrollX": false,
        "autoWidth": true,
        "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        "columnDefs": [
            {
                "targets": [9], // Columna de acciones
                "orderable": false,
                "searchable": false
            }
        ]
    });
    
    // Actualizar información de resultados
    function updateResultsInfo() {
        var info = table.page.info();
        var total = info.recordsTotal;
        var filtered = info.recordsDisplay;
        var start = info.start + 1;
        var end = info.end;
        
        if (filtered < total) {
            // La información ya está en el footer de DataTables, no necesitamos actualizar manualmente
        }
    }
    
    // Actualizar información cuando cambia la página o el filtro
    table.on('draw', function() {
        updateResultsInfo();
    });
    
    // Actualizar información inicial
    updateResultsInfo();
});
</script>

<?php require_once('footer.php'); ?>
