<?php

/**
 * Utilidades para conversión de números a letras
 *
 * Este archivo contiene funciones para convertir valores numéricos a su
 * representación textual en español, especialmente útil para documentos
 * financieros como facturas, recibos y cheques.
 *
 * @package     App\Controllers\pedidos
 */

/**
 * Convierte un número a su representación en palabras (en español)
 *
 * Esta función toma un valor numérico y lo convierte a su representación
 * textual completa en español, incluyendo los centavos y el símbolo
 * monetario (Bs.) para uso en documentos contables bolivianos.
 *
 * Ejemplo: 1234.56 -> "MIL DOSCIENTOS TREINTA Y CUATRO CON 56/100 Bs."
 *
 * @param float $numero Número a convertir (puede incluir decimales)
 * @return string El número expresado en palabras con formato monetario en Bolivianos
 * @throws Exception Si la entrada no es un valor numérico válido
 */

function numeroletras(float $numero): string
{
    // Diccionario de números
    $diccionario = array(
        0 => "CERO",
        1 => "UN",
        2 => "DOS",
        3 => "TRES",
        4 => "CUATRO",
        5 => "CINCO",
        6 => "SEIS",
        7 => "SIETE",
        8 => "OCHO",
        9 => "NUEVE",
        10 => "DIEZ",
        11 => "ONCE",
        12 => "DOCE",
        13 => "TRECE",
        14 => "CATORCE",
        15 => "QUINCE",
        16 => "DIECISÉIS",
        17 => "DIECISIETE",
        18 => "DIECIOCHO",
        19 => "DIECINUEVE",
        20 => "VEINTE",
        30 => "TREINTA",
        40 => "CUARENTA",
        50 => "CINCUENTA",
        60 => "SESENTA",
        70 => "SETENTA",
        80 => "OCHENTA",
        90 => "NOVENTA",
        100 => "CIENTO",
        200 => "DOSCIENTOS",
        300 => "TRESCIENTOS",
        400 => "CUATROCIENTOS",
        500 => "QUINIENTOS",
        600 => "SEISCIENTOS",
        700 => "SETECIENTOS",
        800 => "OCHOCIENTOS",
        900 => "NOVECIENTOS"
    );

    // Validar que la entrada sea numérica
    if (!is_numeric($numero)) {
        return "ERROR: Entrada no numérica";
    }

    // Formatear el número con dos decimales
    $numero = number_format((float)$numero, 2, '.', '');

    // Separar parte entera y decimal
    $partes = explode('.', $numero);
    $entero = $partes[0];
    $decimal = !isset($partes[1]) ? '00' : $partes[1];

    // Si el número es cero
    if ($entero == 0) {
        return "CERO CON $decimal/100 Bs.";
    }

    // Convertir la parte entera a palabras
    $entero = str_pad($entero, 18, "0", STR_PAD_LEFT);
    $resultado = '';

    // Procesar por grupos de 6 dígitos (billones, millones, miles)
    for ($i = 0; $i < 3; $i++) {
        $grupo = substr($entero, $i * 6, 6);

        if ((int)$grupo > 0) {
            $texto_grupo = procesar_grupo($grupo, $diccionario);

            // Agregar sufijo según la posición (billones, millones, o nada)
            switch ($i) {
                case 0: // Billones
                    $texto_grupo .= ((int)$grupo == 1) ? " BILLON" : " BILLONES";
                    break;
                case 1: // Millones
                    $texto_grupo .= ((int)$grupo == 1) ? " MILLON" : " MILLONES";
                    break;
            }

            $resultado .= " " . $texto_grupo;
        }
    }

    // Formatear el resultado
    $resultado = trim($resultado);

    // Ajuste final para valores menores a 2
    if ($entero == '000000000000000001') {
        $resultado = "UN";
    }

    // Agregar los decimales
    $resultado .= " CON $decimal/100 Bs.";

    // Limpiar la salida (eliminar espacios dobles, etc.)
    $resultado = str_replace("VEINTI ", "VEINTI", $resultado);
    $resultado = str_replace("  ", " ", $resultado);
    $resultado = str_replace("UN UN", "UN", $resultado);

    return trim($resultado);
}

/**
 * Procesa un grupo de hasta 6 dígitos y lo convierte a palabras
 */
function procesar_grupo($grupo, $diccionario): string
{
    $resultado = '';

    // Procesar centenas, decenas y unidades de miles
    $miles = (int)substr($grupo, 0, 3);
    if ($miles > 0) {
        if ($miles == 1) {
            $resultado .= " MIL";
        } else {
            $resultado .= " " . procesar_centenas($miles, $diccionario) . " MIL";
        }
    }

    // Procesar centenas, decenas y unidades
    $unidades = (int)substr($grupo, 3, 3);
    if ($unidades > 0) {
        $resultado .= " " . procesar_centenas($unidades, $diccionario);
    }

    return trim($resultado);
}

/**
 * Procesa un número de hasta 3 dígitos y lo convierte a palabras
 */
function procesar_centenas($numero, $diccionario): string
{
    $resultado = '';

    // Caso especial: 100
    if ($numero == 100) {
        return "CIEN";
    }

    // Centenas
    $centena = floor($numero / 100) * 100;
    if ($centena > 0) {
        $resultado .= " " . $diccionario[$centena];
    }

    // Decenas y unidades
    $resto = $numero % 100;

    if ($resto > 0) {
        if ($resto <= 20) {
            // Números del 1 al 20
            $resultado .= " " . $diccionario[$resto];
        } else {
            // Números mayores a 20
            $decena = floor($resto / 10) * 10;
            $unidad = $resto % 10;

            $resultado .= " " . $diccionario[$decena];

            if ($unidad > 0) {
                $resultado .= " Y " . $diccionario[$unidad];
            }
        }
    }

    return trim($resultado);
}
