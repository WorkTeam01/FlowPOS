<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/ProductoController.php';

// Instanciar el controlador de productos
$controller = new ProductoController();

$id_producto = null;
$estado_actual = null;

// Validar método GET y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['estado'])) {
    // Filtrar y validar los parámetros
    $id_producto = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $estado_actual = filter_var($_GET['estado'], FILTER_VALIDATE_INT);

    // Verificar si los parámetros son válidos
    if ($id_producto === false || $estado_actual === false) {
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado del producto.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/productos/index.php');
        exit;
    }

    // Cambiar el estado del producto
    $resultado = $controller->cambiarEstadoProducto($id_producto, $estado_actual);
    
    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de productos
header('Location: ' . $URL . 'views/productos/index.php');
exit;