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
        
        if ($action_post === 'save_user') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $users_type = intval($_POST['users_type'] ?? 1);
            $status = intval($_POST['status'] ?? 1);
            $user_id = intval($_POST['user_id'] ?? 0);
            $is_own_profile = ($user_id > 0 && $user_id == $UsersId);
            
            if (empty($username) || empty($full_name)) {
                $message = 'El nombre de usuario y el nombre completo son obligatorios';
                $message_type = 'danger';
            } elseif ($user_id == 0 && empty($password)) {
                $message = 'La contraseña es obligatoria al crear un nuevo usuario';
                $message_type = 'danger';
            } else {
                // Si es el propio perfil, no permitir cambiar el username
                if ($is_own_profile) {
                    // Obtener el username original
                    $original_result = $conn_phoenix->query("SELECT Username FROM users WHERE UsersId = $user_id");
                    if ($original_result && $original_result->num_rows > 0) {
                        $original_row = $original_result->fetch_assoc();
                        $username = $original_row['Username'];
                    }
                    // No permitir cambiar el tipo de usuario ni el estado en el propio perfil
                    $original_user_result = $conn_phoenix->query("SELECT UsersType, Status FROM users WHERE UsersId = $user_id");
                    if ($original_user_result && $original_user_result->num_rows > 0) {
                        $original_user_row = $original_user_result->fetch_assoc();
                        $users_type = $original_user_row['UsersType'];
                        $status = $original_user_row['Status'];
                    }
                } else {
                    // Verificar si el username ya existe (excepto si es el mismo usuario)
                    $check_sql = "SELECT UsersId FROM users WHERE Username = ? AND UsersId != ?";
                    $check_stmt = $conn_phoenix->prepare($check_sql);
                    $check_stmt->bind_param('si', $username, $user_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $message = 'El nombre de usuario ya existe';
                        $message_type = 'danger';
                        $check_stmt->close();
                        $check_stmt = null;
                    } else {
                        $check_stmt->close();
                    }
                }
                
                // Procesar imagen del avatar
                $avatar_path = null;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    // Usar ruta absoluta basada en el directorio del script
                    $base_dir = dirname(__FILE__);
                    $upload_dir_relative = 'assets/images/avatars/';
                    $upload_dir_absolute = $base_dir . '/' . $upload_dir_relative;
                    
                    // Crear directorio si no existe con permisos correctos
                    if (!file_exists($upload_dir_absolute)) {
                        if (!@mkdir($upload_dir_absolute, 0755, true)) {
                            $message = 'Error al crear el directorio de avatares. Verifique los permisos del servidor.';
                            $message_type = 'danger';
                        }
                    }
                    
                    // Verificar que el directorio sea escribible
                    if (file_exists($upload_dir_absolute) && !is_writable($upload_dir_absolute)) {
                        // Intentar cambiar permisos
                        @chmod($upload_dir_absolute, 0755);
                        if (!is_writable($upload_dir_absolute)) {
                            $message = 'El directorio de avatares no tiene permisos de escritura. Contacte al administrador del servidor.';
                            $message_type = 'danger';
                        }
                    }
                    
                    // Solo continuar si no hay errores de permisos
                    if (empty($message)) {
                        $file = $_FILES['avatar'];
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        $max_size = 5 * 1024 * 1024; // 5MB
                        
                        if (!in_array($file['type'], $allowed_types)) {
                            $message = 'Tipo de archivo no permitido. Solo se permiten imágenes (JPG, PNG, GIF, WEBP)';
                            $message_type = 'danger';
                        } elseif ($file['size'] > $max_size) {
                            $message = 'El archivo es demasiado grande. Máximo 5MB';
                            $message_type = 'danger';
                        } else {
                            // Generar nombre único
                            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $new_filename = 'avatar_' . ($user_id > 0 ? $user_id : 'new') . '_' . time() . '.' . $extension;
                            $target_path_absolute = $upload_dir_absolute . $new_filename;
                            
                            // Ruta relativa para guardar en la base de datos
                            $avatar_relative_path = $upload_dir_relative . $new_filename;
                            
                            // Mover archivo y redimensionar si GD está disponible
                            if (@move_uploaded_file($file['tmp_name'], $target_path_absolute)) {
                                // Verificar si GD está disponible para redimensionar
                                $gd_available = extension_loaded('gd') && function_exists('imagecreatefromjpeg');
                                
                                if ($gd_available) {
                                // Redimensionar a 200x200 manteniendo proporción
                                $image_info = getimagesize($target_path_absolute);
                                if ($image_info) {
                                    $width = $image_info[0];
                                    $height = $image_info[1];
                                    $type = $image_info[2];
                                    
                                    // Crear imagen desde archivo
                                    $source = false;
                                    switch ($type) {
                                        case IMAGETYPE_JPEG:
                                            if (function_exists('imagecreatefromjpeg')) {
                                                $source = imagecreatefromjpeg($target_path_absolute);
                                            }
                                            break;
                                        case IMAGETYPE_PNG:
                                            if (function_exists('imagecreatefrompng')) {
                                                $source = imagecreatefrompng($target_path_absolute);
                                            }
                                            break;
                                        case IMAGETYPE_GIF:
                                            if (function_exists('imagecreatefromgif')) {
                                                $source = imagecreatefromgif($target_path_absolute);
                                            }
                                            break;
                                        case IMAGETYPE_WEBP:
                                            if (function_exists('imagecreatefromwebp')) {
                                                $source = imagecreatefromwebp($target_path_absolute);
                                            }
                                            break;
                                    }
                                    
                                    if ($source) {
                                        // Calcular nuevas dimensiones (máximo 200x200, manteniendo proporción)
                                        $max_size = 200;
                                        if ($width > $height) {
                                            $new_width = $max_size;
                                            $new_height = intval(($height * $max_size) / $width);
                                        } else {
                                            $new_height = $max_size;
                                            $new_width = intval(($width * $max_size) / $height);
                                        }
                                        
                                        // Crear imagen redimensionada
                                        $resized = imagecreatetruecolor($new_width, $new_height);
                                        
                                        // Mantener transparencia para PNG y GIF
                                        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                                            imagealphablending($resized, false);
                                            imagesavealpha($resized, true);
                                            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                                            imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
                                        }
                                        
                                        imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                                        
                                        // Guardar imagen optimizada
                                        switch ($type) {
                                            case IMAGETYPE_JPEG:
                                                if (function_exists('imagejpeg')) {
                                                    imagejpeg($resized, $target_path_absolute, 85);
                                                }
                                                break;
                                            case IMAGETYPE_PNG:
                                                if (function_exists('imagepng')) {
                                                    imagepng($resized, $target_path_absolute, 6);
                                                }
                                                break;
                                            case IMAGETYPE_GIF:
                                                if (function_exists('imagegif')) {
                                                    imagegif($resized, $target_path_absolute);
                                                }
                                                break;
                                            case IMAGETYPE_WEBP:
                                                if (function_exists('imagewebp')) {
                                                    imagewebp($resized, $target_path_absolute, 85);
                                                }
                                                break;
                                        }
                                        
                                        imagedestroy($source);
                                        imagedestroy($resized);
                                    }
                                }
                                }
                                // Si GD no está disponible, la imagen se guarda tal cual (sin redimensionar)
                                
                                // Eliminar avatar anterior si existe
                                if ($user_id > 0) {
                                    $old_avatar_result = $conn_phoenix->query("SELECT AvatarImage FROM users WHERE UsersId = $user_id");
                                    if ($old_avatar_result && $old_avatar_result->num_rows > 0) {
                                        $old_avatar_row = $old_avatar_result->fetch_assoc();
                                        if (!empty($old_avatar_row['AvatarImage'])) {
                                            $old_avatar_full_path = $base_dir . '/' . $old_avatar_row['AvatarImage'];
                                            if (file_exists($old_avatar_full_path)) {
                                                @unlink($old_avatar_full_path);
                                            }
                                        }
                                    }
                                }
                                
                                $avatar_path = $avatar_relative_path;
                            } else {
                                $error_msg = error_get_last();
                                $message = 'Error al subir la imagen. ' . ($error_msg ? $error_msg['message'] : 'Verifique los permisos del directorio.');
                                $message_type = 'danger';
                            }
                        }
                    }
                } elseif ($user_id > 0 && !isset($_FILES['avatar'])) {
                    // Si no se sube nueva imagen, mantener la existente
                    $old_avatar_result = $conn_phoenix->query("SELECT AvatarImage FROM users WHERE UsersId = $user_id");
                    if ($old_avatar_result && $old_avatar_result->num_rows > 0) {
                        $old_avatar_row = $old_avatar_result->fetch_assoc();
                        $avatar_path = $old_avatar_row['AvatarImage'] ?? null;
                    }
                }
                
                if (!isset($check_stmt) || $check_stmt === null || ($check_result && $check_result->num_rows == 0)) {
                    if ($user_id > 0) {
                        // Actualizar
                        if (!empty($password)) {
                            // Si se proporciona una nueva contraseña, actualizarla
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            if ($avatar_path !== null) {
                                // Verificar si la columna AvatarImage existe
                                $check_column = $conn_phoenix->query("SHOW COLUMNS FROM users LIKE 'AvatarImage'");
                                if ($check_column->num_rows == 0) {
                                    // Agregar columna si no existe
                                    $conn_phoenix->query("ALTER TABLE users ADD COLUMN AvatarImage VARCHAR(255) NULL AFTER Email");
                                }
                                if ($is_own_profile) {
                                    // Si es el propio perfil, no actualizar UsersType ni Status
                                    $sql = "UPDATE users SET Password = ?, FullName = ?, Email = ?, AvatarImage = ?, LastPasswordChanged = NOW() WHERE UsersId = ?";
                                    $stmt = $conn_phoenix->prepare($sql);
                                    if ($stmt === false) {
                                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                        $message_type = 'danger';
                                    } else {
                                        $stmt->bind_param('ssssi', $hashed_password, $full_name, $email, $avatar_path, $user_id);
                                    }
                                } else {
                                    $sql = "UPDATE users SET Password = ?, FullName = ?, Email = ?, AvatarImage = ?, UsersType = ?, Status = ?, LastPasswordChanged = NOW(), ModifiedBy = ? WHERE UsersId = ?";
                                    $stmt = $conn_phoenix->prepare($sql);
                                    if ($stmt === false) {
                                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                        $message_type = 'danger';
                                    } else {
                                        $stmt->bind_param('sssssiii', $hashed_password, $full_name, $email, $avatar_path, $users_type, $status, $UsersId, $user_id);
                                    }
                                }
                            } else {
                                if ($is_own_profile) {
                                    // Si es el propio perfil, no actualizar UsersType ni Status
                                    $sql = "UPDATE users SET Password = ?, FullName = ?, Email = ?, LastPasswordChanged = NOW() WHERE UsersId = ?";
                                    $stmt = $conn_phoenix->prepare($sql);
                                    if ($stmt === false) {
                                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                        $message_type = 'danger';
                                    } else {
                                        $stmt->bind_param('sssi', $hashed_password, $full_name, $email, $user_id);
                                    }
                                } else {
                                    $sql = "UPDATE users SET Password = ?, FullName = ?, Email = ?, UsersType = ?, Status = ?, LastPasswordChanged = NOW(), ModifiedBy = ? WHERE UsersId = ?";
                                    $stmt = $conn_phoenix->prepare($sql);
                                    if ($stmt === false) {
                                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                        $message_type = 'danger';
                                    } else {
                                        $stmt->bind_param('sssiiii', $hashed_password, $full_name, $email, $users_type, $status, $UsersId, $user_id);
                                    }
                                }
                            }
                        } else {
                            // Si no se proporciona contraseña, no actualizarla
                            if ($avatar_path !== null) {
                                $check_column = $conn_phoenix->query("SHOW COLUMNS FROM users LIKE 'AvatarImage'");
                                if ($check_column->num_rows == 0) {
                                    $conn_phoenix->query("ALTER TABLE users ADD COLUMN AvatarImage VARCHAR(255) NULL AFTER Email");
                                }
                                if ($is_own_profile) {
                                    // Si es el propio perfil, no actualizar UsersType ni Status
                                    $sql = "UPDATE users SET FullName = ?, Email = ?, AvatarImage = ? WHERE UsersId = ?";
                                    $stmt = $conn_phoenix->prepare($sql);
                                    if ($stmt === false) {
                                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                        $message_type = 'danger';
                                    } else {
                                        $stmt->bind_param('sssi', $full_name, $email, $avatar_path, $user_id);
                                    }
                                } else {
                                    $sql = "UPDATE users SET FullName = ?, Email = ?, AvatarImage = ?, UsersType = ?, Status = ?, ModifiedBy = ? WHERE UsersId = ?";
                                    $stmt = $conn_phoenix->prepare($sql);
                                    if ($stmt === false) {
                                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                        $message_type = 'danger';
                                    } else {
                                        $stmt->bind_param('sssiiii', $full_name, $email, $avatar_path, $users_type, $status, $UsersId, $user_id);
                                    }
                                }
                            } else {
                                if ($is_own_profile) {
                                    // Si es el propio perfil, no actualizar UsersType ni Status
                                    $sql = "UPDATE users SET FullName = ?, Email = ? WHERE UsersId = ?";
                                    $stmt = $conn_phoenix->prepare($sql);
                                    if ($stmt === false) {
                                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                        $message_type = 'danger';
                                    } else {
                                        $stmt->bind_param('ssi', $full_name, $email, $user_id);
                                    }
                                } else {
                                    $sql = "UPDATE users SET FullName = ?, Email = ?, UsersType = ?, Status = ?, ModifiedBy = ? WHERE UsersId = ?";
                                    $stmt = $conn_phoenix->prepare($sql);
                                    if ($stmt === false) {
                                        $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                        $message_type = 'danger';
                                    } else {
                                        $stmt->bind_param('ssiiii', $full_name, $email, $users_type, $status, $UsersId, $user_id);
                                    }
                                }
                            }
                        }
                    } else {
                        // Insertar
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $check_column = $conn_phoenix->query("SHOW COLUMNS FROM users LIKE 'AvatarImage'");
                        if ($check_column->num_rows == 0) {
                            $conn_phoenix->query("ALTER TABLE users ADD COLUMN AvatarImage VARCHAR(255) NULL AFTER Email");
                        }
                        if ($avatar_path !== null) {
                            $sql = "INSERT INTO users (Username, Password, FullName, Email, AvatarImage, UsersType, Status, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $conn_phoenix->prepare($sql);
                            if ($stmt === false) {
                                $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                $message_type = 'danger';
                            } else {
                                $stmt->bind_param('sssssiii', $username, $hashed_password, $full_name, $email, $avatar_path, $users_type, $status, $UsersId);
                            }
                        } else {
                            $sql = "INSERT INTO users (Username, Password, FullName, Email, UsersType, Status, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $conn_phoenix->prepare($sql);
                            if ($stmt === false) {
                                $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                $message_type = 'danger';
                            } else {
                                $stmt->bind_param('ssssiii', $username, $hashed_password, $full_name, $email, $users_type, $status, $UsersId);
                            }
                        }
                    }
                    
                    if ($stmt && $stmt->execute()) {
                        $message = $user_id > 0 ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente';
                        $message_type = 'success';
                        if ($is_own_profile) {
                            // Si es el propio perfil, recargar la sesión para actualizar el avatar
                            $_SESSION['avatar_updated'] = true;
                        }
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
                // No permitir eliminar el propio usuario
                if ($delete_id == $UsersId) {
                    $message = 'No se puede eliminar su propio usuario';
                    $message_type = 'danger';
                } else {
                    // Verificar si tiene reportes asociados
                    $check = $conn_phoenix->query("SELECT COUNT(*) as count FROM reports WHERE UsersId = $delete_id");
                    if ($check) {
                        $row = $check->fetch_assoc();
                        if ($row['count'] > 0) {
                            $message = 'No se puede eliminar: el usuario tiene reportes asociados';
                            $message_type = 'danger';
                        } else {
                            $sql = "DELETE FROM users WHERE UsersId = ?";
                            $stmt = $conn_phoenix->prepare($sql);
                            if ($stmt === false) {
                                $message = 'Error al preparar la consulta: ' . $conn_phoenix->error;
                                $message_type = 'danger';
                            } else {
                                $stmt->bind_param('i', $delete_id);
                                if ($stmt->execute()) {
                                    $message = 'Usuario eliminado exitosamente';
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
}

// Obtener datos para edición
$edit_data = null;
if ($action === 'edit' && $id > 0) {
    $result = $conn_phoenix->query("SELECT * FROM users WHERE UsersId = $id");
    if ($result && $result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

// Obtener lista de usuarios
$users = [];
$result = $conn_phoenix->query("SELECT u.*, 
    (SELECT Username FROM users WHERE UsersId = u.CreatedBy) AS CreatedByName,
    (SELECT Username FROM users WHERE UsersId = u.ModifiedBy) AS ModifiedByName
    FROM users u ORDER BY u.FullName ASC, u.Username ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="mb-0">Configuración de Usuarios</h5>
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
          <h6 class="mb-0">Usuarios del Sistema</h6>
          <?php if ($action === 'list'): ?>
          <a href="users_config.php?action=add" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Agregar Usuario
          </a>
          <?php endif; ?>
        </div>
        
        <?php 
        $is_own_profile = ($edit_data && $edit_data['UsersId'] == $UsersId);
        ?>
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulario -->
        <form method="POST" action="users_config.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_user">
          <?php if ($edit_data): ?>
          <input type="hidden" name="user_id" value="<?php echo $edit_data['UsersId']; ?>">
          <?php endif; ?>
          
          <div class="row g-3">
            <?php if ($is_own_profile): ?>
            <!-- Vista previa del avatar -->
            <div class="col-12 mb-3">
              <label class="form-label">Foto de Perfil</label>
              <div class="d-flex align-items-center">
                <div class="me-3">
                  <?php if (!empty($edit_data['AvatarImage']) && file_exists($edit_data['AvatarImage'])): ?>
                    <img src="<?php echo htmlspecialchars($edit_data['AvatarImage']); ?>" alt="Avatar" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover; border: 2px solid #dee2e6;">
                  <?php else: ?>
                    <img src="assets/images/avatar.webp" alt="Avatar" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover; border: 2px solid #dee2e6;">
                  <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                  <input type="file" class="form-control" name="avatar" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="previewAvatar(this)">
                  <small class="text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo 5MB. La imagen se redimensionará automáticamente a 200x200px.</small>
                </div>
              </div>
            </div>
            <?php endif; ?>
            
            <div class="col-md-6">
              <label class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="username" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Username']) : ''; ?>" <?php echo $is_own_profile ? 'readonly' : 'required'; ?> maxlength="50">
              <small class="text-muted"><?php echo $is_own_profile ? 'No se puede cambiar el nombre de usuario' : 'Máximo 50 caracteres'; ?></small>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Contraseña <?php echo $edit_data ? '' : '<span class="text-danger">*</span>'; ?></label>
              <input type="password" class="form-control" name="password" <?php echo $edit_data ? '' : 'required'; ?>>
              <?php if ($edit_data): ?>
              <small class="text-muted">Dejar vacío para mantener la contraseña actual</small>
              <?php else: ?>
              <small class="text-muted">Mínimo 6 caracteres recomendado</small>
              <?php endif; ?>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="full_name" value="<?php echo $edit_data ? htmlspecialchars($edit_data['FullName']) : ''; ?>" required maxlength="255">
              <small class="text-muted">Máximo 255 caracteres</small>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Correo Electrónico</label>
              <input type="email" class="form-control" name="email" value="<?php echo $edit_data ? htmlspecialchars($edit_data['Email']) : ''; ?>" maxlength="255">
              <small class="text-muted">Máximo 255 caracteres</small>
            </div>
            
            <?php if (!$is_own_profile): ?>
            <div class="col-md-6">
              <label class="form-label">Tipo de Usuario <span class="text-danger">*</span></label>
              <select class="form-select" name="users_type" required>
                <option value="1" <?php echo (!$edit_data || $edit_data['UsersType'] == 1) ? 'selected' : ''; ?>>1 - Administrador</option>
                <option value="2" <?php echo ($edit_data && $edit_data['UsersType'] == 2) ? 'selected' : ''; ?>>2 - Usuario</option>
                <option value="3" <?php echo ($edit_data && $edit_data['UsersType'] == 3) ? 'selected' : ''; ?>>3 - Usuario Limitado</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" name="status">
                <option value="1" <?php echo (!$edit_data || $edit_data['Status'] == 1) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo ($edit_data && $edit_data['Status'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
              </select>
            </div>
            <?php endif; ?>
            
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="users_config.php" class="btn btn-secondary">Cancelar</a>
            </div>
          </div>
        </form>
        <?php else: ?>
        <!-- Lista con buscador y paginación -->
        <div class="table-responsive">
          <table class="table table-hover" id="usersTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Nombre Completo</th>
                <th>Correo</th>
                <th>Tipo</th>
                <th>Fecha Creación</th>
                <th>Última Modificación</th>
                <th>Último Cambio Contraseña</th>
                <th>Último Login</th>
                <th>Creado Por</th>
                <th>Modificado Por</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
              <tr>
                <td colspan="13" class="text-center text-muted">
                  No hay usuarios registrados
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($users as $user): ?>
              <tr>
                <td><?php echo $user['UsersId']; ?></td>
                <td><strong><?php echo htmlspecialchars($user['Username']); ?></strong></td>
                <td><?php echo htmlspecialchars($user['FullName']); ?></td>
                <td><?php echo $user['Email'] ? htmlspecialchars($user['Email']) : '<span class="text-muted">-</span>'; ?></td>
                <td>
                  <?php
                  $type_labels = [1 => 'Administrador', 2 => 'Usuario', 3 => 'Usuario Limitado'];
                  $type_colors = [1 => 'danger', 2 => 'primary', 3 => 'warning'];
                  $type = $user['UsersType'] ?? 1;
                  ?>
                  <span class="badge bg-<?php echo $type_colors[$type] ?? 'secondary'; ?>">
                    <?php echo $type . ' - ' . ($type_labels[$type] ?? 'N/A'); ?>
                  </span>
                </td>
                <td><?php echo $user['CreateDate'] ? date('Y-m-d H:i', strtotime($user['CreateDate'])) : '<span class="text-muted">-</span>'; ?></td>
                <td><?php echo $user['LasModify'] ? date('Y-m-d H:i', strtotime($user['LasModify'])) : '<span class="text-muted">-</span>'; ?></td>
                <td><?php echo $user['LastPasswordChanged'] ? date('Y-m-d H:i', strtotime($user['LastPasswordChanged'])) : '<span class="text-muted">-</span>'; ?></td>
                <td><?php echo $user['LastLogin'] ? date('Y-m-d H:i', strtotime($user['LastLogin'])) : '<span class="text-muted">Nunca</span>'; ?></td>
                <td><?php echo isset($user['CreatedByName']) && $user['CreatedByName'] ? htmlspecialchars($user['CreatedByName']) : '<span class="text-muted">-</span>'; ?></td>
                <td><?php echo isset($user['ModifiedByName']) && $user['ModifiedByName'] ? htmlspecialchars($user['ModifiedByName']) : '<span class="text-muted">-</span>'; ?></td>
                <td>
                  <span class="badge bg-<?php echo ($user['Status'] == 1) ? 'success' : 'secondary'; ?>">
                    <?php echo ($user['Status'] == 1) ? 'Activo' : 'Inactivo'; ?>
                  </span>
                </td>
                <td>
                  <a href="users_config.php?action=edit&id=<?php echo $user['UsersId']; ?>" class="btn btn-sm btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </a>
                  <?php if ($user['UsersId'] != $UsersId): ?>
                  <form method="POST" action="users_config.php" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este usuario?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="delete_id" value="<?php echo $user['UsersId']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </form>
                  <?php endif; ?>
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
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = input.closest('.d-flex').querySelector('img');
            if (preview) {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Esperar a que jQuery esté disponible
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no está disponible');
        return;
    }
    
    // Inicializar DataTables
    var table = jQuery('#usersTable').DataTable({
        "language": {
            "decimal": ",",
            "emptyTable": "No hay usuarios registrados",
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
        "order": [[2, "asc"]], // Ordenar por nombre completo por defecto
        "responsive": false,
        "scrollX": false,
        "autoWidth": true,
        "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        "columnDefs": [
            {
                "targets": [12], // Columna de acciones
                "orderable": false,
                "searchable": false
            }
        ]
    });
});
</script>

<?php require_once('footer.php'); ?>
