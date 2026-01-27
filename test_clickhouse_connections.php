<?php

$host = "clickhouse.pomcr.local";
$user = "phoenix";
$pass = "Solid256!";

// Puertos a validar
$ports = [
    8123 => "HTTP API",
    9000 => "Native TCP",
    9009 => "Inter-server / Cluster",
    9004 => "MySQL interface",
    9005 => "PostgreSQL interface",
];

// Timeout de conexión (segundos)
$timeout = 2;

function checkPort($host, $port, $timeout = 2) {
    $errno = 0;
    $errstr = "";
    $conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($conn) {
        fclose($conn);
        return [true, null];
    }
    return [false, "($errno) $errstr"];
}

function testClickHouseHttpQuery($host, $user, $pass) {
    $url = "http://{$host}:8123/?query=" . urlencode("SELECT 1 FORMAT JSON");

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => "{$user}:{$pass}",
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [false, "cURL error: $curlErr"];
    }

    if ($httpCode !== 200) {
        return [false, "HTTP status $httpCode, response: $response"];
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        return [false, "Respuesta no es JSON válido: $response"];
    }

    return [true, $json];
}

// =====================
// Ejecución de pruebas
// =====================

echo "=== ClickHouse Connection Check ===\n";
echo "Host: $host\n";
echo "User: $user\n\n";

foreach ($ports as $port => $label) {
    [$ok, $err] = checkPort($host, $port, $timeout);
    echo str_pad("$port ($label)", 35, " ") . " → " . ($ok ? "abierto ✅" : "cerrado ❌ $err") . "\n";
}

echo "\n=== Test Query (HTTP API 8123) ===\n";

[$okHttp, $data] = testClickHouseHttpQuery($host, $user, $pass);

if ($okHttp) {
    echo "Query OK ✅\n";
    echo "Respuesta:\n";
    print_r($data);
} else {
    echo "Query FAIL ❌\n";
    echo "Detalle: $data\n";
}

echo "\nListo.\n";
