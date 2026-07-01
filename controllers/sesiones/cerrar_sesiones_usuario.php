<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/SesionController.php';

// Instanciar el controlador de sesiones
$controller = new SesionController();

// Validar método GET y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    // Filtrar y validar el ID
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    // Verificar si el ID es válido
    if ($id === false) {
        $_SESSION['mensaje'] = 'ID de usuario inválido.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/sesiones/index.php');
        exit;
    }

    // Ejecutar la acción de cierre de sesiones
    $resultado = $controller->cerrarSesionesUsuario($id);

    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de sesiones
header('Location: ' . $URL . 'views/sesiones/index.php');
exit;
