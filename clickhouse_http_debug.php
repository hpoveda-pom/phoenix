<?php

$host = "clickhouse.pomcr.local";
$user = "phoenix";
$pass = "sCMPRZm8Y@@";

function clickhouseQuery(string $host, string $user, string $pass, string $sql): array {
    $url = "http://{$host}:8123/?query=" . urlencode($sql);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => "{$user}:{$pass}",
    ]);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        throw new Exception("cURL error: $err");
    }

    if ($code !== 200) {
        throw new Exception("HTTP $code - Response: $resp");
    }

    $json = json_decode($resp, true);
    if (!is_array($json)) {
        throw new Exception("Respuesta no JSON: $resp");
    }

    return $json;
}

try {
    $result = clickhouseQuery($host, $user, $pass, "SELECT 1 AS ok FORMAT JSON");
    echo "✅ Query OK\n";
    print_r($result);
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
