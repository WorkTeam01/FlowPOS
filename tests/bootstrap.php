<?php

/**
 * Arranque común de los tests: apunta la app a `flowpos_test` (nunca a `flowpos`),
 * clona la estructura de la BD real la primera vez y expone helpers mínimos.
 */

const BD_TEST = 'flowpos_test';

require_once __DIR__ . '/../config/env.php';

$host = env('DB_HOST');
$user = env('DB_USER');
$pass = env('DB_PASS');
$bdReal = env('DB_NAME');

if ($bdReal === BD_TEST) {
    fwrite(STDERR, "DB_NAME ya apunta a la BD de pruebas; el .env debe apuntar a la real para clonar.\n");
    exit(2);
}

$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
$admin = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, $opts);

$existe = $admin->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '" . BD_TEST . "'")
    ->fetchColumn();
if (!$existe) {
    $admin->exec('CREATE DATABASE `' . BD_TEST . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    $admin->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($admin->query("SHOW FULL TABLES FROM `$bdReal` WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM) as $t) {
        $ddl = $admin->query("SHOW CREATE TABLE `$bdReal`.`{$t[0]}`")->fetch(PDO::FETCH_NUM)[1];
        $admin->exec('USE `' . BD_TEST . '`');
        $admin->exec($ddl);
    }
    $admin->exec('SET FOREIGN_KEY_CHECKS = 1');
}
unset($admin, $pass);

// env() lee getenv() en cada llamada, así que el cambio llega a Conexion.
putenv('DB_NAME=' . BD_TEST);
require_once __DIR__ . '/../config/conexion.php';

$pdo = Conexion::getInstance()->getConnection();
if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== BD_TEST) {
    fwrite(STDERR, "La conexión no apunta a " . BD_TEST . "; se aborta.\n");
    exit(2);
}

$GLOBALS['fallos_test'] = [];

function verificar($nombre, $esperado, $real)
{
    $ok = is_float($esperado) || is_float($real)
        ? abs($esperado - $real) < 0.0001
        : $esperado === $real;
    $fmt = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE);
    echo ($ok ? 'OK    ' : 'FALLA ') . $nombre . ($ok ? '' : ': esperado ' . $fmt($esperado) . ', real ' . $fmt($real)) . "\n";
    if (!$ok) {
        $GLOBALS['fallos_test'][] = $nombre;
    }
}

/** Inserta una fila ignorando las claves que no sean columnas de la tabla. */
function insertarFila(PDO $pdo, $tabla, array $fila)
{
    $cols = array_column($pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(), 'Field');
    $fila = array_intersect_key($fila, array_flip($cols));
    $lista = implode(',', array_map(fn($c) => "`$c`", array_keys($fila)));
    $marks = implode(',', array_fill(0, count($fila), '?'));
    $pdo->prepare("INSERT INTO `$tabla` ($lista) VALUES ($marks)")->execute(array_values($fila));
}

function vaciarTablas(PDO $pdo, array $tablas)
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tablas as $tabla) {
        $pdo->exec("DELETE FROM `$tabla`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

function terminarTests()
{
    exit($GLOBALS['fallos_test'] ? 1 : 0);
}
