<?php

/**
 * Modelo Producto
 * 
 * Gestiona las operaciones relacionadas con los productos en la base de datos
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Producto
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de productos en la base de datos
     * @var string
     */
    private $tabla = 'producto';  // Cambiado a 'producto' para coincidir con tu tabla

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
     * Obtiene todos los productos con información de categoría
     * 
     * @return array Lista de productos
     */
    public function getAll()
    {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                      FROM {$this->tabla} p
                      JOIN categoria c ON p.idcategoria = c.idcategoria
                      ORDER BY p.idproducto DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene un producto por su ID
     * 
     * @param int $id ID del producto
     * @return array|bool Datos del producto o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                      FROM {$this->tabla} p
                      JOIN categoria c ON p.idcategoria = c.idcategoria
                      WHERE p.idproducto = :id";
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
     * Crea un nuevo producto
     * 
     * @param array $datos Datos del producto
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} 
                     (idcategoria, codigo, nombre, descripcion, stock, stockminimo, 
                     stockmaximo, precioventa, preciocompra, imagen, estado) 
                     VALUES 
                     (:idcategoria, :codigo, :nombre, :descripcion, :stock, :stockminimo, 
                     :stockmaximo, :precioventa, :preciocompra, :imagen, :estado)";

            $stmt = $this->conexion->prepare($query);

            $stmt->bindParam(':idcategoria', $datos['idcategoria'], PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':precioventa', $datos['precioventa'], PDO::PARAM_STR);
            $stmt->bindParam(':preciocompra', $datos['preciocompra'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            // Manejar campos opcionales
            $this->bindOptionalParam($stmt, ':codigo', $datos['codigo'] ?? null);
            $this->bindOptionalParam($stmt, ':descripcion', $datos['descripcion'] ?? null);
            $this->bindOptionalParam($stmt, ':stock', $datos['stock'] ?? 0, PDO::PARAM_INT);
            $this->bindOptionalParam($stmt, ':stockminimo', $datos['stockminimo'] ?? 0, PDO::PARAM_INT);
            $this->bindOptionalParam($stmt, ':stockmaximo', $datos['stockmaximo'] ?? null, PDO::PARAM_INT);
            $this->bindOptionalParam($stmt, ':imagen', $datos['imagen'] ?? null);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza un producto existente
     * 
     * @param int $id ID del producto
     * @param array $datos Datos del producto
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                     idcategoria = :idcategoria, 
                     codigo = :codigo, 
                     nombre = :nombre, 
                     descripcion = :descripcion, 
                     stock = :stock, 
                     stockminimo = :stockminimo, 
                     stockmaximo = :stockmaximo, 
                     precioventa = :precioventa, 
                     preciocompra = :preciocompra, 
                     imagen = :imagen, 
                     estado = :estado 
                     WHERE idproducto = :id";

            $stmt = $this->conexion->prepare($query);

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':idcategoria', $datos['idcategoria'], PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':precioventa', $datos['precioventa'], PDO::PARAM_STR);
            $stmt->bindParam(':preciocompra', $datos['preciocompra'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            // Manejar campos opcionales
            $this->bindOptionalParam($stmt, ':codigo', $datos['codigo'] ?? null);
            $this->bindOptionalParam($stmt, ':descripcion', $datos['descripcion'] ?? null);
            $this->bindOptionalParam($stmt, ':stock', $datos['stock'] ?? 0, PDO::PARAM_INT);
            $this->bindOptionalParam($stmt, ':stockminimo', $datos['stockminimo'] ?? 0, PDO::PARAM_INT);
            $this->bindOptionalParam($stmt, ':stockmaximo', $datos['stockmaximo'] ?? null, PDO::PARAM_INT);
            $this->bindOptionalParam($stmt, ':imagen', $datos['imagen'] ?? null);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el stock de un producto
     * 
     * @param int $id ID del producto
     * @param int $cantidad Cantidad a sumar (positivo) o restar (negativo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarStock($id, $cantidad)
    {
        try {
            $query = "UPDATE {$this->tabla} SET stock = stock + :cantidad WHERE idproducto = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de un producto
     * 
     * @param int $id ID del producto
     * @param int $estado Nuevo estado (1: activo, 0: inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idproducto = :id";
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
     * Activa un producto
     * 
     * @param int $id ID del producto
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function activar($id)
    {
        return $this->actualizarEstado($id, 1);
    }

    /**
     * Desactiva un producto
     * 
     * @param int $id ID del producto
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function desactivar($id)
    {
        return $this->actualizarEstado($id, 0);
    }

    /**
     * Verifica si existe un producto con el código especificado
     * 
     * @param string $codigo Código del producto
     * @param int $id_excluir ID del producto a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeCodigo($codigo, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE codigo = :codigo AND idproducto != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE codigo = :codigo";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Obtiene productos por categoría
     * 
     * @param int $id_categoria ID de la categoría
     * @return array Lista de productos
     */
    public function getByCategoria($id_categoria)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} 
                     WHERE idcategoria = :id_categoria AND estado = 1
                     ORDER BY nombre";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene productos con stock bajo (por debajo del mínimo)
     * 
     * @return array Lista de productos con stock bajo
     */
    public function getStockBajo()
    {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                      FROM {$this->tabla} p
                      JOIN categoria c ON p.idcategoria = c.idcategoria
                      WHERE p.stock < p.stockminimo AND p.estado = 1
                      ORDER BY p.stock ASC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Valida los datos del producto antes de crear o actualizar
     * 
     * @param array $datos Datos del producto
     * @param int $id_excluir ID del producto a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        // Validar campos obligatorios
        $campos_obligatorios = ['idcategoria', 'nombre', 'precioventa', 'preciocompra'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo {$campo} es obligatorio";
            }
        }

        // Validar que el precio de venta sea mayor o igual al de compra
        if (isset($datos['precioventa']) && isset($datos['preciocompra'])) {
            if ($datos['precioventa'] < $datos['preciocompra']) {
                $errores[] = "El precio de venta no puede ser menor al precio de compra";
            }
        }

        // Validar stock mínimo y máximo
        if (isset($datos['stockminimo']) && isset($datos['stockmaximo']) && $datos['stockmaximo'] !== null) {
            if ($datos['stockminimo'] > $datos['stockmaximo']) {
                $errores[] = "El stock mínimo no puede ser mayor al stock máximo";
            }
        }

        // Validar código único si se proporcionó
        if (!empty($datos['codigo'])) {
            if ($this->existeCodigo($datos['codigo'], $id_excluir)) {
                $errores[] = 'El código del producto ya está registrado';
            }
        }

        return $errores;
    }

    /**
     * Obtiene un producto por su nombre
     * 
     * @param string $nombre Nombre del producto
     * @return array|false Datos del producto o false si no existe
     */
    public function getByNombre($nombre)
    {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                      FROM {$this->tabla} p 
                      LEFT JOIN categoria c ON p.idcategoria = c.idcategoria 
                      WHERE p.nombre = :nombre AND p.estado = 1";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error en getByNombre: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reduce el stock de un producto en 1 unidad
     * 
     * @param int $idproducto ID del producto
     * @param int $cantidad Cantidad a reducir (por defecto 1)
     * @return array|false Resultado de la operación
     */
    public function reducirStock($idproducto, $cantidad = 1)
    {
        try {
            // Primero obtener el producto actual
            $producto = $this->getById($idproducto);

            if (!$producto) {
                $this->lastError = "Producto no encontrado";
                return false;
            }

            $stockActual = (int)$producto['stock'];
            $stockMinimo = (int)($producto['stockminimo'] ?? 0);

            // Verificar que hay stock suficiente
            if ($stockActual < $cantidad) {
                $this->lastError = "Stock insuficiente. Disponible: {$stockActual}, Requerido: {$cantidad}";
                return false;
            }

            // Reducir el stock
            $nuevoStock = $stockActual - $cantidad;

            $query = "UPDATE {$this->tabla} SET 
                      stock = :nuevo_stock,
                      fechaactualizacion = NOW()
                      WHERE idproducto = :idproducto";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nuevo_stock', $nuevoStock, PDO::PARAM_INT);
            $stmt->bindParam(':idproducto', $idproducto, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Verificar si el stock está por debajo del mínimo
                $alertaStockBajo = ($nuevoStock <= $stockMinimo);

                return [
                    'success' => true,
                    'stock_anterior' => $stockActual,
                    'stock_nuevo' => $nuevoStock,
                    'alertaStockBajo' => $alertaStockBajo,
                    'stock_minimo' => $stockMinimo
                ];
            } else {
                $this->lastError = "Error al actualizar el stock";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error en reducirStock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Incrementa el stock de un producto
     * 
     * @param int $idproducto ID del producto
     * @param int $cantidad Cantidad a incrementar
     * @return bool True si se incrementó correctamente, False en caso contrario
     */
    public function incrementarStock($idproducto, $cantidad = 1)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                      stock = stock + :cantidad,
                      fechaactualizacion = NOW()
                      WHERE idproducto = :idproducto";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':idproducto', $idproducto, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error en incrementarStock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un producto tiene stock suficiente
     * 
     * @param int $idproducto ID del producto
     * @param int $cantidadRequerida Cantidad requerida
     * @return array Información sobre el stock
     */
    public function verificarStock($idproducto, $cantidadRequerida = 1)
    {
        try {
            $producto = $this->getById($idproducto);

            if (!$producto) {
                return [
                    'tiene_stock' => false,
                    'mensaje' => 'Producto no encontrado',
                    'stock_actual' => 0
                ];
            }

            $stockActual = (int)$producto['stock'];
            $tieneStock = ($stockActual >= $cantidadRequerida);

            return [
                'tiene_stock' => $tieneStock,
                'stock_actual' => $stockActual,
                'cantidad_requerida' => $cantidadRequerida,
                'stock_restante' => $stockActual - $cantidadRequerida,
                'mensaje' => $tieneStock ? 'Stock suficiente' : "Stock insuficiente. Disponible: {$stockActual}, Requerido: {$cantidadRequerida}"
            ];
        } catch (Exception $e) {
            return [
                'tiene_stock' => false,
                'mensaje' => 'Error al verificar stock: ' . $e->getMessage(),
                'stock_actual' => 0
            ];
        }
    }

    /**
     * Obtiene los últimos movimientos de un producto específico
     * 
     * @param int $idproducto ID del producto
     * @param int $limite Número máximo de movimientos a obtener (por defecto 5)
     * @return array Array con los últimos movimientos del producto (ventas, compras y servicios)
     */
    public function getUltimosMovimientos($idproducto, $limite = 5)
    {
        try {
            // Array para almacenar todos los movimientos
            $movimientos = [];

            // Consulta para obtener las últimas ventas del producto
            $sqlVentas = "SELECT 
                        'venta' as tipo_movimiento,
                        v.idventa as id_movimiento,
                        v.fechaventa as fecha,
                        dv.cantidad,
                        dv.precioventa as precio,
                        (dv.cantidad * dv.precioventa) as total,
                        CONCAT(p.nombre, ' ', COALESCE(p.apellidopaterno, '')) as referencia,
                        u.nombre as usuario
                    FROM detalleventa dv
                    JOIN venta v ON dv.idventa = v.idventa
                    LEFT JOIN persona p ON v.idcliente = p.idpersona
                    LEFT JOIN usuarios u ON v.idusuario = u.idusuario
                    WHERE dv.idproducto = :idproducto
                    ORDER BY v.fechaventa DESC, v.idventa DESC
                    LIMIT :limite";

            $stmtVentas = $this->conexion->prepare($sqlVentas);
            $stmtVentas->bindParam(':idproducto', $idproducto, PDO::PARAM_INT);
            $stmtVentas->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmtVentas->execute();
            $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);

            // Añadir las ventas al array de movimientos
            foreach ($ventas as $venta) {
                $venta['icono'] = 'fa-shopping-cart';
                $venta['color'] = 'success';
                $venta['operacion'] = '-'; // Resta de stock
                $movimientos[] = $venta;
            }

            // Consulta para obtener las últimas compras del producto
            $sqlCompras = "SELECT 
                        'compra' as tipo_movimiento,
                        c.idcompra as id_movimiento,
                        c.fechacompra as fecha,
                        dc.cantidad,
                        dc.preciocompra as precio,
                        (dc.cantidad * dc.preciocompra) as total,
                        u.nombre as referencia,
                        u.nombre as usuario
                    FROM detallecompra dc
                    JOIN compra c ON dc.idcompra = c.idcompra
                    JOIN usuarios u ON c.idusuario = u.idusuario
                    WHERE dc.idproducto = :idproducto
                    ORDER BY c.fechacompra DESC, c.idcompra DESC
                    LIMIT :limite";

            $stmtCompras = $this->conexion->prepare($sqlCompras);
            $stmtCompras->bindParam(':idproducto', $idproducto, PDO::PARAM_INT);
            $stmtCompras->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmtCompras->execute();
            $compras = $stmtCompras->fetchAll(PDO::FETCH_ASSOC);

            // Añadir las compras al array de movimientos
            foreach ($compras as $compra) {
                $compra['icono'] = 'fa-truck';
                $compra['color'] = 'primary';
                $compra['operacion'] = '+'; // Suma de stock
                $movimientos[] = $compra;
            }

            // Para papel higiénico (o cualquier producto consumible en servicios de baño)
            // Verificamos si este producto es papel higiénico según su nombre o categoría
            // Esto es un ejemplo, deberías adaptarlo según cómo identificas el papel higiénico en tu sistema
            $sqlProducto = "SELECT nombre, idcategoria FROM producto WHERE idproducto = :idproducto";
            $stmtProducto = $this->conexion->prepare($sqlProducto);
            $stmtProducto->bindParam(':idproducto', $idproducto, PDO::PARAM_INT);
            $stmtProducto->execute();
            $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);

            // Verificar si el producto es papel higiénico por su nombre o categoría
            // Ajusta esta condición según tu lógica de negocio
            $esPapelHigienico = false;
            if ($producto) {
                $nombreProducto = strtolower($producto['nombre']);
                $esPapelHigienico = (
                    strpos($nombreProducto, 'papel') !== false &&
                    (strpos($nombreProducto, 'rollo') !== false || strpos($nombreProducto, 'baño') !== false)
                );

                // También podrías verificar por categoría
                // Por ejemplo, si tienes una categoría específica para consumibles de baño
                // $esPapelHigienico = $esPapelHigienico || $producto['idcategoria'] == ID_CATEGORIA_CONSUMIBLES_BAÑO;
            }

            // Si es papel higiénico, incluir consumos por servicios de baño
            if ($esPapelHigienico) {
                $sqlServicios = "SELECT 
                            'servicio_baño' as tipo_movimiento,
                            sb.idservicio as id_movimiento,
                            sb.fecha,
                            1 as cantidad, -- Asumiendo que cada servicio usa 1 rollo
                            0 as precio, -- No hay precio directo para este consumo
                            0 as total,
                            CONCAT('Baño: ', b.nombre) as referencia,
                            u.nombre as usuario
                        FROM servicio_bano sb
                        JOIN bano b ON sb.idbano = b.idbano
                        JOIN usuarios u ON sb.idusuario = u.idusuario
                        WHERE sb.estado = 'finalizado'
                        ORDER BY sb.fecha DESC
                        LIMIT :limite";

                $stmtServicios = $this->conexion->prepare($sqlServicios);
                $stmtServicios->bindParam(':limite', $limite, PDO::PARAM_INT);
                $stmtServicios->execute();
                $servicios = $stmtServicios->fetchAll(PDO::FETCH_ASSOC);

                // Añadir los servicios al array de movimientos
                foreach ($servicios as $servicio) {
                    $servicio['icono'] = 'fa-toilet-paper';
                    $servicio['color'] = 'warning';
                    $servicio['operacion'] = '-'; // Resta de stock
                    $movimientos[] = $servicio;
                }
            }

            // Ordenar todos los movimientos por fecha (más recientes primero)
            usort($movimientos, function ($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });

            // Limitar al número especificado
            return array_slice($movimientos, 0, $limite);
        } catch (PDOException $e) {
            error_log('Error al obtener los últimos movimientos del producto: ' . $e->getMessage());
            return [];
        }
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