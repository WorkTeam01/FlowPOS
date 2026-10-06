<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/SucursalController.php';

// Verificar si es una solicitud AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso no permitido');
}

// Verificar si el usuario está autenticado
requireLogin();

requireCSRF();

// El permiso del módulo no se puede delegar a la vista: un POST directo evade el menú.
$authService = new AuthorizationService();
if (!$authService->tienePermisoNombre($_SESSION['usuario_id'], 'sucursales') && !$authService->esAdministrador($_SESSION['usuario_id'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para realizar esta acción', 'icon' => 'error']);
    exit;
}

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