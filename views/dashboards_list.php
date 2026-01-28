<?php
// Obtener lista de dashboards
$query_list = "SELECT r.*, c.Title as CategoryTitle, u.Fullname as OwnerName 
               FROM reports r 
               LEFT JOIN category c ON c.CategoryId = r.CategoryId 
               LEFT JOIN users u ON u.UsersId = r.UsersId 
               WHERE r.TypeId = 2 
               ORDER BY r.Order ASC, r.Title ASC";
$list_result = class_Recordset(1, $query_list, null, null, null);
$dashboards_list = $list_result['data'] ?? [];
?>

<div class="card mb-3">
    <div class="card-header bg-body-tertiary">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0">Gestión de Dashboards</h5>
            </div>
            <div class="col-auto">
                <a href="?action=edit" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuevo Dashboard
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
        
        <?php if (empty($dashboards_list)): ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <p class="text-muted">No hay dashboards creados aún.</p>
                <a href="?action=edit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Primer Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Dueño</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dashboards_list as $dashboard): ?>
                            <tr>
                                <td><?php echo $dashboard['ReportsId']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($dashboard['Title']); ?></strong>
                                    <?php if (!empty($dashboard['Description'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($dashboard['Description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($dashboard['CategoryTitle'] ?? 'Sin categoría'); ?></td>
                                <td><?php echo htmlspecialchars($dashboard['OwnerName'] ?? 'N/A'); ?></td>
                                <td><?php echo $dashboard['Order']; ?></td>
                                <td>
                                    <?php
                                    $status_labels = [1 => 'Activo', 0 => 'Inactivo', 2 => 'Mantenimiento'];
                                    $status_colors = [1 => 'success', 0 => 'secondary', 2 => 'warning'];
                                    $status_label = $status_labels[$dashboard['Status']] ?? 'Desconocido';
                                    $status_color = $status_colors[$dashboard['Status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $status_color; ?>"><?php echo $status_label; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="reports.php?Id=<?php echo $dashboard['ReportsId']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Ver Dashboard">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=widgets&id=<?php echo $dashboard['ReportsId']; ?>" 
                                           class="btn btn-outline-info" 
                                           title="Gestionar Widgets">
                                            <i class="fas fa-th"></i>
                                        </a>
                                        <a href="?action=edit&id=<?php echo $dashboard['ReportsId']; ?>" 
                                           class="btn btn-outline-warning" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                title="Eliminar"
                                                onclick="confirmDelete(<?php echo $dashboard['ReportsId']; ?>, '<?php echo htmlspecialchars($dashboard['Title'], ENT_QUOTES); ?>')">
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

<!-- Formulario oculto para eliminar -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_dashboard">
    <input type="hidden" name="dashboard_id" id="delete_dashboard_id">
</form>

<script>
function confirmDelete(dashboardId, title) {
    if (confirm('¿Está seguro de eliminar el dashboard "' + title + '"?\n\nEsta acción también eliminará todos los widgets asociados y no se puede deshacer.')) {
        document.getElementById('delete_dashboard_id').value = dashboardId;
        document.getElementById('deleteForm').submit();
    }
}
</script>
