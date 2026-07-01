<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador de Compras
require_once __DIR__ . '/CompraController.php';

// Instanciar el controlador de Compras
$controller = new CompraController();

// Procesar el formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controller->guardarr();

    // Guardar mensaje en la sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];

    // Redirigir según el resultado
    header('Location: ' . $URL . 'views/compras/' . $resultado['redirect']);
    exit;
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $URL . 'views/compras/ingresar.php');
    exit;
}
