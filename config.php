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

// Verificar si el modo debug está activo en la sesión
$debug_mode_active = isset($_SESSION['debug_mode']) && $_SESSION['debug_mode'] === true;

// Configurar reporte de errores basado en modo debug
if ($debug_mode_active) {
    // Modo debug activo: mostrar TODOS los errores y warnings
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('html_errors', 1);
    ini_set('log_errors', 1);
    error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
    
    // Habilitar mostrar warnings también
    ini_set('display_warnings', 1);
} else {
    // Modo producción: ocultar errores pero loguearlos
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

// Manejador de errores personalizado para modo debug
if ($debug_mode_active) {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        // Solo mostrar si está en modo debug
        if (isset($_SESSION['debug_mode']) && $_SESSION['debug_mode'] === true) {
            $error_types = [
                E_ERROR => 'ERROR',
                E_WARNING => 'WARNING',
                E_PARSE => 'PARSE',
                E_NOTICE => 'NOTICE',
                E_CORE_ERROR => 'CORE_ERROR',
                E_CORE_WARNING => 'CORE_WARNING',
                E_COMPILE_ERROR => 'COMPILE_ERROR',
                E_COMPILE_WARNING => 'COMPILE_WARNING',
                E_USER_ERROR => 'USER_ERROR',
                E_USER_WARNING => 'USER_WARNING',
                E_USER_NOTICE => 'USER_NOTICE',
                E_STRICT => 'STRICT',
                E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
                E_DEPRECATED => 'DEPRECATED',
                E_USER_DEPRECATED => 'USER_DEPRECATED'
            ];
            
            $error_type = isset($error_types[$errno]) ? $error_types[$errno] : 'UNKNOWN';
            
            echo '<div style="background: #ffebee; border-left: 4px solid #f44336; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px;">';
            echo '<strong style="color: #d32f2f;">[' . $error_type . ']</strong> ';
            echo '<span style="color: #333;">' . htmlspecialchars($errstr) . '</span><br>';
            echo '<small style="color: #666;">Archivo: ' . htmlspecialchars($errfile) . ' (Línea: ' . $errline . ')</small>';
            echo '</div>';
        }
        return false; // Continuar con el manejador de errores por defecto
    });
    
    // Manejador de excepciones no capturadas
    set_exception_handler(function($exception) {
        if (isset($_SESSION['debug_mode']) && $_SESSION['debug_mode'] === true) {
            echo '<div style="background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 10px 0; font-family: monospace; font-size: 12px;">';
            echo '<strong style="color: #d32f2f;">[EXCEPCIÓN NO CAPTURADA]</strong><br>';
            echo '<span style="color: #333;">' . htmlspecialchars($exception->getMessage()) . '</span><br>';
            echo '<small style="color: #666;">Archivo: ' . htmlspecialchars($exception->getFile()) . ' (Línea: ' . $exception->getLine() . ')</small><br>';
            echo '<details style="margin-top: 10px;"><summary style="cursor: pointer; color: #666;">Stack Trace</summary>';
            echo '<pre style="background: #fff; padding: 10px; margin-top: 5px; overflow-x: auto; font-size: 11px;">';
            echo htmlspecialchars($exception->getTraceAsString());
            echo '</pre></details>';
            echo '</div>';
        }
    });
    
    // Manejador de errores fatales
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error !== null && isset($_SESSION['debug_mode']) && $_SESSION['debug_mode'] === true) {
            $error_types = [
                E_ERROR => 'FATAL ERROR',
                E_PARSE => 'PARSE ERROR',
                E_CORE_ERROR => 'CORE ERROR',
                E_COMPILE_ERROR => 'COMPILE ERROR'
            ];
            
            $error_type = isset($error_types[$error['type']]) ? $error_types[$error['type']] : 'FATAL ERROR';
            
            if (in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                echo '<div style="background: #ffebee; border-left: 4px solid #d32f2f; padding: 15px; margin: 10px 0; font-family: monospace; font-size: 12px;">';
                echo '<strong style="color: #d32f2f;">[' . $error_type . ']</strong><br>';
                echo '<span style="color: #333;">' . htmlspecialchars($error['message']) . '</span><br>';
                echo '<small style="color: #666;">Archivo: ' . htmlspecialchars($error['file']) . ' (Línea: ' . $error['line'] . ')</small>';
                echo '</div>';
            }
        }
    });
}

// Verificar el reCAPTCHA
$secretKey = "xxxxx"; // Reemplaza con tu clave secreta de reCAPTCHA
$siteKey = "xxxxx";
