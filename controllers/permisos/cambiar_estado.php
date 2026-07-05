<?php
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos sobre el módulo de permisos
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
if (!$authService->tienePermisoNombre($_SESSION['usuario_id'], 'permisos') && !$authService->esAdministrador($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'index.php');
    exit;
}

requireCSRF();

require_once __DIR__ . '/PermisoController.php';

// Instanciar el controlador de permisos
$controller = new PermisoController();

// Validar método POST y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['estado'])) {
    // Filtrar y validar el ID y estado
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $estado = filter_var($_POST['estado'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

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
