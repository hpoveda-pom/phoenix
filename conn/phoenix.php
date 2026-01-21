<?php 
// Conectar a la base de datos
$conn_phoenix = new mysqli($row_config['db_host'], $row_config['db_user'], $row_config['db_pass'], $row_config['db_name']);

// Verificar conexión
if ($conn_phoenix->connect_error) {
    die("Conexión fallida: " . $conn_phoenix->connect_error);
}