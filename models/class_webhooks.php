<?php
function class_webhook($channel, $text){

    switch ($channel) {
        case 'urgents':
            $webhook_url = "https://test.webhook.office.com/webhookb2/5d4473ea-7133-4905-a812-09c8678b4bf6@ce86aad0-0f1d-4b27-917f-22ab75cd99dd/IncomingWebhook/31bb486803e14bf6b89acb035572c16e/68d52588-ee8a-4a46-8ee6-052d9a2ff0c9/V2OwS6KfiRNOOd0ahX7O6IPN2EvfX3dwaZTiYBZ-W4ACs1";
            break;
        
        default:
                $webhook_url = null;

            break;
    }

    // URL del webhook (la que te proporcionó Teams)
    $webhook_url = "https://test.webhook.office.com/webhookb2/5d4473ea-7133-4905-a812-09c8678b4bf6@ce86aad0-0f1d-4b27-917f-22ab75cd99dd/IncomingWebhook/31bb486803e14bf6b89acb035572c16e/68d52588-ee8a-4a46-8ee6-052d9a2ff0c9/V2OwS6KfiRNOOd0ahX7O6IPN2EvfX3dwaZTiYBZ-W4ACs1";

    // Mensaje que deseas enviar
    $data = [
        "text" => $text
    ];

    // Convertimos el mensaje a JSON
    $json_data = json_encode($data);

    // Iniciamos cURL
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data)
    ]);

    // Ejecutamos la solicitud
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verificamos la respuesta
    if ($http_code == 200) {
        $results = "Mensaje enviado correctamente a Microsoft Teams.";
    } else {
        $results = "Error al enviar el mensaje. Código HTTP: " . $http_code;
    }

    return $results;

}

