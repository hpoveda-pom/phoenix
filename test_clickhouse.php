<?php
/**
 * Archivo de prueba para conexión a ClickHouse
 * 
 * Credenciales:
 * - Hostname: clickhouse.pomcr.local
 * - User: phoenix
 * - Pass: sCMPRZm8Y@@
 * - Database: POM_Aplicaciones
 * - Port: 8123 (HTTP, sin SSL)
 */

// Incluir la clase de conexión a ClickHouse
require_once('models/class_connclickhouse.php');

// Configuración de conexión
$hostname = 'clickhouse.pomcr.local';
$port = '8123'; // HTTP API
$username = 'phoenix';
$password = 'sCMPRZm8Y@@';
$database = 'POM_Aplicaciones';
$secure = false; // Sin SSL

// Query a ejecutar
$query = "SELECT * FROM PC_Catalogo_Filtros_Telefono LIMIT 5";

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test ClickHouse Connection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            padding: 15px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
            border-radius: 4px;
        }
        .error {
            background: #f8d7da;
            padding: 15px;
            border-left: 4px solid #dc3545;
            margin: 20px 0;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .query-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            border-left: 4px solid #6c757d;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            flex: 1;
            text-align: center;
        }
        .stat-box strong {
            display: block;
            font-size: 24px;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Test de Conexión ClickHouse</h1>
        
        <div class='info'>
            <strong>Configuración de Conexión:</strong><br>
            Hostname: <code>$hostname</code><br>
            Puerto: <code>$port</code> (HTTP, sin SSL)<br>
            Usuario: <code>$username</code><br>
            Base de Datos: <code>$database</code>
        </div>
        
        <div class='query-box'>
            <strong>Query a ejecutar:</strong><br>
            <code>$query</code>
        </div>";

// Intentar conectar
echo "<h2>1. Estableciendo Conexión...</h2>";

$start_time = microtime(true);
$conn = class_connClickHouse($hostname, $port, $username, $password, $database, $secure);
$connection_time = round((microtime(true) - $start_time) * 1000, 2);

if ($conn) {
    echo "<div class='success'>
            ✅ <strong>Conexión establecida exitosamente</strong><br>
            Tiempo de conexión: {$connection_time}ms<br>
            URL Base: <code>{$conn->base_url}</code>
          </div>";
    
    // Ejecutar query
    echo "<h2>2. Ejecutando Query...</h2>";
    
    $query_start_time = microtime(true);
    $error_info = null;
    $result = class_clickhouse_execute($conn, $query, $error_info);
    $query_time = round((microtime(true) - $query_start_time) * 1000, 2);
    
    if ($result !== false) {
        echo "<div class='success'>
                ✅ <strong>Query ejecutada exitosamente</strong><br>
                Tiempo de ejecución: {$query_time}ms
              </div>";
        
        // Mostrar estadísticas
        $row_count = is_array($result) ? count($result) : 0;
        echo "<div class='stats'>
                <div class='stat-box'>
                    <strong>$row_count</strong>
                    <span>Filas obtenidas</span>
                </div>
                <div class='stat-box'>
                    <strong>" . ($connection_time + $query_time) . "ms</strong>
                    <span>Tiempo total</span>
                </div>
              </div>";
        
        // Mostrar resultados
        if ($row_count > 0) {
            echo "<h2>3. Resultados:</h2>";
            echo "<table>";
            
            // Obtener headers de la primera fila
            $first_row = $result[0];
            $headers = array_keys($first_row);
            
            // Imprimir encabezados
            echo "<thead><tr>";
            foreach ($headers as $header) {
                echo "<th>" . htmlspecialchars($header) . "</th>";
            }
            echo "</tr></thead>";
            
            // Imprimir datos
            echo "<tbody>";
            foreach ($result as $row) {
                echo "<tr>";
                foreach ($headers as $header) {
                    $value = isset($row[$header]) ? $row[$header] : '';
                    // Mostrar NULL como texto
                    if ($value === null) {
                        $value = '<em style="color: #999;">NULL</em>';
                    } else {
                        $value = htmlspecialchars($value);
                    }
                    echo "<td>$value</td>";
                }
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<div class='info'>
                    ℹ️ La query se ejecutó correctamente pero no retornó filas.
                  </div>";
        }
        
    } else {
        echo "<div class='error'>
                ❌ <strong>Error al ejecutar la query:</strong><br>
                " . htmlspecialchars($error_info ?? 'Error desconocido') . "
              </div>";
    }
    
} else {
    // Obtener información de error detallada
    $error_details = '';
    if (isset($GLOBALS['debug_info']) && is_array($GLOBALS['debug_info'])) {
        foreach ($GLOBALS['debug_info'] as $debug_msg) {
            if (stripos($debug_msg, 'ClickHouse') !== false) {
                $error_details .= htmlspecialchars($debug_msg) . "<br>";
            }
        }
    }
    
    echo "<div class='error'>
            ❌ <strong>Error al establecer la conexión</strong><br>";
    
    if ($error_details) {
        echo "Detalles: " . $error_details;
    } else {
        echo "No se pudo conectar a ClickHouse. Verifique las credenciales y que el servidor esté accesible.";
    }
    
    echo "</div>";
    
    // Mostrar información de debug si está disponible
    if (isset($GLOBALS['debug_info']) && is_array($GLOBALS['debug_info'])) {
        echo "<h3>Información de Debug:</h3>";
        echo "<div class='info'>";
        foreach ($GLOBALS['debug_info'] as $debug_msg) {
            if (stripos($debug_msg, 'ClickHouse') !== false) {
                echo htmlspecialchars($debug_msg) . "<br>";
            }
        }
        echo "</div>";
    }
    
    // Intentar conexión directa con cURL para diagnóstico
    echo "<h3>Diagnóstico de Conexión:</h3>";
    echo "<div class='info'>";
    
    $test_url = "http://{$hostname}:{$port}/?database=" . urlencode($database) . "&default_format=JSON";
    echo "<strong>URL de prueba:</strong> <code>$test_url</code><br><br>";
    
    // Probar diferentes métodos de autenticación
    echo "<strong>Probando diferentes métodos de autenticación:</strong><br><br>";
    
    // Método 1: Basic Auth estándar
    echo "<strong>Método 1: Basic Auth (CURLOPT_USERPWD)</strong><br>";
    $ch = curl_init($test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "SELECT 1");
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain; charset=utf-8',
        'Accept: application/json'
    ]);
    
    $test_response = curl_exec($ch);
    $test_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $test_curl_error = curl_error($ch);
    $test_curl_errno = curl_errno($ch);
    
    echo "HTTP Code: " . ($test_http_code ?: 'N/A') . "<br>";
    echo "cURL Error: " . ($test_curl_error ?: 'Ninguno') . "<br>";
    
    if ($test_response) {
        echo "Response: " . htmlspecialchars(substr(trim($test_response), 0, 200)) . "<br>";
    }
    echo "<br>";
    curl_close($ch);
    
    // Método 2: Basic Auth en header manual
    echo "<strong>Método 2: Basic Auth en Header manual</strong><br>";
    $auth_string = base64_encode("$username:$password");
    $ch2 = curl_init($test_url);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, "SELECT 1");
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain; charset=utf-8',
        'Accept: application/json',
        'Authorization: Basic ' . $auth_string
    ]);
    
    $test_response2 = curl_exec($ch2);
    $test_http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $test_curl_error2 = curl_error($ch2);
    
    echo "HTTP Code: " . ($test_http_code2 ?: 'N/A') . "<br>";
    echo "cURL Error: " . ($test_curl_error2 ?: 'Ninguno') . "<br>";
    
    if ($test_response2) {
        echo "Response: " . htmlspecialchars(substr(trim($test_response2), 0, 200)) . "<br>";
    }
    echo "<br>";
    curl_close($ch2);
    
    // Método 3: Sin especificar base de datos primero
    echo "<strong>Método 3: Sin especificar base de datos (solo autenticación)</strong><br>";
    $test_url3 = "http://{$hostname}:{$port}/?default_format=JSON";
    $ch3 = curl_init($test_url3);
    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch3, CURLOPT_POST, true);
    curl_setopt($ch3, CURLOPT_POSTFIELDS, "SELECT 1");
    curl_setopt($ch3, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch3, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch3, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch3, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch3, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain; charset=utf-8',
        'Accept: application/json'
    ]);
    
    $test_response3 = curl_exec($ch3);
    $test_http_code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
    $test_curl_error3 = curl_error($ch3);
    
    echo "HTTP Code: " . ($test_http_code3 ?: 'N/A') . "<br>";
    echo "cURL Error: " . ($test_curl_error3 ?: 'Ninguno') . "<br>";
    
    if ($test_response3) {
        echo "Response: " . htmlspecialchars(substr(trim($test_response3), 0, 200)) . "<br>";
    }
    curl_close($ch3);
    
    // Mostrar información de la contraseña (sin mostrarla completa)
    echo "<br><strong>Información de credenciales:</strong><br>";
    echo "Usuario: <code>$username</code><br>";
    echo "Longitud de contraseña: " . strlen($password) . " caracteres<br>";
    echo "Contraseña contiene caracteres especiales: " . (preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password) ? 'Sí' : 'No') . "<br>";
    
    // Mostrar mensaje específico si es error de autenticación
    if ($test_http_code == 403 || $test_http_code2 == 403 || $test_http_code3 == 403 || 
        (stripos($test_response, 'Authentication failed') !== false) ||
        (stripos($test_response2, 'Authentication failed') !== false) ||
        (stripos($test_response3, 'Authentication failed') !== false)) {
        echo "<div class='error' style='margin-top: 15px;'>
                <strong>⚠️ Error de Autenticación Detectado</strong><br>
                El servidor ClickHouse está rechazando las credenciales proporcionadas.<br><br>
                <strong>Posibles causas:</strong><br>
                • El usuario '<code>$username</code>' no existe en ClickHouse<br>
                • La contraseña es incorrecta<br>
                • El usuario no tiene permisos para acceder a la base de datos '<code>$database</code>'<br>
                • Caracteres especiales en la contraseña que necesitan ser escapados<br><br>
                <strong>Comandos para verificar en el servidor ClickHouse:</strong><br>
                <code>clickhouse-client --host localhost --port 9000 --query \"SELECT name, auth_type FROM system.users WHERE name = '$username'\"</code><br>
                <code>clickhouse-client --host localhost --port 9000 --query \"SHOW GRANTS FOR $username\"</code><br><br>
                <strong>Nota:</strong> El cliente nativo de ClickHouse usa el puerto <strong>9000</strong>, no 8123. El puerto 8123 es solo para HTTP API.
              </div>";
    }
    
    echo "</div>";
}

echo "    </div>
</body>
</html>";
?>
