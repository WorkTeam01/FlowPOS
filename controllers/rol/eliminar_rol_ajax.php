<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/RolController.php';

requireAjaxEndpoint(null, 'No tiene permisos para gestionar roles');

$controller = new RolController();
$resultado = $controller->eliminarAjax();

if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Rol eliminado correctamente';
    $_SESSION['icono'] = 'success';
}

echo json_encode($resultado);
exit;
