<?php

/**
 * Modelo Sesion
 * 
 * Gestiona las operaciones relacionadas con las sesiones de usuarios
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Sesion
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de sesiones en la base de datos
     * @var string
     */
    private $tabla = 'sesionusuario';

    /**
     * Último error ocurrido
     * @var string
     */
    private $lastError = '';

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Obtiene el último error ocurrido
     * 
     * @return string Mensaje de error
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Obtiene todas las sesiones con información del usuario
     * 
     * @param bool $activas Si es true, obtiene solo las sesiones activas
     * @return array Lista de sesiones
     */
    public function getAll($activas = false)
    {
        try {
            $query = "SELECT s.*, 
                     u.nombre, u.apellidopaterno, u.apellidomaterno, u.correo, u.imagen, u.cargo
                     FROM {$this->tabla} s
                     JOIN usuarios u ON s.idusuario = u.idusuario";

            if ($activas) {
                $query .= " WHERE s.estado = 1";
            }

            $query .= " ORDER BY s.horaingreso DESC";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene las sesiones de un usuario específico
     * 
     * @param int $idUsuario ID del usuario
     * @param bool $activas Si es true, obtiene solo las sesiones activas
     * @return array Lista de sesiones del usuario
     */
    public function getByUsuario($idUsuario, $activas = false)
    {
        try {
            $query = "SELECT s.*, 
                     u.nombre, u.apellidopaterno, u.apellidomaterno, u.correo, u.imagen, u.cargo
                     FROM {$this->tabla} s
                     JOIN usuarios u ON s.idusuario = u.idusuario
                     WHERE s.idusuario = :idusuario";

            if ($activas) {
                $query .= " AND s.estado = 1";
            }

            $query .= " ORDER BY s.horaingreso DESC";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idUsuario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene una sesión por su ID
     * 
     * @param int $id ID de la sesión
     * @return array|bool Datos de la sesión o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT s.*, 
                     u.nombre, u.apellidopaterno, u.apellidomaterno, u.correo, u.imagen, u.cargo
                     FROM {$this->tabla} s
                     JOIN usuarios u ON s.idusuario = u.idusuario
                     WHERE s.idsesion = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Obtiene las sesiones activas actuales
     * 
     * @return array Lista de sesiones activas
     */
    public function getSesionesActivas()
    {
        return $this->getAll(true);
    }

    /**
     * Cierra una sesión (actualiza la hora de salida y cambia el estado)
     * 
     * @param int $id ID de la sesión
     * @return bool True si se cerró correctamente, False en caso contrario
     */
    public function cerrarSesion($id)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                     horasalida = NOW(), 
                     estado = 0
                     WHERE idsesion = :id AND estado = 1";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Cierra todas las sesiones activas de un usuario
     * 
     * @param int $idUsuario ID del usuario
     * @return bool True si se cerraron correctamente, False en caso contrario
     */
    public function cerrarSesionesUsuario($idUsuario)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                     horasalida = NOW(), 
                     estado = 0
                     WHERE idusuario = :idusuario AND estado = 1";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idUsuario, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Obtiene estadísticas de sesiones
     * 
     * @return array Estadísticas (sesiones activas, totales, usuarios únicos)
     */
    public function getEstadisticas()
    {
        try {
            $stats = [
                'sesiones_activas' => 0,
                'sesiones_hoy' => 0,
                'sesiones_semana' => 0,
                'usuarios_unicos' => 0,
            ];

            // Sesiones activas
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $stats['sesiones_activas'] = $stmt->fetchColumn();

            // Sesiones hoy
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE DATE(horaingreso) = CURDATE()";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $stats['sesiones_hoy'] = $stmt->fetchColumn();

            // Sesiones esta semana
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE YEARWEEK(horaingreso) = YEARWEEK(NOW())";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $stats['sesiones_semana'] = $stmt->fetchColumn();

            // Usuarios únicos que han iniciado sesión
            $query = "SELECT COUNT(DISTINCT idusuario) FROM {$this->tabla}";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $stats['usuarios_unicos'] = $stmt->fetchColumn();

            return $stats;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [
                'sesiones_activas' => 0,
                'sesiones_hoy' => 0,
                'sesiones_semana' => 0,
                'usuarios_unicos' => 0,
            ];
        }
    }

    /**
     * Obtiene la duración promedio de las sesiones en minutos
     * 
     * @return float Duración promedio en minutos
     */
    public function getDuracionPromedio()
    {
        try {
            $query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, horaingreso, horasalida)) as duracion_promedio
                     FROM {$this->tabla}
                     WHERE horasalida IS NOT NULL AND estado = 0";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['duracion_promedio'] ?? 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return 0;
        }
    }

    /**
     * Obtiene los 5 usuarios con más sesiones
     * 
     * @return array Lista de usuarios con conteo de sesiones
     */
    public function getUsuariosTopSesiones()
    {
        try {
            $query = "SELECT s.idusuario, 
                     u.nombre, u.apellidopaterno,
                     COUNT(*) as total_sesiones
                     FROM {$this->tabla} s
                     JOIN usuarios u ON s.idusuario = u.idusuario
                     GROUP BY s.idusuario, u.nombre, u.apellidopaterno
                     ORDER BY total_sesiones DESC
                     LIMIT 5";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
}
