<?php

/**
 * Modelo Sucursal
 * 
 * Gestiona las operaciones relacionadas con las sucursales de la empresa en la base de datos
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Sucursal
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de sucursales en la base de datos
     * @var string
     */
    private $tabla = 'sucursal';

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
     * Obtiene todas las sucursales
     * 
     * @param bool $soloActivas Si es true, solo devuelve sucursales activas
     * @return array Lista de sucursales
     */
    public function getAll($soloActivas = false)
    {
        try {
            $query = "SELECT s.*, e.nombre as empresa_nombre 
                      FROM {$this->tabla} s
                      LEFT JOIN empresa e ON s.idempresa = e.idempresa";

            if ($soloActivas) {
                $query .= " WHERE s.estado = 1";
            }

            $query .= " ORDER BY s.nombre";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene una sucursal por su ID
     * 
     * @param int $id ID de la sucursal
     * @return array|bool Datos de la sucursal o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT s.*, e.nombre as empresa_nombre 
                      FROM {$this->tabla} s
                      LEFT JOIN empresa e ON s.idempresa = e.idempresa
                      WHERE s.idsucursal = :id";
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
     * Crea una nueva sucursal
     * 
     * @param array $datos Datos de la sucursal (nombre, idempresa)
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (nombre, idempresa) 
                      VALUES (:nombre, :idempresa)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':idempresa', $datos['idempresa'], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza una sucursal existente
     * 
     * @param int $id ID de la sucursal
     * @param array $datos Datos de la sucursal (nombre, idempresa)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} 
                      SET nombre = :nombre, idempresa = :idempresa 
                      WHERE idsucursal = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':idempresa', $datos['idempresa'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de una sucursal
     * 
     * @param int $id ID de la sucursal
     * @param int $estado Nuevo estado (1: activo, 0: inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idsucursal = :id";
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
     * Activa una sucursal
     * 
     * @param int $id ID de la sucursal
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function activar($id)
    {
        return $this->actualizarEstado($id, 1);
    }

    /**
     * Desactiva una sucursal
     * 
     * @param int $id ID de la sucursal
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function desactivar($id)
    {
        return $this->actualizarEstado($id, 0);
    }

    /**
     * Verifica si existe una sucursal con el mismo nombre
     * 
     * @param string $nombre Nombre de la sucursal
     * @param int $id_excluir ID de la sucursal a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeNombre($nombre, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nombre = :nombre AND idsucursal != :id";
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
     * Valida los datos de la sucursal antes de crear o actualizar
     * 
     * @param array $datos Datos de la sucursal
     * @param int $id_excluir ID de la sucursal a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        // Validar campos obligatorios
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre de la sucursal es obligatorio';
        }

        if (empty($datos['idempresa'])) {
            $errores[] = 'Debe seleccionar una empresa';
        }

        // Validar nombre único
        if (!empty($datos['nombre']) && $this->existeNombre($datos['nombre'], $id_excluir)) {
            $errores[] = 'Ya existe una sucursal con este nombre';
        }

        return $errores;
    }

    /**
     * Obtiene el ID de la última sucursal insertada
     * 
     * @return int ID de la última sucursal insertada
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
     * Obtiene las sucursales con información básica para selects
     * 
     * @param bool $soloActivas Si es true, solo devuelve sucursales activas
     * @return array Lista de sucursales en formato id => nombre
     */
    public function getParaSelect($soloActivas = true)
    {
        try {
            $query = "SELECT idsucursal, nombre FROM {$this->tabla}";

            if ($soloActivas) {
                $query .= " WHERE estado = 1";
            }

            $query .= " ORDER BY nombre ASC";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();

            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $opciones = [];

            foreach ($resultados as $sucursal) {
                $opciones[$sucursal['idsucursal']] = $sucursal['nombre'];
            }

            return $opciones;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene estadísticas de sucursales
     * 
     * @return array Estadísticas de sucursales
     */
    public function getEstadisticas()
    {
        try {
            // Total de sucursales
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM {$this->tabla}");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Sucursales activas
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as activas FROM {$this->tabla} WHERE estado = 1");
            $stmt->execute();
            $activas = $stmt->fetch(PDO::FETCH_ASSOC)['activas'];

            // Sucursales inactivas
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as inactivas FROM {$this->tabla} WHERE estado = 0");
            $stmt->execute();
            $inactivas = $stmt->fetch(PDO::FETCH_ASSOC)['inactivas'];

            return [
                'total' => $total,
                'activas' => $activas,
                'inactivas' => $inactivas
            ];
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [
                'total' => 0,
                'activas' => 0,
                'inactivas' => 0
            ];
        }
    }

    /**
     * Obtiene el número de usuarios asociados a una sucursal
     * 
     * @param int $idSucursal ID de la sucursal
     * @return int Número de usuarios activos
     */
 
 
public function contarUsuarios($idSucursal)
{
    try {
        $query = "SELECT COUNT(*) FROM usuarios WHERE estado = 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        $this->lastError = $e->getMessage();
        return 0;
    }
}

    /**
     * Obtiene las sucursales por empresa
     * 
     * @param int $idEmpresa ID de la empresa
     * @param bool $soloActivas Si es true, solo devuelve sucursales activas
     * @return array Lista de sucursales
     */
    public function getPorEmpresa($idEmpresa, $soloActivas = true)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idempresa = :idempresa";

            if ($soloActivas) {
                $query .= " AND estado = 1";
            }

            $query .= " ORDER BY nombre";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idempresa', $idEmpresa, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
}