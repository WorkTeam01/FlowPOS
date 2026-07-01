<?php

/**
 * Servicio de Autorización
 * 
 * Gestiona los permisos y autorización de usuarios
 * 
 * @version 1.0
 */
class AuthorizationService
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        require_once __DIR__ . '/../config/conexion.php';
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Verifica si un usuario tiene un permiso específico
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso a verificar
     * @return bool True si tiene permiso, False en caso contrario
     */
    public function tienePermiso($idusuario, $idpermiso)
    {
        try {
            // Verificar si el usuario es administrador
            if ($this->esAdministrador($idusuario)) {
                return true;
            }

            // Verificar permiso específico asignado al usuario
            $query = "SELECT COUNT(*) FROM permisousuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // Registrar error
            error_log('Error al verificar permiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un usuario tiene un permiso por su nombre
     * 
     * @param int $idusuario ID del usuario
     * @param string $nombre_permiso Nombre del permiso a verificar
     * @return bool True si tiene permiso, False en caso contrario
     */
    public function tienePermisoNombre($idusuario, $nombre_permiso)
    {
        try {
            // Si el usuario es administrador, tiene todos los permisos
            if ($this->esAdministrador($idusuario)) {
                return true;
            }

            // Si el permiso es 'admin_completo', solo los administradores lo tienen
            if ($nombre_permiso === 'admin_completo') {
                return $this->esAdministrador($idusuario);
            }

            // Obtener el ID del permiso por su nombre
            $query = "SELECT idpermiso FROM permiso WHERE nombre = :nombre AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $nombre_permiso, PDO::PARAM_STR);
            $stmt->execute();

            $idpermiso = $stmt->fetchColumn();

            if (!$idpermiso) {
                return false; // El permiso no existe
            }

            return $this->tienePermiso($idusuario, $idpermiso);
        } catch (PDOException $e) {
            error_log('Error al verificar permiso por nombre: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un usuario es administrador
     * 
     * @param int $idusuario ID del usuario
     * @return bool True si es administrador, False en caso contrario
     */
    public function esAdministrador($idusuario)
    {
        try {
            $query = "SELECT cargo FROM usuarios WHERE idusuario = :idusuario AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            $cargo = $stmt->fetchColumn();

            return strtolower($cargo) === 'administrador';
        } catch (PDOException $e) {
            error_log('Error al verificar si es administrador: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los permisos de un usuario
     * 
     * @param int $idusuario ID del usuario
     * @return array Lista de permisos
     */
    public function obtenerPermisosUsuario($idusuario)
    {
        try {
            // Si es administrador, devolver todos los permisos
            if ($this->esAdministrador($idusuario)) {
                $query = "SELECT idpermiso, nombre FROM permiso WHERE estado = 1";
                $stmt = $this->conexion->prepare($query);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Obtener permisos específicos del usuario
            $query = "SELECT p.idpermiso, p.nombre 
                     FROM permisousuario pu 
                     JOIN permiso p ON pu.idpermiso = p.idpermiso 
                     WHERE pu.idusuario = :idusuario AND p.estado = 1";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Registrar error
            error_log('Error al obtener permisos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Asigna un permiso a un usuario
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si se asignó correctamente, False en caso contrario
     */
    public function asignarPermiso($idusuario, $idpermiso)
    {
        try {
            // Verificar si ya existe la asignación
            $query = "SELECT COUNT(*) FROM permisousuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                // Ya existe, no hacer nada
                return true;
            } else {
                // No existe, crear nueva asignación
                $query = "INSERT INTO permisousuario (idpermiso, idusuario) 
                         VALUES (:idpermiso, :idusuario)";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
                $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
                return $stmt->execute();
            }
        } catch (PDOException $e) {
            error_log('Error al asignar permiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoca un permiso a un usuario
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si se revocó correctamente, False en caso contrario
     */
    public function revocarPermiso($idusuario, $idpermiso)
    {
        try {
            // Verificar si existe la asignación
            $query = "SELECT COUNT(*) FROM permisousuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                // Si existe, eliminar la asignación
                $query = "DELETE FROM permisousuario 
                         WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
                $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
                return $stmt->execute();
            }

            return true; // Si no existe, no hay que revocar nada
        } catch (PDOException $e) {
            error_log('Error al revocar permiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los permisos disponibles en el sistema
     * 
     * @return array Lista de todos los permisos
     */
    public function obtenerTodosLosPermisos()
    {
        try {
            $query = "SELECT idpermiso, nombre FROM permiso WHERE estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error al obtener todos los permisos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un usuario tiene asignado un permiso específico
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si tiene el permiso asignado, False en caso contrario
     */
    public function tienePermisoAsignado($idusuario, $idpermiso)
    {
        try {
            $query = "SELECT COUNT(*) FROM permisousuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error al verificar permiso asignado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza los permisos de un usuario (elimina todos y asigna los nuevos)
     * 
     * @param int $idusuario ID del usuario
     * @param array $permisos Lista de IDs de permisos a asignar
     * @return bool True si se actualizaron correctamente, False en caso contrario
     */
    public function actualizarPermisosUsuario($idusuario, $permisos)
    {
        try {
            // Iniciar transacción
            $this->conexion->beginTransaction();

            // Eliminar todos los permisos actuales del usuario
            $query = "DELETE FROM permisousuario WHERE idusuario = :idusuario";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            // Asignar los nuevos permisos
            foreach ($permisos as $idpermiso) {
                $query = "INSERT INTO permisousuario (idpermiso, idusuario) 
                         VALUES (:idpermiso, :idusuario)";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
                $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Confirmar transacción
            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            // Revertir cambios en caso de error
            $this->conexion->rollBack();
            error_log('Error al actualizar permisos de usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los permisos asignados a un usuario específico
     * 
     * @param int $idusuario ID del usuario
     * @return array Lista de IDs de permisos asignados
     */
    public function obtenerPermisosAsignados($idusuario)
    {
        try {
            $query = "SELECT idpermiso FROM permisousuario WHERE idusuario = :idusuario";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            $permisos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permisos[] = $row['idpermiso'];
            }

            return $permisos;
        } catch (PDOException $e) {
            error_log('Error al obtener permisos asignados: ' . $e->getMessage());
            return [];
        }
    }
}
