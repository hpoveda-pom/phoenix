<?php 
function class_curl($url){
    // Inicializar cURL
    $ch = curl_init($url);

    // Configurar opciones
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retornar el resultado en lugar de imprimirlo directamente
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Establecer un tiempo máximo de espera

    // Ejecutar la solicitud y almacenar la respuesta
    $response = curl_exec($ch);

    // Verificar si hubo errores
    if (curl_errno($ch)) {
        $results = 'Error:' . curl_error($ch);
    } else {
        $results = $response; // Mostrar el contenido obtenido de la URL
    }

    // Cerrar cURL
    curl_close($ch); 

    return $results;
}

