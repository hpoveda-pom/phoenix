<?php
ob_start();

if (!isset($_SESSION['UsersId'])) {
    // Guardar la URL actual en la sesión
    $_SESSION['last_url'] = $_SERVER['REQUEST_URI'];

    // Redirigir al login
    header('Location: login.php');
    exit();
}
?>
