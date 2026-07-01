<?php

/**
 * Endpoint para obtener datos del dashboard vía AJAX
 * 
 * Este archivo procesa solicitudes AJAX para obtener datos del dashboard
 * 
 * @version 1.0
 */

// Incluir archivo de sesión para verificar autenticación
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar si el usuario es administrador
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
$currentUser = getCurrentUser();
$idusuariosesion = $currentUser['id'];

if (!$authService->esAdministrador($idusuariosesion)) {
    // Devolver error si no es administrador
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para acceder a esta funcionalidad'
    ]);
    exit;
}

// Cargar el controlador del dashboard
require_once __DIR__ . '/DashboardController.php';
$controller = new DashboardController();

// Obtener parámetros de la solicitud
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'hoy';
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 5;

// Validar parámetros básicos
if (!in_array($periodo, ['hoy', 'semana', 'mes', 'anio', 'personalizado'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Período no válido'
    ]);
    exit;
}

// Obtener datos
$resultado = $controller->getAjaxData([
    'periodo' => $periodo,
    'fecha_inicio' => $fechaInicio,
    'fecha_fin' => $fechaFin,
    'limite' => $limite
]);

// Enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode($resultado);
