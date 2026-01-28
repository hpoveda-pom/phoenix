<?php
$is_edit = ($dashboard_id > 0 && $dashboard_data);
$form_title = $is_edit ? 'Editar Dashboard' : 'Crear Nuevo Dashboard';
?>

<div class="card mb-3">
    <div class="card-header bg-body-tertiary">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0"><?php echo $form_title; ?></h5>
            </div>
            <div class="col-auto">
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
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="save_dashboard">
            <input type="hidden" name="dashboard_id" value="<?php echo $dashboard_id; ?>">
            
            <div class="row">
                <!-- Título -->
                <div class="col-md-12 mb-3">
                    <label class="form-label" for="title">Título <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($dashboard_data['Title'] ?? ''); ?>" 
                           required>
                </div>
                
                <!-- Descripción -->
                <div class="col-md-12 mb-3">
                    <label class="form-label" for="description">Descripción</label>
                    <textarea class="form-control" 
                              id="description" 
                              name="description" 
                              rows="3"><?php echo htmlspecialchars($dashboard_data['Description'] ?? ''); ?></textarea>
                </div>
                
                <!-- Categoría -->
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="category_id">Categoría <span class="text-danger">*</span></label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Seleccione una categoría...</option>
                        <?php foreach ($categories as $category): ?>
                            <?php
                            // Obtener subcategorías (grupos) de esta sección
                            $qry_subcategories = "SELECT CategoryId, Title FROM category WHERE Status = 1 AND ParentId = " . intval($category['CategoryId']) . " ORDER BY Title ASC";
                            $subcategories_result = class_Recordset(1, $qry_subcategories, null, null, null);
                            $subcategories = $subcategories_result['data'] ?? [];
                            ?>
                            
                            <!-- Sección (categoría principal) -->
                            <option value="<?php echo $category['CategoryId']; ?>" 
                                    <?php echo (isset($dashboard_data['CategoryId']) && $dashboard_data['CategoryId'] == $category['CategoryId']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['Title']); ?>
                            </option>
                            
                            <!-- Subcategorías (grupos) -->
                            <?php if (!empty($subcategories)): ?>
                                <?php foreach ($subcategories as $subcategory): ?>
                                    <option value="<?php echo $subcategory['CategoryId']; ?>" 
                                            <?php echo (isset($dashboard_data['CategoryId']) && $dashboard_data['CategoryId'] == $subcategory['CategoryId']) ? 'selected' : ''; ?>>
                                        - <?php echo htmlspecialchars($subcategory['Title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Orden -->
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="order">Orden</label>
                    <input type="number" 
                           class="form-control" 
                           id="order" 
                           name="order" 
                           value="<?php echo $dashboard_data['Order'] ?? 0; ?>" 
                           min="0">
                </div>
                
                <!-- Estado -->
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="status">Estado</label>
                    <select class="form-select" id="status" name="status">
                        <?php foreach ($status_options as $label => $value): ?>
                            <option value="<?php echo $value; ?>" 
                                    <?php echo (isset($dashboard_data['Status']) && $dashboard_data['Status'] == $value) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="?" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar' : 'Crear'; ?> Dashboard
                </button>
            </div>
        </form>
        
        <?php if ($is_edit): ?>
            <hr class="my-4">
            <div class="d-grid">
                <a href="?action=widgets&id=<?php echo $dashboard_id; ?>" class="btn btn-info">
                    <i class="fas fa-th"></i> Gestionar Widgets de este Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
