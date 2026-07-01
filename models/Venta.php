<?php

/**
 * Modelo Venta
 * 
 * Gestiona las operaciones relacionadas con las ventas y pagos mixtos en la base de datos
 * 
 * @version 2.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Venta
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de ventas en la base de datos
     * @var string
     */
    private $tabla = 'venta';

    /**
     * Tabla de detalles de venta
     * @var string
     */
    private $tablaDetalle = 'detalleventa';

    /**
     * Tabla de pagos de venta
     * @var string
     */
    private $tablaPago = 'pagoventa';

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
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizarDatos($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Obtiene todas las ventas con información del cliente y usuario
     * 
     * @return array Lista de ventas
     */
    public function getAll()
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(c.nombres, ' ', c.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN cliente c ON v.idcliente = c.idcliente
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      ORDER BY v.idventa DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene una venta por su ID con detalles y métodos de pago
     * 
     * @param int $id ID de la venta
     * @return array|bool Datos de la venta con detalles y pagos o false si no existe
     */
    public function getById($id)
    {
        try {
            // Obtener información básica de la venta
            $query = "SELECT v.*, 
                CONCAT(c.nombres, ' ', c.apellidopaterno) as cliente_nombre,
                c.numdocumento as numdocumento_cliente,
                u.nombre as usuario_nombre, 
                u.apellidopaterno as usuario_apellido
                FROM {$this->tabla} v
                LEFT JOIN cliente c ON v.idcliente = c.idcliente
                JOIN usuarios u ON v.idusuario = u.idusuario
                WHERE v.idventa = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                return false;
            }

            // Obtener detalles de la venta (productos)
            $queryDetalle = "SELECT dv.*, p.nombre as producto_nombre, p.codigo as producto_codigo
                            FROM {$this->tablaDetalle} dv
                            JOIN producto p ON dv.idproducto = p.idproducto
                            WHERE dv.idventa = :id";
            $stmtDetalle = $this->conexion->prepare($queryDetalle);
            $stmtDetalle->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtDetalle->execute();
            $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

            // Obtener métodos de pago
            $queryPagos = "SELECT * FROM {$this->tablaPago} 
                            WHERE idventa = :id ORDER BY idpagoventa ASC";
            $stmtPagos = $this->conexion->prepare($queryPagos);
            $stmtPagos->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtPagos->execute();
            $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

            // Obtener datos de la empresa
            $queryEmpresa = "SELECT * FROM empresa LIMIT 1";
            $stmtEmpresa = $this->conexion->prepare($queryEmpresa);
            $stmtEmpresa->execute();
            $empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

            // Obtener datos de la sucursal
            $querySucursal = "SELECT nombre as nombre_sucursal FROM sucursal LIMIT 1";
            $stmtSucursal = $this->conexion->prepare($querySucursal);
            $stmtSucursal->execute();
            $sucursal = $stmtSucursal->fetch(PDO::FETCH_ASSOC);

            // Agregar detalles y pagos a la venta
            $venta['detalles'] = $detalles;
            $venta['pagos'] = $pagos;
            $venta['empresa'] = $empresa;
            $venta['sucursal'] = $sucursal;

            return $venta;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Crea una nueva venta con sus detalles y métodos de pago
     * 
     * @param array $datos Datos de la venta, detalles y métodos de pago
     * @return int|bool ID de la nueva venta o false en caso de error
     */
    public function crear($datos)
    {
        try {
            $this->conexion->beginTransaction();

            // Insertar la cabecera de la venta
            $query = "INSERT INTO {$this->tabla} 
                 (idcliente, idusuario, totalventa, observacion, fechacreacion, estado) 
                 VALUES 
                 (:idcliente, :idusuario, :totalventa, :observacion, NOW(), :estado)";

            $stmt = $this->conexion->prepare($query);

            // Variables para bindParam
            $idcliente = $datos['idcliente'];
            $idusuario = $datos['idusuario'];
            $totalventa = $datos['totalventa'];
            $observacion = $datos['observacion'];
            $estado = $datos['estado'];

            $stmt->bindParam(':idcliente', $idcliente, PDO::PARAM_INT);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':totalventa', $totalventa, PDO::PARAM_STR);
            $stmt->bindParam(':observacion', $observacion, PDO::PARAM_STR);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new PDOException("Error al crear la venta");
            }

            $idVenta = $this->conexion->lastInsertId();

            // Insertar los detalles de la venta (productos)
            foreach ($datos['detalles'] as $detalle) {
                $queryDetalle = "INSERT INTO {$this->tablaDetalle} 
                                (idventa, idproducto, cantidad, precioventa, descuento) 
                                VALUES 
                                (:idventa, :idproducto, :cantidad, :precioventa, :descuento)";

                $stmtDetalle = $this->conexion->prepare($queryDetalle);

                // Variables para bindParam
                $idproducto = $detalle['idproducto'];
                $cantidad = $detalle['cantidad'];
                $precioventa = $detalle['precioventa'];
                $descuento = $detalle['descuento'] ?? 0.00;

                $stmtDetalle->bindParam(':idventa', $idVenta, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':idproducto', $idproducto, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':precioventa', $precioventa, PDO::PARAM_STR);
                $stmtDetalle->bindParam(':descuento', $descuento, PDO::PARAM_STR);

                if (!$stmtDetalle->execute()) {
                    throw new PDOException("Error al crear el detalle de venta");
                }

                // Actualizar el stock del producto (reducir)
                $this->actualizarStockProducto($idproducto, -$cantidad);
            }

            // Insertar los métodos de pago
            if (isset($datos['pagos']) && is_array($datos['pagos']) && count($datos['pagos']) > 0) {
                foreach ($datos['pagos'] as $pago) {
                    // Verificar si el monto es mayor que cero antes de insertar
                    $monto = (float)($pago['monto'] ?? 0);
                    if ($monto <= 0) continue; // Omitir pagos con monto cero

                    $queryPago = "INSERT INTO {$this->tablaPago} 
                                 (idventa, metodopago, monto, pagorecibido, cambio, fechacreacion, estado) 
                                 VALUES 
                                 (:idventa, :metodopago, :monto, :pagorecibido, :cambio, NOW(), 1)";

                    $stmtPago = $this->conexion->prepare($queryPago);

                    // Variables para bindParam
                    $metodopago = $pago['metodopago'];
                    $pagorecibido = $pago['pagorecibido'];
                    $cambio = $pago['cambio'];

                    $stmtPago->bindParam(':idventa', $idVenta, PDO::PARAM_INT);
                    $stmtPago->bindParam(':metodopago', $metodopago, PDO::PARAM_STR);
                    $stmtPago->bindParam(':monto', $monto, PDO::PARAM_STR);
                    $stmtPago->bindParam(':pagorecibido', $pagorecibido, PDO::PARAM_STR);
                    $stmtPago->bindParam(':cambio', $cambio, PDO::PARAM_STR);

                    if (!$stmtPago->execute()) {
                        throw new PDOException("Error al registrar el método de pago");
                    }
                }
            } else {
                // Si no hay pagos definidos, lanzar error
                throw new PDOException("No se han definido métodos de pago para la venta");
            }

            $this->conexion->commit();
            return $idVenta;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de una venta
     * 
     * @param int $id ID de la venta
     * @param int $estado Nuevo estado (1: activo, 0: anulado)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $query = "UPDATE {$this->tabla} SET estado = :estado, fechaactualizacion = NOW() WHERE idventa = :id";
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
     * Anula una venta y revierte el stock de productos
     * 
     * @param int $id ID de la venta
     * @return bool True si se anuló correctamente, False en caso contrario
     */
    public function anular($id)
    {
        try {
            $this->conexion->beginTransaction();

            // Obtener los detalles de la venta
            $venta = $this->getById($id);
            if (!$venta || $venta['estado'] == 0) {
                throw new PDOException("La venta no existe o ya está anulada");
            }

            // Revertir el stock de cada producto
            foreach ($venta['detalles'] as $detalle) {
                $this->actualizarStockProducto($detalle['idproducto'], $detalle['cantidad']);
            }

            // Actualizar el estado de los pagos
            $queryPagos = "UPDATE {$this->tablaPago} SET estado = 0, fechaactualizacion = NOW() WHERE idventa = :id";
            $stmtPagos = $this->conexion->prepare($queryPagos);
            $stmtPagos->bindParam(':id', $id, PDO::PARAM_INT);

            if (!$stmtPagos->execute()) {
                throw new PDOException("Error al anular los pagos");
            }

            // Actualizar el estado de la venta
            if (!$this->actualizarEstado($id, 0)) {
                throw new PDOException("Error al actualizar el estado de la venta");
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
     * Obtiene las ventas por rango de fechas
     * 
     * @param string $fechaInicio Fecha de inicio (formato YYYY-MM-DD)
     * @param string $fechaFin Fecha de fin (formato YYYY-MM-DD)
     * @return array Lista de ventas en el rango de fechas
     */
    public function getPorRangoFechas($fechaInicio, $fechaFin)
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(c.nombres, ' ', c.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN cliente c ON v.idcliente = c.idcliente
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      WHERE DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin
                      ORDER BY v.fechacreacion DESC";
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
     * Obtiene las ventas por estado
     * 
     * @param int $estado Estado de las ventas a buscar (1: Activo, 0: Inactivo)
     * @return array Lista de ventas con el estado especificado
     */
    public function getPorEstado($estado)
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(c.nombres, ' ', c.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN cliente c ON v.idcliente = c.idcliente
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      WHERE v.estado = :estado
                      ORDER BY v.fechacreacion DESC";
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
     * Obtiene las ventas de un usuario específico
     * 
     * @param int $idUsuario ID del usuario
     * @return array Lista de ventas del usuario
     */
    public function getPorUsuario($idUsuario)
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(c.nombres, ' ', c.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN cliente c ON v.idcliente = c.idcliente
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      WHERE v.idusuario = :idUsuario
                      ORDER BY v.fechacreacion DESC";
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
     * Obtiene las ventas de un cliente específico
     * 
     * @param int $idCliente ID del cliente
     * @return array Lista de ventas del cliente
     */
    public function getPorCliente($idCliente)
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(c.nombres, ' ', c.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN cliente c ON v.idcliente = c.idcliente
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      WHERE v.idcliente = :idCliente
                      ORDER BY v.fechacreacion DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene los métodos de pago de una venta específica
     * 
     * @param int $idVenta ID de la venta
     * @return array Lista de métodos de pago de la venta
     */
    public function getMetodosPago($idVenta)
    {
        try {
            $query = "SELECT * FROM {$this->tablaPago} 
                 WHERE idventa = :idVenta AND estado = 1
                 ORDER BY idpagoventa";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idVenta', $idVenta, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Valida los datos de la venta antes de crear
     * 
     * @param array $datos Datos de la venta
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos)
    {
        $errores = [];

        // Validar campos obligatorios
        $campos_obligatorios = ['idusuario', 'totalventa'];
        foreach ($campos_obligatorios as $campo) {
            if (!isset($datos[$campo]) || $datos[$campo] === '') {
                $errores[] = "El campo {$campo} es obligatorio";
            }
        }

        // Validar detalles de la venta
        if (empty($datos['detalles']) || !is_array($datos['detalles']) || count($datos['detalles']) == 0) {
            $errores[] = "La venta debe tener al menos un producto";
        } else {
            foreach ($datos['detalles'] as $detalle) {
                if (empty($detalle['idproducto'])) {
                    $errores[] = "Todos los productos deben ser válidos";
                }
                if (empty($detalle['cantidad']) || $detalle['cantidad'] <= 0) {
                    $errores[] = "La cantidad debe ser mayor que cero";
                }
                if (empty($detalle['precioventa']) || $detalle['precioventa'] <= 0) {
                    $errores[] = "Todos los productos deben tener un precio de venta";
                }
            }
        }

        // Validar métodos de pago
        if (empty($datos['pagos']) || !is_array($datos['pagos']) || count($datos['pagos']) == 0) {
            $errores[] = "Debe especificar al menos un método de pago";
        } else {
            $totalPagos = 0;
            foreach ($datos['pagos'] as $pago) {
                if (empty($pago['metodopago'])) {
                    $errores[] = "Todos los métodos de pago deben ser válidos";
                }
                if (empty($pago['monto']) || $pago['monto'] <= 0) {
                    $errores[] = "Todos los pagos deben tener un monto mayor que cero";
                } else {
                    $totalPagos += (float)$pago['monto'];
                }

                // Validar que el pago recibido cubra el monto para pagos en efectivo
                if ($pago['metodopago'] == 'efectivo' && (float)$pago['pagorecibido'] < (float)$pago['monto']) {
                    $errores[] = "El pago recibido en efectivo debe ser mayor o igual al monto";
                }
            }

            // Verificar que el total de pagos coincida con el total de la venta
            if (abs($totalPagos - (float)$datos['totalventa']) > 0.01) {
                $errores[] = "El total de los pagos no coincide con el total de la venta";
            }
        }

        return $errores;
    }

    /**
     * Obtiene estadísticas de ventas del día
     * 
     * @return array Estadísticas de ventas (ventas hoy, cliente que más compró, producto más vendido)
     */
    public function getEstadisticas()
    {
        try {
            $estadisticas = [
                'ventas_hoy' => 0,
                'total_hoy' => 0,
                'cliente_mas_compro' => null,
                'producto_mas_vendido' => null,
                'metodos_pago' => []
            ];

            // Obtener fecha actual
            $hoy = date('Y-m-d');

            // Ventas hoy (cantidad y monto total)
            $query = "SELECT COUNT(*) as cantidad, SUM(totalventa) as total 
                     FROM {$this->tabla} 
                     WHERE DATE(fechacreacion) = :hoy AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $estadisticas['ventas_hoy'] = $result['cantidad'] ?? 0;
            $estadisticas['total_hoy'] = $result['total'] ?? 0;

            // Cliente que más compró hoy
            $query = "SELECT c.idcliente, CONCAT(c.nombres, ' ', c.apellidopaterno) as nombre_cliente, 
                     COUNT(v.idventa) as compras, SUM(v.totalventa) as total_gastado
                     FROM {$this->tabla} v
                     LEFT JOIN cliente c ON v.idcliente = c.idcliente
                     WHERE DATE(v.fechacreacion) = :hoy AND v.estado = 1
                     GROUP BY v.idcliente
                     ORDER BY total_gastado DESC
                     LIMIT 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
            $stmt->execute();
            $estadisticas['cliente_mas_compro'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Producto más vendido hoy
            $query = "SELECT p.idproducto, p.nombre as producto_nombre, 
                     SUM(dv.cantidad) as cantidad_vendida, 
                     SUM(dv.cantidad * dv.precioventa) as total_vendido
                     FROM {$this->tablaDetalle} dv
                     JOIN {$this->tabla} v ON dv.idventa = v.idventa
                     JOIN producto p ON dv.idproducto = p.idproducto
                     WHERE DATE(v.fechacreacion) = :hoy AND v.estado = 1
                     GROUP BY dv.idproducto
                     ORDER BY cantidad_vendida DESC
                     LIMIT 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
            $stmt->execute();
            $estadisticas['producto_mas_vendido'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Métodos de pago utilizados hoy
            $query = "SELECT metodopago, COUNT(*) as cantidad, SUM(monto) as total
                     FROM {$this->tablaPago} pv
                     JOIN {$this->tabla} v ON pv.idventa = v.idventa
                     WHERE DATE(v.fechacreacion) = :hoy AND v.estado = 1 AND pv.estado = 1
                     GROUP BY metodopago
                     ORDER BY total DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
            $stmt->execute();
            $estadisticas['metodos_pago'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $estadisticas;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [
                'ventas_hoy' => 0,
                'total_hoy' => 0,
                'cliente_mas_compro' => null,
                'producto_mas_vendido' => null,
                'metodos_pago' => []
            ];
        }
    }

    /**
     * Verifica si una venta tiene pagos mixtos
     * 
     * @param int $idVenta ID de la venta
     * @return bool True si tiene pagos mixtos, False en caso contrario
     */
    public function tienePagosMixtos($idVenta)
    {
        try {
            $query = "SELECT COUNT(DISTINCT metodopago) as num_metodos 
                     FROM {$this->tablaPago} 
                     WHERE idventa = :idVenta AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idVenta', $idVenta, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return ($result['num_metodos'] ?? 0) > 1;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
