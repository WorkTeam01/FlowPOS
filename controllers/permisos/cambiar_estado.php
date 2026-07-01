<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/PermisoController.php';

// Instanciar el controlador de permisos
$controller = new PermisoController();

// Validar método GET y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['estado'])) {
    // Filtrar y validar el ID y estado
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $estado = filter_var($_GET['estado'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

    // Verificar si los valores son válidos
    if ($id === false || $estado === false) {
        $_SESSION['mensaje'] = 'Parámetros inválidos.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/permisos/index.php');
        exit;
    }

    // Ejecutar la acción de cambio de estado
    $resultado = $controller->cambiarEstado($id, $estado);

    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de permisos
header('Location: ' . $URL . 'views/permisos/index.php');
exit;
