<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/EmpresaController.php';

// El permiso se exige en servidor: un POST directo evade el menú de la vista.
requireAjaxEndpoint('empresa');

// Instanciar el controlador
$controller = new EmpresaController();

// Procesar el cambio de estado
$resultado = $controller->cambiarEstadoAjax();

// Establecer mensaje en la sesión para mensajes.php
if ($resultado['success']) {
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = 'success';
}

// Devolver respuesta JSON
echo json_encode($resultado);
exit;