<?php
$is_edit = ($action === 'edit' && $pipeline_data);
$form_title = $is_edit ? 'Editar Pipeline' : 'Crear Nuevo Pipeline';
?>
<div class="card mb-3">
    <div class="card-header bg-body-tertiary">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0"><?php echo $form_title; ?></h5>
                <p class="text-muted small mb-0 mt-1">Configura el reporte de origen y la base de datos destino</p>
            </div>
            <div class="col-auto">
                <a href="pipelines.php" class="btn btn-outline-secondary btn-sm">
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

        <?php if (empty($reports_for_pipeline)): ?>
            <div class="alert alert-warning">
                No hay reportes disponibles. Los pipelines requieren reportes con consulta SQL y conexión asignada.
                <a href="menu_config.php?type=items">Ir a Reportes</a>
            </div>
        <?php elseif (empty($connections_list)): ?>
            <div class="alert alert-warning">
                No hay conexiones configuradas. Agrega al menos una conexión de destino.
                <a href="connections_config.php">Ir a Conexiones</a>
            </div>
        <?php else: ?>
        <form method="POST" action="pipelines.php">
            <input type="hidden" name="action" value="save_pipeline">
            <input type="hidden" name="pipeline_id" value="<?php echo $is_edit ? intval($pipeline_data['PipelinesId']) : 0; ?>">

            <!-- Origen (Reporte) -->
            <div class="mb-4">
                <h6 class="text-primary border-bottom pb-2 mb-3"><i class="fas fa-database me-1"></i> Origen (Reporte)</h6>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="reports_id">Reporte <span class="text-danger">*</span></label>
                        <select class="form-select" id="reports_id" name="reports_id" required>
                            <option value="">Seleccione un reporte...</option>
                            <?php foreach ($reports_for_pipeline as $r): ?>
                                <option value="<?php echo intval($r['ReportsId']); ?>"
                                    data-conn="<?php echo htmlspecialchars($r['ConnSourceTitle'] ?? 'N/A'); ?>"
                                    <?php echo ($is_edit && $pipeline_data['ReportsId'] == $r['ReportsId']) ? 'selected' : ''; ?>>
                                    <?php echo $r['ReportsId']; ?>. <?php echo htmlspecialchars($r['Title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Los datos se obtienen ejecutando la consulta SQL de este reporte</small>
                    </div>
                    <div class="col-md-12">
                        <div id="report-source-info" class="small text-body-secondary py-2" style="display:none;">
                            <i class="fas fa-plug me-1"></i> <strong>Conexión origen:</strong> <span id="report-conn-name">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Destino -->
            <div class="mb-4">
                <h6 class="text-success border-bottom pb-2 mb-3"><i class="fas fa-arrow-right me-1"></i> Destino (Data Warehouse)</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="conn_source">Conexión destino <span class="text-danger">*</span></label>
                        <select class="form-select" id="conn_source" name="conn_source" required>
                            <option value="">Seleccione la conexión...</option>
                            <?php foreach ($connections_list as $c): ?>
                                <option value="<?php echo intval($c['ConnectionId']); ?>"
                                    <?php echo ($is_edit && $pipeline_data['ConnSource'] == $c['ConnectionId']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['Title']); ?> (<?php echo htmlspecialchars($c['Connector'] ?? ''); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Base de datos donde se insertarán los datos (ej. MySQL, ClickHouse)</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="table_source">Tabla destino</label>
                        <input type="text" class="form-control" id="table_source" name="table_source"
                            value="<?php echo htmlspecialchars($pipeline_data['TableSource'] ?? ''); ?>"
                            placeholder="Ej: mi_tabla_staging">
                        <small class="text-muted">Si se deja vacío, se usa el título del reporte en snake_case</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="schema_source">Schema/Base de datos destino</label>
                        <input type="text" class="form-control" id="schema_source" name="schema_source"
                            value="<?php echo htmlspecialchars($pipeline_data['SchemaSource'] ?? ''); ?>"
                            placeholder="Opcional">
                    </div>
                </div>
            </div>

            <!-- Opciones avanzadas -->
            <div class="mb-4">
                <h6 class="text-secondary border-bottom pb-2 mb-3"><i class="fas fa-cog me-1"></i> Opciones</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="table_create">Crear tabla si no existe</label>
                        <select class="form-select" id="table_create" name="table_create">
                            <option value="1" <?php echo (($pipeline_data['TableCreate'] ?? 1) == 1) ? 'selected' : ''; ?>>Sí</option>
                            <option value="0" <?php echo (($pipeline_data['TableCreate'] ?? 1) == 0) ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="table_truncate">Truncar tabla antes de insertar</label>
                        <select class="form-select" id="table_truncate" name="table_truncate">
                            <option value="1" <?php echo (($pipeline_data['TableTruncate'] ?? 1) == 1) ? 'selected' : ''; ?>>Sí</option>
                            <option value="0" <?php echo (($pipeline_data['TableTruncate'] ?? 1) == 0) ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="time_stamp">Agregar created_at/updated_at</label>
                        <select class="form-select" id="time_stamp" name="time_stamp">
                            <option value="0" <?php echo (($pipeline_data['TimeStamp'] ?? 0) == 0) ? 'selected' : ''; ?>>No</option>
                            <option value="1" <?php echo (($pipeline_data['TimeStamp'] ?? 0) == 1) ? 'selected' : ''; ?>>Sí</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="records_alert">Alerta si registros &lt;</label>
                        <input type="number" class="form-control" id="records_alert" name="records_alert"
                            value="<?php echo $pipeline_data['RecordsAlert'] ?? ''; ?>"
                            placeholder="Opcional" min="0">
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="description">Descripción</label>
                        <input type="text" class="form-control" id="description" name="description"
                            value="<?php echo htmlspecialchars($pipeline_data['Description'] ?? ''); ?>"
                            placeholder="Descripción opcional del pipeline">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">Estado</label>
                        <select class="form-select" id="status" name="status">
                            <option value="1" <?php echo (($pipeline_data['Status'] ?? 1) == 1) ? 'selected' : ''; ?>>Activo</option>
                            <option value="0" <?php echo (($pipeline_data['Status'] ?? 1) == 0) ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="pipelines.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar' : 'Crear'; ?> Pipeline
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('reports_id');
    var info = document.getElementById('report-source-info');
    var span = document.getElementById('report-conn-name');
    if (sel && info && span) {
        function updateSourceInfo() {
            var opt = sel.options[sel.selectedIndex];
            if (opt && opt.value) {
                span.textContent = opt.getAttribute('data-conn') || '—';
                info.style.display = 'block';
            } else {
                info.style.display = 'none';
            }
        }
        sel.addEventListener('change', updateSourceInfo);
        updateSourceInfo();
    }
});
</script>
