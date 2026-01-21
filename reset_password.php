<?php
require_once('config.php');
require_once('conn/phoenix.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Si el usuario ya está logueado, redirigir
if (isset($_SESSION['UsersId'])) {
    header("Location: index.php");
    exit();
}

$reset_error = '';
$reset_success = '';

// Función para validar la fortaleza de la contraseña
function isValidPassword($password) {
    $minLength = 8;
    $hasUpperCase = preg_match('/[A-Z]/', $password);
    $hasLowerCase = preg_match('/[a-z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecialChar = preg_match('/[\W_]/', $password); // Incluye caracteres especiales

    return strlen($password) >= $minLength && $hasUpperCase && $hasLowerCase && $hasNumber && $hasSpecialChar;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username)) {
        $reset_error = "El nombre de usuario es obligatorio.";
    } elseif (empty($newPassword) || empty($confirmPassword)) {
        $reset_error = "Ambos campos de contraseña son obligatorios.";
    } elseif ($newPassword !== $confirmPassword) {
        $reset_error = "Las contraseñas no coinciden.";
    } elseif (!isValidPassword($newPassword)) {
        $reset_error = "La contraseña debe tener al menos 8 caracteres, incluyendo una letra mayúscula, una letra minúscula, un número y un carácter especial.";
    } else {
        // Verificar que el usuario existe
        $sql = "SELECT UsersId, Username FROM users WHERE Username = ?";
        $stmt = $conn_phoenix->prepare($sql);
        
        if ($stmt === false) {
            $reset_error = "Error al preparar la consulta: " . $conn_phoenix->error;
        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Actualizar la contraseña
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateSql = "UPDATE users SET Password = ?, LastPasswordChanged = NOW() WHERE UsersId = ?";
                $updateStmt = $conn_phoenix->prepare($updateSql);

                if ($updateStmt === false) {
                    $reset_error = "Error al preparar la actualización: " . $conn_phoenix->error;
                } else {
                    $updateStmt->bind_param('si', $hashedPassword, $user['UsersId']);
                    
                    if ($updateStmt->execute()) {
                        $reset_success = "Contraseña restablecida exitosamente. Ahora puedes iniciar sesión.";
                        // Redirigir al login con mensaje de éxito
                        header("Location: login.php?reset_success=" . urlencode($reset_success));
                        exit();
                    } else {
                        $reset_error = "Error al restablecer la contraseña: " . $updateStmt->error;
                    }
                    
                    $updateStmt->close();
                }
            } else {
                $reset_error = "Usuario no encontrado. Verifica el nombre de usuario.";
            }
            
            $stmt->close();
        }
    }
}

$conn_phoenix->close();
?>

