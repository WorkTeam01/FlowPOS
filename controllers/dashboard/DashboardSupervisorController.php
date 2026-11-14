<?php

/**
 * Controlador de Dashboard para Supervisores
 * 
 * Gestiona la lógica para mostrar y filtrar los datos del dashboard específico para supervisores
 * 
 * @version 1.0
 */

require_once __DIR__ . '/DashboardBase.php';

class DashboardSupervisorController extends DashboardBase
{
    /**
     * ID del supervisor actual
     * @var int
     */
    private $idSupervisor;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        parent::__construct();

        // Obtener el ID del usuario actual (supervisor)
        $currentUser = getCurrentUser();
        $this->idSupervisor = $currentUser['id'];
    }

    /**
     * Obtiene datos específicos para el supervisor
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @param int $limite Límite de elementos en las listas
     * @return array Datos específicos del supervisor
     */
    protected function obtenerDatosRol($fechaInicio, $fechaFin, $limite = 5)
    {
        try {
            // Conectar a la base de datos
            $conexion = $this->getConnection();

            // Resumen de ventas del equipo
            $query = "SELECT 
                    COUNT(*) as totalVentas, 
                    COALESCE(SUM(v.totalventa), 0) as ventasEquipo,
                    COUNT(DISTINCT v.idusuario) as vendedoresActivos,
                    CASE 
                        WHEN COUNT(DISTINCT v.idusuario) > 0 THEN COUNT(DISTINCT v.idventa) / COUNT(DISTINCT v.idusuario) 
                        ELSE 0 
                    END as ventasPorVendedor,
                    CASE 
                        WHEN COUNT(*) > 0 THEN COALESCE(SUM(v.totalventa), 0) / COUNT(*) 
                        ELSE 0 
                    END as ticketPromedio
                 FROM venta v
                 JOIN usuarios u ON v.idusuario = u.idusuario
                 JOIN rol r ON r.idrol = u.idrol
                 WHERE v.fechacreacion >= ? AND v.fechacreacion < DATE_ADD(?, INTERVAL 1 DAY)
                 AND v.estado = 1
                 AND r.nombre = 'vendedor'";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(1, $fechaInicio);
            $stmt->bindParam(2, $fechaFin);
            $stmt->execute();

            $resumenVentas = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si no hay datos, proporcionar valores predeterminados para evitar errores
            if (!$resumenVentas) {
                $resumenVentas = [
                    'totalVentas' => 0,
                    'ventasEquipo' => 0,
                    'vendedoresActivos' => 0,
                    'ventasPorVendedor' => 0,
                    'ticketPromedio' => 0
                ];
            }

            // Meta del equipo (por ahora simulada - idealmente vendría de una tabla de metas)
            $metaVentasEquipo = 50000.00; // Meta mensual
            $cumplimientoMeta = ($resumenVentas['ventasEquipo'] / $metaVentasEquipo) * 100;

            // Rendimiento por vendedor
            $query = "SELECT 
                    u.idusuario as id,
                    CONCAT(u.nombre, ' ', COALESCE(u.apellidopaterno, '')) as nombre,
                    COUNT(v.idventa) as ventas,
                    COALESCE(SUM(v.totalventa), 0) as montoVentas,
                    COUNT(DISTINCT v.idcliente) as clientes
                 FROM usuarios u
                 JOIN rol r ON r.idrol = u.idrol
                 LEFT JOIN venta v ON u.idusuario = v.idusuario 
                    AND v.fechacreacion >= ? AND v.fechacreacion < DATE_ADD(?, INTERVAL 1 DAY)
                    AND v.estado = 1
                 WHERE r.nombre = 'vendedor' AND u.estado = 1
                 GROUP BY u.idusuario, u.nombre, u.apellidopaterno
                 ORDER BY montoVentas DESC
                 LIMIT ?";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(1, $fechaInicio);
            $stmt->bindParam(2, $fechaFin);
            $stmt->bindParam(3, $limite, PDO::PARAM_INT);
            $stmt->execute();

            $rendimientoVendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si no hay vendedores, proporcionar datos simulados para evitar errores
            if (empty($rendimientoVendedores)) {
                $rendimientoVendedores = [
                    [
                        'id' => 0,
                        'nombre' => 'No hay datos',
                        'ventas' => 0,
                        'montoVentas' => 0,
                        'clientes' => 0
                    ]
                ];
            }

            // Tendencia de ventas (comparación con período anterior)
            $diasPeriodo = $this->calcularDiferenciaDias($fechaInicio, $fechaFin);
            $fechaFinAnterior = date('Y-m-d', strtotime($fechaInicio . ' - 1 day'));
            $fechaInicioAnterior = date('Y-m-d', strtotime($fechaInicio . " - {$diasPeriodo} days"));

            // Ventas del período actual por día
            $query = "SELECT 
                    DATE_FORMAT(v.fechacreacion, '%Y-%m-%d') as fecha,
                    COALESCE(SUM(v.totalventa), 0) as venta
                 FROM venta v
                 JOIN usuarios u ON v.idusuario = u.idusuario
                 JOIN rol r ON r.idrol = u.idrol
                 WHERE v.fechacreacion >= ? AND v.fechacreacion < DATE_ADD(?, INTERVAL 1 DAY)
                 AND v.estado = 1
                 AND r.nombre = 'vendedor'
                 GROUP BY DATE_FORMAT(v.fechacreacion, '%Y-%m-%d')
                 ORDER BY fecha ASC";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(1, $fechaInicio);
            $stmt->bindParam(2, $fechaFin);
            $stmt->execute();

            $ventasPeriodoActual = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si no hay datos para el período actual, proporcionar un registro vacío
            if (empty($ventasPeriodoActual)) {
                $ventasPeriodoActual = [
                    [
                        'fecha' => $fechaInicio,
                        'venta' => 0
                    ]
                ];
            }

            // Ventas del período anterior por día
            $query = "SELECT 
                    DATE_FORMAT(v.fechacreacion, '%Y-%m-%d') as fecha,
                    COALESCE(SUM(v.totalventa), 0) as venta
                 FROM venta v
                 JOIN usuarios u ON v.idusuario = u.idusuario
                 JOIN rol r ON r.idrol = u.idrol
                 WHERE v.fechacreacion >= ? AND v.fechacreacion < DATE_ADD(?, INTERVAL 1 DAY)
                 AND v.estado = 1
                 AND r.nombre = 'vendedor'
                 GROUP BY DATE_FORMAT(v.fechacreacion, '%Y-%m-%d')
                 ORDER BY fecha ASC";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(1, $fechaInicioAnterior);
            $stmt->bindParam(2, $fechaFinAnterior);
            $stmt->execute();

            $ventasPeriodoAnterior = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular totales para comparación
            $totalPeriodoActual = array_sum(array_column($ventasPeriodoActual, 'venta'));
            $totalPeriodoAnterior = array_sum(array_column($ventasPeriodoAnterior, 'venta'));

            // Calcular cambio porcentual
            $cambioPorcentual = $totalPeriodoAnterior > 0
                ? (($totalPeriodoActual - $totalPeriodoAnterior) / $totalPeriodoAnterior) * 100
                : ($totalPeriodoActual > 0 ? 100 : 0);

            // Ventas por categoría
            $query = "SELECT 
                    COALESCE(c.nombre, 'Sin Categoría') as categoria,
                    COALESCE(SUM(dv.cantidad * dv.precioventa), 0) as ventas
                 FROM detalleventa dv
                 JOIN venta v ON dv.idventa = v.idventa
                 JOIN producto p ON dv.idproducto = p.idproducto
                 LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                 JOIN usuarios u ON v.idusuario = u.idusuario
                 JOIN rol r ON r.idrol = u.idrol
                 WHERE v.fechacreacion >= ? AND v.fechacreacion < DATE_ADD(?, INTERVAL 1 DAY)
                 AND v.estado = 1
                 AND r.nombre = 'vendedor'
                 GROUP BY c.nombre
                 ORDER BY ventas DESC";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(1, $fechaInicio);
            $stmt->bindParam(2, $fechaFin);
            $stmt->execute();

            $ventasPorCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si no hay categorías, proporcionar datos simulados
            if (empty($ventasPorCategoria)) {
                $ventasPorCategoria = [
                    [
                        'categoria' => 'Sin datos',
                        'ventas' => 0
                    ]
                ];
            }

            // Calcular porcentajes para categorías
            $totalVentasCategorias = array_sum(array_column($ventasPorCategoria, 'ventas'));
            foreach ($ventasPorCategoria as &$categoria) {
                $categoria['porcentaje'] = $totalVentasCategorias > 0
                    ? ($categoria['ventas'] / $totalVentasCategorias) * 100
                    : 0;
                $categoria['porcentaje'] = round($categoria['porcentaje'], 1);
                $categoria['categoria'] = $categoria['categoria'] ?? 'Sin Categoría';
                $categoria['ventas'] = floatval($categoria['ventas']);
            }

            // Alertas de inventario (productos por agotar)
            $productosAgotar = $this->modelo->getProductosPorAgotar($limite);

            // Datos de conversión de clientes (simplificado para evitar el error)
            $conversion = [
                'clientesNuevos' => 0,
                'clientesRecurrentes' => 0,
                'totalClientes' => 0,
                'tasaRetencion' => 0,
                'distribucion' => [
                    [
                        'etiqueta' => 'Nuevos',
                        'valor' => 0,
                        'color' => '#28a745' // verde
                    ],
                    [
                        'etiqueta' => 'Recurrentes',
                        'valor' => 0,
                        'color' => '#17a2b8' // cian
                    ]
                ]
            ];

            // Intentar obtener datos reales de conversión si es posible
            try {
                $query = "SELECT 
                        COUNT(DISTINCT CASE WHEN v2.idventa IS NULL THEN c.idcliente ELSE NULL END) as clientesNuevos,
                        COUNT(DISTINCT CASE WHEN v2.idventa IS NOT NULL THEN c.idcliente ELSE NULL END) as clientesRecurrentes,
                        COUNT(DISTINCT c.idcliente) as totalClientes
                     FROM cliente c
                     JOIN venta v1 ON c.idcliente = v1.idcliente 
                     LEFT JOIN venta v2 ON c.idcliente = v2.idcliente AND v2.fechacreacion < ?
                     WHERE v1.fechacreacion >= ? AND v1.fechacreacion < DATE_ADD(?, INTERVAL 1 DAY)
                     AND v1.estado = 1";

                $stmt = $conexion->prepare($query);
                $stmt->bindParam(1, $fechaInicio);
                $stmt->bindParam(2, $fechaInicio);
                $stmt->bindParam(3, $fechaFin);
                $stmt->execute();

                $datosConversion = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($datosConversion) {
                    // Calcular tasa de retención
                    $tasaRetencion = $datosConversion['totalClientes'] > 0
                        ? ($datosConversion['clientesRecurrentes'] / $datosConversion['totalClientes']) * 100
                        : 0;

                    // Actualizar datos de conversión con valores reales
                    $conversion = [
                        'clientesNuevos' => intval($datosConversion['clientesNuevos']),
                        'clientesRecurrentes' => intval($datosConversion['clientesRecurrentes']),
                        'totalClientes' => intval($datosConversion['totalClientes']),
                        'tasaRetencion' => round($tasaRetencion, 1),
                        'distribucion' => [
                            [
                                'etiqueta' => 'Nuevos',
                                'valor' => intval($datosConversion['clientesNuevos']),
                                'color' => '#28a745' // verde
                            ],
                            [
                                'etiqueta' => 'Recurrentes',
                                'valor' => intval($datosConversion['clientesRecurrentes']),
                                'color' => '#17a2b8' // cian
                            ]
                        ]
                    ];
                }
            } catch (Exception $e) {
                // Si hay error, mantener los datos de conversión simulados
                error_log("Error al obtener datos de conversión: " . $e->getMessage());
            }

            // Construir estructura de datos para respuesta
            $datos = [
                'resumen' => [
                    'ventasEquipo' => floatval($resumenVentas['ventasEquipo']),
                    'cumplimientoMeta' => round($cumplimientoMeta, 1),
                    'vendedoresActivos' => intval($resumenVentas['vendedoresActivos']),
                    'ticketPromedio' => floatval($resumenVentas['ticketPromedio'])
                ],
                'rendimientoVendedores' => $rendimientoVendedores,
                'tendencia' => [
                    'periodoActual' => $ventasPeriodoActual,
                    'periodoAnterior' => $ventasPeriodoAnterior,
                    'totalActual' => $totalPeriodoActual,
                    'totalAnterior' => $totalPeriodoAnterior,
                    'cambioPorcentual' => round($cambioPorcentual, 1)
                ],
                'ventasPorCategoria' => $ventasPorCategoria,
                'productosAgotar' => $productosAgotar,
                'conversion' => $conversion
            ];

            return $datos;
        } catch (PDOException $e) {
            // Registrar error
            error_log('Error en obtenerDatosSupervisor: ' . $e->getMessage());
            error_log('SQL State: ' . $e->getCode());
            error_log('Trace: ' . $e->getTraceAsString());
            return [];
        } catch (Exception $e) {
            error_log('Excepción general en obtenerDatosSupervisor: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Genera datos para un endpoint AJAX que alimentará el dashboard de supervisor
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

        try {
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
            $datos = $resultado['data'];

            // Simplificar y transformar datos para la visualización
            $respuesta = [
                'success' => true,
                'periodo' => $datos['periodo'],
                'resumen' => $datos['resumen'] ?? [
                    'ventasEquipo' => 0,
                    'cumplimientoMeta' => 0,
                    'vendedoresActivos' => 0,
                    'ticketPromedio' => 0
                ],
                'rendimientoVendedores' => $datos['rendimientoVendedores'] ?? [],
                'tendencia' => $datos['tendencia'] ?? [
                    'periodoActual' => [],
                    'periodoAnterior' => [],
                    'totalActual' => 0,
                    'totalAnterior' => 0,
                    'cambioPorcentual' => 0
                ],
                'ventasPorCategoria' => $datos['ventasPorCategoria'] ?? [],
                'productosAgotar' => $datos['productosAgotar'] ?? [],
                'conversion' => $datos['conversion'] ?? [
                    'clientesNuevos' => 0,
                    'clientesRecurrentes' => 0,
                    'totalClientes' => 0,
                    'tasaRetencion' => 0,
                    'distribucion' => [
                        [
                            'etiqueta' => 'Nuevos',
                            'valor' => 0,
                            'color' => '#28a745'
                        ],
                        [
                            'etiqueta' => 'Recurrentes',
                            'valor' => 0,
                            'color' => '#17a2b8'
                        ]
                    ]
                ]
            ];

            return $respuesta;
        } catch (Exception $e) {
            error_log("Error en getAjaxData: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error al procesar datos: " . $e->getMessage()
            ];
        }
    }
}
