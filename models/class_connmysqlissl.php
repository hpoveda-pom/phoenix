<?php
function class_connMysqliSSL($hostname, $port, $username, $password, $database, $ssl_ca = null, $ssl_cert = null, $ssl_key = null, $ssl_cipher = null, $ssl_verify = true) {
    // Validar que los parámetros obligatorios no estén vacíos
    if (empty($hostname) || empty($username) || empty($database)) {
        return null;
    }
    
    // Crear objeto mysqli sin conectar aún
    $conn = @new mysqli();
    
    // Verificar si el objeto se creó correctamente
    if (!$conn || !($conn instanceof mysqli)) {
        return null;
    }
    
    // Configurar opciones SSL antes de conectar
    if ($ssl_verify) {
        // Verificar certificado del servidor
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
    } else {
        // No verificar certificado (útil para desarrollo, no recomendado en producción)
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    }
    
    // Configurar SSL si se proporcionan certificados
    if ($ssl_ca || $ssl_cert || $ssl_key || $ssl_cipher) {
        mysqli_ssl_set(
            $conn,
            $ssl_key,      // key
            $ssl_cert,     // cert
            $ssl_ca,       // ca
            null,          // capath
            $ssl_cipher    // cipher
        );
    } else {
        // SSL sin certificados específicos (usa los del sistema)
        mysqli_ssl_set($conn, null, null, null, null, null);
    }
    
    // Conectar con SSL habilitado
    // mysqli_real_connect acepta hostname y puerto por separado
    $port_num = !empty($port) ? intval($port) : 3306;
    
    $connected = @mysqli_real_connect($conn, $hostname, $username, $password, $database, $port_num);
    
    // Verificar si la conexión fue exitosa
    if (!$connected || $conn->connect_error) {
        return null;
    }
    
    // Configurar charset
    $conn->set_charset("utf8mb4");
    
    return $conn;
}
?>
