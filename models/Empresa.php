<?php

/**
 * Modelo Empresa
 * 
 * Gestiona las operaciones relacionadas con las empresas en la base de datos
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Empresa
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de empresas en la base de datos
     * @var string
     */
    private $tabla = 'empresa';

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
     * Obtiene todas las empresas
     * 
     * @param bool $soloActivas Si es true, solo devuelve empresas activas
     * @return array Lista de empresas
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
     * Obtiene una empresa por su ID
     * 
     * @param int $id ID de la empresa
     * @return array|bool Datos de la empresa o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idempresa = :id";
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
     * Crea una nueva empresa
     * 
     * @param array $datos Datos de la empresa
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} 
                     (nombre, nit, direccion, telefono, email, imagen) 
                     VALUES 
                     (:nombre, :nit, :direccion, :telefono, :email, :imagen)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':nit', $datos['nit'], PDO::PARAM_STR);
            $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $datos['email'], PDO::PARAM_STR);
            $stmt->bindParam(':imagen', $datos['imagen'], PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza una empresa existente
     * 
     * @param int $id ID de la empresa
     * @param array $datos Datos de la empresa
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                      nombre = :nombre,
                      nit = :nit,
                      direccion = :direccion,
                      telefono = :telefono,
                      email = :email,
                      imagen = :imagen
                      WHERE idempresa = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':nit', $datos['nit'], PDO::PARAM_STR);
            $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $datos['email'], PDO::PARAM_STR);
            $stmt->bindParam(':imagen', $datos['imagen'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de una empresa
     * 
     * @param int $id ID de la empresa
     * @param int $estado Nuevo estado (1: activo, 0: inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idempresa = :id";
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
     * Activa una empresa
     * 
     * @param int $id ID de la empresa
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function activar($id)
    {
        return $this->actualizarEstado($id, 1);
    }

    /**
     * Desactiva una empresa
     * 
     * @param int $id ID de la empresa
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function desactivar($id)
    {
        return $this->actualizarEstado($id, 0);
    }

    /**
     * Verifica si existe una empresa con el mismo NIT
     * 
     * @param string $nit NIT de la empresa
     * @param int $id_excluir ID de la empresa a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeNit($nit, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nit = :nit AND idempresa != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':nit', $nit, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nit = :nit";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':nit', $nit, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Verifica si existe una empresa con el mismo email
     * 
     * @param string $email Email de la empresa
     * @param int $id_excluir ID de la empresa a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeEmail($email, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE email = :email AND idempresa != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE email = :email";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Valida los datos de la empresa antes de crear o actualizar
     * 
     * @param array $datos Datos de la empresa
     * @param int $id_excluir ID de la empresa a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        // Validar campos obligatorios
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre de la empresa es obligatorio';
        }

        if (empty($datos['nit'])) {
            $errores[] = 'El NIT de la empresa es obligatorio';
        }

        if (empty($datos['direccion'])) {
            $errores[] = 'La dirección de la empresa es obligatoria';
        }

        if (empty($datos['telefono'])) {
            $errores[] = 'El teléfono de la empresa es obligatorio';
        }

        if (empty($datos['email'])) {
            $errores[] = 'El email de la empresa es obligatorio';
        } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email de la empresa no es válido';
        }

        // Validar NIT único
        if (!empty($datos['nit']) && $this->existeNit($datos['nit'], $id_excluir)) {
            $errores[] = 'Ya existe una empresa con este NIT';
        }

        // Validar email único
        if (!empty($datos['email']) && $this->existeEmail($datos['email'], $id_excluir)) {
            $errores[] = 'Ya existe una empresa con este email';
        }

        return $errores;
    }

    /**
     * Obtiene el ID de la última empresa insertada
     * 
     * @return int ID de la última empresa insertada
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
     * Obtiene las empresas con información básica para selects
     * 
     * @param bool $soloActivas Si es true, solo devuelve empresas activas
     * @return array Lista de empresas en formato id => nombre
     */
    public function getParaSelect($soloActivas = true)
    {
        try {
            $query = "SELECT idempresa, nombre FROM {$this->tabla}";

            if ($soloActivas) {
                $query .= " WHERE estado = 1";
            }

            $query .= " ORDER BY nombre ASC";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();

            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $opciones = [];

            foreach ($resultados as $empresa) {
                $opciones[$empresa['idempresa']] = $empresa['nombre'];
            }

            return $opciones;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene estadísticas de empresas
     * 
     * @return array Estadísticas de empresas
     */
    public function getEstadisticas()
    {
        try {
            // Total de empresas
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM {$this->tabla}");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Empresas activas
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as activas FROM {$this->tabla} WHERE estado = 1");
            $stmt->execute();
            $activas = $stmt->fetch(PDO::FETCH_ASSOC)['activas'];

            // Empresas inactivas
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
     * Obtiene el número de sucursales asociadas a una empresa
     * 
     * @param int $idEmpresa ID de la empresa
     * @return int Número de sucursales activas
     */
    public function contarSucursales($idEmpresa)
    {
        try {
            $query = "SELECT COUNT(*) FROM sucursal WHERE idempresa = :id AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $idEmpresa, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return 0;
        }
    }
}