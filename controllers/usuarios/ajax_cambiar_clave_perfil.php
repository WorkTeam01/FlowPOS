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

// Aplicar las mismas reglas de corte que requireLogin() (token de revocación,
// inactividad, IP/User-Agent), pero respondiendo JSON: una redirección aquí
// dejaría al cliente con un parse error en silencio. El motivo de cierre se
// registra igualmente en BD, para que el panel no muestre la sesión como activa.
$corteSesion = motivoCorteSesion();
if ($corteSesion !== null) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $corteSesion]);
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
