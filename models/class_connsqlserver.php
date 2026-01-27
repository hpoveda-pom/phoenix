<?php
function class_connSqlServer($hostname, $port, $username, $password, $database) {
    // Validar que los parámetros obligatorios no estén vacíos
    if (empty($hostname) || empty($username) || empty($database)) {
        if (isset($GLOBALS['debug_info'])) {
            $GLOBALS['debug_info'][] = "✗ SQL Server: Faltan parámetros obligatorios (hostname, username o database)";
        }
        return null;
    }
    
    // Detectar si el hostname contiene una instancia nombrada (ej: SERVER\SQLEXPRESS)
    $has_named_instance = (strpos($hostname, '\\') !== false);
    
    // Si hay instancia nombrada, no usar puerto (SQL Server usa el puerto dinámico de la instancia)
    // Si no hay instancia nombrada y no hay puerto, usar 1433 por defecto
    $server_string = $hostname;
    if (!$has_named_instance && !empty($port)) {
        $server_string = "{$hostname},{$port}";
    } elseif (!$has_named_instance && empty($port)) {
        $server_string = "{$hostname},1433";
    }
    // Si hay instancia nombrada, usar el hostname tal cual (incluye el \SQLEXPRESS)
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    // Intentar conectar usando PDO con pdo_sqlsrv primero (extensión nativa de Microsoft)
    // Esta es la extensión más eficiente y recomendada para SQL Server
    try {
        // Construir DSN para SQL Server con pdo_sqlsrv
        // pdo_sqlsrv usa el formato: sqlsrv:Server=...;Database=...
        $dsn = "sqlsrv:Server={$server_string};Database={$database};Encrypt=false;TrustServerCertificate=true";
        
        $conn = new PDO($dsn, $username, $password, $options);
        
        // Marcar el tipo de conexión
        $conn->type = 'sqlserver';
        
        if (isset($GLOBALS['debug_info'])) {
            $GLOBALS['debug_info'][] = "✓ SQL Server: Conexión exitosa usando pdo_sqlsrv";
        }
        
        return $conn;
    } catch (PDOException $e) {
        // Si falla con pdo_sqlsrv, intentar con ODBC (más común en Windows sin pdo_sqlsrv)
        try {
            // ODBC Driver 17 for SQL Server es el más común
            // Si el hostname tiene instancia nombrada, usarlo directamente
            $odbc_server = $has_named_instance ? $hostname : $server_string;
            
            $dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server={$odbc_server};Database={$database};TrustServerCertificate=yes";
            $conn = new PDO($dsn, $username, $password, $options);
            $conn->type = 'sqlserver';
            
            if (isset($GLOBALS['debug_info'])) {
                $GLOBALS['debug_info'][] = "✓ SQL Server: Conexión exitosa usando ODBC Driver 17";
            }
            
            return $conn;
        } catch (PDOException $e2) {
            // Intentar con otros drivers ODBC comunes
            $odbc_drivers = [
                'ODBC Driver 18 for SQL Server',
                'ODBC Driver 13 for SQL Server',
                'SQL Server Native Client 11.0',
                'SQL Server'
            ];
            
            foreach ($odbc_drivers as $driver) {
                try {
                    $odbc_server = $has_named_instance ? $hostname : $server_string;
                    $dsn = "odbc:Driver={{$driver}};Server={$odbc_server};Database={$database};TrustServerCertificate=yes";
                    $conn = new PDO($dsn, $username, $password, $options);
                    $conn->type = 'sqlserver';
                    
                    if (isset($GLOBALS['debug_info'])) {
                        $GLOBALS['debug_info'][] = "✓ SQL Server: Conexión exitosa usando {$driver}";
                    }
                    
                    return $conn;
                } catch (PDOException $e3) {
                    // Continuar con el siguiente driver
                    continue;
                }
            }
            
            // Si todos los métodos fallan, intentar con dblib (Linux)
            try {
                $dblib_port = $has_named_instance ? '1433' : ($port ?: '1433');
                $dblib_host = $has_named_instance ? explode('\\', $hostname)[0] : $hostname;
                $dsn = "dblib:host={$dblib_host}:{$dblib_port};dbname={$database}";
                $conn = new PDO($dsn, $username, $password, $options);
                $conn->type = 'sqlserver';
                
                if (isset($GLOBALS['debug_info'])) {
                    $GLOBALS['debug_info'][] = "✓ SQL Server: Conexión exitosa usando dblib";
                }
                
                return $conn;
            } catch (PDOException $e4) {
                if (isset($GLOBALS['debug_info'])) {
                    $error_msg = "✗ SQL Server: Error de conexión - ";
                    $error_msg .= "pdo_sqlsrv: " . $e->getMessage() . "; ";
                    $error_msg .= "ODBC: " . $e2->getMessage();
                    $GLOBALS['debug_info'][] = $error_msg;
                }
                return null;
            }
        }
    }
}
?>
