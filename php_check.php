<?php
header("Content-Type: text/plain; charset=utf-8");

echo "=== PHP CHECK ===\n";
echo "Date: " . date("Y-m-d H:i:s") . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "OS: " . PHP_OS . "\n";
echo "SAPI: " . php_sapi_name() . "\n\n";

$exts = [
    "curl", "openssl", "pdo", "pdo_mysql", "mysqli",
    "pdo_sqlsrv", "sqlsrv",
];

echo "=== Extensions ===\n";
foreach ($exts as $e) {
    echo str_pad($e, 12) . " => " . (extension_loaded($e) ? "OK" : "MISSING") . "\n";
}

echo "\n=== Loaded .ini files ===\n";
echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
echo "Scan dir: " . (php_ini_scanned_files() ?: "none") . "\n";
