<?php
function class_Connections($Id){
    global $row_config, $conn_phoenix;
    
    // Inicializar array de debug global si no existe
    if (!isset($GLOBALS['debug_info'])) {
        $GLOBALS['debug_info'] = [];
    }
    $debug_info = &$GLOBALS['debug_info'];
    
    // Solo loggear si hay un flag de debug detallado o si hay errores
    $debug_detailed = isset($GLOBALS['debug_detailed']) && $GLOBALS['debug_detailed'] === true;
    
    $conn = null;
    $phoenix_conn = null;
    $created_temp_conn = false;
    
    // Intentar obtener conexión a Phoenix (definida en conn/phoenix.php usando config.php)
    if (isset($conn_phoenix) && $conn_phoenix instanceof mysqli && !$conn_phoenix->connect_error) {
        $phoenix_conn = $conn_phoenix;
        if ($debug_detailed) {
            $debug_info[] = "✓ Usando \$conn_phoenix existente";
        }
    } elseif (isset($row_config) && 
              isset($row_config['db_host']) && !empty(trim($row_config['db_host'])) && 
              isset($row_config['db_user']) && !empty(trim($row_config['db_user'])) &&
              isset($row_config['db_name']) && !empty(trim($row_config['db_name']))) {
        // Si $conn_phoenix no está disponible, crear una conexión temporal usando las credenciales de config.php
        // Usar @ para suprimir warnings y verificar el error manualmente
        $temp_conn = @new mysqli(
            trim($row_config['db_host']),
            trim($row_config['db_user']),
            isset($row_config['db_pass']) ? $row_config['db_pass'] : '',
            trim($row_config['db_name'])
        );
        
        if ($temp_conn && !$temp_conn->connect_error) {
            $temp_conn->set_charset("utf8mb4");
            $phoenix_conn = $temp_conn;
            $created_temp_conn = true;
            if ($debug_detailed) {
                $debug_info[] = "✓ Conexión temporal a Phoenix creada exitosamente";
            }
        } elseif ($temp_conn) {
            // Si hay error de conexión, cerrar el objeto
            $debug_info[] = "✗ Error al crear conexión temporal a Phoenix: " . $temp_conn->connect_error;
            @$temp_conn->close();
        } else {
            $debug_info[] = "✗ No se pudo crear conexión temporal a Phoenix";
        }
    }
    
    // Siempre leer de la tabla connections primero (si tenemos conexión a Phoenix)
    $found_in_table = false;
    if ($phoenix_conn instanceof mysqli) {
        $stmt = $phoenix_conn->prepare("SELECT Connector, Hostname, Port, Username, Password, ServiceName, `Schema`, Status FROM connections WHERE ConnectionId = ? AND Status = 1");
        if ($stmt) {
            $stmt->bind_param('i', $Id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $found_in_table = true;
                if ($debug_detailed) {
                    $debug_info[] = "✓ Conexión encontrada en tabla connections";
                    $debug_info[] = "  - Connector: " . (isset($row['Connector']) ? $row['Connector'] : 'N/A');
                    $debug_info[] = "  - Hostname: " . (isset($row['Hostname']) ? ($row['Hostname'] ?: '(vacío)') : 'N/A');
                    $debug_info[] = "  - Username: " . (isset($row['Username']) ? ($row['Username'] ?: '(vacío)') : 'N/A');
                    $debug_info[] = "  - Schema: " . (isset($row['Schema']) ? ($row['Schema'] ?: '(vacío)') : 'N/A');
                    $debug_info[] = "  - ServiceName: " . (isset($row['ServiceName']) ? ($row['ServiceName'] ?: '(vacío)') : 'N/A');
                }
                
                // Validar que todos los campos necesarios estén presentes
                $hostname = isset($row['Hostname']) ? trim($row['Hostname']) : '';
                $port = isset($row['Port']) && !empty($row['Port']) ? trim($row['Port']) : '3306';
                $username = isset($row['Username']) ? trim($row['Username']) : '';
                $password = isset($row['Password']) ? $row['Password'] : '';
                
                // Si ConnectionId es 1 o 2 y los campos Hostname/Username están vacíos, usar datos de config.php
                if (($Id == 1 || $Id == 2) && (empty($hostname) || empty($username))) {
                    // Usar datos de config.php como fallback
                    $hostname = (isset($row_config['db_host']) && !empty(trim($row_config['db_host']))) ? trim($row_config['db_host']) : 'localhost';
                    $username = (isset($row_config['db_user']) && !empty(trim($row_config['db_user']))) ? trim($row_config['db_user']) : 'root';
                    $password = isset($row_config['db_pass']) ? $row_config['db_pass'] : '';
                    $port = '3306';
                    if ($debug_detailed) {
                        $debug_info[] = "  → Usando datos de config.php como fallback (Hostname/Username vacíos)";
                    }
                }
                
                // Validar que los campos obligatorios no estén vacíos
                if (!empty($hostname) && !empty($username)) {
                    // Determinar la base de datos según el conector
                    $service_name = isset($row['ServiceName']) && $row['ServiceName'] !== null ? trim($row['ServiceName']) : '';
                    $schema = isset($row['Schema']) && $row['Schema'] !== null ? trim($row['Schema']) : '';
                    
                    if ($debug_detailed) {
                        $debug_info[] = "  → Valores raw - ServiceName: '" . (isset($row['ServiceName']) ? $row['ServiceName'] : 'NULL') . "', Schema: '" . (isset($row['Schema']) ? $row['Schema'] : 'NULL') . "'";
                        $debug_info[] = "  → Valores procesados - ServiceName: '$service_name', Schema: '$schema'";
                    }
                    
                    if ($row['Connector'] == 'MySQL' || $row['Connector'] == 'MariaDB' || $row['Connector'] == 'mysqli') {
                        // Para MySQL/MariaDB/mysqli, usar ServiceName como database si está disponible, sino Schema
                        if (!empty($service_name)) {
                            $database = $service_name;
                        } elseif (!empty($schema)) {
                            $database = $schema;
                        } else {
                            $database = '';
                        }
                        if ($debug_detailed) {
                            $debug_info[] = "  → Database determinada: '$database' (ServiceName: '$service_name', Schema: '$schema')";
                        }
                    } else {
                        // Para otros conectores, usar ServiceName
                        $database = !empty($service_name) ? $service_name : '';
                        if ($debug_detailed) {
                            $debug_info[] = "  → Database determinada: '$database' (ServiceName: '$service_name')";
                        }
                    }
                    
                    // Validar que la base de datos no esté vacía
                    if (!empty($database)) {
                        if ($debug_detailed) {
                            $debug_info[] = "  → Intentando conectar a: $hostname:$port/$database (usuario: $username)";
                        }
                        // Crear la conexión solo si todos los datos son válidos
                        $conn = class_connMysqli($hostname, $port, $username, $password, $database);
                        
                        if ($conn) {
                            if ($debug_detailed) {
                                $debug_info[] = "  ✓ Conexión establecida exitosamente";
                            }
                        } else {
                            $debug_info[] = "  ✗ Error al establecer conexión para ConnectionId: $Id";
                            
                            // Si la conexión falla y es ID 1 o 2, intentar con la base de datos de config.php
                            if (($Id == 1 || $Id == 2) && isset($row_config['db_name']) && !empty(trim($row_config['db_name']))) {
                                $db_name = trim($row_config['db_name']);
                                // Solo intentar con config.php si el Schema de la tabla es diferente
                                if ($database !== $db_name) {
                                    $debug_info[] = "  → Intentando fallback con base de datos de config.php: $db_name";
                                    $conn = class_connMysqli($hostname, $port, $username, $password, $db_name);
                                    if ($conn) {
                                        if ($debug_detailed) {
                                            $debug_info[] = "  ✓ Conexión establecida con fallback";
                                        }
                                    } else {
                                        $debug_info[] = "  ✗ Error también con fallback";
                                    }
                                }
                            }
                        }
                    } else {
                        $debug_info[] = "  ✗ Base de datos vacía o no determinada";
                    }
                }
            } else {
                $debug_info[] = "✗ No se encontró conexión con ConnectionId = $Id en la tabla connections";
            }
            $stmt->close();
        } else {
            $debug_info[] = "✗ Error al preparar consulta para leer tabla connections";
        }
    } else {
        $debug_info[] = "✗ No hay conexión a Phoenix disponible para leer tabla connections";
    }
    
    // Si no se pudo leer de la tabla (porque no hay conexión a Phoenix o no se encontró), usar fallback para IDs 1 y 2
    
    // Si no se encontró en la tabla connections y es ID 1 o 2, usar fallback con credenciales de config.php
    if (!$conn && ($Id == 1 || $Id == 2)) {
        // Intentar leer el Schema de la tabla connections aunque no tenga otros datos
        $schema_from_table = null;
        if ($phoenix_conn instanceof mysqli && !$phoenix_conn->connect_error) {
            $stmt_schema = $phoenix_conn->prepare("SELECT `Schema`, ServiceName FROM connections WHERE ConnectionId = ?");
            if ($stmt_schema) {
                $stmt_schema->bind_param('i', $Id);
                $stmt_schema->execute();
                $result_schema = $stmt_schema->get_result();
                if ($result_schema && $row_schema = $result_schema->fetch_assoc()) {
                    $schema_from_table = !empty($row_schema['ServiceName']) ? trim($row_schema['ServiceName']) : (!empty($row_schema['Schema']) ? trim($row_schema['Schema']) : null);
                }
                $stmt_schema->close();
            }
        }
        
        // Cerrar conexión temporal si la creamos (solo si NO es $conn_phoenix) - después de usarla
        if ($created_temp_conn && $phoenix_conn && $phoenix_conn !== $conn_phoenix) {
            try {
                if ($phoenix_conn && !$phoenix_conn->connect_error) {
                    @$phoenix_conn->close();
                }
            } catch (Exception $e) {
                // Ignorar errores al cerrar
            }
        }
        
        // Usar las credenciales de config.php (valores por defecto si no están disponibles)
        $db_host = (isset($row_config['db_host']) && !empty(trim($row_config['db_host']))) ? trim($row_config['db_host']) : 'localhost';
        $db_user = (isset($row_config['db_user']) && !empty(trim($row_config['db_user']))) ? trim($row_config['db_user']) : 'root';
        $db_pass = isset($row_config['db_pass']) ? $row_config['db_pass'] : '';
        
        // Determinar la base de datos: primero intentar Schema de la tabla, luego valores por defecto
        if ($schema_from_table) {
            $database = $schema_from_table;
        } else {
            $db_name = (isset($row_config['db_name']) && !empty(trim($row_config['db_name']))) ? trim($row_config['db_name']) : 'phoenix';
            // Para ID 1, usar la base de datos de Phoenix
            // Para ID 2, intentar phoenixdw, pero si no existe, usar phoenix
            $database = ($Id == 1) ? $db_name : 'phoenixdw';
        }
        
        $conn = class_connMysqli($db_host, '3306', $db_user, $db_pass, $database);
        
        // Si ID 2 falla y no usamos Schema de la tabla, intentar con phoenix
        if (!$conn && $Id == 2 && !$schema_from_table) {
            $db_name = (isset($row_config['db_name']) && !empty(trim($row_config['db_name']))) ? trim($row_config['db_name']) : 'phoenix';
            $conn = class_connMysqli($db_host, '3306', $db_user, $db_pass, $db_name);
        }
    }
    
    // Si no se pudo obtener la conexión, retornar null
    // Esto permitirá que el código que llama maneje el error apropiadamente
    if (!$conn) {
        $debug_info[] = "✗ No se pudo establecer conexión final para ConnectionId: $Id";
    }
    
    return $conn;
}
?>