<?php

/**
 * Controlador de Autenticación
 * 
 * Gestiona las operaciones relacionadas con la autenticación de usuarios
 * 
 * @version 1.0
 */
class AuthController
{
    /**
     * Modelo de Usuario
     * @var Usuario
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Usuario
        require_once __DIR__ . '/../../models/Usuario.php';
        $this->modelo = new Usuario();

        // Iniciar sesión si no está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Muestra la página de login
     */
    public function showLoginForm()
    {
        // Incluir la vista del formulario de login
        require_once __DIR__ . '/../../views/login/login.php';
    }

    /**
     * Genera un token CSRF para proteger formularios
     * 
     * @return string Token CSRF
     */
    public function generarCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verifica si el token CSRF es válido
     * 
     * @param string $token Token CSRF a verificar
     * @return bool True si es válido, False en caso contrario
     */
    public function verificarCSRFToken($token)
    {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }

    /**
     * Procesa el formulario de login
     */
    public function login()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Verificar token CSRF si está presente
            if (isset($_POST['csrf_token'])) {
                if (!$this->verificarCSRFToken($_POST['csrf_token'])) {
                    $_SESSION['mensaje'] = 'Error de seguridad. Por favor, intente nuevamente.';
                    $_SESSION['icono'] = 'error';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit;
                }
            }

            // Validar datos
            $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
            $clave = isset($_POST['clave']) ? trim($_POST['clave']) : '';
            $errors = [];

            // Validaciones básicas
            if (empty($identifier)) {
                $errors[] = 'Debe ingresar un correo o número de documento';
            }

            if (empty($clave)) {
                $errors[] = 'Debe ingresar una contraseña';
            }

            // Si hay errores básicos, mostrar el primero y redirigir
            if (!empty($errors)) {
                $_SESSION['mensaje'] = $errors[0];
                $_SESSION['icono'] = 'error';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // Determinar si el identificador es un correo o un número de documento
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

            // Verificar si el usuario existe (por correo o número de documento)
            $usuario_existe = false;
            $usuario_id = null;

            if ($isEmail) {
                // Es un correo electrónico
                $usuario_existe = $this->modelo->existeCorreo($identifier);
                if ($usuario_existe) {
                    $usuario_id = $this->modelo->obtenerIdPorCorreo($identifier);
                } else {
                    $_SESSION['mensaje'] = 'El correo electrónico no está registrado en el sistema';
                    $_SESSION['icono'] = 'error';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit;
                }
            } else {
                // Es un número de documento
                $usuario_existe = $this->modelo->existeNumDocumento($identifier);
                if ($usuario_existe) {
                    $usuario_id = $this->modelo->obtenerIdPorNumDocumento($identifier);
                } else {
                    $_SESSION['mensaje'] = 'El número de documento no está registrado en el sistema';
                    $_SESSION['icono'] = 'error';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit;
                }
            }

            // Verificar si el usuario está activo
            $estado_usuario = $this->modelo->obtenerEstadoPorId($usuario_id);

            if ($estado_usuario === 0) {
                $_SESSION['mensaje'] = 'Su cuenta está desactivada. Contacte al administrador.';
                $_SESSION['icono'] = 'warning';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // Verificar credenciales completas (identificador y contraseña)
            if ($isEmail) {
                $usuario = $this->modelo->loginPorCorreo($identifier, $clave);
            } else {
                $usuario = $this->modelo->loginPorNumDocumento($identifier, $clave);
            }

            if ($usuario) {
                // Iniciar sesión
                $this->iniciarSesion($usuario);

                // Redirigir al dashboard
                $_SESSION['mensaje'] = 'Bienvenido al sistema ' . $_SESSION['usuario_nombre'];
                $_SESSION['icono'] = 'success';
                header('Location: ../../');
            } else {
                // Credenciales incorrectas (la contraseña es incorrecta)
                $_SESSION['mensaje'] = 'La contraseña ingresada es incorrecta';
                $_SESSION['icono'] = 'error';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;
        }

        // Si no se envió el formulario, redirigir al login
        header('Location: ../../views/login/login.php');
    }

    /**
     * Inicia la sesión del usuario
     * 
     * @param array $usuario Datos del usuario
     */
    private function iniciarSesion($usuario)
    {
        // Regenerar ID de sesión para evitar session fixation
        session_regenerate_id(true);

        // Guardar datos del usuario en la sesión
        $_SESSION['usuario_id'] = $usuario['idusuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_correo'] = $usuario['correo'];
        $_SESSION['usuario_cargo'] = $usuario['cargo'];
        $_SESSION['usuario_imagen'] = $usuario['imagen'] ?? 'user_default.jpg';
        $_SESSION['usuario_sucursal'] = $usuario['idsucursal'] ?? null;
        $_SESSION['autenticado'] = true;

        // Registrar último acceso
        $_SESSION['ultimo_acceso'] = time();

        // Registrar IP y User Agent para seguridad adicional
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

        // Registrar inicio de sesión en la tabla sesionusuario
        try {
            // Usar la clase Conexion directamente
            require_once __DIR__ . '/../../config/conexion.php';
            $db = Conexion::getInstance();

            $sql = "INSERT INTO sesionusuario (idusuario, ipusuario, navegador) 
                   VALUES (:idusuario, :ipusuario, :navegador)";

            $params = [
                ':idusuario' => $usuario['idusuario'],
                ':ipusuario' => $_SERVER['REMOTE_ADDR'],
                ':navegador' => $_SERVER['HTTP_USER_AGENT']
            ];

            $db->query($sql, $params);
        } catch (Exception $e) {
            // Silenciar errores al registrar sesión
            // error_log('Error al registrar sesión: ' . $e->getMessage());
        }
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        // Registrar cierre de sesión en la tabla sesionusuario
        if (isset($_SESSION['usuario_id']) && isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
            try {
                // Usar la clase Conexion directamente
                require_once __DIR__ . '/../../config/conexion.php';
                $db = Conexion::getInstance();

                $sql = "UPDATE sesionusuario SET horasalida = NOW(), estado = 0 
                       WHERE idusuario = :idusuario AND estado = 1 
                       ORDER BY idsesion DESC LIMIT 1";

                $params = [
                    ':idusuario' => $_SESSION['usuario_id']
                ];

                $db->query($sql, $params);
            } catch (Exception $e) {
                // Silenciar errores al actualizar sesión
                // error_log('Error al cerrar sesión: ' . $e->getMessage());
            }
        }

        // Eliminar todas las variables de sesión
        $_SESSION = array();

        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destruir la sesión
        session_destroy();

        // Redirigir al login
        header('Location: ../../views/login/login.php');
        exit;
    }

    /**
     * Verifica si la sesión es válida (para uso en middleware)
     * 
     * @return bool True si la sesión es válida, False en caso contrario
     */
    public function verificarSesion()
    {
        // Verificar si el usuario está autenticado
        if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
            return false;
        }

        // Verificar tiempo de inactividad
        $timeout = 3600; // 60 minutos en segundos
        if (!isset($_SESSION['ultimo_acceso']) || (time() - $_SESSION['ultimo_acceso']) > $timeout) {
            $this->logout();
            return false;
        }

        // Verificar posible session hijacking comparando IP y User Agent
        if (isset($_SESSION['ip']) && isset($_SESSION['user_agent'])) {
            if (
                $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] ||
                $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
            ) {
                $this->logout();
                return false;
            }
        }

        // Actualizar tiempo de último acceso
        $_SESSION['ultimo_acceso'] = time();

        return true;
    }
}
