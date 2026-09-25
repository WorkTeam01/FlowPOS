<?php
// Incluir archivo de sesión
require_once 'views/layouts/session.php';

// Verificar si el usuario está autenticado (con mensaje de sesión finalizada si aplica)
requireLogin();

// Incluir el servicio de autorización
require_once __DIR__ . '/services/AuthorizationService.php';
require_once __DIR__ . '/models/Rol.php';
$authService = new AuthorizationService();

// Obtener datos del usuario actual
$currentUser = getCurrentUser();
$idusuariosesion = $currentUser['id'];

// El dashboard a cargar se resuelve contra la BD por rol.dashboard, nunca por
// el dato de sesión (que puede quedar desactualizado tras un deploy o cambio de rol).
$rolModelo = new Rol();
$dashboardArchivo = $rolModelo->getDashboardDeUsuario($idusuariosesion);

// Whitelist de dashboards válidos -> archivo físico en views/dashboard/
$dashboardsDisponibles = [
    'dashboard.php' => 'views/dashboard/dashboard.php',
    'dashboard_vendedor.php' => 'views/dashboard/dashboard_vendedor.php',
    'dashboard_supervisor.php' => 'views/dashboard/dashboard_supervisor.php',
    'dashboard_general.php' => 'views/dashboard/dashboard_general.php',
];
$dashboardInclude = $dashboardsDisponibles[$dashboardArchivo] ?? 'views/dashboard/dashboard_general.php';

// Ninguno de los 4 dashboards usa DataTables; Select2 solo lo usan los de
// administrador, vendedor y supervisor (dashboard_general no tiene JS propio).
$skip_datatables = true;
$skip_select2 = !in_array($dashboardInclude, [
    'views/dashboard/dashboard.php',
    'views/dashboard/dashboard_vendedor.php',
    'views/dashboard/dashboard_supervisor.php',
], true);

// Incluir el header común para todos los usuarios
require_once 'views/layouts/header.php';

// Cargar el contenido específico según el dashboard asignado al rol
include $dashboardInclude;

// Incluir los mensajes y el footer común para todos los usuarios
include_once 'views/layouts/mensajes.php';
require_once 'views/layouts/footer.php';
