<?php
// Incluir el archivo de sesión (inicia sesión y provee las funciones CSRF)
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador
require_once __DIR__ . '/PerfilController.php';

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Este endpoint siempre responde JSON; no depender de la heurística isAjaxRequest()
if (!verifyCSRFToken(getRequestCSRFToken())) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tu sesión de formulario expiró. Vuelve a intentarlo, por favor.']);
    exit;
}

// Instanciar el controlador
$controller = new PerfilController();

// Procesar la solicitud
$result = $controller->cambiarClave();

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode($result);
exit;
