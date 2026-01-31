<?php
header("Content-Type: text/plain; charset=utf-8");

$host = "TU_CLICKHOUSE_HOST";
$port = 8123;
$user = "default";
$pass = ""; // si aplica

$url = "http://{$host}:{$port}/?query=" . urlencode("SELECT 1");

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 5,
]);

if ($user !== "") {
    curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
}

$response = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "=== CLICKHOUSE HTTP TEST ===\n";
echo "URL: $url\n\n";

if ($response === false) {
    echo "CURL ERROR: $err\n";
} else {
    echo "HTTP CODE: " . ($info["http_code"] ?? "?") . "\n";
    echo "RESPONSE:\n$response\n";
}
