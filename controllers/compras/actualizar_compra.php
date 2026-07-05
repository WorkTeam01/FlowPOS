<?php
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos sobre el módulo de compras
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
if (!$authService->tienePermisoNombre($_SESSION['usuario_id'], 'compras') && !$authService->esAdministrador($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'index.php');
    exit;
}

requireCSRF();

require_once __DIR__ . '/CompraController.php';

// Instanciar el controlador de compras
$controller = new CompraController();

$id_compra = null;

// Validar método POST y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Filtrar y validar el parámetro
    $id_compra = filter_var($_POST['id'], FILTER_VALIDATE_INT);

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
