<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/RolController.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso no permitido');
}

requireLogin();
requireCSRF();

header('Content-Type: application/json');

$auth = new AuthorizationService();
if (!$auth->esAdministrador($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para gestionar roles']);
    exit;
}

$controller = new RolController();
$resultado = $controller->actualizarAjax();

if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Rol actualizado correctamente';
    $_SESSION['icono'] = 'success';
}

echo json_encode($resultado);
exit;
