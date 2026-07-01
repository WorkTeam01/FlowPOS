<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador de productos
require_once __DIR__ . '/ProductoController.php';

// Instanciar el controlador
$controller = new ProductoController();

// Procesar el formulario de actualización
$resultado = $controller->actualizar();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $URL . 'views/productos/' . $resultado['redirect']);
exit;