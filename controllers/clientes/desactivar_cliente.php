<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/ClienteController.php';

// Instanciar el controlador de Persona
$controller = new ClienteController();

$id_persona = null;
$estado_actual = null;

// Verificar método GET y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['estado'])) {
    // Validar y filtrar los parámetros
    $id_persona = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $estado_actual = filter_var($_GET['estado'], FILTER_VALIDATE_INT);

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
