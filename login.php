<?php
require_once('config.php');
require_once('conn/phoenix.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$change_success = null;
if (isset($_GET['change_success'])) {
  $change_success = $_GET['change_success'];
}

$change_error = null;
if (isset($_GET['change_error'])) {
  $change_error = $_GET['change_error'];
}

$reset_success = null;
if (isset($_GET['reset_success'])) {
  $reset_success = $_GET['reset_success'];
}

$recaptchaStatus = 0;

if ($recaptchaStatus) {
  $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
  $recaptchaURL = "https://www.google.com/recaptcha/api/siteverify";

  // Realizar la solicitud a Google
  $data = [
      'secret' => $secretKey,
      'response' => $recaptchaResponse,
      'remoteip' => $_SERVER['REMOTE_ADDR']
  ];

  $options = [
      'http' => [
          'header' => "Content-type: application/x-www-form-urlencoded\r\n",
          'method' => 'POST',
          'content' => http_build_query($data)
      ]
  ];

  $context = stream_context_create($options);
  $verify = file_get_contents($recaptchaURL, false, $context);
  $captchaSuccess = json_decode($verify);
}else{
  //force recaptcha when recaptchaStatus = 0
  $captchaSuccess = new stdClass();
  $captchaSuccess->success = true;
}

// Verificar si la sesión está activa
if (isset($_SESSION['UsersId'])) {
    $lastURL = $_SESSION['last_url'] ?? 'index.php';
    header("Location: $lastURL");
    exit();
}

// Verificar si la cookie 'remember_user' está presente
if (isset($_COOKIE['remember_user'])) {
    $cookieData = json_decode($_COOKIE['remember_user'], true);
    if (isset($cookieData['UsersId'])) {
        // Buscar el usuario en la base de datos
        $sql = "SELECT UsersId, Username, LastPasswordChanged FROM users WHERE UsersId = ?";
        $stmt = $conn_phoenix->prepare($sql);
        $stmt->bind_param('i', $cookieData['UsersId']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['UsersId'] = $user['UsersId'];
            $_SESSION['Username'] = $user['Username'];

            $lastURL = $_SESSION['last_url'] ?? 'index.php';
            unset($_SESSION['last_url']);
            header("Location: $lastURL");
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Username = $_POST['Username'] ?? '';
    $Password = $_POST['Password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!$captchaSuccess->success) {
        $login_error = "Error: Falló la verificación del reCAPTCHA.";
    } else {
        $sql = "SELECT UsersId, Username, Password, LastPasswordChanged FROM users WHERE Username = ?";
        $stmt = $conn_phoenix->prepare($sql);
        if ($stmt === false) {
            die('Error al preparar la declaración: ' . $conn_phoenix->error);
        }

        $stmt->bind_param('s', $Username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verificar contraseña usando password_verify
            if (password_verify($Password, $user['Password'])) {

                $_SESSION['UsersId'] = $user['UsersId'];
                $_SESSION['Username'] = $user['Username'];

                // Si el usuario seleccionó "Recordar"
                if ($remember) {
                    $cookieData = json_encode(['UsersId' => $user['UsersId']]);
                    setcookie('remember_user', $cookieData, [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict',
                    ]);
                }

                //force change password
                if (!$user['LastPasswordChanged']) {
                  header("Location: change_password.php");
                  exit();
                }

                // Redirigir al último URL solicitado o a la página principal
                $lastURL = $_SESSION['last_url'] ?? 'index.php';
                unset($_SESSION['last_url']);
                header("Location: $lastURL");
                exit();

            } else {
                $login_error = "Credenciales incorrectas.";
            }
        } else {
            $login_error = "Usuario no encontrado.";
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
          <!-- Tarjeta con borde -->
          <div class="card shadow-lg border rounded-4 p-3" style="max-width: 500px; margin: auto;">
            <div class="card-body">
              <!-- Encabezado -->
              <div class="text-center mb-4">
                <a class="d-flex flex-center text-decoration-none mb-4" href="index.php">
                  <img src="assets/images/<?php echo $row_config['site_logo']; ?>" alt="<?php echo $row_config['site_name']; ?>" width="58">
                </a>
                <h3 class="text-body-highlight mb-3"><?php echo $row_config['site_name']; ?></h3>
              </div>
              
              <!-- Formulario de inicio de sesión -->
              <form action="login.php" method="POST">

                <!-- Campo de Usuario -->


            <div class="mb-3 text-start"><label class="form-label" for="Username">Usuario</label>
              <div class="form-icon-container"><input class="form-control form-icon-input" id="Username" name="Username" type="Username" placeholder="usuario"><svg class="svg-inline--fa fa-user text-body fs-9 form-icon" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="user" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"></path></svg><!-- <span class="fas fa-user text-body fs-9 form-icon"></span> Font Awesome fontawesome.com --></div>
            </div>

<!-- Campo de Contraseña con Mostrar/Ocultar -->
<div class="mb-3 text-start"><label class="form-label" for="password">Contraseña</label>
  <div class="form-icon-container">
    <input class="form-control form-icon-input pe-6" id="Password" name="Password" type="password" placeholder="Contraseña">
    <svg class="svg-inline--fa fa-key text-body fs-9 form-icon" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="key" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
      <path fill="currentColor" d="M336 352c97.2 0 176-78.8 176-176S433.2 0 336 0S160 78.8 160 176c0 18.7 2.9 36.8 8.3 53.7L7 391c-4.5 4.5-7 10.6-7 17v80c0 13.3 10.7 24 24 24h80c13.3 0 24-10.7 24-24V448h40c13.3 0 24-10.7 24-24V384h40c6.4 0 12.5-2.5 17-7l33.3-33.3c16.9 5.4 35 8.3 53.7 8.3zM376 96a40 40 0 1 1 0 80 40 40 0 1 1 0-80z"></path>
    </svg>

    <!-- Botón Mostrar/Ocultar -->
    <button type="button" id="togglePassword" class="btn px-3 py-0 h-100 position-absolute top-0 end-0">
      <span class="text-start show">Mostrar</span>
      <span class="text-start hide" style="display: none;">Ocultar</span>
    </button>
  </div>
</div>
                <!-- Recordar Contraseña -->
                <div class="mb-3 form-check">
                  <input class="form-check-input" type="checkbox" id="remember" name="remember">
                  <label class="form-check-label" for="remember">Mantener sesión activa</label>
                </div>
                <!-- Enlace Olvidé mi contraseña -->
                <div class="mb-3 text-end">
                  <a href="reset_password.php" class="text-decoration-none">¿Olvidé mi contraseña?</a>
                </div>
                      <!-- reCAPTCHA -->
                      <?php if ($recaptchaStatus) { ?>
                      <div class="mb-3 text-start">
                        <div class="g-recaptcha" data-sitekey="<?php echo $siteKey; ?>"></div>
                      </div>
                      <?php } ?>
                <!-- Botón de envío -->
                <button class="btn btn-primary w-100" type="submit">Ingresar</button>
              </form>
              <!-- Mensaje de error -->
              <?php if (isset($login_error)) { ?>
              <div class="alert alert-danger mt-3 text-center">
                <?php echo $login_error; ?>
              </div>
              <?php } ?>

              <!-- Mensaje de success -->
              <?php if (isset($change_success)) { ?>
              <div class="alert alert-success mt-3 text-center">
                <?php echo $change_success; ?>
              </div>
              <?php } ?>

              <!-- Mensaje de error -->
              <?php if (isset($change_error)) { ?>
              <div class="alert alert-danger mt-3 text-center">
                <?php echo $change_error; ?>
              </div>
              <?php } ?>

              <!-- Mensaje de éxito de reseteo -->
              <?php if (isset($reset_success)) { ?>
              <div class="alert alert-success mt-3 text-center">
                <?php echo $reset_success; ?>
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
// Mostrar/Ocultar contraseña
document.getElementById('togglePassword').addEventListener('click', function () {
  const passwordField = document.getElementById('Password');
  const showText = this.querySelector('.show');
  const hideText = this.querySelector('.hide');

  if (passwordField.type === 'password') {
    passwordField.type = 'text'; // Cambiar a tipo texto
    showText.style.display = 'none'; // Ocultar "Mostrar"
    hideText.style.display = 'inline'; // Mostrar "Ocultar"
  } else {
    passwordField.type = 'password'; // Cambiar a tipo contraseña
    showText.style.display = 'inline'; // Mostrar "Mostrar"
    hideText.style.display = 'none'; // Ocultar "Ocultar"
  }
});
</script>
<script type="text/javascript">
  // Detectar modo oscuro automáticamente
  /*
const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");

if (prefersDarkScheme.matches) {
    document.body.setAttribute('data-bs-theme', 'dark');
} else {
    document.body.setAttribute('data-bs-theme', 'light');
}
*/
</script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

</body>
