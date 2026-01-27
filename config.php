<?php
// Configurar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', 1);  
    ini_set('session.cookie_httponly', 1); 
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

ob_start();

/*Site*/
$row_config['site_name'] = "Phoenix - POM";
$row_config['site_logo'] = "logo.png";

/*DB Connections*/
$row_config['db_host'] = "phoenix.pomcr.local";
$row_config['db_user'] = "hpoveda";
$row_config['db_pass'] = "Solid256!";
$row_config['db_name'] = "phoenix";

$row_config['time_zone'] = "America/Costa_Rica";
$row_config['memory_limit'] = "8198M";
$row_config['set_time_limit'] = 30;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar el reCAPTCHA
$secretKey = "xxxxx"; // Reemplaza con tu clave secreta de reCAPTCHA
$siteKey = "xxxxx";
