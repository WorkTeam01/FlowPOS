<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el controlador
require_once __DIR__ . '/PerfilController.php';

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Instanciar el controlador
$controller = new PerfilController();

// Procesar la solicitud
$result = $controller->cambiarClave();

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode($result);
exit;
