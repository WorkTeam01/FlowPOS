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
    $appName     = $_ENV['APP_NAME']     ?? getenv('APP_NAME')     ?: 'FlowPOS';
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
    if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
        return false;
    }

    require_once __DIR__ . '/../../services/SesionTokenService.php';

    return (new SesionTokenService())->estadoValidacion() === 'ok';
}

/**
 * Verificar tiempo de inactividad
 *
 * Predicado puro: no destruye la sesión ni cierra filas, el corte lo hace
 * requireLogin() con el motivo correspondiente.
 *
 * @param int $timeout Tiempo de inactividad en segundos (por defecto 86400 = 1 día)
 * @return bool True si la sesión sigue activa, False si ha expirado
 */
function checkSessionTimeout($timeout = 86400)
{
    if (isset($_SESSION['ultimo_acceso'])) {
        $inactivo = time() - $_SESSION['ultimo_acceso'];

        if ($inactivo >= $timeout) {
            return false;
        }
    }

    // Actualizar tiempo de último acceso (ventana deslizante)
    $_SESSION['ultimo_acceso'] = time();
    return true;
}

/**
 * Verificar posible secuestro de sesión
 *
 * Predicado puro: no destruye la sesión ni cierra filas, el corte lo hace
 * requireLogin() con el motivo correspondiente.
 *
 * @return bool True si la sesión es segura, False si se detectó posible secuestro
 */
function checkSessionSecurity()
{
    if (isset($_SESSION['ip']) && isset($_SESSION['user_agent'])) {
        if (
            $_SESSION['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? null) ||
            $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? null)
        ) {
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

    require_once __DIR__ . '/../../services/SesionTokenService.php';

    if (!$redirect_url) {
        $redirect_url = $URL . 'views/login/login.php';
    }

    $motivoCierre = null;
    $mensaje = null;

    if (!isAuthenticated()) {
        // Sesión sin bandera de login, o con token ausente/revocado
        $sesionIniciada = isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true;
        $mensaje = $sesionIniciada
            ? 'Su sesión fue finalizada. Inicie sesión nuevamente.'
            : 'Debe iniciar sesión para acceder a esta página.';
        // Sesión iniciada pero sin token (p. ej. creada antes de un despliegue):
        // su fila activa no es revocable por hash, se cierra por ID para que el
        // panel no la muestre como activa. 'revocada' ya está cerrada en BD y
        // 'error' no debe tumbar nada por un fallo transitorio de lectura.
        if ($sesionIniciada && (new SesionTokenService())->estadoValidacion() === 'ausente') {
            $motivoCierre = SesionTokenService::MOTIVO_SECURITY;
        }
    } elseif (!checkSessionTimeout()) {
        $motivoCierre = SesionTokenService::MOTIVO_TIMEOUT;
        $mensaje = 'Sesión expirada por inactividad. Por favor inicie sesión nuevamente.';
    } elseif (!checkSessionSecurity()) {
        $motivoCierre = SesionTokenService::MOTIVO_SECURITY;
        $mensaje = 'Sesión cerrada por seguridad. Inicie sesión nuevamente.';
    }

    if ($mensaje === null) {
        return;
    }

    // Cerrar la fila de auditoría ANTES de destruir la sesión PHP: una vez
    // destruida ya no queda el token con el que identificarla. Sin token en
    // la sesión (caso 'ausente') el cierre por hash no aplica y se usa el
    // ID de fila guardado al registrar.
    if ($motivoCierre !== null) {
        $tokenService = new SesionTokenService();
        if (!$tokenService->cerrarPorToken($motivoCierre) && isset($_SESSION[SesionTokenService::CLAVE_FILA])) {
            $tokenService->cerrarPorId((int) $_SESSION[SesionTokenService::CLAVE_FILA], $motivoCierre);
        }
    }

    // Abrir una sesión nueva solo para el mensaje flash: escribirlo sobre una
    // sesión ya destruida no persiste en ninguna parte. Si ya salió contenido
    // no hay nada que salvar: se redigirá igual y sin intentar arrancar sesión.
    if (!headers_sent()) {
        session_destroy();
        session_start();
        session_regenerate_id(true);

        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['icono'] = 'warning';
    }

    // Redirigir al login
    header('Location: ' . $redirect_url);
    exit;
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
            'rol' => $_SESSION['usuario_rol'] ?? null,
            'cargo' => isset($_SESSION['usuario_rol']) ? ucfirst($_SESSION['usuario_rol']) : null,
            'idrol' => $_SESSION['usuario_idrol'] ?? null,
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
    if (!isset($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
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

/**
 * Generar el meta tag con el token CSRF para que el JS lo lea en peticiones AJAX
 *
 * @return string Tag <meta> con el token
 */
function csrfMetaTag()
{
    return '<meta name="csrf-token" content="' . htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Generar el input oculto con el token CSRF para incluir en formularios
 *
 * @return string Input oculto con el token
 */
function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Obtener el token CSRF enviado en la petición actual (form o AJAX)
 *
 * @return string|null Token recibido o null si no viene ninguno
 */
function getRequestCSRFToken()
{
    if (isset($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    return null;
}

/**
 * Determinar si la petición actual es una llamada AJAX
 *
 * @return bool True si parece ser AJAX
 */
function isAjaxRequest()
{
    return (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || isset($_SERVER['HTTP_X_CSRF_TOKEN'])
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    );
}

/**
 * Exigir un token CSRF válido en peticiones POST; corta la ejecución si falta o es inválido.
 * No destruye la sesión activa del usuario.
 */
function requireCSRF()
{
    global $URL;

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    if (verifyCSRFToken(getRequestCSRFToken())) {
        return;
    }

    $mensaje = 'Tu sesión de formulario expiró. Vuelve a intentarlo, por favor.';

    if (isAjaxRequest()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $mensaje
        ]);
        exit;
    }

    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['icono'] = 'error';
    header('Location: ' . getSafeRedirectBack($URL));
    exit;
}

/**
 * Obtener una URL segura a la cual volver tras un error (mismo origen que $URL),
 * usando el referer de la petición si es válido, o $URL como respaldo.
 *
 * @param string $URL URL base de la aplicación
 * @return string URL a la que redirigir
 */
function getSafeRedirectBack($URL)
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    if ($referer !== '' && strpos($referer, $URL) === 0) {
        return $referer;
    }

    return $URL . 'index.php';
}
