<?php

/**
 * Controlador de Dashboard para Vendedores
 * 
 * Gestiona la lógica para mostrar y filtrar los datos del dashboard específico para vendedores
 * 
 * @version 1.0
 */

require_once __DIR__ . '/DashboardBase.php';

class DashboardVendedorController extends DashboardBase
{
    /**
     * ID del vendedor actual
     * @var int
     */
    private $idVendedor;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        parent::__construct();

        // Obtener el ID del usuario actual (vendedor)
        $currentUser = getCurrentUser();
        $this->idVendedor = $currentUser['id'];
    }

    /**
     * Obtiene datos específicos del vendedor
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @param int $limite Límite de elementos en las listas
     * @return array Datos específicos del vendedor
     */
    protected function obtenerDatosRol($fechaInicio, $fechaFin, $limite = 5)
    {
        try {
            // Conectar a la base de datos
            $conexion = $this->getConnection();

            // Ventas totales del vendedor
            $query = "SELECT 
                        COUNT(*) as totalVentas, 
                        COALESCE(SUM(v.totalventa), 0) as ventasTotales,
                        COUNT(DISTINCT v.idcliente) as clientesAtendidos
                     FROM venta v
                     WHERE v.idusuario = :idVendedor
                     AND v.fechacreacion >= :fechaInicio AND v.fechacreacion < DATE_ADD(:fechaFin, INTERVAL 1 DAY)
                     AND v.estado = 1";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':idVendedor', $this->idVendedor, PDO::PARAM_INT);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();

            $resumenVentas = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calcular comisión estimada (5% de las ventas)
            $comisionEstimada = floatval($resumenVentas['ventasTotales']) * 0.05;

            // Mis productos más vendidos
            $query = "SELECT 
                        p.nombre as producto,
                        c.nombre as categoria,
                        p.precioventa as precio,
                        SUM(dv.cantidad) as unidades,
                        SUM(dv.cantidad * dv.precioventa) as total
                     FROM detalleventa dv
                     JOIN venta v ON dv.idventa = v.idventa
                     JOIN producto p ON dv.idproducto = p.idproducto
                     LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                     WHERE v.idusuario = :idVendedor
                     AND v.fechacreacion >= :fechaInicio AND v.fechacreacion < DATE_ADD(:fechaFin, INTERVAL 1 DAY)
                     AND v.estado = 1
                     GROUP BY p.idproducto, p.nombre, c.nombre, p.precioventa
                     ORDER BY unidades DESC
                     LIMIT :limite";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':idVendedor', $this->idVendedor, PDO::PARAM_INT);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            $productosVendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mis últimas ventas
            $query = "SELECT 
                        v.idventa as id,
                        CONCAT(c.nombres, ' ', c.apellidopaterno) as cliente,
                        v.fechacreacion as fecha,
                        (SELECT COUNT(*) FROM detalleventa WHERE idventa = v.idventa) as productos,
                        (SELECT GROUP_CONCAT(DISTINCT metodopago SEPARATOR ', ') 
                         FROM pagoventa 
                         WHERE idventa = v.idventa AND estado = 1) as metodo,
                        v.totalventa as total
                     FROM venta v
                     LEFT JOIN cliente c ON v.idcliente = c.idcliente
                     WHERE v.idusuario = :idVendedor
                     AND v.fechacreacion >= :fechaInicio AND v.fechacreacion < DATE_ADD(:fechaFin, INTERVAL 1 DAY)
                     AND v.estado = 1
                     ORDER BY v.fechacreacion DESC
                     LIMIT :limite";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':idVendedor', $this->idVendedor, PDO::PARAM_INT);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            $ultimasVentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($ultimasVentas as &$venta) {
                $venta['fecha'] = date('d/m/Y H:i', strtotime($venta['fecha']));
                $venta['metodo'] = ucfirst($venta['metodo'] ?? 'Múltiple');
                $venta['total'] = floatval($venta['total']);
                $venta['productos'] = intval($venta['productos']);
                $venta['cliente'] = $venta['cliente'] ?? 'Cliente No Registrado';
            }

            // Datos de rendimiento para el gráfico
            $diasPeriodo = min(30, $this->calcularDiferenciaDias($fechaInicio, $fechaFin));

            // Si el período es mayor a 30 días, dividir en tramos semanales
            $intervalo = $diasPeriodo > 30 ? 'WEEK' : 'DAY';

            $query = "SELECT 
                        DATE_FORMAT(v.fechacreacion, '%Y-%m-%d') as fecha,
                        COALESCE(SUM(v.totalventa), 0) as venta
                     FROM venta v
                     WHERE v.idusuario = :idVendedor
                     AND v.fechacreacion >= :fechaInicio AND v.fechacreacion < DATE_ADD(:fechaFin, INTERVAL 1 DAY)
                     AND v.estado = 1
                     GROUP BY DATE_FORMAT(v.fechacreacion, '%Y-%m-%d')
                     ORDER BY fecha ASC";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':idVendedor', $this->idVendedor, PDO::PARAM_INT);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();

            $datosRendimiento = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Productos por agotar (usar la misma función del dashboard principal)
            $productosAgotar = $this->modelo->getProductosPorAgotar($limite);

            // Metas del vendedor (datos simulados o de una tabla de metas)
            $metas = [
                'ventas' => [
                    'meta' => 10000.00,
                    'actual' => floatval($resumenVentas['ventasTotales']),
                    'porcentaje' => min(100, round((floatval($resumenVentas['ventasTotales']) / 10000.00) * 100, 1))
                ],
                'clientes' => [
                    'meta' => 20,
                    'actual' => intval($resumenVentas['clientesAtendidos']),
                    'porcentaje' => min(100, round((intval($resumenVentas['clientesAtendidos']) / 20) * 100, 1))
                ],
                'productos' => [
                    'meta' => 50,
                    'actual' => array_sum(array_column($productosVendidos, 'unidades')),
                    'porcentaje' => min(100, round((array_sum(array_column($productosVendidos, 'unidades')) / 50) * 100, 1))
                ]
            ];

            // Construir estructura de datos para respuesta
            $datos = [
                'resumen' => [
                    'ventasTotales' => floatval($resumenVentas['ventasTotales']),
                    'comisionEstimada' => $comisionEstimada,
                    'totalVentas' => intval($resumenVentas['totalVentas']),
                    'clientesAtendidos' => intval($resumenVentas['clientesAtendidos'])
                ],
                'productosVendidos' => $productosVendidos,
                'ultimasVentas' => $ultimasVentas,
                'datosRendimiento' => $datosRendimiento,
                'productosAgotar' => $productosAgotar,
                'metas' => $metas
            ];

            return $datos;
        } catch (PDOException $e) {
            // Registrar error
            error_log('Error en obtenerDatosVendedor: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Genera datos para un endpoint AJAX que alimentará el dashboard de vendedor
     * 
     * @param array $params Parámetros de la solicitud
     * @return array Datos en formato JSON para el cliente
     */
    public function getAjaxData($params)
    {
        // Extraer parámetros
        $periodo = isset($params['periodo']) ? $params['periodo'] : 'hoy';
        $fechaInicio = isset($params['fecha_inicio']) ? $params['fecha_inicio'] : null;
        $fechaFin = isset($params['fecha_fin']) ? $params['fecha_fin'] : null;
        $limite = isset($params['limite']) ? intval($params['limite']) : 5;

        // Obtener datos
        $resultado = $this->getDatosDashboard($periodo, $fechaInicio, $fechaFin, $limite);

        // Si hay error, devolver mensaje
        if (!$resultado['success']) {
            return [
                'success' => false,
                'message' => $resultado['message']
            ];
        }

        // Convertir datos a formato adecuado para el frontend
        // Si hay datos de fechas en ultimasVentas, asegurarse de formatearlas correctamente
        if (isset($datos['ultimasVentas']) && is_array($datos['ultimasVentas'])) {
            foreach ($datos['ultimasVentas'] as $venta) {
                // La fecha ya debe estar formateada en el controlador
                if (isset($venta['fechacreacion']) && strpos($venta['fechacreacion'], '/') === false) {
                    // Si la fecha no está ya formateada con /, la formateamos
                    $fecha = new DateTime($venta['fechacreacion']);
                    $venta['fechacreacion'] = $fecha->format('d/m/Y H:i');
                }
            }
        }

        // Asegurarse de que los datos de rendimiento (gráfico) tengan fechas correctas
        if (isset($datos['datosRendimiento']) && is_array($datos['datosRendimiento'])) {
            foreach ($datos['datosRendimiento'] as &$item) {
                if (isset($item['fecha'])) {
                    // No es necesario formatear aquí, lo haremos en el frontend
                    // para mantener el formato original que es necesario para ordenar
                    // correctamente los datos
                }
            }
        }

        $datos = $resultado['data'];

        // Simplificar y transformar datos para la visualización
        $respuesta = [
            'success' => true,
            'periodo' => $datos['periodo'],
            'resumen' => $datos['resumen'],
            'productosVendidos' => $datos['productosVendidos'],
            'ultimasVentas' => $datos['ultimasVentas'],
            'datosRendimiento' => $datos['datosRendimiento'],
            'productosAgotar' => $datos['productosAgotar'],
            'metas' => $datos['metas']
        ];

        return $respuesta;
    }
}
