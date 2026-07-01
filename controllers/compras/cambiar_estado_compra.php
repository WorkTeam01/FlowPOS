<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/CompraController.php';

// Instanciar el controlador de compras
$controller = new CompraController();

$id_compra = null;
$accion = null;

// Validar método GET y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['accion'])) {
    // Filtrar y validar los parámetros
    $id_compra = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $accion = filter_var($_GET['accion'], FILTER_SANITIZE_STRING);

    // Verificar si los parámetros son válidos
    if ($id_compra === false || !in_array($accion, ['completar', 'cancelar'])) {
        $_SESSION['mensaje'] = 'Parámetros inválidos.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/compras/index.php');
        exit;
    }

    // Ejecutar la acción correspondiente
    if ($accion === 'completar') {
        $resultado = $controller->completar($id_compra);
    } else {
        $resultado = $controller->cancelar($id_compra);
    }

    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de compras
header('Location: ' . $URL . 'views/compras');
exit;
