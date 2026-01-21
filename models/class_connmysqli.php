<?php
function class_connMysqli($hostname, $port, $username, $password, $database){
    // Validar que los parámetros obligatorios no estén vacíos
    if (empty($hostname) || empty($username) || empty($database)) {
        return null;
    }
    
    // Usar @ para suprimir warnings y verificar el error manualmente
    $conn = @new mysqli($hostname, $username, $password, $database);

    // Verificar si la conexión se creó correctamente
    if (!$conn || !($conn instanceof mysqli)) {
        return null;
    }

    // Verificar si hay error de conexión
    if ($conn->connect_error) {
        // No intentar cerrar la conexión si hay error, simplemente retornar null
        // El objeto mysqli se limpiará automáticamente cuando se pierda la referencia
        return null;
    }

    // Cambiar a la variable correcta para la conexión
    $conn->set_charset("utf8mb4");

    return $conn;
}