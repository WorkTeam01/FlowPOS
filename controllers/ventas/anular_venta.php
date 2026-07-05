<?php
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos sobre el módulo de ventas
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
if (!$authService->tienePermisoNombre($_SESSION['usuario_id'], 'ventas') && !$authService->esAdministrador($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'index.php');
    exit;
}

requireCSRF();

require_once __DIR__ . '/VentaController.php';

// Instanciar el controlador de ventas
$controller = new VentaController();

$id_venta = null;

// Validar método POST y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Filtrar y validar el ID
    $id_venta = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    // Verificar si el ID es válido
    if ($id_venta === false) {
        $_SESSION['mensaje'] = 'ID de venta inválido.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/ventas/index.php');
        exit;
    }

    // Ejecutar la acción de anulación
    $resultado = $controller->anular($id_venta);

    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de ventas
header('Location: ' . $URL . 'views/ventas/index.php');
exit;
