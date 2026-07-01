<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador de perfil
require_once __DIR__ . '/PerfilController.php';

// Instanciar el controlador
$controller = new PerfilController();
$resultado = $controller->actualizarImagenPerfil();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $URL . $resultado['redirect']);
exit;
