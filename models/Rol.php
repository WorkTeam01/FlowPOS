<?php

/**
 * Modelo Rol
 *
 * Gestiona las operaciones relacionadas con los roles del sistema en la base de datos
 *
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Rol
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de roles en la base de datos
     * @var string
     */
    private $tabla = 'rol';

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
     * Sanitiza los datos de entrada para prevenir inyección SQL y XSS
     *
     * @param array $datos Datos a sanitizar
     * @return array Datos sanitizados
     */
    public function sanitizarDatos($datos)
    {
        $sanitized = [];
        foreach ($datos as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Obtiene todos los roles
     *
     * @param bool $soloActivos Si es true, solo devuelve roles activos
     * @return array Lista de roles
     */
    public function getAll($soloActivos = false)
    {
        try {
            $query = "SELECT * FROM {$this->tabla}";

            if ($soloActivos) {
                $query .= " WHERE estado = 1";
            }

            $query .= " ORDER BY idrol";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene un rol por su ID
     *
     * @param int $id ID del rol
     * @return array|bool Datos del rol o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idrol = :id";
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
     * Crea un nuevo rol
     *
     * @param array $datos Datos del rol (nombre, descripcion, dashboard)
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (nombre, descripcion, dashboard, es_sistema, es_admin)
                     VALUES (:nombre, :descripcion, :dashboard, 0, 0)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            $stmt->bindParam(':dashboard', $datos['dashboard'], PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza un rol existente (nombre y dashboard no se tocan si es_sistema=1,
     * validado en el controlador antes de llamar a este método)
     *
     * @param int $id ID del rol
     * @param array $datos Datos del rol (nombre, descripcion, dashboard)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla}
                     SET nombre = :nombre, descripcion = :descripcion, dashboard = :dashboard
                     WHERE idrol = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            $stmt->bindParam(':dashboard', $datos['dashboard'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de un rol
     *
     * @param int $id ID del rol
     * @param int $estado Nuevo estado (1: activo, 0: inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idrol = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Activa un rol
     *
     * @param int $id ID del rol
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function activar($id)
    {
        return $this->actualizarEstado($id, 1);
    }

    /**
     * Desactiva un rol
     *
     * @param int $id ID del rol
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function desactivar($id)
    {
        return $this->actualizarEstado($id, 0);
    }

    /**
     * Elimina un rol (solo permitido si no es de sistema y no tiene usuarios asignados,
     * validado en el controlador antes de llamar a este método)
     *
     * @param int $id ID del rol
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function eliminar($id)
    {
        try {
            $query = "DELETE FROM {$this->tabla} WHERE idrol = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Verifica si existe un rol con el mismo nombre
     *
     * @param string $nombre Nombre del rol
     * @param int $id_excluir ID del rol a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeNombre($nombre, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nombre = :nombre AND idrol != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nombre = :nombre";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Valida los datos del rol antes de crear o actualizar
     *
     * @param array $datos Datos del rol
     * @param int $id_excluir ID del rol a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre del rol es obligatorio';
        }

        if (!empty($datos['nombre']) && $this->existeNombre($datos['nombre'], $id_excluir)) {
            $errores[] = 'Ya existe un rol con este nombre';
        }

        if (empty($datos['dashboard'])) {
            $errores[] = 'El dashboard del rol es obligatorio';
        }

        return $errores;
    }

    /**
     * Obtiene el ID del último rol insertado
     *
     * @return int ID del último rol insertado
     */
    public function getLastInsertId()
    {
        try {
            return $this->conexion->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return 0;
        }
    }

    /**
     * Obtiene los roles con información básica para selects
     *
     * @param bool $soloActivos Si es true, solo devuelve roles activos
     * @return array Lista de roles en formato id => nombre
     */
    public function getParaSelect($soloActivos = true)
    {
        try {
            $query = "SELECT idrol, nombre FROM {$this->tabla}";

            if ($soloActivos) {
                $query .= " WHERE estado = 1";
            }

            $query .= " ORDER BY idrol ASC";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();

            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $opciones = [];

            foreach ($resultados as $rol) {
                $opciones[$rol['idrol']] = $rol['nombre'];
            }

            return $opciones;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Cuenta cuántos usuarios activos tiene asignado un rol
     *
     * @param int $idRol ID del rol
     * @return int Número de usuarios activos con ese rol
     */
    public function contarUsuarios($idRol)
    {
        try {
            $query = "SELECT COUNT(*) FROM usuarios WHERE idrol = :id AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $idRol, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return 0;
        }
    }

    /**
     * Cuenta cuántos roles con es_admin=1 están activos (para proteger el
     * último admin de quedar sin acceso)
     *
     * @param int $id_excluir ID del rol a excluir del conteo (opcional)
     * @return int Número de roles admin activos
     */
    public function contarRolesAdminActivos($id_excluir = null)
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE es_admin = 1 AND estado = 1";
            if ($id_excluir) {
                $query .= " AND idrol != :id";
            }
            $stmt = $this->conexion->prepare($query);
            if ($id_excluir) {
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return 0;
        }
    }

    /**
     * Obtiene el idrol de un usuario
     *
     * @param int $idusuario ID del usuario
     * @return int|null idrol del usuario, o null si no tiene rol asignado
     */
    public function getIdRolDeUsuario($idusuario)
    {
        try {
            $query = "SELECT idrol FROM usuarios WHERE idusuario = :idusuario";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();
            $idrol = $stmt->fetchColumn();
            return $idrol !== false && $idrol !== null ? (int) $idrol : null;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    /**
     * Obtiene el dashboard asignado al rol de un usuario, resuelto siempre contra BD
     * (nunca contra el dato de sesión, para no quedar desactualizado tras un deploy)
     *
     * @param int $idusuario ID del usuario
     * @return string Nombre de archivo del dashboard (fallback 'dashboard_general.php')
     */
    public function getDashboardDeUsuario($idusuario)
    {
        try {
            $query = "SELECT r.dashboard
                     FROM usuarios u
                     JOIN rol r ON r.idrol = u.idrol
                     WHERE u.idusuario = :idusuario";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();
            $dashboard = $stmt->fetchColumn();
            return $dashboard !== false && !empty($dashboard) ? $dashboard : 'dashboard_general.php';
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return 'dashboard_general.php';
        }
    }

    /**
     * Obtiene estadísticas de roles
     *
     * @return array Estadísticas de roles
     */
    public function getEstadisticas()
    {
        try {
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM {$this->tabla}");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $this->conexion->prepare("SELECT COUNT(*) as activos FROM {$this->tabla} WHERE estado = 1");
            $stmt->execute();
            $activos = $stmt->fetch(PDO::FETCH_ASSOC)['activos'];

            $stmt = $this->conexion->prepare("SELECT COUNT(*) as inactivos FROM {$this->tabla} WHERE estado = 0");
            $stmt->execute();
            $inactivos = $stmt->fetch(PDO::FETCH_ASSOC)['inactivos'];

            return [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos
            ];
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0
            ];
        }
    }
}
