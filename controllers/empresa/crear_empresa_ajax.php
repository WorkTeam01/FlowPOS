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

header('Content-Type: application/json');

// Instanciar el controlador
$controller = new EmpresaController();

// Procesar la creación
$resultado = $controller->crearAjax();

// Establecer mensaje en la sesión para mensajes.php
if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Empresa creada correctamente';
    $_SESSION['icono'] = 'success';
    
    // Opcional: Guardar datos de la nueva empresa en sesión si se necesitan después
    $_SESSION['nueva_empresa'] = $resultado['empresa']['idempresa'];
} else {
    // Guardar mensaje de error si falla la creación
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = 'error';
    
    // Opcional: Guardar los datos del formulario para repoblar
    $_SESSION['form_data'] = $_POST;
}

// Devolver respuesta JSON
echo json_encode($resultado);
exit;