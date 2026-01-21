<?php
function class_ws($url, $data_node){

    // Inicializar cURL
    $ch = curl_init();

    // Configurar cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Devolver la respuesta en lugar de imprimirla
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evitar problemas con SSL si es necesario
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Tiempo máximo de espera

    // Ejecutar la solicitud
    $response = curl_exec($ch);

    // Manejo de errores en cURL
    if ($response === false) {
        die("Error en cURL: " . curl_error($ch));
    }

    // Cerrar cURL
    curl_close($ch);

    // Convertir XML a SimpleXML
    $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);

    // Convertir a Array
    $data = json_decode(json_encode($xml), true);
    if ($data_node) {
        $data = $data[$data_node];
    }


    $info = array(
        'page_rows' => count($data),
        'total_pages' => 1,
        'Total_rows' => count($data)

    );

    $headers = array_keys($data[0]);

    $response = array(
        'info' => $info,
        'headers' => $headers,
        'data' => $data,
    );

    return $response;
}