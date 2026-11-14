<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/RolController.php';

// Solo administradores pueden tocar el módulo de Roles (no basta el permiso
// granular 'permisos': un supervisor con ese permiso podría auto-otorgarse más).
requireAjaxEndpoint(null, 'No tiene permisos para gestionar roles');

$controller = new RolController();
$resultado = $controller->guardarPermisosAjax($_SESSION['usuario_id']);

if ($resultado['success']) {
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = 'success';
}

echo json_encode($resultado);
exit;
