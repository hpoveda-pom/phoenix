<?php
if (!$dashboard_data) {
    echo '<div class="alert alert-danger">Dashboard no encontrado.</div>';
    echo '<a href="?" class="btn btn-secondary">Volver</a>';
    return;
}
?>

<div class="card mb-3">
    <div class="card-header bg-body-tertiary">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0">
                    <i class="fas fa-th"></i> Widgets del Dashboard: 
                    <strong><?php echo htmlspecialchars($dashboard_data['Title']); ?></strong>
                </h5>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#widgetModal">
                    <i class="fas fa-plus"></i> Nuevo Widget
                </button>
                <a href="?" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($widgets)): ?>
            <div class="text-center py-5">
                <i class="fas fa-th fa-3x text-muted mb-3"></i>
                <p class="text-muted">Este dashboard no tiene widgets aún.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#widgetModal">
                    <i class="fas fa-plus"></i> Crear Primer Widget
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Conexión</th>
                            <th>Diseño</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($widgets as $widget): ?>
                            <tr>
                                <td><?php echo $widget['ReportsId']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($widget['Title']); ?></strong>
                                    <?php if (!empty($widget['Description'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($widget['Description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $conn_name = 'N/A';
                                    foreach ($connections as $conn) {
                                        if ($conn['ConnectionId'] == $widget['ConnectionId']) {
                                            $conn_name = htmlspecialchars($conn['Title']);
                                            break;
                                        }
                                    }
                                    echo $conn_name;
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($widget['LayoutGridClass'] ?? 'col'); ?></span>
                                </td>
                                <td><?php echo $widget['Order']; ?></td>
                                <td>
                                    <?php
                                    $status_labels = [1 => 'Activo', 0 => 'Inactivo', 2 => 'Mantenimiento'];
                                    $status_colors = [1 => 'success', 0 => 'secondary', 2 => 'warning'];
                                    $status_label = $status_labels[$widget['Status']] ?? 'Desconocido';
                                    $status_color = $status_colors[$widget['Status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $status_color; ?>"><?php echo $status_label; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" 
                                                class="btn btn-outline-warning" 
                                                title="Editar"
                                                onclick="editWidget(<?php echo htmlspecialchars(json_encode($widget)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                title="Eliminar"
                                                onclick="confirmDeleteWidget(<?php echo $widget['ReportsId']; ?>, '<?php echo htmlspecialchars($widget['Title'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para crear/editar Widget -->
<div class="modal fade" id="widgetModal" tabindex="-1" aria-labelledby="widgetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="widgetModalLabel">Nuevo Widget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="widgetForm">
                    <input type="hidden" name="action" value="save_widget">
                    <input type="hidden" name="parent_id" value="<?php echo $dashboard_id; ?>">
                    <input type="hidden" name="widget_id" id="widget_id">
                    
                    <div class="row">
                        <!-- Título -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="widget_title">Título <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="widget_title" 
                                   name="title" 
                                   required>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="widget_description">Descripción</label>
                            <textarea class="form-control" 
                                      id="widget_description" 
                                      name="description" 
                                      rows="2"></textarea>
                        </div>
                        
                        <!-- Conexión -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="widget_connection_id">Conexión <span class="text-danger">*</span></label>
                            <select class="form-select" id="widget_connection_id" name="connection_id" required>
                                <option value="">Seleccione una conexión...</option>
                                <?php foreach ($connections as $connection): ?>
                                    <option value="<?php echo $connection['ConnectionId']; ?>">
                                        <?php echo htmlspecialchars($connection['Title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Diseño -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="widget_layout_grid_class">Diseño (Tamaño)</label>
                            <select class="form-select" id="widget_layout_grid_class" name="layout_grid_class">
                                <?php foreach ($layout_grid_classes as $label => $value): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?> (<?php echo $value; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Query SQL -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="widget_query">Query SQL <span class="text-danger">*</span></label>
                            <textarea class="form-control font-monospace" 
                                      id="widget_query" 
                                      name="query" 
                                      rows="6" 
                                      placeholder="SELECT campo1, campo2 FROM tabla WHERE condicion"
                                      required></textarea>
                            <small class="text-muted">Escribe la consulta SQL que devolverá los datos para este widget.</small>
                        </div>
                        
                        <!-- Totalizadores -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="widget_total_axis_x">Totalizar Eje X</label>
                            <select class="form-select" id="widget_total_axis_x" name="total_axis_x">
                                <option value="0">Inactivo</option>
                                <option value="1">Activo</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="widget_total_axis_y">Totalizar Eje Y</label>
                            <select class="form-select" id="widget_total_axis_y" name="total_axis_y">
                                <option value="0">Inactivo</option>
                                <option value="1">Activo</option>
                            </select>
                        </div>
                        
                        <!-- Orden y Estado -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="widget_order">Orden</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="widget_order" 
                                   name="order" 
                                   value="0" 
                                   min="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="widget_status">Estado</label>
                            <select class="form-select" id="widget_status" name="status">
                                <?php foreach ($status_options as $label => $value): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $value == 1 ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="widgetForm" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Widget
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminar widget -->
<form id="deleteWidgetForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_widget">
    <input type="hidden" name="widget_id" id="delete_widget_id">
    <input type="hidden" name="parent_id" value="<?php echo $dashboard_id; ?>">
</form>

<script>
function editWidget(widget) {
    document.getElementById('widgetModalLabel').textContent = 'Editar Widget';
    document.getElementById('widget_id').value = widget.ReportsId;
    document.getElementById('widget_title').value = widget.Title || '';
    document.getElementById('widget_description').value = widget.Description || '';
    document.getElementById('widget_connection_id').value = widget.ConnectionId || '';
    document.getElementById('widget_layout_grid_class').value = widget.LayoutGridClass || 'col';
    document.getElementById('widget_query').value = widget.Query || '';
    document.getElementById('widget_total_axis_x').value = widget.TotalAxisX || 0;
    document.getElementById('widget_total_axis_y').value = widget.TotalAxisY || 0;
    document.getElementById('widget_order').value = widget.Order || 0;
    document.getElementById('widget_status').value = widget.Status || 1;
    
    var modal = new bootstrap.Modal(document.getElementById('widgetModal'));
    modal.show();
}

function confirmDeleteWidget(widgetId, title) {
    if (confirm('¿Está seguro de eliminar el widget "' + title + '"?\n\nEsta acción no se puede deshacer.')) {
        document.getElementById('delete_widget_id').value = widgetId;
        document.getElementById('deleteWidgetForm').submit();
    }
}

// Limpiar formulario al cerrar modal
document.getElementById('widgetModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('widgetModalLabel').textContent = 'Nuevo Widget';
    document.getElementById('widgetForm').reset();
    document.getElementById('widget_id').value = '';
});
</script>
