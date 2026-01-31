<?php
header("Content-Type: text/plain; charset=utf-8");

$host = "TU_HOST_AQUI";   // ejemplo: 10.0.0.5 o clickhouse.local o sqlserver.domain
$port = 9000;             // ClickHouse nativo = 9000 | HTTP = 8123 | SQL Server = 1433

echo "=== NETWORK CHECK ===\n";
echo "Host: $host\n";
echo "Port: $port\n\n";

echo "DNS resolve:\n";
$ip = gethostbyname($host);
echo "Resolved IP: $ip\n\n";

echo "TCP Connect test:\n";
$start = microtime(true);
$fp = @fsockopen($host, $port, $errno, $errstr, 3);
$time = round((microtime(true) - $start) * 1000);

if ($fp) {
    fclose($fp);
    echo "OK connected in {$time}ms\n";
} else {
    echo "FAILED ($errno) $errstr in {$time}ms\n";
}
