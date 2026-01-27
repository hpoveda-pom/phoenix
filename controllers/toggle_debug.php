<?php
session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['UsersId'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
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
