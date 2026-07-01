<?php

/**
 * Endpoint para obtener datos del dashboard de supervisor vía AJAX
 * 
 * Este archivo procesa solicitudes AJAX para obtener datos del dashboard de supervisor
 * 
 * @version 1.0
 */

// Activar reporte de errores para depuración
ini_set('display_errors', 0); // Desactivar muestra de errores en producción
error_reporting(E_ALL);

// Incluir archivo de sesión para verificar autenticación
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar si el usuario es supervisor
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
$currentUser = getCurrentUser();
$idusuariosesion = $currentUser['id'];

if (strtolower($currentUser['cargo']) !== 'supervisor') {
    // Devolver error si no es supervisor
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para acceder a esta funcionalidad'
    ]);
    exit;
}

try {
    // Cargar el controlador del dashboard de supervisor
    require_once __DIR__ . '/DashboardSupervisorController.php';
    $controller = new DashboardSupervisorController();

    // Obtener parámetros de la solicitud
    $periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'hoy';
    $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
    $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 5;

    // Validar parámetros básicos
    if (!in_array($periodo, ['hoy', 'semana', 'mes', 'anio', 'personalizado'])) {
        throw new Exception('Período no válido');
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
} catch (Exception $e) {
    // Capturar cualquier error y devolverlo en formato JSON
    error_log('Error en get_supervisor_dashboard_data.php: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
