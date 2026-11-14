<?php

/**
 * Stock en la venta: el descuento es atómico y un exceso se rechaza con un mensaje
 * de negocio, sin dejar la venta a medias.
 *
 *   php tests/VentaStockTest.php
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../controllers/ventas/VentaController.php';

$tablas = ['pagoventa', 'detalleventa', 'venta', 'producto', 'categoria', 'usuarios'];
vaciarTablas($pdo, $tablas);

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
insertarFila($pdo, 'categoria', ['idcategoria' => 9001, 'nombre' => 'TEST categoria', 'estado' => 1]);
insertarFila($pdo, 'producto', [
    'idproducto' => 9001, 'codigo' => 'TEST-9001', 'nombre' => 'TEST producto', 'stock' => 5,
    'stockminimo' => 1, 'stockmaximo' => 100, 'preciocompra' => 5.00, 'precioventa' => 10.00,
    'idcategoria' => 9001, 'estado' => 1,
]);

insertarFila($pdo, 'usuarios', [
    'idusuario' => 7, 'nombre' => 'TEST', 'apellidopaterno' => 'TEST', 'tipodocumento' => 'CI', 'numdocumento' => 'TEST-7',
    'correo' => 'test-stock@example.invalid', 'idrol' => 1, 'clave' => 'x', 'estado' => 1,
]);

$stock = fn() => (int) $pdo->query('SELECT stock FROM producto WHERE idproducto = 9001')->fetchColumn();
$ventas = fn() => (int) $pdo->query('SELECT COUNT(*) FROM venta')->fetchColumn();

$datos = fn($cantidad) => [
    'idcliente' => null, 'idusuario' => 7, 'totalventa' => 10 * $cantidad, 'observacion' => null, 'estado' => 1,
    'detalles' => [['idproducto' => 9001, 'cantidad' => $cantidad, 'precioventa' => 10, 'descuento' => 0]],
    'pagos' => [['metodopago' => 'qr', 'monto' => 10 * $cantidad, 'pagorecibido' => 10 * $cantidad, 'cambio' => 0]],
];

$venta = new Venta();

verificar('venta dentro del stock se registra', true, (bool) $venta->crear($datos(3)));
verificar('el stock baja', 2, $stock());

verificar('venta que excede el stock se rechaza', false, $venta->crear($datos(3)));
verificar('mensaje de negocio', 'Stock insuficiente para completar la venta', $venta->getLastError());
verificar('el stock no cambia', 2, $stock());
verificar('no queda venta a medias', 1, $ventas());

verificar('vender exactamente el stock restante', true, (bool) $venta->crear($datos(2)));
verificar('el stock llega a cero sin error', 0, $stock());

// Validación previa del controlador: suma las líneas de un mismo producto.
$pdo->exec('UPDATE producto SET stock = 4 WHERE idproducto = 9001');
$controller = new VentaController();
$validar = new ReflectionMethod($controller, 'validarReferencias');
$validar->setAccessible(true);

$dosLineas = [
    'idcliente' => null,
    'detalles' => [
        ['idproducto' => 9001, 'cantidad' => 3],
        ['idproducto' => 9001, 'cantidad' => 2],
    ],
];
$errores = $validar->invoke($controller, $dosLineas);
verificar('suma las líneas del mismo producto', ['Stock insuficiente para «TEST producto»: disponible 4, solicitado 5'], $errores);

$dosLineas['detalles'][1]['cantidad'] = 1;
verificar('dentro del stock no hay errores', [], $validar->invoke($controller, $dosLineas));

// Anular devuelve el stock.
$pdo->exec('UPDATE producto SET stock = 5 WHERE idproducto = 9001');
$id = $venta->crear($datos(2));
verificar('stock tras vender 2 de 5', 3, $stock());
verificar('anular la venta', true, $venta->anular($id));
verificar('anular devuelve el stock', 5, $stock());

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
vaciarTablas($pdo, $tablas);
terminarTests();
