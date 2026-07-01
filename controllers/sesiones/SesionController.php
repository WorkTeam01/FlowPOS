<?php

/**
 * Controlador de Sesiones
 * 
 * Gestiona las operaciones relacionadas con las sesiones de usuarios
 * 
 * @version 1.0
 */

class SesionController
{
    /**
     * Modelo de Sesion
     * @var Sesion
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Sesion
        require_once __DIR__ . '/../../models/Sesion.php';
        $this->modelo = new Sesion();
    }

    /**
     * Muestra la lista de todas las sesiones
     * 
     * @param bool $activas Si es true, muestra solo las sesiones activas
     * @return array Lista de sesiones
     */
    public function index($activas = false)
    {
        return $this->modelo->getAll($activas);
    }

    /**
     * Obtiene una sesión por su ID
     * 
     * @param int $id ID de la sesión
     * @return array|bool Datos de la sesión o false si no existe
     */
    public function getById($id)
    {
        return $this->modelo->getById($id);
    }

    /**
     * Obtiene las sesiones de un usuario
     * 
     * @param int $idUsuario ID del usuario
     * @param bool $activas Si es true, muestra solo las sesiones activas
     * @return array Lista de sesiones del usuario
     */
    public function getSesionesUsuario($idUsuario, $activas = false)
    {
        return $this->modelo->getByUsuario($idUsuario, $activas);
    }

    /**
     * Obtiene las sesiones activas
     * 
     * @return array Lista de sesiones activas
     */
    public function getSesionesActivas()
    {
        return $this->modelo->getSesionesActivas();
    }

    /**
     * Cierra una sesión
     * 
     * @param int $id ID de la sesión
     * @return array Resultado de la operación
     */
    public function cerrarSesion($id)
    {
        // Verificar si la sesión existe
        $sesion = $this->modelo->getById($id);
        if (!$sesion) {
            return [
                'success' => false,
                'message' => 'La sesión no existe',
                'icon' => 'error'
            ];
        }

        // Verificar si la sesión ya está cerrada
        if ($sesion['estado'] == 0) {
            return [
                'success' => false,
                'message' => 'La sesión ya está cerrada',
                'icon' => 'warning'
            ];
        }

        // Cerrar la sesión
        if ($this->modelo->cerrarSesion($id)) {
            return [
                'success' => true,
                'message' => 'Sesión cerrada correctamente',
                'icon' => 'success'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cerrar la sesión: ' . $this->modelo->getLastError(),
                'icon' => 'error'
            ];
        }
    }

    /**
     * Cierra todas las sesiones activas de un usuario
     * 
     * @param int $idUsuario ID del usuario
     * @return array Resultado de la operación
     */
    public function cerrarSesionesUsuario($idUsuario)
    {
        // Verificar si hay sesiones activas del usuario
        $sesiones = $this->modelo->getByUsuario($idUsuario, true);
        if (empty($sesiones)) {
            return [
                'success' => false,
                'message' => 'El usuario no tiene sesiones activas',
                'icon' => 'warning'
            ];
        }

        // Cerrar las sesiones
        if ($this->modelo->cerrarSesionesUsuario($idUsuario)) {
            return [
                'success' => true,
                'message' => 'Sesiones del usuario cerradas correctamente',
                'icon' => 'success'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cerrar las sesiones: ' . $this->modelo->getLastError(),
                'icon' => 'error'
            ];
        }
    }

    /**
     * Obtiene estadísticas de sesiones
     * 
     * @return array Estadísticas de sesiones
     */
    public function getEstadisticas()
    {
        $stats = $this->modelo->getEstadisticas();
        $stats['duracion_promedio'] = $this->modelo->getDuracionPromedio();
        $stats['usuarios_top'] = $this->modelo->getUsuariosTopSesiones();
        return $stats;
    }

    /**
     * Formatea la duración entre dos fechas
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string|null $fechaFin Fecha de fin (null si la sesión está activa)
     * @return string Duración formateada
     */
    public function formatearDuracion($fechaInicio, $fechaFin = null)
    {
        if (!$fechaFin) {
            // Sesión activa, calcular tiempo transcurrido hasta ahora
            $inicio = new DateTime($fechaInicio);
            $fin = new DateTime();
        } else {
            $inicio = new DateTime($fechaInicio);
            $fin = new DateTime($fechaFin);
        }

        $intervalo = $inicio->diff($fin);

        if ($intervalo->days > 0) {
            return $intervalo->format('%d días, %h horas, %i minutos');
        } elseif ($intervalo->h > 0) {
            return $intervalo->format('%h horas, %i minutos');
        } else {
            return $intervalo->format('%i minutos, %s segundos');
        }
    }

    /**
     * Determina el tipo de dispositivo basado en el user agent
     * 
     * @param string $userAgent User Agent del navegador
     * @return string Tipo de dispositivo (Desktop, Mobile, Tablet, Unknown)
     */
    public function detectarDispositivo($userAgent)
    {
        if (empty($userAgent)) {
            return 'Desconocido';
        }

        $userAgent = strtolower($userAgent);

        if (strpos($userAgent, 'mobile') !== false) {
            if (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
                return 'Tablet';
            }
            return 'Móvil';
        } elseif (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    /**
     * Detecta el navegador basado en el user agent
     * 
     * @param string $userAgent User Agent del navegador
     * @return string Nombre del navegador
     */
    public function detectarNavegador($userAgent)
    {
        if (empty($userAgent)) {
            return 'Desconocido';
        }

        $userAgent = strtolower($userAgent);

        if (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'edge') === false && strpos($userAgent, 'opr') === false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'opr') !== false || strpos($userAgent, 'opera') !== false) {
            return 'Opera';
        } elseif (strpos($userAgent, 'msie') !== false || strpos($userAgent, 'trident') !== false) {
            return 'Internet Explorer';
        } else {
            return 'Desconocido';
        }
    }
}
