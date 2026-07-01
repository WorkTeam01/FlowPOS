<?php

/**
 * Modelo Dashboard
 * 
 * Gestiona las operaciones para obtener datos y estadísticas para el dashboard
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Dashboard
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

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
     * Método para obtener el objeto de conexión PDO
     * 
     * Este método debe ser añadido a la clase Dashboard en el archivo Dashboard.php
     * 
     * @return PDO Objeto de conexión PDO
     */
    public function getConnection()
    {
        return $this->conexion;
    }

    /**
     * Obtiene estadísticas generales para el período especificado
     * 
     * @param string $fechaInicio Fecha de inicio (formato YYYY-MM-DD)
     * @param string $fechaFin Fecha de fin (formato YYYY-MM-DD)
     * @return array Estadísticas generales
     */
    public function getEstadisticasGenerales($fechaInicio, $fechaFin)
    {
        try {
            $estadisticas = [
                'ventasTotales' => 0,
                'gananciasNetas' => 0,
                'totalTransacciones' => 0,
                'ventaPromedio' => 0,
                'tendencias' => [
                    'ventas' => 0,
                    'ganancias' => 0,
                    'transacciones' => 0,
                    'ventaPromedio' => 0
                ]
            ];

            // Ventas totales y número de transacciones
            // Ahora usamos SUM(pv.monto) en lugar de SUM(v.totalventa) para considerar los pagos efectivos
            $query = "SELECT COUNT(*) as totalTransacciones, COALESCE(SUM(pv.monto), 0) as ventasTotales 
                 FROM venta v 
                 LEFT JOIN pagoventa pv ON v.idventa = pv.idventa AND pv.estado = 1
                 WHERE DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin 
                 AND v.estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $estadisticas['ventasTotales'] = floatval($result['ventasTotales']);
            $estadisticas['totalTransacciones'] = intval($result['totalTransacciones']);

            // Calcular venta promedio
            $estadisticas['ventaPromedio'] = $estadisticas['totalTransacciones'] > 0
                ? $estadisticas['ventasTotales'] / $estadisticas['totalTransacciones']
                : 0;

            // Calcular ganancias netas (ventas - costo de productos vendidos)
            // Ahora consideramos el descuento en el cálculo
            $query = "SELECT COALESCE(SUM((dv.precioventa - dv.descuento - p.preciocompra) * dv.cantidad), 0) as gananciasNetas 
                 FROM detalleventa dv 
                 JOIN venta v ON dv.idventa = v.idventa 
                 JOIN producto p ON dv.idproducto = p.idproducto 
                 WHERE DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin 
                 AND v.estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $estadisticas['gananciasNetas'] = floatval($result['gananciasNetas']);

            // Calcular tendencias (comparando con el periodo anterior de igual duración)
            $diasPeriodo = $this->calcularDiferenciaDias($fechaInicio, $fechaFin);
            $fechaFinAnterior = date('Y-m-d', strtotime($fechaInicio . ' - 1 day'));
            $fechaInicioAnterior = date('Y-m-d', strtotime($fechaInicio . " - {$diasPeriodo} days"));

            $estadisticas['tendencias'] = $this->calcularTendencias(
                $fechaInicio,
                $fechaFin,
                $fechaInicioAnterior,
                $fechaFinAnterior
            );

            return $estadisticas;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [
                'ventasTotales' => 0,
                'gananciasNetas' => 0,
                'totalTransacciones' => 0,
                'ventaPromedio' => 0,
                'tendencias' => [
                    'ventas' => 0,
                    'ganancias' => 0,
                    'transacciones' => 0,
                    'ventaPromedio' => 0
                ]
            ];
        }
    }

    /**
     * Calcula la diferencia en días entre dos fechas
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return int Diferencia en días
     */
    private function calcularDiferenciaDias($fechaInicio, $fechaFin)
    {
        $datetime1 = new DateTime($fechaInicio);
        $datetime2 = new DateTime($fechaFin);
        $interval = $datetime1->diff($datetime2);
        return $interval->days + 1; // Incluimos el día final
    }

    /**
     * Calcula las tendencias comparando el período actual con el período anterior
     * 
     * @param string $fechaInicio Fecha de inicio del período actual
     * @param string $fechaFin Fecha de fin del período actual
     * @param string $fechaInicioAnterior Fecha de inicio del período anterior
     * @param string $fechaFinAnterior Fecha de fin del período anterior
     * @return array Tendencias calculadas
     */
    private function calcularTendencias($fechaInicio, $fechaFin, $fechaInicioAnterior, $fechaFinAnterior)
    {
        try {
            // Obtener datos del período actual
            $actual = $this->getDatosPeriodo($fechaInicio, $fechaFin);

            // Obtener datos del período anterior
            $anterior = $this->getDatosPeriodo($fechaInicioAnterior, $fechaFinAnterior);

            // Calcular tendencias (porcentaje de cambio)
            $tendencias = [
                'ventas' => $this->calcularPorcentajeCambio($anterior['ventas'], $actual['ventas']),
                'ganancias' => $this->calcularPorcentajeCambio($anterior['ganancias'], $actual['ganancias']),
                'transacciones' => $this->calcularPorcentajeCambio($anterior['transacciones'], $actual['transacciones']),
                'ventaPromedio' => $this->calcularPorcentajeCambio($anterior['ventaPromedio'], $actual['ventaPromedio'])
            ];

            return $tendencias;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return [
                'ventas' => 0,
                'ganancias' => 0,
                'transacciones' => 0,
                'ventaPromedio' => 0
            ];
        }
    }

    /**
     * Obtiene datos de ventas para un período específico
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return array Datos del período
     */
    private function getDatosPeriodo($fechaInicio, $fechaFin)
    {
        // Ventas totales y transacciones
        // Consideramos pagos efectivos en lugar del total de venta
        $query = "SELECT COUNT(*) as transacciones, COALESCE(SUM(pv.monto), 0) as ventas 
             FROM venta v 
             LEFT JOIN pagoventa pv ON v.idventa = pv.idventa AND pv.estado = 1
             WHERE DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin 
             AND v.estado = 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $ventas = floatval($result['ventas']);
        $transacciones = intval($result['transacciones']);
        $ventaPromedio = $transacciones > 0 ? $ventas / $transacciones : 0;

        // Ganancias considerando descuentos
        $query = "SELECT COALESCE(SUM((dv.precioventa - dv.descuento - p.preciocompra) * dv.cantidad), 0) as ganancias 
             FROM detalleventa dv 
             JOIN venta v ON dv.idventa = v.idventa 
             JOIN producto p ON dv.idproducto = p.idproducto 
             WHERE DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin 
             AND v.estado = 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $ganancias = floatval($result['ganancias']);

        return [
            'ventas' => $ventas,
            'ganancias' => $ganancias,
            'transacciones' => $transacciones,
            'ventaPromedio' => $ventaPromedio
        ];
    }

    /**
     * Calcula el porcentaje de cambio entre dos valores
     * 
     * @param float $valorAnterior Valor anterior
     * @param float $valorActual Valor actual
     * @return float Porcentaje de cambio
     */
    private function calcularPorcentajeCambio($valorAnterior, $valorActual)
    {
        if ($valorAnterior == 0) {
            return $valorActual > 0 ? 100 : 0;
        }

        $cambio = (($valorActual - $valorAnterior) / $valorAnterior) * 100;
        return round($cambio, 1);
    }

    /**
     * Obtiene los métodos de pago utilizados en el período
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return array Métodos de pago con montos y porcentajes
     */
    public function getMetodosPago($fechaInicio, $fechaFin)
    {
        try {
            $query = "SELECT pv.metodopago, 
                     COUNT(*) as cantidad, 
                     COALESCE(SUM(pv.monto), 0) as monto 
                     FROM pagoventa pv 
                     JOIN venta v ON pv.idventa = v.idventa 
                     WHERE DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin 
                     AND v.estado = 1 AND pv.estado = 1 
                     GROUP BY pv.metodopago 
                     ORDER BY monto DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            $metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular total para porcentajes
            $total = 0;
            foreach ($metodos as $metodo) {
                $total += floatval($metodo['monto']);
            }

            // Calcular porcentajes
            $result = [];
            foreach ($metodos as $metodo) {
                $porcentaje = $total > 0 ? (floatval($metodo['monto']) / $total) * 100 : 0;

                $result[] = [
                    'metodo' => ucfirst($metodo['metodopago']),
                    'cantidad' => intval($metodo['cantidad']),
                    'monto' => floatval($metodo['monto']),
                    'porcentaje' => round($porcentaje, 1)
                ];
            }

            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene los productos más vendidos en el período
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @param int $limite Número máximo de productos a retornar
     * @return array Productos más vendidos
     */
    public function getProductosMasVendidos($fechaInicio, $fechaFin, $limite = 5)
    {
        try {
            $query = "SELECT p.idproducto, p.nombre as producto, c.nombre as categoria, 
                 p.precioventa as precio, SUM(dv.cantidad) as unidades, 
                 SUM(dv.cantidad * (dv.precioventa - dv.descuento)) as total,
                 SUM(dv.cantidad * dv.descuento) as descuento_total
                 FROM detalleventa dv 
                 JOIN venta v ON dv.idventa = v.idventa 
                 JOIN producto p ON dv.idproducto = p.idproducto 
                 LEFT JOIN categoria c ON p.idcategoria = c.idcategoria 
                 WHERE DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin 
                 AND v.estado = 1 
                 GROUP BY p.idproducto, p.nombre, c.nombre, p.precioventa
                 ORDER BY unidades DESC 
                 LIMIT :limite";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            $productos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $productos[] = [
                    'idproducto' => $row['idproducto'],
                    'producto' => $row['producto'],
                    'categoria' => $row['categoria'] ?? 'Sin Categoría',
                    'precio' => floatval($row['precio']),
                    'unidades' => intval($row['unidades']),
                    'descuento_total' => floatval($row['descuento_total']),
                    'total' => floatval($row['total'])
                ];
            }

            return $productos;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene las ventas por categoría en el período
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return array Ventas por categoría
     */
    public function getVentasPorCategoria($fechaInicio, $fechaFin)
    {
        try {
            // Actualizado para considerar descuentos
            $query = "SELECT c.nombre as categoria, 
                 COALESCE(SUM(dv.cantidad * (dv.precioventa - dv.descuento)), 0) as ventas 
                 FROM detalleventa dv 
                 JOIN venta v ON dv.idventa = v.idventa 
                 JOIN producto p ON dv.idproducto = p.idproducto 
                 LEFT JOIN categoria c ON p.idcategoria = c.idcategoria 
                 WHERE DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin 
                 AND v.estado = 1 
                 GROUP BY c.nombre 
                 ORDER BY ventas DESC";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();

            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular total para porcentajes
            $total = 0;
            foreach ($categorias as $categoria) {
                $total += floatval($categoria['ventas']);
            }

            // Calcular porcentajes
            $result = [];
            foreach ($categorias as $categoria) {
                $nombreCategoria = $categoria['categoria'] ?? 'Sin Categoría';
                $ventasCategoria = floatval($categoria['ventas']);
                $porcentaje = $total > 0 ? ($ventasCategoria / $total) * 100 : 0;

                $result[] = [
                    'categoria' => $nombreCategoria,
                    'ventas' => $ventasCategoria,
                    'porcentaje' => round($porcentaje, 1)
                ];
            }

            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene los productos con stock bajo
     * 
     * @param int $limite Número máximo de productos a retornar
     * @return array Productos con stock bajo
     */
    public function getProductosPorAgotar($limite = 5)
    {
        try {
            $query = "SELECT p.nombre as producto, c.nombre as categoria, 
                     p.stock as stockActual, p.stockminimo,
                     CASE 
                        WHEN p.stock <= p.stockminimo * 0.5 THEN 'crítico'
                        WHEN p.stock <= p.stockminimo THEN 'bajo'
                        ELSE 'normal'
                     END as estado 
                     FROM producto p 
                     LEFT JOIN categoria c ON p.idcategoria = c.idcategoria 
                     WHERE p.stock <= p.stockminimo AND p.estado = 1 
                     ORDER BY 
                        CASE 
                            WHEN p.stock <= p.stockminimo * 0.5 THEN 1
                            WHEN p.stock <= p.stockminimo THEN 2
                            ELSE 3
                        END,
                        p.stock ASC 
                     LIMIT :limite";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            $productos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $productos[] = [
                    'producto' => $row['producto'],
                    'categoria' => $row['categoria'] ?? 'Sin Categoría',
                    'stockActual' => intval($row['stockActual']),
                    'stockMinimo' => intval($row['stockminimo']),
                    'estado' => $row['estado']
                ];
            }

            return $productos;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene las últimas ventas realizadas
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @param int $limite Número máximo de ventas a retornar
     * @return array Últimas ventas
     */
    public function getUltimasVentas($fechaInicio, $fechaFin, $limite = 5)
    {
        try {
            $query = "SELECT v.idventa as id, 
                 CONCAT(c.nombres, ' ', c.apellidopaterno) as cliente, 
                 v.fechacreacion as fecha, 
                 (SELECT COUNT(*) FROM detalleventa WHERE idventa = v.idventa) as productos,
                 (SELECT GROUP_CONCAT(DISTINCT metodopago SEPARATOR ', ') 
                  FROM pagoventa 
                  WHERE idventa = v.idventa AND estado = 1) as metodo,
                 v.totalventa as total,
                 (SELECT COALESCE(SUM((dv.precioventa - dv.descuento - p.preciocompra) * dv.cantidad), 0)
                  FROM detalleventa dv
                  JOIN producto p ON dv.idproducto = p.idproducto
                  WHERE dv.idventa = v.idventa) as ganancia
                 FROM venta v
                 LEFT JOIN cliente c ON v.idcliente = c.idcliente
                 WHERE DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin
                 AND v.estado = 1
                 ORDER BY v.fechacreacion DESC
                 LIMIT :limite";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            $ventas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ventas[] = [
                    'id' => $row['id'],
                    'cliente' => $row['cliente'] ?? 'Cliente No Registrado',
                    'fecha' => date('d/m/Y H:i', strtotime($row['fecha'])),
                    'productos' => intval($row['productos']),
                    'metodo' => ucfirst($row['metodo'] ?? 'Múltiple'),
                    'total' => floatval($row['total']),
                    'ganancia' => floatval($row['ganancia'])
                ];
            }

            return $ventas;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene datos completos del dashboard para un período específico
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @param int $limite Límite de elementos en las listas
     * @return array Datos completos del dashboard
     */
    public function getDatosDashboard($fechaInicio, $fechaFin, $limite = 5)
    {
        return [
            'estadisticas' => $this->getEstadisticasGenerales($fechaInicio, $fechaFin),
            'metodosPago' => $this->getMetodosPago($fechaInicio, $fechaFin),
            'productosMasVendidos' => $this->getProductosMasVendidos($fechaInicio, $fechaFin, $limite),
            'ventasPorCategoria' => $this->getVentasPorCategoria($fechaInicio, $fechaFin),
            'productosAgotar' => $this->getProductosPorAgotar($limite),
            'ultimasVentas' => $this->getUltimasVentas($fechaInicio, $fechaFin, $limite)
        ];
    }

    /**
     * Obtiene fechas predefinidas para diferentes períodos
     * 
     * @param string $periodo Período solicitado (hoy, semana, mes, anio, personalizado)
     * @return array Fechas de inicio y fin
     */
    public function getFechasPeriodo($periodo)
    {
        // Asegurarse de obtener la fecha actual correcta del servidor
        $hoy = date('Y-m-d');
        $resultado = ['fechaInicio' => $hoy, 'fechaFin' => $hoy];

        switch ($periodo) {
            case 'hoy':
                // Ya está configurado por defecto
                break;

            case 'semana':
                // Usar una lógica más clara para obtener el inicio de la semana actual
                $inicioSemana = date('Y-m-d', strtotime('monday this week'));
                // Si hoy es domingo, ajustar para que muestre la semana correcta
                if (date('w') == 0) {
                    $inicioSemana = date('Y-m-d', strtotime('monday last week'));
                }
                $resultado = ['fechaInicio' => $inicioSemana, 'fechaFin' => $hoy];
                break;

            case 'mes':
                $inicioMes = date('Y-m-01');
                $resultado = ['fechaInicio' => $inicioMes, 'fechaFin' => $hoy];
                break;

            case 'anio':
                $inicioAnio = date('Y-01-01');
                $resultado = ['fechaInicio' => $inicioAnio, 'fechaFin' => $hoy];
                break;

            case 'personalizado':
                // Este caso se manejará con fechas proporcionadas por el usuario
                break;
        }

        return $resultado;
    }
}
