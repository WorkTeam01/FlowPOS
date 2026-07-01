<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/UsuarioController.php';

// Instanciar el controlador
$controller = new UsuarioController();

$id_usuario = null;
$estado_actual = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['estado'])) {
    $id_usuario = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $estado_actual = filter_var($_GET['estado'], FILTER_VALIDATE_INT);

    if ($id_usuario === false || $estado_actual === false) {
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado del usuario.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/usuarios/index.php');
        exit;
    }

    $resultado = $controller->cambiarEstadoUsuario($id_usuario, $estado_actual);
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

header('Location: ' . $URL . 'views/usuarios/index.php');
exit;
