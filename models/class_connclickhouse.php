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
    
    // Headers adicionales para ClickHouse
    $headers = [
        'Content-Type: text/plain; charset=utf-8',
        'Accept: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Configuración SSL según el parámetro $secure
    if ($conn->secure) {
        // Con SSL: verificar certificado
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    } else {
        // Sin SSL: deshabilitar verificación
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
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
    
    // ClickHouse JSON format puede retornar diferentes estructuras:
    // 1. {"data": [...], "rows": N, "meta": [...]} - Formato estándar
    // 2. Array directo de objetos - Formato alternativo
    // 3. Objeto simple con datos
    
    if (isset($result['data']) && is_array($result['data'])) {
        // Formato estándar: extraer el array de data
        return $result['data'];
    } elseif (is_array($result) && !empty($result)) {
        // Si es un array directo, verificar si es array de objetos o array indexado
        $first_item = reset($result);
        if (is_array($first_item) && isset($first_item[0])) {
            // Es un array indexado, convertir a asociativo si es posible
            // ClickHouse a veces retorna arrays indexados
            $converted = [];
            foreach ($result as $row) {
                if (is_array($row)) {
                    $converted[] = $row;
                }
            }
            return !empty($converted) ? $converted : $result;
        } else {
            // Es un array de objetos asociativos, retornar tal cual
            return $result;
        }
    }
    
    // Si no tiene estructura esperada, retornar array vacío
    return [];
}
?>
