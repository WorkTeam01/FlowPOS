<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador de Persona
require_once __DIR__ . '/ClienteController.php';

// Instanciar el controlador
$controller = new ClienteController();

// Procesar el formulario de creación
$resultado = $controller->guardar();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $URL . 'views/clientes/' . $resultado['redirect']);
exit;
