<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos sobre el módulo de nueva venta
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
if (!$authService->tienePermisoNombre($_SESSION['usuario_id'], 'nueva_venta') && !$authService->esAdministrador($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'index.php');
    exit;
}

requireCSRF();

// Incluir el controlador de Ventas
require_once __DIR__ . '/VentaController.php';

// Instanciar el controlador de Ventas
$controller = new VentaController();

// Procesar el formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controller->guardara();

    // Guardar mensaje en la sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];

    // Redirigir según el resultado
    header('Location: ' . $URL . 'views/ventas/' . $resultado['redirect']);
    exit;
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $URL . 'views/ventas/nueva.php');
    exit;
}
