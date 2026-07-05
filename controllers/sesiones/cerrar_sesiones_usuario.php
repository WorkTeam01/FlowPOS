<?php
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos sobre el módulo de sesiones
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
if (!$authService->tienePermisoNombre($_SESSION['usuario_id'], 'sesiones') && !$authService->esAdministrador($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'index.php');
    exit;
}

requireCSRF();

require_once __DIR__ . '/SesionController.php';

// Instanciar el controlador de sesiones
$controller = new SesionController();

// Validar método POST y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Filtrar y validar el ID
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

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
