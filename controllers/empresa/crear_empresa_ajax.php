<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/EmpresaController.php';

// El permiso se exige en servidor: un POST directo evade el menú de la vista.
requireAjaxEndpoint('empresa');

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