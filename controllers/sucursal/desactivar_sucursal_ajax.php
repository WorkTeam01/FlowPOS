<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/SucursalController.php';

// Verificar si es una solicitud AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso no permitido');
}

// Verificar si el usuario está autenticado
requireLogin();

header('Content-Type: application/json');

// Instanciar el controlador
$controller = new SucursalController();

// Procesar el cambio de estado
$resultado = $controller->cambiarEstadoAjax();

// Establecer mensaje en la sesión para mensajes.php
if ($resultado['success']) {
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = 'success';
} else {
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = 'error';
}

// Devolver respuesta JSON
echo json_encode($resultado);
exit;