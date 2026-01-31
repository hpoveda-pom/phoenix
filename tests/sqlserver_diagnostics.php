<?php
// Script de diagnóstico para SQL Server (no integrado a la UI)
// Uso:
//   /phoenix/tests/sqlserver_diagnostics.php
//   /phoenix/tests/sqlserver_diagnostics.php?id=8

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../conn/phoenix.php');
require_once(__DIR__ . '/../models/class_connsqlserver.php');

function bool_text($value) {
    return $value ? 'SI' : 'NO';
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function get_ini_value($key) {
    $value = ini_get($key);
    if ($value === false) {
        return 'N/A';
    }
    if ($value === '') {
        return '(vacío)';
    }
    return $value;
}

function build_sqlserver_server_string($hostname, $port) {
    $hostname = trim((string)$hostname);
    $port = trim((string)$port);
    $has_named_instance = (strpos($hostname, '\\') !== false);

    if ($has_named_instance) {
        return $hostname;
    }
    if (!empty($port)) {
        return $hostname . ',' . $port;
    }
    return $hostname . ',1433';
}

function test_tcp_socket($hostname, $port, $timeout = 3) {
    $hostname = trim((string)$hostname);
    $port = trim((string)$port);
    if ($hostname === '' || $port === '') {
        return [
            'ok' => false,
            'message' => 'Hostname o puerto vacío'
        ];
    }

    $errno = 0;
    $errstr = '';
    $start = microtime(true);
    $fp = @fsockopen($hostname, (int)$port, $errno, $errstr, $timeout);
    $elapsed = round((microtime(true) - $start) * 1000, 2);

    if ($fp) {
        fclose($fp);
        return [
            'ok' => true,
            'message' => "OK ({$elapsed} ms)"
        ];
    }

    return [
        'ok' => false,
        'message' => "FALLÓ ({$elapsed} ms) - {$errstr} ({$errno})"
    ];
}

function test_pdo_sqlsrv($server_string, $database, $username, $password) {
    if (!extension_loaded('pdo_sqlsrv')) {
        return ['ok' => false, 'message' => 'Extensión pdo_sqlsrv no cargada'];
    }

    $dsn = "sqlsrv:Server={$server_string};Database={$database};Encrypt=false;TrustServerCertificate=true";
    try {
        $conn = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        $conn->query("SELECT 1");
        return ['ok' => true, 'message' => 'Conexión OK'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function test_pdo_odbc($driver, $server_string, $database, $username, $password) {
    if (!extension_loaded('pdo_odbc')) {
        return ['ok' => false, 'message' => 'Extensión pdo_odbc no cargada'];
    }

    $dsn = "odbc:Driver={" . $driver . "};Server={$server_string};Database={$database};TrustServerCertificate=yes";
    try {
        $conn = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        $conn->query("SELECT 1");
        return ['ok' => true, 'message' => 'Conexión OK'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function test_class_conn_sqlserver($hostname, $port, $username, $password, $database) {
    $GLOBALS['debug_info'] = [];
    $GLOBALS['debug_detailed'] = true;

    try {
        $conn = class_connSqlServer($hostname, $port, $username, $password, $database);
        if ($conn instanceof PDO) {
            $conn->query("SELECT 1");
            return [
                'ok' => true,
                'message' => 'Conexión OK',
                'debug' => $GLOBALS['debug_info']
            ];
        }
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'message' => $e->getMessage(),
            'debug' => $GLOBALS['debug_info']
        ];
    }

    return [
        'ok' => false,
        'message' => 'No se pudo establecer conexión (retornó null)',
        'debug' => $GLOBALS['debug_info']
    ];
}

// Obtener conexiones SQL Server desde la tabla connections
$connections = [];
$conn_error = null;

if (!isset($conn_phoenix) || !($conn_phoenix instanceof mysqli) || $conn_phoenix->connect_error) {
    $conn_error = 'No se pudo conectar a la BD Phoenix para leer conexiones.';
} else {
    $id_filter = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $sql = "SELECT ConnectionId, Title, Hostname, Port, Username, Password, ServiceName, `Schema`, Connector 
            FROM connections 
            WHERE (Connector = 'sqlserver' OR Connector = 'mssql')";
    if ($id_filter > 0) {
        $sql .= " AND ConnectionId = " . $id_filter;
    }
    $sql .= " ORDER BY ConnectionId ASC";

    $result = $conn_phoenix->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $connections[] = $row;
        }
    } else {
        $conn_error = 'Error al consultar conexiones: ' . $conn_phoenix->error;
    }
}

// Info del entorno
$info = [
    'PHP Version' => PHP_VERSION,
    'SAPI' => php_sapi_name(),
    'OS' => PHP_OS_FAMILY . ' - ' . PHP_OS,
    'php.ini cargado' => php_ini_loaded_file() ?: 'N/A',
    'php.ini escaneados' => php_ini_scanned_files() ?: 'N/A',
    'extension_dir' => get_ini_value('extension_dir'),
    'memory_limit' => get_ini_value('memory_limit'),
    'default_socket_timeout' => get_ini_value('default_socket_timeout'),
];

$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_sqlsrv' => extension_loaded('pdo_sqlsrv'),
    'sqlsrv' => extension_loaded('sqlsrv'),
    'pdo_odbc' => extension_loaded('pdo_odbc'),
    'odbc' => extension_loaded('odbc'),
    'openssl' => extension_loaded('openssl'),
    'curl' => extension_loaded('curl'),
    'mbstring' => extension_loaded('mbstring')
];

$pdo_drivers = [];
try {
    $pdo_drivers = PDO::getAvailableDrivers();
} catch (Throwable $e) {
    $pdo_drivers = ['Error: ' . $e->getMessage()];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Diagnóstico SQL Server - Phoenix</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0b1220; color: #e5e7eb; margin: 20px; }
        h1, h2, h3 { color: #93c5fd; }
        .card { background: #111827; border: 1px solid #1f2937; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #1f2937; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #0f172a; }
        .ok { color: #4ade80; font-weight: bold; }
        .fail { color: #f87171; font-weight: bold; }
        .muted { color: #9ca3af; }
        .mono { font-family: Consolas, monospace; white-space: pre-wrap; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 6px; font-size: 12px; }
        .badge-ok { background: #064e3b; color: #bbf7d0; }
        .badge-fail { background: #7f1d1d; color: #fecaca; }
    </style>
</head>
<body>
    <h1>Diagnóstico SQL Server - Phoenix</h1>
    <div class="card">
        <h2>Entorno PHP</h2>
        <table>
            <tbody>
                <?php foreach ($info as $k => $v): ?>
                    <tr>
                        <th><?php echo h($k); ?></th>
                        <td><?php echo h($v); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th>PDO drivers</th>
                    <td><?php echo h(implode(', ', $pdo_drivers)); ?></td>
                </tr>
            </tbody>
        </table>
        <h3>Extensiones clave</h3>
        <table>
            <thead>
                <tr>
                    <th>Extensión</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($extensions as $ext => $loaded): ?>
                    <tr>
                        <td><?php echo h($ext); ?></td>
                        <td>
                            <span class="badge <?php echo $loaded ? 'badge-ok' : 'badge-fail'; ?>">
                                <?php echo $loaded ? 'Cargada' : 'No cargada'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Conexiones SQL Server</h2>
        <?php if ($conn_error): ?>
            <p class="fail"><?php echo h($conn_error); ?></p>
        <?php elseif (empty($connections)): ?>
            <p class="muted">No hay conexiones SQL Server registradas en la tabla connections.</p>
        <?php else: ?>
            <?php foreach ($connections as $c): ?>
                <?php
                    $hostname = trim((string)$c['Hostname']);
                    $port = trim((string)$c['Port']);
                    $username = trim((string)$c['Username']);
                    $password = (string)$c['Password'];
                    $database = !empty($c['Schema']) ? trim((string)$c['Schema']) : trim((string)$c['ServiceName']);
                    $server_string = build_sqlserver_server_string($hostname, $port);

                    $tcp_test = test_tcp_socket($hostname, $port);
                    $class_test = test_class_conn_sqlserver($hostname, $port, $username, $password, $database);
                    $pdo_sqlsrv_test = test_pdo_sqlsrv($server_string, $database, $username, $password);
                    $odbc_drivers = [
                        'ODBC Driver 18 for SQL Server',
                        'ODBC Driver 17 for SQL Server',
                        'ODBC Driver 13 for SQL Server',
                        'SQL Server Native Client 11.0',
                        'SQL Server'
                    ];
                ?>
                <h3><?php echo h($c['Title']); ?> (ID: <?php echo h($c['ConnectionId']); ?>)</h3>
                <table>
                    <tbody>
                        <tr>
                            <th>Hostname</th>
                            <td><?php echo h($hostname); ?></td>
                        </tr>
                        <tr>
                            <th>Puerto</th>
                            <td><?php echo h($port ?: '(vacío)'); ?></td>
                        </tr>
                        <tr>
                            <th>Base de datos</th>
                            <td><?php echo h($database ?: '(vacío)'); ?></td>
                        </tr>
                        <tr>
                            <th>Server string</th>
                            <td class="mono"><?php echo h($server_string); ?></td>
                        </tr>
                        <tr>
                            <th>TCP (hostname:port)</th>
                            <td class="<?php echo $tcp_test['ok'] ? 'ok' : 'fail'; ?>">
                                <?php echo h($tcp_test['message']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>class_connSqlServer</th>
                            <td class="<?php echo $class_test['ok'] ? 'ok' : 'fail'; ?>">
                                <?php echo h($class_test['message']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>pdo_sqlsrv directo</th>
                            <td class="<?php echo $pdo_sqlsrv_test['ok'] ? 'ok' : 'fail'; ?>">
                                <?php echo h($pdo_sqlsrv_test['message']); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h4>Drivers ODBC</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($odbc_drivers as $driver): ?>
                            <?php $odbc_test = test_pdo_odbc($driver, $server_string, $database, $username, $password); ?>
                            <tr>
                                <td><?php echo h($driver); ?></td>
                                <td class="<?php echo $odbc_test['ok'] ? 'ok' : 'fail'; ?>">
                                    <?php echo h($odbc_test['message']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h4>Debug de class_connSqlServer</h4>
                <pre class="mono"><?php echo h(implode("\n", $class_test['debug'] ?? [])); ?></pre>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
