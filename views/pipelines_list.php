<?php
// $pipelines_list y $message, $message_type vienen del controlador
?>
<div class="card mb-3">
    <div class="card-header bg-body-tertiary">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0">Gestión de Pipelines</h5>
                <p class="text-muted small mb-0 mt-1">Ejecuta los pipelines para cargar datos del reporte al destino configurado</p>
            </div>
            <div class="col-auto">
                <a href="pipelines.php?action=add" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Crear Pipeline
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
        
        <?php if (empty($pipelines_list)): ?>
            <div class="text-center py-5">
                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                <p class="text-muted">No hay pipelines configurados o activos.</p>
                <p class="text-muted small">Crea un pipeline seleccionando el reporte de origen y la base de datos destino.</p>
                <a href="pipelines.php?action=add" class="btn btn-primary mt-2">
                    <i class="fas fa-plus"></i> Crear Primer Pipeline
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reporte</th>
                            <th>Categoría</th>
                            <th>Destino</th>
                            <th>Tabla destino</th>
                            <th>Última ejecución</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pipelines_list as $pipeline): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $pipeline['ReportsId']; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pipeline['ReportTitle']); ?></strong>
                                    <?php if (!empty($pipeline['Description'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($pipeline['Description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($pipeline['CategoryTitle'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($pipeline['ConnDestTitle'] ?? 'Conexión #'.$pipeline['ConnSource']); ?></td>
                                <td><code><?php echo htmlspecialchars($pipeline['TableSource'] ?? '—'); ?></code></td>
                                <td>
                                    <?php if (!empty($pipeline['LastExecution'])): ?>
                                        <span title="<?php echo htmlspecialchars($pipeline['LastExecution']); ?>">
                                            <?php 
                                            $exec = strtotime($pipeline['LastExecution']);
                                            echo date('d/m/Y H:i', $exec);
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Nunca</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="data.php?action=pipeline&Id=<?php echo intval($pipeline['ReportsId']); ?>&PipelinesId=<?php echo intval($pipeline['PipelinesId']); ?>" 
                                           class="btn btn-primary" 
                                           title="Ejecutar pipeline (verás el resultado aquí)">
                                            <i class="fas fa-play"></i> Ejecutar
                                        </a>
                                        <a href="pipelines.php?action=edit&id=<?php echo intval($pipeline['PipelinesId']); ?>" 
                                           class="btn btn-outline-warning" 
                                           title="Editar pipeline">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="reports.php?Id=<?php echo intval($pipeline['ReportsId']); ?>" 
                                           class="btn btn-outline-secondary" 
                                           title="Ver reporte">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
