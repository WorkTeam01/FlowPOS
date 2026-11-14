<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/SucursalController.php';

// El permiso se exige en servidor: un POST directo evade el menú de la vista.
requireAjaxEndpoint('sucursales');

// Instanciar el controlador
$controller = new SucursalController();

// Procesar la creación
$resultado = $controller->crearAjax();

// Establecer mensaje en la sesión para mensajes.php
if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Sucursal creada correctamente';
    $_SESSION['icono'] = 'success';
}

// Devolver respuesta JSON
echo json_encode($resultado);
exit;