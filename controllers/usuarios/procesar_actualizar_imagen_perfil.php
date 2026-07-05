<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos sobre el módulo de perfil
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
if (!$authService->tienePermisoNombre($_SESSION['usuario_id'], 'perfil') && !$authService->esAdministrador($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'index.php');
    exit;
}

requireCSRF();

// Incluir el controlador de perfil
require_once __DIR__ . '/PerfilController.php';

// Instanciar el controlador
$controller = new PerfilController();
$resultado = $controller->actualizarImagenPerfil();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $URL . $resultado['redirect']);
exit;
