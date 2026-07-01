<?php

/**
 * Funciones para manejar variables de entorno
 * 
 * Este archivo proporciona funciones para cargar y acceder a
 * variables de entorno desde un archivo .env
 * 
 * @package Sistema_Alojamiento
 * @subpackage Config
 * @version 1.0
 */

/**
 * Carga las variables de entorno desde un archivo .env
 * 
 * Lee el archivo .env línea por línea y establece las variables
 * de entorno utilizando putenv() y $_ENV.
 * 
 * @param string $path Ruta al archivo .env
 * @throws Exception Si el archivo .env no existe
 * @return void
 */
function loadEnv($path)
{
    if (!file_exists($path)) {
        throw new Exception("El archivo .env no existe. Crea uno basado en .env.example");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Eliminar comillas si existen
        if (!empty($value)) {
            $value = trim($value, '"');
            $value = trim($value, "'");
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
    }
}

/**
 * Obtiene el valor de una variable de entorno
 * 
 * Recupera el valor de una variable de entorno y maneja
 * conversiones de tipos para valores booleanos, nulos y vacíos.
 * 
 * @param string $key Nombre de la variable de entorno
 * @param mixed $default Valor por defecto si la variable no existe
 * @return mixed El valor de la variable de entorno o el valor por defecto
 */
function env($key, $default = null)
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Manejar valores booleanos
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }

    return $value;
}

// Cargar variables de entorno
try {
    loadEnv(__DIR__ . '/../.env');
} catch (Exception $e) {
    die('Error al cargar el archivo .env: ' . $e->getMessage());
}
