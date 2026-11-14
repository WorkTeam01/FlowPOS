<?php

/**
 * Tests de caracterización de la preparación y validación de una venta:
 * `VentaController::prepararDatosVenta` (privado, se invoca por reflexión) y
 * `Venta::validarDatos`.
 *
 *   php tests/VentaValidacionTest.php
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../controllers/ventas/VentaController.php';

$tablas = ['producto', 'categoria'];
vaciarTablas($pdo, $tablas);

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
insertarFila($pdo, 'categoria', ['idcategoria' => 9001, 'nombre' => 'TEST categoria', 'estado' => 1]);
insertarFila($pdo, 'producto', [
    'idproducto' => 9001,
    'codigo' => 'TEST-9001',
    'nombre' => 'TEST producto',
    'stock' => 50,
    'stockminimo' => 1,
    'stockmaximo' => 100,
    'preciocompra' => 5.00,
    'precioventa' => 10.00,
    'idcategoria' => 9001,
    'estado' => 1,
]);
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$_SESSION['usuario_id'] = 7;

$controller = new VentaController();
$preparar = new ReflectionMethod($controller, 'prepararDatosVenta');
$preparar->setAccessible(true);
$prep = fn(array $post) => $preparar->invoke($controller, $post);

// --- prepararDatosVenta ---
$d = $prep([
    'idcliente' => '',
    'productos' => [9001],
    'cantidades' => [3],
    'descuentos' => [1],
    'precios' => [999],              // el precio del formulario se ignora
    'tipo_pago' => 'unico',
    'metodopago_unico' => 'efectivo',
    'monto_unico' => '27',
    'pago_recibido_unico' => '30',
    'cambio_unico' => '999',         // el cambio del cliente se ignora
]);
verificar('idcliente vacío queda null (no 0)', null, $d['idcliente']);
verificar('idusuario sale de la sesión', 7, $d['idusuario']);
verificar('precio de venta sale de la BD', 10.0, $d['detalles'][0]['precioventa']);
verificar('total = cantidad * (precio - descuento)', 27.0, $d['totalventa']);
verificar('cambio calculado en servidor', 3.0, $d['pagos'][0]['cambio']);

$d = $prep([
    'productos' => [9001],
    'cantidades' => [1],
    'descuentos' => [0],
    'tipo_pago' => 'unico',
    'metodopago_unico' => 'qr',
    'monto_unico' => '10',
    'pago_recibido_unico' => '50',
]);
verificar('pago no efectivo: recibido = monto', 10.0, $d['pagos'][0]['pagorecibido']);
verificar('pago no efectivo: cambio 0', 0.0, $d['pagos'][0]['cambio']);

$d = $prep([
    'productos' => [9001],
    'cantidades' => [2],
    'descuentos' => [0],
    'metodopago' => ['efectivo', 'tarjeta', ''],
    'montopago' => [12, 8, 5],
    'pagorecibido' => [20, 8, 5],
]);
verificar('pago mixto: ignora métodos vacíos', 2, count($d['pagos']));
verificar('pago mixto: cambio del efectivo', 8.0, $d['pagos'][0]['cambio']);

$d = $prep(['productos' => [8888], 'cantidades' => [1]]);
verificar('producto inexistente: precio 0', 0.0, $d['detalles'][0]['precioventa']);

// --- validarDatos ---
$venta = new Venta();
$ok = [
    'idusuario' => 7,
    'totalventa' => 20,
    'detalles' => [['idproducto' => 9001, 'cantidad' => 2, 'precioventa' => 10, 'descuento' => 0]],
    'pagos' => [['metodopago' => 'efectivo', 'monto' => 20, 'pagorecibido' => 20]],
];
verificar('venta válida sin errores', [], $venta->validarDatos($ok));

$x = $ok;
$x['detalles'] = [];
verificar('sin productos', ['La venta debe tener al menos un producto'], $venta->validarDatos($x));

$x = $ok;
$x['pagos'] = [];
verificar('sin pagos', ['Debe especificar al menos un método de pago'], $venta->validarDatos($x));

$x = $ok;
$x['totalventa'] = 25;
verificar('pagos no cuadran con el total', ['El total de los pagos no coincide con el total de la venta'], $venta->validarDatos($x));

$x = $ok;
$x['detalles'][0]['cantidad'] = 0;
verificar('cantidad cero', ['La cantidad debe ser mayor que cero'], $venta->validarDatos($x));

$x = $ok;
$x['detalles'][0]['descuento'] = 11;
verificar('descuento mayor al precio', ['El descuento no puede ser negativo ni superar el precio unitario'], $venta->validarDatos($x));

$x = $ok;
$x['detalles'][0]['descuento'] = -1;
verificar('descuento negativo', ['El descuento no puede ser negativo ni superar el precio unitario'], $venta->validarDatos($x));

$x = $ok;
$x['pagos'][0]['pagorecibido'] = 5;
verificar('efectivo recibido menor al monto', ['El pago recibido en efectivo debe ser mayor o igual al monto'], $venta->validarDatos($x));

$x = $ok;
unset($x['idusuario']);
verificar('idusuario obligatorio', ['El campo idusuario es obligatorio'], $venta->validarDatos($x));

vaciarTablas($pdo, $tablas);
terminarTests();
