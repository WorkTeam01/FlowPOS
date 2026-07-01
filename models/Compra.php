<?php

/**
 * Modelo Compra
 * 
 * Gestiona las operaciones relacionadas con las compras en la base de datos
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Compra
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de compras en la base de datos
     * @var string
     */
    private $tabla = 'compra';

    /**
     * Tabla de detalles de compra
     * @var string
     */
    private $tablaDetalle = 'detallecompra';

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
     * Obtiene todas las compras con información del usuario
     * 
     * @return array Lista de compras
     */
    public function getAll()
    {
        try {
            $query = "SELECT c.*, u.nombre as usuario_nombre 
                      FROM {$this->tabla} c
                      JOIN usuarios u ON c.idusuario = u.idusuario
                      ORDER BY c.idcompra DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene una compra por su ID con detalles
     * 
     * @param int $id ID de la compra
     * @return array|bool Datos de la compra con detalles o false si no existe
     */
    public function getById($id)
    {
        try {
            // Obtener información básica de la compra
            $query = "SELECT c.*, u.nombre as usuario_nombre 
                      FROM {$this->tabla} c
                      JOIN usuarios u ON c.idusuario = u.idusuario
                      WHERE c.idcompra = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $compra = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$compra) {
                return false;
            }

            // Obtener detalles de la compra
            $queryDetalle = "SELECT dc.*, p.nombre as producto_nombre, p.codigo as producto_codigo
                             FROM {$this->tablaDetalle} dc
                             JOIN producto p ON dc.idproducto = p.idproducto
                             WHERE dc.idcompra = :id";
            $stmtDetalle = $this->conexion->prepare($queryDetalle);
            $stmtDetalle->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtDetalle->execute();
            $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

            $compra['detalles'] = $detalles;

            return $compra;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Crea una nueva compra con sus detalles
     * 
     * @param array $datos Datos de la compra y sus detalles
     * @return int|bool ID de la nueva compra o false en caso de error
     */
    public function crear($datos)
    {
        try {
            $this->conexion->beginTransaction();

            // Insertar la cabecera de la compra
            $query = "INSERT INTO {$this->tabla} 
                     (idusuario, fechacompra, observaciones, totalcompra, estado) 
                     VALUES 
                     (:idusuario, :fechacompra, :observaciones, :totalcompra, :estado)";

            $stmt = $this->conexion->prepare($query);

            $stmt->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);
            $stmt->bindParam(':fechacompra', $datos['fechacompra'], PDO::PARAM_STR);
            $this->bindOptionalParam($stmt, ':observaciones', $datos['observaciones'] ?? null);
            $stmt->bindParam(':totalcompra', $datos['totalcompra'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new PDOException("Error al crear la compra");
            }

            $idCompra = $this->conexion->lastInsertId();

            // Insertar los detalles de la compra
            foreach ($datos['detalles'] as $detalle) {
                $queryDetalle = "INSERT INTO {$this->tablaDetalle} 
                                (idcompra, idproducto, cantidad, preciocompra) 
                                VALUES 
                                (:idcompra, :idproducto, :cantidad, :preciocompra)";

                $stmtDetalle = $this->conexion->prepare($queryDetalle);

                $stmtDetalle->bindParam(':idcompra', $idCompra, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':idproducto', $detalle['idproducto'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $detalle['cantidad'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':preciocompra', $detalle['preciocompra'], PDO::PARAM_STR);

                if (!$stmtDetalle->execute()) {
                    throw new PDOException("Error al crear el detalle de compra");
                }

                // Actualizar el stock del producto
                $this->actualizarStockProducto($detalle['idproducto'], $detalle['cantidad']);
            }

            $this->conexion->commit();
            return $idCompra;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de una compra
     * 
     * @param int $id ID de la compra
     * @param int $estado Nuevo estado (1 = activo, 0 = inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $query = "UPDATE {$this->tabla} SET estado = :estado WHERE idcompra = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Cancela una compra y revierte el stock de productos
     * 
     * @param int $id ID de la compra
     * @return bool True si se canceló correctamente, False en caso contrario
     */
    public function cancelar($id)
    {
        try {
            $this->conexion->beginTransaction();

            // Obtener los detalles de la compra
            $compra = $this->getById($id);
            if (!$compra || $compra['estado'] == 0) {
                throw new PDOException("La compra no existe o ya está cancelada");
            }

            // Revertir el stock de cada producto
            foreach ($compra['detalles'] as $detalle) {
                $this->actualizarStockProducto($detalle['idproducto'], -$detalle['cantidad']);
            }

            // Actualizar el estado de la compra (0 = cancelada/inactiva)
            if (!$this->actualizarEstado($id, 0)) {
                throw new PDOException("Error al actualizar el estado de la compra");
            }

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el stock de un producto
     * 
     * @param int $idProducto ID del producto
     * @param int $cantidad Cantidad a sumar (positivo) o restar (negativo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    private function actualizarStockProducto($idProducto, $cantidad)
    {
        try {
            $query = "UPDATE producto SET stock = stock + :cantidad WHERE idproducto = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':id', $idProducto, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PDOException("Error al actualizar el stock del producto: " . $e->getMessage());
        }
    }

    /**
     * Obtiene las compras por rango de fechas
     * 
     * @param string $fechaInicio Fecha de inicio (formato YYYY-MM-DD)
     * @param string $fechaFin Fecha de fin (formato YYYY-MM-DD)
     * @return array Lista de compras en el rango de fechas
     */
    public function getPorRangoFechas($fechaInicio, $fechaFin)
    {
        try {
            $query = "SELECT c.*, u.nombre as usuario_nombre 
                      FROM {$this->tabla} c
                      JOIN usuarios u ON c.idusuario = u.idusuario
                      WHERE c.fechacompra BETWEEN :fechaInicio AND :fechaFin
                      ORDER BY c.fechacompra DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene las compras por estado
     * 
     * @param int $estado Estado de las compras a buscar (1 = activo, 0 = inactivo)
     * @return array Lista de compras con el estado especificado
     */
    public function getPorEstado($estado)
    {
        try {
            $query = "SELECT c.*, u.nombre as usuario_nombre 
                      FROM {$this->tabla} c
                      JOIN usuarios u ON c.idusuario = u.idusuario
                      WHERE c.estado = :estado
                      ORDER BY c.fechacompra DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene las compras de un usuario específico
     * 
     * @param int $idUsuario ID del usuario
     * @return array Lista de compras del usuario
     */
    public function getPorUsuario($idUsuario)
    {
        try {
            $query = "SELECT c.*, u.nombre as usuario_nombre 
                      FROM {$this->tabla} c
                      JOIN usuarios u ON c.idusuario = u.idusuario
                      WHERE c.idusuario = :idUsuario
                      ORDER BY c.fechacompra DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Valida los datos de la compra antes de crear
     * 
     * @param array $datos Datos de la compra
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos)
    {
        $errores = [];

        // Validar campos obligatorios
        $campos_obligatorios = ['idusuario', 'fechacompra', 'totalcompra'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo {$campo} es obligatorio";
            }
        }

        // Validar detalles de la compra
        if (empty($datos['detalles']) || !is_array($datos['detalles']) || count($datos['detalles']) == 0) {
            $errores[] = "La compra debe tener al menos un producto";
        } else {
            foreach ($datos['detalles'] as $detalle) {
                if (empty($detalle['idproducto'])) {
                    $errores[] = "Todos los productos deben ser válidos";
                }
                if (empty($detalle['cantidad']) || $detalle['cantidad'] <= 0) {
                    $errores[] = "La cantidad debe ser mayor que cero";
                }
                if (empty($detalle['preciocompra'])) {
                    $errores[] = "Todos los productos deben tener un precio de compra";
                }
            }
        }

        return $errores;
    }

    /**
     * Método auxiliar para manejar parámetros opcionales en consultas preparadas
     * 
     * @param PDOStatement $stmt Declaración preparada
     * @param string $param Nombre del parámetro
     * @param mixed $value Valor del parámetro
     * @param int $type Tipo de dato (PDO::PARAM_*)
     */
    private function bindOptionalParam(&$stmt, $param, $value, $type = PDO::PARAM_STR)
    {
        if ($value === null) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam($param, $value, $type);
        }
    }
}
