<?php
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos sobre el módulo de clientes
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
if (!$authService->tienePermisoNombre($_SESSION['usuario_id'], 'clientes') && !$authService->esAdministrador($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'index.php');
    exit;
}

requireCSRF();

require_once __DIR__ . '/ClienteController.php';

// Instanciar el controlador de Persona
$controller = new ClienteController();

$id_persona = null;
$estado_actual = null;

// Verificar método POST y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['estado'])) {
    // Validar y filtrar los parámetros
    $id_persona = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $estado_actual = filter_var($_POST['estado'], FILTER_VALIDATE_INT);

    // Verificar si los datos son válidos
    if ($id_persona === false || $estado_actual === false) {
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado de la persona.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/clientes');
        exit;
    }

    // Cambiar el estado de la persona
    $resultado = $controller->cambiarEstado($id_persona, $estado_actual);

    // Establecer mensaje de sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir a la lista de personas
header('Location: ' . $URL . 'views/clientes/index.php');
exit;
