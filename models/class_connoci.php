<?php
function class_connOci($hostname, $port, $username, $password, $servicename) {
    
    // Verificar si la función oci_connect() está disponible
    if (!function_exists('oci_connect')) {
        $errorMsg = "Error: La extensión OCI8 no está habilitada en PHP.";
        return false;
    }

    $conn = oci_connect($username, $password, $hostname.$servicename, 'AL32UTF8');

    if (!$conn) {
        $e = oci_error();
        $errorMsg = $e['message'];
        return false;
    }

    return $conn;
}