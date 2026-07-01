<?php

/**
 * Gestión de Sesiones
 * 
 * Este archivo verifica si el usuario está autenticado y gestiona la sesión
 * 
 * @version 1.0
 */

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuración desde .env
try {
    // Cargar variables de entorno desde .env
    $env_file = __DIR__ . '/../../.env';

    if (!file_exists($env_file)) {
        die("Error: Archivo .env no encontrado. Por favor, configure el archivo .env con APP_URL y otras variables necesarias.");
    }

    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        // Ignorar comentarios y líneas vacías
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }

    // Obtener URL de la aplicación desde .env
    $app_url = $_ENV['APP_URL'] ?? getenv('APP_URL');

    if (empty($app_url)) {
        die("Error: APP_URL no está configurada en el archivo .env. Por favor, añada APP_URL=su_dominio_aqui/ en el archivo .env");
    }

    // Asegurar que termine con barra
    if (substr($app_url, -1) !== '/') {
        $app_url .= '/';
    }

    // Definir URL base global
    $GLOBALS['URL'] = $app_url;
    $URL = $GLOBALS['URL'];

    // Identidad de la aplicación (configurable en .env)
    $appName     = $_ENV['APP_NAME']     ?? getenv('APP_NAME')     ?: 'Sistema de Ventas';
    $appVersion  = $_ENV['APP_VERSION']  ?? getenv('APP_VERSION')  ?: '1.0.0';
    $appCurrency = $_ENV['APP_CURRENCY'] ?? getenv('APP_CURRENCY') ?: '$';
    $GLOBALS['appName']     = $appName;
    $GLOBALS['appVersion']  = $appVersion;
    $GLOBALS['appCurrency'] = $appCurrency;
} catch (Exception $e) {
    die("Error al cargar la configuración: " . $e->getMessage() . ". Verifique que el archivo .env esté configurado correctamente.");
}

/**
 * Verificar si el usuario está autenticado
 * 
 * @return bool True si está autenticado, False en caso contrario
 */
function isAuthenticated()
{
    return isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true;
}

/**
 * Verificar tiempo de inactividad
 * 
 * @param int $timeout Tiempo de inactividad en segundos (por defecto 86400 = 1 día)
 * @return bool True si la sesión sigue activa, False si ha expirado
 */
function checkSessionTimeout($timeout = 86400)
{
    if (isset($_SESSION['ultimo_acceso'])) {
        $inactivo = time() - $_SESSION['ultimo_acceso'];

        if ($inactivo >= $timeout) {
            // Sesión expirada, destruir sesión
            session_unset();
            session_destroy();
            return false;
        }
    }

    // Actualizar tiempo de último acceso
    $_SESSION['ultimo_acceso'] = time();
    return true;
}

/**
 * Verificar posible secuestro de sesión
 * 
 * @return bool True si la sesión es segura, False si se detectó posible secuestro
 */
function checkSessionSecurity()
{
    if (isset($_SESSION['ip']) && isset($_SESSION['user_agent'])) {
        if (
            $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] ||
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
        ) {
            // Posible session hijacking, destruir sesión
            session_unset();
            session_destroy();
            return false;
        }
    }
    return true;
}

/**
 * Requerir inicio de sesión para acceder a una página
 * 
 * @param string $redirect_url URL a la que redirigir si no hay sesión
 */
function requireLogin($redirect_url = null)
{
    global $URL;

    if (!$redirect_url) {
        $redirect_url = $URL . 'views/login/login.php';
    }

    if (!isAuthenticated() || !checkSessionTimeout() || !checkSessionSecurity()) {
        // Guardar mensaje de sesión expirada
        if (isset($_SESSION)) {
            if (!isAuthenticated()) {
                $_SESSION['mensaje'] = 'Debe iniciar sesión para acceder a esta página.';
            } else {
                $_SESSION['mensaje'] = 'Sesión expirada por inactividad. Por favor inicie sesión nuevamente.';
            }
            $_SESSION['icono'] = 'warning';
        }

        // Registrar cierre de sesión en la tabla sesionusuario si expiró por inactividad
        if (isset($_SESSION['usuario_id']) && !checkSessionTimeout()) {
            try {
                require_once __DIR__ . '/../../config/conexion.php';
                $conn = Conexion::getInstance()->getConnection();

                $sql = "UPDATE sesionusuario SET horasalida = NOW(), estado = 0 
                        WHERE idusuario = ? AND estado = 1 
                        ORDER BY idsesion DESC LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_SESSION['usuario_id']]);
            } catch (Exception $e) {
                // Error al registrar cierre, continuar con el redireccionamiento
            }
        }

        // Redirigir al login
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Requerir un rol específico para acceder a una página
 * 
 * @param array $roles_permitidos Roles que pueden acceder
 * @param string $redirect_url URL a la que redirigir si no tiene permiso
 */
function requireRole($roles_permitidos, $redirect_url = null)
{
    global $URL;

    if (!$redirect_url) {
        $redirect_url = $URL . 'index.php';
    }

    // Primero verificar que haya sesión
    requireLogin();

    // Verificar rol
    $rol_usuario = $_SESSION['usuario_cargo'] ?? '';

    if (!in_array($rol_usuario, $roles_permitidos)) {
        $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Obtener datos del usuario actual
 * 
 * @return array|null Datos del usuario o null si no hay sesión
 */
function getCurrentUser()
{
    if (isAuthenticated()) {
        return [
            'id' => $_SESSION['usuario_id'] ?? null,
            'nombre' => $_SESSION['usuario_nombre'] ?? null,
            'correo' => $_SESSION['usuario_correo'] ?? null,
            'cargo' => $_SESSION['usuario_cargo'] ?? null,
            'imagen' => $_SESSION['usuario_imagen'] ?? 'public/img/user_default.jpg',
            'sucursal' => $_SESSION['usuario_sucursal'] ?? null
        ];
    }
    return null;
}

/**
 * Generar un token CSRF para proteger formularios
 * 
 * @return string Token CSRF
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar si un token CSRF es válido
 * 
 * @param string $token Token a verificar
 * @return bool True si es válido, False en caso contrario
 */
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Regenerar el token CSRF (útil después de usarlo)
 */
function regenerateCSRFToken()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
