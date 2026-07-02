<?php

/**
 * Archivo de configuración principal
 * 
 * Contiene la configuración global de la aplicación, incluyendo
 * parámetros de conexión a la base de datos y configuración general.
 * 
 * @version 1.0
 */

require_once __DIR__ . '/env.php';

// Configurar zona horaria con valor predeterminado si no está definida
$timezone = env('TIMEZONE');
date_default_timezone_set($timezone);

return [
    'database' => [
        'host' => env('DB_HOST'),
        'name' => env('DB_NAME'),
        'user' => env('DB_USER'),
        'pass' => env('DB_PASS'),
        'charset' => env('DB_CHARSET')
    ],
    'app' => [
        'timezone' => $timezone,
        'name' => env('APP_NAME', 'FlowPOS'),
        'version' => env('APP_VERSION', '1.0.0'),
        'currency' => env('APP_CURRENCY', '$'),
        'url' => env('APP_URL'),
        'debug' => env('DEBUG')
    ]
];
