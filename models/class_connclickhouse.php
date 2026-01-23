<?php
function class_connClickHouse($hostname, $port, $username, $password, $database, $secure = true) {
    // Validar que los parámetros obligatorios no estén vacíos
    if (empty($hostname) || empty($username) || empty($database)) {
        if (isset($GLOBALS['debug_info'])) {
            $GLOBALS['debug_info'][] = "✗ ClickHouse: Faltan parámetros obligatorios (hostname, username o database)";
        }
        return null;
    }
    
    // ClickHouse usa HTTP/HTTPS, así que creamos un objeto que encapsule la conexión
    $conn = new stdClass();
    $conn->hostname = $hostname;
    $conn->port = !empty($port) ? $port : ($secure ? 8443 : 8123);
    $conn->username = $username;
    $conn->password = $password;
    $conn->database = $database;
    $conn->secure = $secure;
    $conn->type = 'clickhouse';
    
    // URL base para las consultas
    $protocol = $secure ? 'https' : 'http';
    $conn->base_url = "{$protocol}://{$hostname}:{$conn->port}";
    
    // Probar la conexión con una consulta simple
    $test_query = "SELECT 1";
    $result = class_clickhouse_query($conn, $test_query, 'JSON', $error_info);
    
    if ($result === false) {
        if (isset($GLOBALS['debug_info']) && isset($error_info)) {
            $GLOBALS['debug_info'][] = "✗ ClickHouse: Error de conexión - " . $error_info;
        }
        return null;
    }
    
    return $conn;
}

function class_clickhouse_query($conn, $query, $format = 'JSON', &$error_info = null) {
    // ClickHouse HTTP API: POST con query en el body
    // Para ClickHouse Cloud, usar el endpoint correcto
    $url = $conn->base_url . "/?database=" . urlencode($conn->database) . "&default_format=" . $format;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    
    // Autenticación Basic Auth
    curl_setopt($ch, CURLOPT_USERPWD, $conn->username . ":" . $conn->password);
    
    // Headers adicionales para ClickHouse Cloud
    $headers = [
        'Content-Type: text/plain; charset=utf-8',
        'Accept: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Configuración SSL para ClickHouse Cloud
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    
    // Obtener más información del error si existe
    $error_details = null;
    if ($curl_error || $http_code !== 200) {
        $error_details = [
            'http_code' => $http_code,
            'curl_error' => $curl_error,
            'curl_errno' => $curl_errno,
            'response' => substr($response, 0, 500),
            'url' => $url
        ];
    }
    
    curl_close($ch);
    
    // Manejo de errores de cURL
    if ($curl_error) {
        $error_info = "cURL Error ($curl_errno): $curl_error";
        if ($error_details) {
            $error_info .= " | URL: " . $url;
        }
        return false;
    }
    
    // Manejo de errores HTTP
    if ($http_code !== 200) {
        $error_info = "HTTP Error $http_code";
        if ($response) {
            // Intentar extraer el mensaje de error de ClickHouse
            $error_info .= ": " . substr(trim($response), 0, 200);
        }
        if ($error_details) {
            $error_info .= " | URL: " . $url;
        }
        return false;
    }
    
    if ($format === 'JSON') {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_info = "Error decodificando JSON: " . json_last_error_msg() . " | Response: " . substr($response, 0, 200);
            return false;
        }
        return $data;
    }
    
    return $response;
}

function class_clickhouse_execute($conn, $query, &$error_info = null) {
    // Ejecutar una consulta y retornar los resultados como array asociativo
    $result = class_clickhouse_query($conn, $query, 'JSON', $error_info);
    
    if ($result === false) {
        return false;
    }
    
    // ClickHouse JSON format retorna: {"data": [...], "rows": N, "meta": [...]}
    if (isset($result['data'])) {
        return $result['data'];
    }
    
    // Si no tiene estructura esperada, retornar el resultado tal cual
    return $result;
}
?>
