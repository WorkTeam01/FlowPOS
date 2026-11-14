<?php

/**
 * Test de caracterización de Dashboard::getEstadisticasGenerales: una venta con
 * pago mixto (dos filas en pagoventa) cuenta como una sola transacción.
 *
 *   php tests/DashboardConteoTest.php
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../models/Dashboard.php';

$tablas = ['pagoventa', 'detalleventa', 'venta'];
vaciarTablas($pdo, $tablas);

// Día lejano para no mezclarse con nada más.
$dia = '2001-01-01';
$ventas = [
    ['id' => 9001, 'pagos' => [100.00]],         // pago único
    ['id' => 9002, 'pagos' => [60.00, 40.00]],   // pago mixto
];

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach ($ventas as $v) {
    insertarFila($pdo, 'venta', [
        'idventa' => $v['id'],
        'estado' => 1,
        'fechacreacion' => "$dia 10:00:00",
        'totalventa' => array_sum($v['pagos']),
    ]);
    foreach ($v['pagos'] as $monto) {
        insertarFila($pdo, 'pagoventa', ['idventa' => $v['id'], 'metodopago' => 'efectivo', 'monto' => $monto, 'estado' => 1]);
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$est = (new Dashboard())->getEstadisticasGenerales($dia, $dia);

verificar('totalTransacciones (pago mixto cuenta una vez)', 2, $est['totalTransacciones']);
verificar('ventasTotales', 200.0, $est['ventasTotales']);
verificar('ventaPromedio', 100.0, $est['ventaPromedio']);

vaciarTablas($pdo, $tablas);
terminarTests();
