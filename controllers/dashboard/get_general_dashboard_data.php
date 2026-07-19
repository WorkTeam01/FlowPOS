<?php

/**
 * Endpoint AJAX para el dashboard general (usuarios sin rol específico)
 *
 * Devuelve únicamente los datos de las secciones para las que el usuario
 * autenticado tiene permiso, según AuthorizationService::tienePermisoNombre.
 *
 * @version 1.0
 */

require_once __DIR__ . '/../../views/layouts/session.php';

requireLogin();

require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
$currentUser = getCurrentUser();
$idusuariosesion = $currentUser['id'];

header('Content-Type: application/json');

$respuesta = ['success' => true];

if ($authService->tienePermisoNombre($idusuariosesion, 'productos')) {
    require_once __DIR__ . '/../../models/Producto.php';
    require_once __DIR__ . '/../../models/Categoria.php';

    $productoModel = new Producto();
    $categoriaModel = new Categoria();

    $respuesta['inventario'] = [
        'totalProductos' => count($productoModel->getAll()),
        'totalCategorias' => count($categoriaModel->getAll()),
        'stockBajo' => count($productoModel->getStockBajo()),
    ];
}

if ($authService->tienePermisoNombre($idusuariosesion, 'ventas')) {
    require_once __DIR__ . '/../../models/Venta.php';

    $ventaModel = new Venta();
    $ventasRecientes = array_slice($ventaModel->getAll(), 0, 4);

    $respuesta['actividadReciente'] = array_map(function ($venta) {
        return [
            'id' => $venta['idventa'],
            'total' => (float) $venta['totalventa'],
            'fecha' => $venta['fechacreacion'],
        ];
    }, $ventasRecientes);
}

if ($authService->tienePermisoNombre($idusuariosesion, 'clientes')) {
    require_once __DIR__ . '/../../models/Cliente.php';

    $clienteModel = new Cliente();
    $clientesRecientes = array_slice($clienteModel->getAll(), 0, 4);

    $respuesta['clientesRecientes'] = array_map(function ($cliente) {
        return [
            'id' => $cliente['idcliente'],
            'nombre' => trim($cliente['nombres'] . ' ' . $cliente['apellidopaterno']),
            'fecha' => $cliente['fechacreacion'],
        ];
    }, $clientesRecientes);
}

echo json_encode($respuesta);
