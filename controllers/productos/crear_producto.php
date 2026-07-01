<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador de Productos
require_once __DIR__ . '/ProductoController.php';

// Definir la variable global URL si no existe
if (!isset($GLOBALS['URL'])) {
    $config = require_once __DIR__ . '/../../config/config.php';
    $GLOBALS['URL'] = $config['app']['url'];
}

// Instanciar el controlador de Productos
$controller = new ProductoController();

// Determinar la acción basada en la URL o parámetros
$accion = 'index'; // Acción por defecto

// Verificar si se está enviando un formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determinar si es creación o actualización
    $accion = isset($_POST['idproducto']) && !empty($_POST['idproducto']) ? 'update' : 'create';
}

// Procesar la acción correspondiente
switch ($accion) {
    case 'create':
        $resultado = $controller->guardar();
        break;
    case 'update':
        $resultado = $controller->actualizar();
        break;
    default:
        // Redirigir a la lista si no se reconoce la acción
        header('Location: ' . $GLOBALS['URL'] . 'views/productos/index.php');
        exit;
}

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $GLOBALS['URL'] . 'views/productos/' . $resultado['redirect']);
exit;