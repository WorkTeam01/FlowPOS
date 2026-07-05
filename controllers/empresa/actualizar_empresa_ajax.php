<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/EmpresaController.php';

// Verificar si es una solicitud AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso no permitido');
}

// Verificar si el usuario está autenticado
requireLogin();

requireCSRF();

header('Content-Type: application/json');

// Instanciar el controlador
$controller = new EmpresaController();

// Procesar la actualización
$resultado = $controller->actualizarAjax();

// Establecer mensaje en la sesión para mensajes.php
if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Empresa actualizada correctamente';
    $_SESSION['icono'] = 'success';
} else {
    // Opcional: guardar también los mensajes de error
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = 'error';
}

// Devolver respuesta JSON
echo json_encode($resultado);
exit;