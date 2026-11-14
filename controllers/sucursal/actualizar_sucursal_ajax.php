<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/SucursalController.php';

// El permiso se exige en servidor: un POST directo evade el menú de la vista.
requireAjaxEndpoint('sucursales');

// Instanciar el controlador
$controller = new SucursalController();

// Procesar la actualización
$resultado = $controller->actualizarAjax();

// Establecer mensaje en la sesión para mensajes.php
if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Sucursal actualizada correctamente';
    $_SESSION['icono'] = 'success';
}

// Devolver respuesta JSON
echo json_encode($resultado);
exit;