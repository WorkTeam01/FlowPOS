<?php

/**
 * Modelo Categoria
 * 
 * Gestiona las operaciones relacionadas con las categorías de productos en la base de datos
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Categoria
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de categorías en la base de datos
     * @var string
     */
    private $tabla = 'categoria';

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
     * Obtiene todas las categorías
     * 
     * @param bool $soloActivas Si es true, solo devuelve categorías activas
     * @return array Lista de categorías
     */
    public function getAll($soloActivas = false)
    {
        try {
            $query = "SELECT * FROM {$this->tabla}";

            if ($soloActivas) {
                $query .= " WHERE estado = 1";
            }

            $query .= " ORDER BY nombre";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene una categoría por su ID
     * 
     * @param int $id ID de la categoría
     * @return array|bool Datos de la categoría o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idcategoria = :id";
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
     * Crea una nueva categoría
     * 
     * @param array $datos Datos de la categoría (solo necesita 'nombre')
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (nombre) VALUES (:nombre)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza una categoría existente
     * 
     * @param int $id ID de la categoría
     * @param array $datos Datos de la categoría (solo necesita 'nombre')
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET nombre = :nombre WHERE idcategoria = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de una categoría
     * 
     * @param int $id ID de la categoría
     * @param int $estado Nuevo estado (1: activo, 0: inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idcategoria = :id";
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
     * Activa una categoría
     * 
     * @param int $id ID de la categoría
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function activar($id)
    {
        return $this->actualizarEstado($id, 1);
    }

    /**
     * Desactiva una categoría
     * 
     * @param int $id ID de la categoría
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function desactivar($id)
    {
        return $this->actualizarEstado($id, 0);
    }

    /**
     * Verifica si existe una categoría con el mismo nombre
     * 
     * @param string $nombre Nombre de la categoría
     * @param int $id_excluir ID de la categoría a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeNombre($nombre, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nombre = :nombre AND idcategoria != :id";
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
     * Valida los datos de la categoría antes de crear o actualizar
     * 
     * @param array $datos Datos de la categoría
     * @param int $id_excluir ID de la categoría a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        // Validar campos obligatorios
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre de la categoría es obligatorio';
        }

        // Validar nombre único
        if (!empty($datos['nombre']) && $this->existeNombre($datos['nombre'], $id_excluir)) {
            $errores[] = 'Ya existe una categoría con este nombre';
        }

        return $errores;
    }

    /**
     * Obtiene el ID de la última categoría insertada
     * 
     * @return int ID de la última categoría insertada
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
     * Obtiene las categorías con información básica para selects
     * 
     * @param bool $soloActivas Si es true, solo devuelve categorías activas
     * @return array Lista de categorías en formato id => nombre
     */
    public function getParaSelect($soloActivas = true)
    {
        try {
            $query = "SELECT idcategoria, nombre FROM {$this->tabla}";

            if ($soloActivas) {
                $query .= " WHERE estado = 1";
            }

            $query .= " ORDER BY nombre ASC";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();

            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $opciones = [];

            foreach ($resultados as $categoria) {
                $opciones[$categoria['idcategoria']] = $categoria['nombre'];
            }

            return $opciones;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene estadísticas de categorías
     * 
     * @return array Estadísticas de categorías
     */
    public function getEstadisticas()
    {
        try {
            // Total de categorías
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM {$this->tabla}");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Categorías activas
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as activas FROM {$this->tabla} WHERE estado = 1");
            $stmt->execute();
            $activas = $stmt->fetch(PDO::FETCH_ASSOC)['activas'];

            // Categorías inactivas
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
     * Obtiene el número de productos asociados a una categoría
     * 
     * @param int $idCategoria ID de la categoría
     * @return int Número de productos activos
     */
    public function contarProductos($idCategoria)
    {
        try {
            $query = "SELECT COUNT(*) FROM producto WHERE idcategoria = :id AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $idCategoria, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return 0;
        }
    }
}