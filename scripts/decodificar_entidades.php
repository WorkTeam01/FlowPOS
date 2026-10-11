<?php
// Convierte los datos guardados con el escape al guardar (htmlspecialchars) al formato crudo:
// htmlspecialchars_decode con ENT_QUOTES, UNA sola pasada por fila.
//
// Uso: php scripts/decodificar_entidades.php [--apply]   (sin --apply es dry-run)
//
// Hacer mysqldump antes de --apply. Ejecutarlo una sola vez, en el mismo despliegue que el
// código que escapa al mostrar: una segunda pasada decodificaría un `&amp;` que el usuario
// escribió a propósito. Cada UPDATE exige que la columna siga valiendo el valor leído, así que
// no pisa un cambio concurrente.
if (PHP_SAPI !== 'cli') {
    exit("Solo por línea de comandos.\n");
}
require_once __DIR__ . '/../config/conexion.php';

$apply = in_array('--apply', $argv, true);
$pdo = Conexion::getInstance()->getConnection();

$tablas = [
    'categoria' => ['pk' => 'idcategoria', 'cols' => ['nombre']],
    'cliente'   => ['pk' => 'idcliente',   'cols' => ['nombres', 'apellidopaterno', 'apellidomaterno', 'tipodocumento', 'numdocumento', 'genero', 'direccion', 'celular', 'email']],
    'compra'    => ['pk' => 'idcompra',    'cols' => ['observaciones']],
    'empresa'   => ['pk' => 'idempresa',   'cols' => ['nombre', 'nit', 'direccion', 'telefono', 'email']],
    'producto'  => ['pk' => 'idproducto',  'cols' => ['codigo', 'nombre', 'descripcion']],
    'rol'       => ['pk' => 'idrol',       'cols' => ['nombre', 'descripcion', 'dashboard']],
    'sucursal'  => ['pk' => 'idsucursal',  'cols' => ['nombre']],
    'usuarios'  => ['pk' => 'idusuario',   'cols' => ['nombre', 'apellidopaterno', 'apellidomaterno', 'tipodocumento', 'numdocumento', 'direccion', 'telefono', 'correo']],
    'venta'     => ['pk' => 'idventa',     'cols' => ['observacion']],
];

$patron = '&(amp|lt|gt|quot|#0?39);';
$totalFilas = 0;
foreach ($tablas as $tabla => $def) {
    $pk = $def['pk'];
    foreach ($def['cols'] as $col) {
        $sel = $pdo->prepare("SELECT `$pk` AS id, `$col` AS valor FROM `$tabla` WHERE `$col` REGEXP :p");
        $sel->execute([':p' => $patron]);
        $filas = $sel->fetchAll(PDO::FETCH_ASSOC);
        if (!$filas) {
            continue;
        }
        echo "== $tabla.$col: " . count($filas) . " fila(s)\n";
        if ($apply) {
            $pdo->beginTransaction();
        }
        foreach ($filas as $f) {
            $nuevo = htmlspecialchars_decode($f['valor'], ENT_QUOTES);
            echo "  [{$f['id']}] " . json_encode($f['valor'], JSON_UNESCAPED_UNICODE) . "\n     -> " . json_encode($nuevo, JSON_UNESCAPED_UNICODE) . "\n";
            if ($apply) {
                $upd = $pdo->prepare("UPDATE `$tabla` SET `$col` = :nuevo WHERE `$pk` = :id AND `$col` = :viejo");
                $upd->execute([':nuevo' => $nuevo, ':id' => $f['id'], ':viejo' => $f['valor']]);
                echo "     filas afectadas: " . $upd->rowCount() . "\n";
            }
            $totalFilas++;
        }
        if ($apply) {
            $pdo->commit();
        }
    }
}
echo ($apply ? "APLICADO" : "DRY-RUN") . ": $totalFilas fila(s) con entidades\n";
