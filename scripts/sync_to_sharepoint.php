<?php
$tenantId = "ce86aad0-0f1d-4b27-917f-22ab75cd99dd";
$clientId = "7f361aa4-1335-49f9-85ef-23f2e4e8e8ad";
$clientSecret = "LHQ8Q~4FO3bnysPXDjkI0V1DnMpBry56NkLmhaMF";  // Asegúrate de usar el nuevo secreto aquí

// URL para obtener el token de acceso
$tokenUrl = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

// Datos para la autenticación
$data = [
    'grant_type' => 'client_credentials',
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'scope' => 'https://graph.microsoft.com/.default',
];

// Usamos cURL para obtener el token de acceso
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
]);

$response = curl_exec($ch);

// Verificar si hubo error en la ejecución de cURL
if ($response === false) {
    die('Error al obtener el token de acceso: ' . curl_error($ch));
}

curl_close($ch);

// Verificar si la respuesta contiene el token de acceso
$tokenData = json_decode($response, true);

// Verificar si el token se obtuvo correctamente
if (isset($tokenData['access_token'])) {
    $accessToken = $tokenData['access_token'];
} else {
    die('Error al obtener el token de acceso: ' . $tokenData['error_description']);
}

// Verificar si el archivo existe
$filePath = '/var/www/phoenix/data/report_162.json'; // Ruta del archivo que deseas subir
if (!file_exists($filePath)) {
    die('El archivo no existe en la ruta especificada.');
}

$fileName = basename($filePath);
$fileContent = file_get_contents($filePath);

// Verificar si el contenido del archivo se cargó correctamente
if ($fileContent === false) {
    die('Error al leer el contenido del archivo.');
}

// Usamos la ruta de la carpeta destino en OneDrive
$folderPath = 'Documentos/3. Data science/Reportes/Sharepoint/Admisiones y Mercadeo';
$folderPath = str_replace(' ', '%20', $folderPath);  // Reemplazar espacios por %20

// Construir la URL de carga
$uploadUrl = "https://graph.microsoft.com/v1.0/me/drive/root:/{$folderPath}/{$fileName}:/content";

// Debugging: Mostrar la URL generada
echo "URL de carga generada: $uploadUrl\n";

// Configuración de cURL para subir el archivo
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $uploadUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PUT, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $accessToken",
    "Content-Type: application/octet-stream",
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);

$response = curl_exec($ch);

// Capturar errores de cURL
if ($response === false) {
    $error = curl_error($ch);
    die("Error al subir el archivo a OneDrive. Detalles del error: $error");
}

curl_close($ch);

// Mostrar la respuesta completa de la API de Microsoft Graph para mayor detalle
echo "Respuesta completa de la API:\n";
echo $response . "\n";

// Decodificar la respuesta de la API
$uploadedFile = json_decode($response, true);

// Verificar si la respuesta contiene errores
if (isset($uploadedFile['error'])) {
    die('Error al subir el archivo: ' . $uploadedFile['error']['message'] . '. Respuesta completa: ' . print_r($uploadedFile, true));
}

// Si el archivo se subió correctamente, mostrar el ID del archivo subido
echo "Archivo subido con éxito: " . $uploadedFile['id'] . "\n";
?>
