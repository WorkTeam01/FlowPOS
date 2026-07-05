<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar token CSRF
requireCSRF();

// Verificar permisos sobre el módulo de clientes
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
if (!$authService->tienePermisoNombre($_SESSION['usuario_id'], 'clientes') && !$authService->esAdministrador($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'index.php');
    exit;
}

// Incluir el controlador de Persona
require_once __DIR__ . '/ClienteController.php';

// Instanciar el controlador
$controller = new ClienteController();

// Procesar el formulario de actualización
$resultado = $controller->actualizar();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $URL . 'views/clientes/' . $resultado['redirect']);
exit;
