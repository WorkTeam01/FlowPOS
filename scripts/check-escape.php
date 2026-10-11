<?php
// Guard del esquema "guardar crudo, escapar al mostrar".
// Uso: php scripts/check-escape.php   (sale con 1 si encuentra algo)
//
// 1. Los modelos, controladores y servicios no deben llamar htmlspecialchars().
// 2. Toda salida con la etiqueta corta de eco de las vistas que imprima un valor de arreglo u objeto
//    (p. ej. $fila['nombre']) debe pasar por e(). Las excepciones razonadas viven en
//    scripts/check-escape.allow (una por línea: archivo:linea).
if (PHP_SAPI !== 'cli') {
    exit("Solo por línea de comandos.\n");
}
$raiz = dirname(__DIR__);
$errores = [];

$permitidas = [];
$archivoAllow = __DIR__ . '/check-escape.allow';
if (is_file($archivoAllow)) {
    foreach (file($archivoAllow, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        $l = trim($l);
        if ($l !== '' && $l[0] !== '#') {
            $permitidas[$l] = true;
        }
    }
}

function archivosPhp($dir)
{
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && substr($f->getFilename(), -4) === '.php') {
            yield $f->getPathname();
        }
    }
}

// 1. htmlspecialchars fuera de las vistas
foreach (['models', 'controllers', 'services'] as $d) {
    foreach (archivosPhp("$raiz/$d") as $ruta) {
        foreach (file($ruta) as $n => $linea) {
            if (strpos($linea, 'htmlspecialchars(') !== false) {
                $errores[] = substr($ruta, strlen($raiz) + 1) . ':' . ($n + 1) . ' htmlspecialchars() fuera de las vistas';
            }
        }
    }
}

// 2. Salidas sin e() en las vistas
// Claves cuyo valor nunca es texto libre: ids, contadores, importes, fechas, nombres de archivo generados
// por ImagenService (uniqid), clases/íconos calculados por el código y banderas de estado.
$clavesSeguras = '(id[a-z_]*|estado|stock[a-z]*|cantidad|total[a-z_]*|precio[a-z]*|monto|descuento|fecha[a-z_]*|horasalida|horaingreso|porcentaje|imagen|color|icono|clase|texto|operacion|tipo_movimiento|es_sistema|es_admin)';
$funcionesNumericas = 'str_pad|round|count|array_sum|array_column|strtotime|date|number_format|intval|floatval|max|min|abs|ucfirst|strtolower|isset|empty|is_null';

// Quita llamadas completas (con paréntesis balanceados) de las funciones indicadas
function quitarLlamadas($expr, $funciones)
{
    do {
        $antes = $expr;
        $expr = preg_replace('/\b(?:' . $funciones . ')\s*(\((?:[^()]++|(?1))*\))/', '', $expr);
    } while ($expr !== $antes);
    return $expr;
}

foreach (archivosPhp("$raiz/views") as $ruta) {
    $rel = substr($ruta, strlen($raiz) + 1);
    $codigo = file_get_contents($ruta);
    if (!preg_match_all('/<\?=\s*(.+?)\s*;?\s*\?>/s', $codigo, $m, PREG_OFFSET_CAPTURE)) {
        continue;
    }
    foreach ($m[1] as [$expr, $pos]) {
        $linea = substr_count(substr($codigo, 0, $pos), "\n") + 1;
        if (isset($permitidas["$rel:$linea"])) {
            continue;
        }
        $resto = quitarLlamadas($expr, 'e|htmlspecialchars|json_encode|csrfField|csrfMetaTag|\(int\)|\(float\)');
        // Accesos con claves seguras (antes de quitar los literales, que contienen la clave)
        $resto = preg_replace('/\$[A-Za-z_]+(?:\[[^\]]*\])*\[\'' . $clavesSeguras . '\'\]/', '', $resto);
        // Literales de texto: no son datos
        $resto = preg_replace('/\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"/s', "''", $resto);
        // Condición de un ternario (lo que va antes del ?): no se imprime
        if (preg_match('/^(.*?)\s\?\s(.*)$/s', $resto, $t)) {
            $resto = $t[2];
        }
        $resto = quitarLlamadas($resto, $funcionesNumericas);
        $resto = preg_replace('/\(int\)|\(float\)/', '', $resto);
        $resto = preg_replace('/\$(?:estadisticas|estadoInfo|URL|appCurrency|appName|appVersion|contador)\b(?:\[[^\]]*\])*/', '', $resto);
        if (preg_match('/\$[A-Za-z_]+(\[|->)/', $resto)) {
            $errores[] = "$rel:$linea <?= " . preg_replace('/\s+/', ' ', mb_substr($expr, 0, 90)) . ' ?>';
        }
    }
}

if ($errores) {
    echo "Salidas sin escapar (" . count($errores) . "):\n  " . implode("\n  ", $errores) . "\n";
    exit(1);
}
echo "OK: sin htmlspecialchars() fuera de las vistas y sin salidas sin e() en las vistas.\n";
