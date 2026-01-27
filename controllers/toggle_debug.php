<?php
require_once('../config.php');
require_once('../conn/phoenix.php');

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['UsersId'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

// Verificar que el usuario sea administrador (UsersType == 1)
$UsersId = intval($_SESSION['UsersId']);
$result = $conn_phoenix->query("SELECT UsersType FROM users WHERE UsersId = $UsersId");
if ($result && $row = $result->fetch_assoc()) {
    $usersType = intval($row['UsersType'] ?? 0);
    if ($usersType != 1) {
        echo json_encode(['success' => false, 'error' => 'Solo los administradores pueden activar el modo debug']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo verificar el tipo de usuario']);
    exit;
}

// Obtener el estado del debug mode
$debug_mode = isset($_POST['debug_mode']) ? intval($_POST['debug_mode']) : 0;

// Guardar en sesión
$_SESSION['debug_mode'] = ($debug_mode == 1);

// Retornar respuesta exitosa
echo json_encode([
    'success' => true,
    'debug_mode' => $_SESSION['debug_mode']
]);
?>
