<?php

/**
 * Controlador de Dashboard para Supervisores
 * 
 * Gestiona la lógica para mostrar y filtrar los datos del dashboard específico para supervisores
 * 
 * @version 1.0
 */

class DashboardSupervisorController
{
    /**
     * Modelo de Dashboard
     * @var Dashboard
     */
    private $modelo;

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
        // Incluir el modelo de Dashboard
        require_once __DIR__ . '/../../models/Dashboard.php';
        $this->modelo = new Dashboard();

        // Obtener el ID del usuario actual (supervisor)
        $currentUser = getCurrentUser();
        $this->idSupervisor = $currentUser['id'];
    }

    /**
     * Obtiene datos del dashboard para el período solicitado
     * 
     * @param string $periodo Período predefinido (hoy, semana, mes, anio)
     * @param string $fechaInicio Fecha de inicio personalizada (opcional)
     * @param string $fechaFin Fecha de fin personalizada (opcional)
     * @param int $limite Límite de elementos en las listas
     * @return array Datos del dashboard
     */
    public function getDatosDashboard($periodo = 'hoy', $fechaInicio = null, $fechaFin = null, $limite = 5)
    {
        // Si no se proporcionan fechas personalizadas, usar el período predefinido
        if (!$fechaInicio || !$fechaFin) {
            $fechas = $this->modelo->getFechasPeriodo($periodo);
            $fechaInicio = $fechas['fechaInicio'];
            $fechaFin = $fechas['fechaFin'];
        }

        // Validar fechas
        if (!$this->validarFechas($fechaInicio, $fechaFin)) {
            return [
                'success' => false,
                'message' => 'Las fechas proporcionadas no son válidas',
                'icon' => 'error'
            ];
        }

        // Obtener datos específicos para el supervisor
        $datos = $this->obtenerDatosSupervisor($fechaInicio, $fechaFin, $limite);

        // Verificar si hubo algún error en el modelo
        if (empty($datos) && $this->modelo->getLastError()) {
            return [
                'success' => false,
                'message' => 'Error al obtener datos: ' . $this->modelo->getLastError(),
                'icon' => 'error'
            ];
        }

        // Añadir información sobre el período
        $datos['periodo'] = [
            'tipo' => $periodo,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'descripcion' => $this->getDescripcionPeriodo($periodo, $fechaInicio, $fechaFin)
        ];

        return [
            'success' => true,
            'data' => $datos
        ];
    }

    /**
     * Obtiene la conexión a la base de datos del modelo Dashboard
     * 
     * @return PDO Objeto de conexión PDO
     */
    private function getConnection()
    {
        return $this->modelo->getConnection();
    }

    /**
     * Obtiene datos específicos para el supervisor
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @param int $limite Límite de elementos en las listas
     * @return array Datos específicos del supervisor
     */
    private function obtenerDatosSupervisor($fechaInicio, $fechaFin, $limite = 5)
    {
        try {
            // Conectar a la base de datos
            $conexion = $this->getConnection();

            // Debuggear parámetros de fechas
            error_log("Fechas: $fechaInicio a $fechaFin, Límite: $limite");

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
                 WHERE DATE(v.fechacreacion) BETWEEN ? AND ?
                 AND v.estado = 1
                 AND LOWER(u.cargo) = 'vendedor'";

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
                 LEFT JOIN venta v ON u.idusuario = v.idusuario 
                    AND DATE(v.fechacreacion) BETWEEN ? AND ?
                    AND v.estado = 1
                 WHERE LOWER(u.cargo) = 'vendedor' AND u.estado = 1
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
                 WHERE DATE(v.fechacreacion) BETWEEN ? AND ?
                 AND v.estado = 1
                 AND LOWER(u.cargo) = 'vendedor'
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
                 WHERE DATE(v.fechacreacion) BETWEEN ? AND ?
                 AND v.estado = 1
                 AND LOWER(u.cargo) = 'vendedor'
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
                 WHERE DATE(v.fechacreacion) BETWEEN ? AND ?
                 AND v.estado = 1
                 AND LOWER(u.cargo) = 'vendedor'
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
                     LEFT JOIN venta v2 ON c.idcliente = v2.idcliente AND DATE(v2.fechacreacion) < ?
                     WHERE DATE(v1.fechacreacion) BETWEEN ? AND ?
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
     * Verifica si las fechas son válidas
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return bool True si las fechas son válidas
     */
    private function validarFechas($fechaInicio, $fechaFin)
    {
        // Verificar formato de fecha (YYYY-MM-DD)
        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)
        ) {
            return false;
        }

        // Convertir a objetos DateTime
        $inicio = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        $fin = DateTime::createFromFormat('Y-m-d', $fechaFin);

        // Verificar si son fechas válidas
        if (!$inicio || !$fin) {
            return false;
        }

        // Verificar que la fecha de inicio no sea posterior a la fecha de fin
        if ($inicio > $fin) {
            return false;
        }

        return true;
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
     * Genera una descripción legible del período seleccionado
     * 
     * @param string $periodo Tipo de período
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return string Descripción del período
     */
    private function getDescripcionPeriodo($periodo, $fechaInicio, $fechaFin)
    {
        $formatoFecha = function ($fecha) {
            return date('d/m/Y', strtotime($fecha));
        };

        switch ($periodo) {
            case 'hoy':
                return 'Hoy, ' . $formatoFecha($fechaInicio);
            case 'semana':
                return 'Esta semana (' . $formatoFecha($fechaInicio) . ' - ' . $formatoFecha($fechaFin) . ')';
            case 'mes':
                return 'Este mes (' . $formatoFecha($fechaInicio) . ' - ' . $formatoFecha($fechaFin) . ')';
            case 'anio':
                return 'Este año (' . $formatoFecha($fechaInicio) . ' - ' . $formatoFecha($fechaFin) . ')';
            case 'personalizado':
                return 'Período personalizado (' . $formatoFecha($fechaInicio) . ' - ' . $formatoFecha($fechaFin) . ')';
            default:
                return 'Período desconocido';
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