<?php require_once('views/template_header.php'); ?>
<body>
  <main class="main" id="top">
    <div class="container-fluid bg-body-tertiary min-vh-100 d-flex align-items-center justify-content-center">
      <div class="row w-100 justify-content-center px-3">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
          <!-- Tarjeta con borde -->
          <div class="card shadow-lg border rounded-4 p-3" style="max-width: 500px; margin: auto;">
            <div class="card-body">
              <!-- Encabezado -->
              <div class="text-center mb-4">
                <a class="d-flex flex-center text-decoration-none mb-4" href="index.php">
                  <img src="assets/images/<?php echo $row_config['site_logo']; ?>" alt="<?php echo $row_config['site_name']; ?>" width="58">
                </a>
                <h3 class="text-body-highlight mb-3">Restablecer Contraseña</h3>
                <p class="text-muted">Ingresa tu nombre de usuario y establece una nueva contraseña</p>
              </div>
              
              <!-- Formulario de restablecimiento -->
              <form action="reset_password.php" method="POST">
                <!-- Campo de Usuario -->
                <div class="mb-3 text-start">
                  <label class="form-label" for="username">Usuario</label>
                  <div class="form-icon-container">
                    <input class="form-control form-icon-input" id="username" name="username" type="text" placeholder="Ingresa tu nombre de usuario" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    <svg class="svg-inline--fa fa-user text-body fs-9 form-icon" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="user" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg="">
                      <path fill="currentColor" d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"></path>
                    </svg>
                  </div>
                </div>

                <!-- Campo de Nueva Contraseña -->
                <div class="mb-3 text-start">
                  <label class="form-label" for="new_password">Nueva Contraseña</label>
                  <div class="form-icon-container">
                    <input class="form-control form-icon-input pe-6" id="new_password" name="new_password" type="password" placeholder="Nueva contraseña" required>
                    <svg class="svg-inline--fa fa-key text-body fs-9 form-icon" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="key" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                      <path fill="currentColor" d="M336 352c97.2 0 176-78.8 176-176S433.2 0 336 0S160 78.8 160 176c0 18.7 2.9 36.8 8.3 53.7L7 391c-4.5 4.5-7 10.6-7 17v80c0 13.3 10.7 24 24 24h80c13.3 0 24-10.7 24-24V448h40c13.3 0 24-10.7 24-24V384h40c6.4 0 12.5-2.5 17-7l33.3-33.3c16.9 5.4 35 8.3 53.7 8.3zM376 96a40 40 0 1 1 0 80 40 40 0 1 1 0-80z"></path>
                    </svg>
                    <!-- Botón Mostrar/Ocultar -->
                    <button type="button" id="toggleNewPassword" class="btn px-3 py-0 h-100 position-absolute top-0 end-0">
                      <span class="text-start show">Mostrar</span>
                      <span class="text-start hide" style="display: none;">Ocultar</span>
                    </button>
                  </div>
                  <small class="text-muted">Mínimo 8 caracteres, incluyendo mayúscula, minúscula, número y carácter especial</small>
                </div>

                <!-- Campo de Confirmar Contraseña -->
                <div class="mb-3 text-start">
                  <label class="form-label" for="confirm_password">Confirmar Contraseña</label>
                  <div class="form-icon-container">
                    <input class="form-control form-icon-input pe-6" id="confirm_password" name="confirm_password" type="password" placeholder="Confirma tu nueva contraseña" required>
                    <svg class="svg-inline--fa fa-key text-body fs-9 form-icon" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="key" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                      <path fill="currentColor" d="M336 352c97.2 0 176-78.8 176-176S433.2 0 336 0S160 78.8 160 176c0 18.7 2.9 36.8 8.3 53.7L7 391c-4.5 4.5-7 10.6-7 17v80c0 13.3 10.7 24 24 24h80c13.3 0 24-10.7 24-24V448h40c13.3 0 24-10.7 24-24V384h40c6.4 0 12.5-2.5 17-7l33.3-33.3c16.9 5.4 35 8.3 53.7 8.3zM376 96a40 40 0 1 1 0 80 40 40 0 1 1 0-80z"></path>
                    </svg>
                    <!-- Botón Mostrar/Ocultar -->
                    <button type="button" id="toggleConfirmPassword" class="btn px-3 py-0 h-100 position-absolute top-0 end-0">
                      <span class="text-start show">Mostrar</span>
                      <span class="text-start hide" style="display: none;">Ocultar</span>
                    </button>
                  </div>
                </div>

                <!-- Botón de envío -->
                <button class="btn btn-primary w-100 mb-3" type="submit">Restablecer Contraseña</button>
                
                <!-- Enlace para volver al login -->
                <div class="text-center">
                  <a href="login.php" class="text-decoration-none">Volver al inicio de sesión</a>
                </div>
              </form>
              
              <!-- Mensaje de error -->
              <?php if (!empty($reset_error)) { ?>
              <div class="alert alert-danger mt-3 text-center">
                <?php echo $reset_error; ?>
              </div>
              <?php } ?>

              <!-- Mensaje de éxito -->
              <?php if (!empty($reset_success)) { ?>
              <div class="alert alert-success mt-3 text-center">
                <?php echo $reset_success; ?>
                <br><small>Redirigiendo al login...</small>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <!-- JavaScript -->
  <script>
    // Mostrar/Ocultar nueva contraseña
    document.getElementById('toggleNewPassword').addEventListener('click', function () {
      const passwordField = document.getElementById('new_password');
      const showText = this.querySelector('.show');
      const hideText = this.querySelector('.hide');

      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        showText.style.display = 'none';
        hideText.style.display = 'inline';
      } else {
        passwordField.type = 'password';
        showText.style.display = 'inline';
        hideText.style.display = 'none';
      }
    });

    // Mostrar/Ocultar confirmar contraseña
    document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
      const passwordField = document.getElementById('confirm_password');
      const showText = this.querySelector('.show');
      const hideText = this.querySelector('.hide');

      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        showText.style.display = 'none';
        hideText.style.display = 'inline';
      } else {
        passwordField.type = 'password';
        showText.style.display = 'inline';
        hideText.style.display = 'none';
      }
    });
  </script>
</body>
<?php require_once('views/template_footer.php'); ?>
