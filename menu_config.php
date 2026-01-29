<?php
require_once('header.php');
require_once('models/class_reportparams.php');

$type = isset($_GET['type']) ? $_GET['type'] : 'sections';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action_post = $_POST['action'];
        
        if ($action_post === 'save_section') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $order = intval($_POST['order'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            $section_id = intval($_POST['section_id'] ?? 0);
            
            if (empty($title)) {
                $message = 'El título es obligatorio';
                $message_type = 'danger';
            } else {
                if ($section_id > 0) {
                    // Actualizar
                    $sql = "UPDATE category SET Title = ?, Description = ?, `Order` = ?, Status = ? WHERE CategoryId = ? AND ParentId IS NULL AND IdType = 1";
                    $stmt = $conn_phoenix->prepare($sql);
                    $stmt->bind_param('ssiii', $title, $description, $order, $status, $section_id);
                } else {
                    // Insertar
                    $sql = "INSERT INTO category (Title, Description, `Order`, IdType, Status, ParentId) VALUES (?, ?, ?, 1, ?, NULL)";
                    $stmt = $conn_phoenix->prepare($sql);
                    $stmt->bind_param('ssii', $title, $description, $order, $status);
                }
                
                if ($stmt->execute()) {
                    $message = $section_id > 0 ? 'Sección actualizada exitosamente' : 'Sección creada exitosamente';
                    $message_type = 'success';
                    $action = 'list';
                } else {
                    $message = 'Error al guardar: ' . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
        
        elseif ($action_post === 'save_group') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $parent_id = intval($_POST['parent_id'] ?? 0);
            $order = intval($_POST['order'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            $group_id = intval($_POST['group_id'] ?? 0);
            
            if (empty($title) || $parent_id == 0) {
                $message = 'El título y la sección son obligatorios';
                $message_type = 'danger';
            } else {
                if ($group_id > 0) {
                    // Actualizar
                    $sql = "UPDATE category SET Title = ?, Description = ?, ParentId = ?, `Order` = ?, Status = ? WHERE CategoryId = ? AND IdType = 1";
                    $stmt = $conn_phoenix->prepare($sql);
                    $stmt->bind_param('ssiiii', $title, $description, $parent_id, $order, $status, $group_id);
                } else {
                    // Insertar
                    $sql = "INSERT INTO category (Title, Description, ParentId, `Order`, IdType, Status) VALUES (?, ?, ?, ?, 1, ?)";
                    $stmt = $conn_phoenix->prepare($sql);
                    $stmt->bind_param('ssiii', $title, $description, $parent_id, $order, $status);
                }
                
                if ($stmt->execute()) {
                    $message = $group_id > 0 ? 'Grupo actualizado exitosamente' : 'Grupo creado exitosamente';
                    $message_type = 'success';
                    $action = 'list';
                } else {
                    $message = 'Error al guardar: ' . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
        
        elseif ($action_post === 'save_item') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category_id = intval($_POST['category_id'] ?? 0);
            $order = intval($_POST['order'] ?? 0);
            $type_id = intval($_POST['type_id'] ?? 1);
            $connection_id = intval($_POST['connection_id'] ?? 0);
            $query = trim($_POST['query'] ?? '');
            $query_id = !empty($_POST['query_id']) ? intval($_POST['query_id']) : 0;
            $version = trim($_POST['version'] ?? '');
            $layout_grid_class = trim($_POST['layout_grid_class'] ?? '');
            $periodic = trim($_POST['periodic'] ?? '');
            $convention_status = intval($_POST['convention_status'] ?? 1);
            $masking_status = intval($_POST['masking_status'] ?? 1);
            $status = intval($_POST['status'] ?? 1);
            $total_axis_x = !empty($_POST['total_axis_x']) ? intval($_POST['total_axis_x']) : 0;
            $total_axis_y = !empty($_POST['total_axis_y']) ? intval($_POST['total_axis_y']) : 0;
            $pipelines_id = !empty($_POST['pipelines_id']) ? intval($_POST['pipelines_id']) : 0;
            $item_id = intval($_POST['item_id'] ?? 0);
            
            if (empty($title) || $category_id == 0) {
                $message = 'El título y la categoría son obligatorios';
                $message_type = 'danger';
            } else {
                // Para campos opcionales, usar 0 en lugar de NULL para evitar problemas con bind_param
                // La base de datos puede manejar 0 como valor por defecto o podemos usar COALESCE
                $query_id_final = ($query_id == 0) ? 0 : $query_id;
                $total_axis_x_final = ($total_axis_x == 0) ? 0 : $total_axis_x;
                $total_axis_y_final = ($total_axis_y == 0) ? 0 : $total_axis_y;
                $pipelines_id_final = ($pipelines_id == 0) ? 0 : $pipelines_id;
                
                if ($item_id > 0) {
                    // Actualizar - usar NULLIF para convertir 0 a NULL en la base de datos
                    $sql = "UPDATE reports SET Title = ?, Description = ?, CategoryId = ?, `Order` = ?, TypeId = ?, ConnectionId = ?, Query = ?, QueryId = NULLIF(?, 0), Version = ?, LayoutGridClass = ?, Periodic = ?, ConventionStatus = ?, MaskingStatus = ?, Status = ?, TotalAxisX = NULLIF(?, 0), TotalAxisY = NULLIF(?, 0), PipelinesId = NULLIF(?, 0), UserUpdated = ? WHERE ReportsId = ?";
                    $stmt = $conn_phoenix->prepare($sql);
                    if ($stmt === false) {
                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                        $message_type = 'danger';
                    } else {
                        $stmt->bind_param('ssiiiiisssssiiiiiii', $title, $description, $category_id, $order, $type_id, $connection_id, $query, $query_id_final, $version, $layout_grid_class, $periodic, $convention_status, $masking_status, $status, $total_axis_x_final, $total_axis_y_final, $pipelines_id_final, $UsersId, $item_id);
                        if ($stmt->execute()) {
                            // Limpiar el caché del reporte si se actualizó el query
                            if (!empty($query)) {
                                ReportParams::clearCache($item_id);
                            }
                            $message = 'Item actualizado exitosamente';
                            $message_type = 'success';
                            $action = 'list';
                        } else {
                            $message = 'Error al guardar: ' . $stmt->error;
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    }
                } else {
                    // Insertar - usar NULLIF para convertir 0 a NULL en la base de datos
                    // 18 parámetros: Title(s), Description(s), CategoryId(i), Order(i), TypeId(i), UsersId(i), ConnectionId(i), Query(s), QueryId(i), Version(s), LayoutGridClass(s), Periodic(s), ConventionStatus(i), MaskingStatus(i), Status(i), TotalAxisX(i), TotalAxisY(i), PipelinesId(i)
                    $sql = "INSERT INTO reports (Title, Description, CategoryId, `Order`, TypeId, UsersId, ConnectionId, Query, QueryId, Version, LayoutGridClass, Periodic, ConventionStatus, MaskingStatus, Status, TotalAxisX, TotalAxisY, PipelinesId, ParentId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, NULLIF(?, 0), NULLIF(?, 0), NULLIF(?, 0), 0)";
                    $stmt = $conn_phoenix->prepare($sql);
                    if ($stmt === false) {
                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                        $message_type = 'danger';
                    } else {
                        // 18 parámetros: s,s,i,i,i,i,i,s,i,s,s,s,i,i,i,i,i,i
                        $stmt->bind_param('ssiiiiisissiiiiiii', $title, $description, $category_id, $order, $type_id, $UsersId, $connection_id, $query, $query_id_final, $version, $layout_grid_class, $periodic, $convention_status, $masking_status, $status, $total_axis_x_final, $total_axis_y_final, $pipelines_id_final);
                        if ($stmt->execute()) {
                            $message = 'Item creado exitosamente';
                            $message_type = 'success';
                            $action = 'list';
                        } else {
                            $message = 'Error al guardar: ' . $stmt->error;
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    }
                }
            }
        }
        
        elseif ($action_post === 'copy_item') {
            $copy_id = intval($_POST['copy_id'] ?? 0);
            
            if ($copy_id > 0) {
                // Obtener el reporte original
                $result = $conn_phoenix->query("SELECT * FROM reports WHERE ReportsId = $copy_id");
                if ($result && $result->num_rows > 0) {
                    $original = $result->fetch_assoc();
                    
                    // Crear título con "- copia"
                    $new_title = trim($original['Title']) . ' - copia';
                    
                    // Insertar el reporte clonado
                    // Preparar valores, usando 0 para campos opcionales que pueden ser NULL
                    $query_id_final = !empty($original['QueryId']) ? intval($original['QueryId']) : 0;
                    $total_axis_x_final = !empty($original['TotalAxisX']) ? intval($original['TotalAxisX']) : 0;
                    $total_axis_y_final = !empty($original['TotalAxisY']) ? intval($original['TotalAxisY']) : 0;
                    $pipelines_id_final = !empty($original['PipelinesId']) ? intval($original['PipelinesId']) : 0;
                    
                    $sql = "INSERT INTO reports (Title, Description, CategoryId, `Order`, TypeId, UsersId, ConnectionId, Query, QueryId, Version, LayoutGridClass, Periodic, ConventionStatus, MaskingStatus, Status, TotalAxisX, TotalAxisY, PipelinesId, ParentId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, NULLIF(?, 0), NULLIF(?, 0), NULLIF(?, 0), 0)";
                    $stmt = $conn_phoenix->prepare($sql);
                    if ($stmt === false) {
                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                        $message_type = 'danger';
                    } else {
                        $stmt->bind_param('ssiiiiisissiiiiiii', 
                            $new_title,
                            $original['Description'],
                            $original['CategoryId'],
                            $original['Order'],
                            $original['TypeId'],
                            $UsersId,
                            $original['ConnectionId'],
                            $original['Query'],
                            $query_id_final,
                            $original['Version'],
                            $original['LayoutGridClass'],
                            $original['Periodic'],
                            $original['ConventionStatus'],
                            $original['MaskingStatus'],
                            $original['Status'],
                            $total_axis_x_final,
                            $total_axis_y_final,
                            $pipelines_id_final
                        );
                        
                        if ($stmt->execute()) {
                            $message = 'Reporte copiado exitosamente';
                            $message_type = 'success';
                            $action = 'list';
                        } else {
                            $message = 'Error al copiar: ' . $stmt->error;
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    }
                } else {
                    $message = 'Reporte no encontrado';
                    $message_type = 'danger';
                }
            }
        }
        
        elseif ($action_post === 'delete') {
            $delete_id = intval($_POST['delete_id'] ?? 0);
            $delete_type = $_POST['delete_type'] ?? '';
            
            if ($delete_id > 0) {
                if ($delete_type === 'section') {
                    // Verificar si tiene grupos
                    $check = $conn_phoenix->query("SELECT COUNT(*) as count FROM category WHERE ParentId = $delete_id");
                    $row = $check->fetch_assoc();
                    if ($row['count'] > 0) {
                        $message = 'No se puede eliminar: la sección tiene grupos asociados';
                        $message_type = 'danger';
                    } else {
                        $sql = "DELETE FROM category WHERE CategoryId = ? AND ParentId IS NULL AND IdType = 1";
                        $stmt = $conn_phoenix->prepare($sql);
                        $stmt->bind_param('i', $delete_id);
                        if ($stmt->execute()) {
                            $message = 'Sección eliminada exitosamente';
                            $message_type = 'success';
                        }
                        $stmt->close();
                    }
                } elseif ($delete_type === 'group') {
                    // Verificar si tiene items
                    $check = $conn_phoenix->query("SELECT COUNT(*) as count FROM reports WHERE CategoryId = $delete_id");
                    $row = $check->fetch_assoc();
                    if ($row['count'] > 0) {
                        $message = 'No se puede eliminar: el grupo tiene items asociados';
                        $message_type = 'danger';
                    } else {
                        $sql = "DELETE FROM category WHERE CategoryId = ? AND IdType = 1";
                        $stmt = $conn_phoenix->prepare($sql);
                        $stmt->bind_param('i', $delete_id);
                        if ($stmt->execute()) {
                            $message = 'Grupo eliminado exitosamente';
                            $message_type = 'success';
                        }
                        $stmt->close();
                    }
                } elseif ($delete_type === 'item') {
                    $sql = "DELETE FROM reports WHERE ReportsId = ?";
                    $stmt = $conn_phoenix->prepare($sql);
                    $stmt->bind_param('i', $delete_id);
                    if ($stmt->execute()) {
                        $message = 'Item eliminado exitosamente';
                        $message_type = 'success';
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
    if ($type === 'sections') {
        $result = $conn_phoenix->query("SELECT * FROM category WHERE CategoryId = $id AND ParentId IS NULL AND IdType = 1");
        if ($result->num_rows > 0) {
            $edit_data = $result->fetch_assoc();
        }
    } elseif ($type === 'groups') {
        $result = $conn_phoenix->query("SELECT * FROM category WHERE CategoryId = $id AND IdType = 1 AND ParentId IS NOT NULL");
        if ($result->num_rows > 0) {
            $edit_data = $result->fetch_assoc();
        }
    } elseif ($type === 'items') {
        $result = $conn_phoenix->query("SELECT * FROM reports WHERE ReportsId = $id");
        if ($result->num_rows > 0) {
            $edit_data = $result->fetch_assoc();
        }
    }
}

// Obtener listas
$sections = [];
// Listar secciones del más nuevo al más viejo por defecto
$result = $conn_phoenix->query("SELECT * FROM category WHERE ParentId IS NULL AND IdType = 1 ORDER BY CategoryId DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
}

$groups = [];
// Listar grupos del más nuevo al más viejo por defecto
$result = $conn_phoenix->query("SELECT c.*, p.Title as SectionTitle FROM category c LEFT JOIN category p ON p.CategoryId = c.ParentId WHERE c.ParentId IS NOT NULL AND c.IdType = 1 ORDER BY c.CategoryId DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }
}

$items = [];
// Listar items (reportes) del más nuevo al más viejo por defecto
$result = $conn_phoenix->query("SELECT r.*, c.Title as CategoryTitle, p.Title as SectionTitle FROM reports r LEFT JOIN category c ON c.CategoryId = r.CategoryId LEFT JOIN category p ON p.CategoryId = c.ParentId WHERE r.UsersId = $UsersId ORDER BY r.ReportsId DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Obtener todas las categorías para select (secciones + grupos)
$all_categories = [];
// Traer también el título de la sección (padre) para poder mostrar "SECCIÓN / GRUPO"
$result = $conn_phoenix->query("
    SELECT c.CategoryId, c.Title, c.ParentId, p.Title AS ParentTitle
    FROM category c
    LEFT JOIN category p ON p.CategoryId = c.ParentId
    WHERE c.IdType = 1 AND c.Status = 1
    ORDER BY c.ParentId IS NULL DESC, c.`Order` ASC, c.Title ASC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_categories[] = $row;
    }
}

// Mapa rápido CategoryId -> datos de categoría (para usar también en la grilla de items)
$category_map = [];
foreach ($all_categories as $cat_row) {
    $category_map[$cat_row['CategoryId']] = $cat_row;
}

// Obtener conexiones disponibles
$connections = [];
$result = $conn_phoenix->query("SELECT ConnectionId, Title FROM connections WHERE Status = 1 ORDER BY Title ASC");
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
            <h5 class="mb-0">Configuración del Menú</h5>
          </div>
          <div class="col-auto">
            <ul class="nav nav-pills">
              <li class="nav-item">
                <a class="nav-link <?php echo $type === 'sections' ? 'active' : ''; ?>" href="menu_config.php?type=sections">Secciones</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo $type === 'groups' ? 'active' : ''; ?>" href="menu_config.php?type=groups">Grupos/Carpetas</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo $type === 'items' ? 'active' : ''; ?>" href="menu_config.php?type=items">Items del Menú</a>
              </li>
            </ul>
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
          <h6 class="mb-0">
            <?php 
            if ($type === 'sections') echo 'Secciones del Menú';
            elseif ($type === 'groups') echo 'Grupos/Carpetas del Menú';
            else echo 'Items del Menú';
            ?>
          </h6>
          <?php if ($action === 'list'): ?>
          <a href="menu_config.php?type=<?php echo $type; ?>&action=add" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Agregar <?php echo $type === 'sections' ? 'Sección' : ($type === 'groups' ? 'Grupo' : 'Item'); ?>
          </a>
          <?php endif; ?>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulario -->
        <form method="POST" action="menu_config.php?type=<?php echo $type; ?>">
          <input type="hidden" name="action" value="save_<?php echo $type === 'sections' ? 'section' : ($type === 'groups' ? 'group' : 'item'); ?>">
          <?php if ($edit_data): ?>
          <input type="hidden" name="<?php echo $type === 'sections' ? 'section' : ($type === 'groups' ? 'group' : 'item'); ?>_id" value="<?php echo $edit_data[$type === 'sections' ? 'CategoryId' : ($type === 'groups' ? 'CategoryId' : 'ReportsId')]; ?>">
          <?php endif; ?>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Título <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Title']) : ''; ?>" required>
            </div>
            
            <?php if ($type === 'groups'): ?>
            <div class="col-md-6">
              <label class="form-label">Sección <span class="text-danger">*</span></label>
              <select class="form-select" name="parent_id" required>
                <option value="">Seleccione una sección</option>
                <?php foreach ($sections as $section): ?>
                <option value="<?php echo $section['CategoryId']; ?>" <?php echo ($edit_data && $edit_data['ParentId'] == $section['CategoryId']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($section['Title']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php elseif ($type === 'items'): ?>
            <div class="col-md-6">
              <label class="form-label">Categoría (Grupo) <span class="text-danger">*</span></label>
              <select class="form-select" name="category_id" required>
                <option value="">Seleccione una categoría</option>
                <?php 
                // Detectar si el reporte tiene una sección asignada directamente (sin grupo)
                $current_category_id = $edit_data ? intval($edit_data['CategoryId']) : 0;
                $is_section = false;
                $suggested_group_id = 0;
                
                if ($current_category_id > 0) {
                  // Verificar si el CategoryId actual es una sección (no tiene ParentId)
                  $check_section = $conn_phoenix->query("SELECT ParentId FROM category WHERE CategoryId = $current_category_id AND IdType = 1");
                  if ($check_section && $check_section->num_rows > 0) {
                    $section_row = $check_section->fetch_assoc();
                    if (empty($section_row['ParentId'])) {
                      // Es una sección, buscar el primer grupo hijo
                      $is_section = true;
                      $first_group = $conn_phoenix->query("SELECT CategoryId FROM category WHERE ParentId = $current_category_id AND IdType = 1 AND Status = 1 ORDER BY `Order` ASC, Title ASC LIMIT 1");
                      if ($first_group && $first_group->num_rows > 0) {
                        $group_row = $first_group->fetch_assoc();
                        $suggested_group_id = intval($group_row['CategoryId']);
                      }
                    }
                  }
                }
                
                // Organizar grupos por sección padre
                $groups_by_section = [];
                foreach ($all_categories as $cat) {
                  // Solo procesar grupos (categorías con ParentId)
                  if (empty($cat['ParentId'])) {
                    continue;
                  }
                  
                  $section_id = $cat['ParentId'];
                  $section_title = $cat['ParentTitle'] ?? 'Sin sección';
                  
                  if (!isset($groups_by_section[$section_id])) {
                    $groups_by_section[$section_id] = [
                      'title' => $section_title,
                      'groups' => []
                    ];
                  }
                  
                  $groups_by_section[$section_id]['groups'][] = $cat;
                }
                
                // Ordenar secciones por título
                uasort($groups_by_section, function($a, $b) {
                  return strcmp($a['title'], $b['title']);
                });
                
                // Mostrar grupos agrupados por sección usando optgroup
                foreach ($groups_by_section as $section_id => $section_data):
                  // Ordenar grupos dentro de cada sección
                  usort($section_data['groups'], function($a, $b) {
                    $order_a = intval($a['Order'] ?? 0);
                    $order_b = intval($b['Order'] ?? 0);
                    if ($order_a != $order_b) {
                      return $order_a <=> $order_b;
                    }
                    return strcmp($a['Title'], $b['Title']);
                  });
                ?>
                <optgroup label="<?php echo htmlspecialchars($section_data['title']); ?>">
                  <?php foreach ($section_data['groups'] as $cat): 
                    // Determinar si está seleccionado
                    $is_selected = false;
                    if ($edit_data) {
                      if ($is_section && $suggested_group_id > 0 && $cat['CategoryId'] == $suggested_group_id) {
                        // Si el reporte tiene una sección, preseleccionar el primer grupo hijo
                        $is_selected = true;
                      } elseif (!$is_section && $edit_data['CategoryId'] == $cat['CategoryId']) {
                        // Si el reporte tiene un grupo, seleccionarlo normalmente
                        $is_selected = true;
                      }
                    }
                  ?>
                  <option value="<?php echo $cat['CategoryId']; ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['Title']); ?>
                  </option>
                  <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
              </select>
              <?php if ($is_section && $suggested_group_id > 0): ?>
              <small class="text-warning d-block mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-triangle" style="display: inline; vertical-align: middle;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                Este reporte tiene una sección asignada. Se ha preseleccionado el primer grupo disponible. Por favor, seleccione el grupo correcto y guarde.
              </small>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo</label>
              <select class="form-select" name="type_id">
                <option value="1" <?php echo ($edit_data && $edit_data['TypeId'] == 1) ? 'selected' : ''; ?>>Reporte</option>
                <option value="2" <?php echo ($edit_data && $edit_data['TypeId'] == 2) ? 'selected' : ''; ?>>Dashboard</option>
                <option value="3" <?php echo ($edit_data && $edit_data['TypeId'] == 3) ? 'selected' : ''; ?>>Herramienta</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Conexión</label>
              <select class="form-select" name="connection_id">
                <option value="0">Seleccione una conexión</option>
                <?php foreach ($connections as $conn): ?>
                <option value="<?php echo $conn['ConnectionId']; ?>" <?php echo ($edit_data && $edit_data['ConnectionId'] == $conn['ConnectionId']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($conn['Title']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-6">
              <label class="form-label">Orden</label>
              <input type="number" class="form-control" name="order" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Order']) : '0'; ?>" min="0">
            </div>
            
            <?php if ($type === 'items'): ?>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" name="status">
                <option value="1" <?php echo (!$edit_data || $edit_data['Status'] == 1) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo ($edit_data && $edit_data['Status'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
              </select>
            </div>
            <?php elseif ($type !== 'items'): ?>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" name="status">
                <option value="1" <?php echo (!$edit_data || $edit_data['Status'] == 1) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo ($edit_data && $edit_data['Status'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
              </select>
            </div>
            <?php endif; ?>
            
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" name="description" rows="3"><?php echo $edit_data ? htmlspecialchars($edit_data['Description']) : ''; ?></textarea>
            </div>
            
            <?php if ($type === 'items'): ?>
            <div class="col-12">
              <label class="form-label">Query SQL</label>
              <textarea class="form-control font-monospace" name="query" rows="8" placeholder="SELECT * FROM tabla WHERE..."><?php echo $edit_data ? htmlspecialchars($edit_data['Query']) : ''; ?></textarea>
              <small class="text-muted">Consulta SQL que se ejecutará para obtener los datos del reporte</small>
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Query ID</label>
              <input type="number" class="form-control" name="query_id" value="<?php echo $edit_data && $edit_data['QueryId'] ? htmlspecialchars($edit_data['QueryId']) : ''; ?>" placeholder="Opcional">
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Versión</label>
              <input type="text" class="form-control" name="version" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Version']) : ''; ?>" placeholder="Ej: 1.0" maxlength="5">
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Layout Grid Class</label>
              <input type="text" class="form-control" name="layout_grid_class" value="<?php echo $edit_data ? htmlspecialchars($edit_data['LayoutGridClass']) : ''; ?>" placeholder="Ej: col-md-12">
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Periodicidad</label>
              <input type="text" class="form-control" name="periodic" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Periodic']) : ''; ?>" placeholder="Ej: Diario, Semanal, Mensual">
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Estado de Convenciones</label>
              <select class="form-select" name="convention_status">
                <option value="1" <?php echo (!$edit_data || $edit_data['ConventionStatus'] == 1) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo ($edit_data && $edit_data['ConventionStatus'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
              </select>
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Estado de Enmascaramiento</label>
              <select class="form-select" name="masking_status">
                <option value="1" <?php echo (!$edit_data || $edit_data['MaskingStatus'] == 1) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo ($edit_data && $edit_data['MaskingStatus'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
              </select>
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Total Axis X</label>
              <input type="number" class="form-control" name="total_axis_x" value="<?php echo $edit_data && $edit_data['TotalAxisX'] ? htmlspecialchars($edit_data['TotalAxisX']) : ''; ?>" placeholder="Para gráficos">
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Total Axis Y</label>
              <input type="number" class="form-control" name="total_axis_y" value="<?php echo $edit_data && $edit_data['TotalAxisY'] ? htmlspecialchars($edit_data['TotalAxisY']) : ''; ?>" placeholder="Para gráficos">
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Pipelines ID</label>
              <input type="number" class="form-control" name="pipelines_id" value="<?php echo $edit_data && $edit_data['PipelinesId'] ? htmlspecialchars($edit_data['PipelinesId']) : ''; ?>" placeholder="Opcional">
            </div>
            <?php endif; ?>
            
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="menu_config.php?type=<?php echo $type; ?>" class="btn btn-secondary">Cancelar</a>
            </div>
          </div>
        </form>
        <?php else: ?>
        <!-- Lista con buscador y paginación -->
        <div class="table-responsive">
          <table class="table table-hover" id="menuConfigTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Título</th>
                <?php if ($type === 'groups'): ?>
                <th>Sección</th>
                <?php elseif ($type === 'items'): ?>
                <th>Categoría</th>
                <th>Tipo</th>
                <?php endif; ?>
                <th>Orden</th>
                <?php if ($type !== 'items'): ?>
                <th>Estado</th>
                <?php endif; ?>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $list = $type === 'sections' ? $sections : ($type === 'groups' ? $groups : $items);
              if (empty($list)): 
              ?>
              <tr>
                <td colspan="<?php echo $type === 'items' ? '7' : ($type === 'groups' ? '6' : '5'); ?>" class="text-center text-muted">
                  No hay registros
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($list as $item): ?>
              <tr>
                <td><?php echo $item[$type === 'sections' || $type === 'groups' ? 'CategoryId' : 'ReportsId']; ?></td>
                <td><?php echo htmlspecialchars($item['Title']); ?></td>
                <?php if ($type === 'groups'): ?>
                <td><?php echo htmlspecialchars($item['SectionTitle'] ?? 'N/A'); ?></td>
                <?php elseif ($type === 'items'): ?>
                <td>
                  <?php
                  // Preferir siempre la jerarquía real desde $category_map para mostrar "SECCIÓN / GRUPO"
                  $catLabel = 'N/A';
                  if (!empty($item['CategoryId']) && isset($category_map[$item['CategoryId']])) {
                      $catData = $category_map[$item['CategoryId']];
                      if (!empty($catData['ParentId']) && !empty($catData['ParentTitle'])) {
                          $catLabel = $catData['ParentTitle'] . ' / ' . $catData['Title'];
                      } else {
                          // Categoría sin padre: solo su título
                          $catLabel = $catData['Title'];
                      }
                  } elseif (isset($item['SectionTitle']) || isset($item['CategoryTitle'])) {
                      // Fallback a los alias que vienen del SELECT original por compatibilidad
                      $parts = [];
                      if (!empty($item['SectionTitle'])) {
                          $parts[] = $item['SectionTitle'];
                      }
                      if (!empty($item['CategoryTitle'])) {
                          $parts[] = $item['CategoryTitle'];
                      }
                      if (!empty($parts)) {
                          $catLabel = implode(' / ', $parts);
                      }
                  }
                  echo htmlspecialchars($catLabel);
                  ?>
                </td>
                <td>
                  <?php 
                  $types = [1 => 'Reporte', 2 => 'Dashboard', 3 => 'Herramienta'];
                  echo $types[$item['TypeId']] ?? 'N/A';
                  ?>
                </td>
                <?php endif; ?>
                <td><?php echo htmlspecialchars($item['Order'] ?? '0'); ?></td>
                <?php if ($type !== 'items'): ?>
                <td>
                  <span class="badge bg-<?php echo ($item['Status'] == 1) ? 'success' : 'secondary'; ?>">
                    <?php echo ($item['Status'] == 1) ? 'Activo' : 'Inactivo'; ?>
                  </span>
                </td>
                <?php endif; ?>
                <td>
                  <?php if ($type === 'items'): ?>
                  <!-- Botón de copiar (solo para reportes) -->
                  <form method="POST" action="menu_config.php?type=<?php echo $type; ?>" style="display: inline;" onsubmit="return confirm('¿Está seguro de copiar este reporte?');">
                    <input type="hidden" name="action" value="copy_item">
                    <input type="hidden" name="copy_id" value="<?php echo $item['ReportsId']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-info" title="Copiar reporte">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                  </form>
                  <?php endif; ?>
                  <a href="menu_config.php?type=<?php echo $type; ?>&action=edit&id=<?php echo $item[$type === 'sections' || $type === 'groups' ? 'CategoryId' : 'ReportsId']; ?>" class="btn btn-sm btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </a>
                  <form method="POST" action="menu_config.php?type=<?php echo $type; ?>" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este registro?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="delete_id" value="<?php echo $item[$type === 'sections' || $type === 'groups' ? 'CategoryId' : 'ReportsId']; ?>">
                    <input type="hidden" name="delete_type" value="<?php echo $type === 'sections' ? 'section' : ($type === 'groups' ? 'group' : 'item'); ?>">
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
    var table = jQuery('#menuConfigTable').DataTable({
        "language": {
            "decimal": ",",
            "emptyTable": "No hay registros",
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
    // Orden por defecto en todos los list del CRUD: del más nuevo al más viejo por ID
    "order": [[0, "desc"]],
        "responsive": false,
        "scrollX": false,
        "autoWidth": true,
        "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        "columnDefs": [
            {
                "targets": -1, // Última columna (acciones)
                "orderable": false,
                "searchable": false
            }
        ]
    });
});
</script>

<?php require_once('footer.php'); ?>
