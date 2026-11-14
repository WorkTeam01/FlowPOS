<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/CategoriaController.php';

// El permiso se exige en servidor: un POST directo evade el menú de la vista.
requireAjaxEndpoint('categorias');

// Instanciar el controlador
$controller = new CategoriaController();

// Procesar la creación
$resultado = $controller->crearAjax();

// Establecer mensaje en la sesión para mensajes.php
if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Categoría creada correctamente';
    $_SESSION['icono'] = 'success';
}

// Devolver respuesta JSON
echo json_encode($resultado);
exit;
