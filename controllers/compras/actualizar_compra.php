<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/CompraController.php';

// Instanciar el controlador de compras
$controller = new CompraController();

$id_compra = null;

// Validar método GET y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    // Filtrar y validar el parámetro
    $id_compra = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    // Verificar si el parámetro es válido
    if ($id_compra === false) {
        $_SESSION['mensaje'] = 'ID de compra inválido.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/compras');
        exit;
    }

    // Completar la compra
    $resultado = $controller->completar($id_compra);

    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de compras
header('Location: ' . $URL . 'views/compras/index.php');
exit;
