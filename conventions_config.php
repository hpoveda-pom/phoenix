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
        
        if ($action_post === 'save_convention') {
            $reports_id = !empty($_POST['reports_id']) ? intval($_POST['reports_id']) : null;
            $field_name = trim($_POST['field_name'] ?? '');
            $field_alias = trim($_POST['field_alias'] ?? '');
            $data_type = trim($_POST['data_type'] ?? '');
            $comments = trim($_POST['comments'] ?? '');
            $masking_level = !empty($_POST['masking_level']) ? intval($_POST['masking_level']) : null;
            $status = intval($_POST['status'] ?? 1);
            $convention_id = intval($_POST['convention_id'] ?? 0);
            
            if (empty($field_name)) {
                $message = 'El nombre del campo es obligatorio';
                $message_type = 'danger';
            } else {
                if ($convention_id > 0) {
                    // Actualizar
                    $sql = "UPDATE conventions SET ReportsId = ?, FieldName = ?, FieldAlias = ?, DataType = ?, Comments = ?, MaskingLevel = ?, Status = ? WHERE IdConventions = ?";
                    $stmt = $conn_phoenix->prepare($sql);
                    if ($stmt === false) {
                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                        $message_type = 'danger';
                    } else {
                        $stmt->bind_param('issssiii', $reports_id, $field_name, $field_alias, $data_type, $comments, $masking_level, $status, $convention_id);
                    }
                } else {
                    // Insertar
                    $sql = "INSERT INTO conventions (ReportsId, FieldName, FieldAlias, DataType, Comments, MaskingLevel, Status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn_phoenix->prepare($sql);
                    if ($stmt === false) {
                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                        $message_type = 'danger';
                    } else {
                        $stmt->bind_param('issssii', $reports_id, $field_name, $field_alias, $data_type, $comments, $masking_level, $status);
                    }
                }
                
                if ($stmt && $stmt->execute()) {
                    $message = $convention_id > 0 ? 'Convención actualizada exitosamente' : 'Convención creada exitosamente';
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
                $sql = "DELETE FROM conventions WHERE IdConventions = ?";
                $stmt = $conn_phoenix->prepare($sql);
                if ($stmt === false) {
                    $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                    $message_type = 'danger';
                } else {
                    $stmt->bind_param('i', $delete_id);
                    if ($stmt->execute()) {
                        $message = 'Convención eliminada exitosamente';
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
    $result = $conn_phoenix->query("SELECT * FROM conventions WHERE IdConventions = $id");
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

// Obtener lista de convenciones
$conventions = [];
$result = $conn_phoenix->query("SELECT c.*, r.Title AS ReportTitle FROM conventions c LEFT JOIN reports r ON r.ReportsId = c.ReportsId ORDER BY c.FieldName ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $conventions[] = $row;
    }
}
?>

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="mb-0">Configuración de Convenciones</h5>
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
          <h6 class="mb-0">Convenciones de Campos</h6>
          <?php if ($action === 'list'): ?>
          <a href="conventions_config.php?action=add" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Agregar Convención
          </a>
          <?php endif; ?>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulario -->
        <form method="POST" action="conventions_config.php">
          <input type="hidden" name="action" value="save_convention">
          <?php if ($edit_data): ?>
          <input type="hidden" name="convention_id" value="<?php echo $edit_data['IdConventions']; ?>">
          <?php endif; ?>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Reporte (Opcional)</label>
              <select class="form-select" name="reports_id">
                <option value="">-- Sin reporte específico --</option>
                <?php foreach ($reports as $report): ?>
                <option value="<?php echo $report['ReportsId']; ?>" <?php echo ($edit_data && $edit_data['ReportsId'] == $report['ReportsId']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($report['Title']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Nombre del Campo <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="field_name" value="<?php echo $edit_data ? htmlspecialchars($edit_data['FieldName']) : ''; ?>" required maxlength="25">
              <small class="text-muted">Máximo 25 caracteres</small>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Alias del Campo</label>
              <input type="text" class="form-control" name="field_alias" value="<?php echo $edit_data ? htmlspecialchars($edit_data['FieldAlias']) : ''; ?>" maxlength="150">
              <small class="text-muted">Máximo 150 caracteres</small>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Tipo de Dato</label>
              <select class="form-select" name="data_type">
                <option value="">-- Seleccionar --</option>
                <option value="VARCHAR" <?php echo ($edit_data && $edit_data['DataType'] == 'VARCHAR') ? 'selected' : ''; ?>>VARCHAR</option>
                <option value="INT" <?php echo ($edit_data && $edit_data['DataType'] == 'INT') ? 'selected' : ''; ?>>INT</option>
                <option value="DECIMAL" <?php echo ($edit_data && $edit_data['DataType'] == 'DECIMAL') ? 'selected' : ''; ?>>DECIMAL</option>
                <option value="DATE" <?php echo ($edit_data && $edit_data['DataType'] == 'DATE') ? 'selected' : ''; ?>>DATE</option>
                <option value="DATETIME" <?php echo ($edit_data && $edit_data['DataType'] == 'DATETIME') ? 'selected' : ''; ?>>DATETIME</option>
                <option value="TEXT" <?php echo ($edit_data && $edit_data['DataType'] == 'TEXT') ? 'selected' : ''; ?>>TEXT</option>
                <option value="BOOLEAN" <?php echo ($edit_data && $edit_data['DataType'] == 'BOOLEAN') ? 'selected' : ''; ?>>BOOLEAN</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Nivel de Enmascaramiento</label>
              <select class="form-select" name="masking_level">
                <option value="">-- Sin enmascaramiento --</option>
                <option value="1" <?php echo ($edit_data && $edit_data['MaskingLevel'] == 1) ? 'selected' : ''; ?>>Nivel 1 - Básico</option>
                <option value="2" <?php echo ($edit_data && $edit_data['MaskingLevel'] == 2) ? 'selected' : ''; ?>>Nivel 2 - Medio</option>
                <option value="3" <?php echo ($edit_data && $edit_data['MaskingLevel'] == 3) ? 'selected' : ''; ?>>Nivel 3 - Alto</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" name="status">
                <option value="1" <?php echo (!$edit_data || $edit_data['Status'] == 1) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo ($edit_data && $edit_data['Status'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
              </select>
            </div>
            
            <div class="col-12">
              <label class="form-label">Comentarios</label>
              <textarea class="form-control" name="comments" rows="3" maxlength="255"><?php echo $edit_data ? htmlspecialchars($edit_data['Comments']) : ''; ?></textarea>
              <small class="text-muted">Máximo 255 caracteres</small>
            </div>
            
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="conventions_config.php" class="btn btn-secondary">Cancelar</a>
            </div>
          </div>
        </form>
        <?php else: ?>
        <!-- Lista con buscador y paginación -->
        <div class="table-responsive">
          <table class="table table-hover" id="conventionsTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Reporte</th>
                <th>Nombre Campo</th>
                <th>Alias</th>
                <th>Tipo Dato</th>
                <th>Nivel Enmascaramiento</th>
                <th>Comentarios</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($conventions)): ?>
              <tr>
                <td colspan="9" class="text-center text-muted">
                  No hay convenciones registradas
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($conventions as $conv): ?>
              <tr>
                <td><?php echo $conv['IdConventions']; ?></td>
                <td><?php echo $conv['ReportTitle'] ? htmlspecialchars($conv['ReportTitle']) : '<span class="text-muted">-</span>'; ?></td>
                <td><strong><?php echo htmlspecialchars($conv['FieldName']); ?></strong></td>
                <td><?php echo $conv['FieldAlias'] ? htmlspecialchars($conv['FieldAlias']) : '<span class="text-muted">-</span>'; ?></td>
                <td><?php echo $conv['DataType'] ? htmlspecialchars($conv['DataType']) : '<span class="text-muted">-</span>'; ?></td>
                <td>
                  <?php if ($conv['MaskingLevel']): ?>
                    <span class="badge bg-<?php echo $conv['MaskingLevel'] == 3 ? 'danger' : ($conv['MaskingLevel'] == 2 ? 'warning' : 'info'); ?>">
                      Nivel <?php echo $conv['MaskingLevel']; ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td><?php echo $conv['Comments'] ? htmlspecialchars(substr($conv['Comments'], 0, 50)) . (strlen($conv['Comments']) > 50 ? '...' : '') : '<span class="text-muted">-</span>'; ?></td>
                <td>
                  <span class="badge bg-<?php echo ($conv['Status'] == 1) ? 'success' : 'secondary'; ?>">
                    <?php echo ($conv['Status'] == 1) ? 'Activo' : 'Inactivo'; ?>
                  </span>
                </td>
                <td>
                  <a href="conventions_config.php?action=edit&id=<?php echo $conv['IdConventions']; ?>" class="btn btn-sm btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </a>
                  <form method="POST" action="conventions_config.php" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar esta convención?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="delete_id" value="<?php echo $conv['IdConventions']; ?>">
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
    var table = jQuery('#conventionsTable').DataTable({
        "language": {
            "decimal": ",",
            "emptyTable": "No hay convenciones registradas",
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
        "order": [[2, "asc"]], // Ordenar por nombre de campo por defecto
        "responsive": false,
        "scrollX": false,
        "autoWidth": true,
        "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        "columnDefs": [
            {
                "targets": [8], // Columna de acciones
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
            $('#resultsInfo').text('Mostrando ' + start + ' a ' + end + ' de ' + filtered + ' registros (de ' + total + ' totales)');
        } else {
            $('#resultsInfo').text('Mostrando ' + start + ' a ' + end + ' de ' + total + ' registros');
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
