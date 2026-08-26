<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/RolController.php';

// Verificar si es una solicitud AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso no permitido');
}

requireLogin();
requireCSRF();

header('Content-Type: application/json');

// Solo administradores pueden tocar el módulo de Roles (no basta el permiso
// granular 'permisos': un supervisor con ese permiso podría auto-otorgarse más).
$auth = new AuthorizationService();
if (!$auth->esAdministrador($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para gestionar roles']);
    exit;
}

$controller = new RolController();
$resultado = $controller->crearAjax();

if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Rol creado correctamente';
    $_SESSION['icono'] = 'success';
}

echo json_encode($resultado);
exit;
