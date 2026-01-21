<?php
require_once('config.php');
require_once('conn/phoenix.php');

$new_password = null;
if (isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
}

$confirm_password = null;
if (isset($_POST['confirm_password'])) {
    $confirm_password = $_POST['confirm_password'];
}

$UsersId = null;
if (isset($_GET['UsersId'])) {
    $UsersId = $_GET['UsersId'];
} elseif (isset($_SESSION['UsersId'])) {
    $UsersId = $_SESSION['UsersId'];
}

if (!$UsersId) {
    $change_error = "No se especificó un usuario válido";
    header("Location: login.php?change_error=" . urlencode($change_error));
    exit();
}

$change_error = '';
$change_success = '';

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
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword === '' || $confirmPassword === '') {
        $change_error = "Ambos campos son obligatorios.";
    } elseif ($newPassword !== $confirmPassword) {
        $change_error = "Las contraseñas no coinciden.";
    } elseif (!isValidPassword($newPassword)) {
        $change_error = "La contraseña debe tener al menos 8 caracteres, incluyendo una letra mayúscula, una letra minúscula, un número y un carácter especial.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $sql = "UPDATE users SET Password = ?, LastPasswordChanged = NOW() WHERE UsersId = ?";
        $stmt = $conn_phoenix->prepare($sql);

        if ($stmt === false) {
            die('Error al preparar la declaración: ' . $conn_phoenix->error);
        }

        $stmt->bind_param('si', $hashedPassword, $UsersId);
        if ($stmt->execute()) {

            // Eliminar todas las variables de sesión
            session_unset();

            // Destruir la sesión
            session_destroy();

            // Eliminar cookies de "Recordar Contraseña" si existen
            if (isset($_COOKIE['PHPSESSID'])) {
                setcookie('PHPSESSID', '', time() - 3600, '/'); // Eliminar cookie de sesión
            }

            if (isset($_COOKIE['remember_user'])) {
                setcookie('remember_user', '', time() - 3600, '/'); // Eliminar cookie de recordar
            }

            $change_success = "Contraseña cambiada exitosamente.";
            header("Location: login.php?change_success=" . urlencode($change_success));
            exit();
        } else {
            $change_error = "Error al cambiar la contraseña: " . $stmt->error;
        }

        $stmt->close();
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
                <div class="card shadow-lg border rounded-4 p-3">
                    <div class="card-body">
                        <h3 class="text-center">Cambiar Contraseña</h3>
                        <form method="POST" action="">

                            <div class="mb-3">
                                <label for="Username" class="form-label">Usuario</label>
                                <div class="d-flex">
                                    <input type="text" class="form-control" value="<?php echo $_SESSION['Username']; ?>" id="Username" name="Username" required disabled >
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nueva Contraseña</label>
                                <div class="d-flex">
                                    <input type="password" class="form-control" value="<?php echo $new_password; ?>" id="new_password" name="new_password" required>
                                    <span class="ms-2" id="toggleNewPassword" style="cursor: pointer; color: blue;">Mostrar</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                <div class="d-flex">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" value="<?php echo $confirm_password; ?>" required>
                                    <span class="ms-2" id="toggleConfirmPassword" style="cursor: pointer; color: blue;">Mostrar</span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Actualizar Contraseña</button>
                        </form>
                        <?php if ($change_error): ?>
                            <div class="alert alert-danger mt-3"><?php echo $change_error; ?></div>
                        <?php endif; ?>
                        <?php if ($change_success): ?>
                            <div class="alert alert-success mt-3"><?php echo $change_success; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Función para alternar la visibilidad de las contraseñas
    function togglePasswordVisibility(inputId, toggleId) {
        var passwordField = document.getElementById(inputId);
        var toggleButton = document.getElementById(toggleId);
        
        if (passwordField.type === "password") {
            passwordField.type = "text";
            toggleButton.innerText = "Ocultar";  // Cambiar a "Ocultar"
        } else {
            passwordField.type = "password";
            toggleButton.innerText = "Mostrar";  // Cambiar a "Mostrar"
        }
    }

    // Añadir los eventos de clic a los botones de "Mostrar/Ocultar"
    document.getElementById("toggleNewPassword").addEventListener("click", function() {
        togglePasswordVisibility("new_password", "toggleNewPassword");
    });
    document.getElementById("toggleConfirmPassword").addEventListener("click", function() {
        togglePasswordVisibility("confirm_password", "toggleConfirmPassword");
    });
</script>

</body>
<?php require_once('views/template_footer.php'); ?>
