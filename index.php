<?php
// Incluir archivo de sesión
require_once 'views/layouts/session.php';

// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    header('Location: ' . $URL . 'views/login/login.php');
    exit;
}

// Incluir el servicio de autorización
require_once __DIR__ . '/services/AuthorizationService.php';
$authService = new AuthorizationService();

// Obtener datos del usuario actual
$currentUser = getCurrentUser();
$idusuariosesion = $currentUser['id'];
$cargoUsuario = strtolower($currentUser['cargo']);

// Incluir el header común para todos los usuarios
require_once 'views/layouts/header.php';

// Cargar el contenido específico según el rol
switch ($cargoUsuario) {
    case 'administrador':
        // Cargar el dashboard de administrador incorporado aquí
        include 'views/dashboard/dashboard.php';
        break;
    case 'vendedor':
        // Cargar el dashboard de vendedor incorporado aquí
        include 'views/dashboard/dashboard_vendedor.php';
        break;
    case 'supervisor':
        // Cargar el dashboard de supervisor incorporado aquí
        include 'views/dashboard/dashboard_supervisor.php';
        break;
    default:
        // Para usuarios con otros roles, cargar el dashboard general
        include 'views/dashboard/dashboard_general.php';
        break;
}

// Incluir los mensajes y el footer común para todos los usuarios
include_once 'views/layouts/mensajes.php';
require_once 'views/layouts/footer.php';
