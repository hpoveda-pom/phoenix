<?php
session_start();

// Eliminar todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Eliminar cookies de "Recordar Contraseña" si existen
if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 3600, '/'); // Eliminar cookie de sesión
}

// Si usaste una cookie personalizada para "Recordar Contraseña", elimínala también
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/'); // Eliminar cookie de recordar
}

// Redirigir al formulario de inicio de sesión
header('Location: login.php');
exit();
