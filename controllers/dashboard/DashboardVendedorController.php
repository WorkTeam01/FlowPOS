<?php

/**
 * Controlador de Dashboard para Vendedores
 * 
 * Gestiona la lógica para mostrar y filtrar los datos del dashboard específico para vendedores
 * 
 * @version 1.0
 */

class DashboardVendedorController
{
    /**
     * Modelo de Dashboard
     * @var Dashboard
     */
    private $modelo;

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
        // Incluir el modelo de Dashboard
        require_once __DIR__ . '/../../models/Dashboard.php';
        $this->modelo = new Dashboard();

        // Obtener el ID del usuario actual (vendedor)
        $currentUser = getCurrentUser();
        $this->idVendedor = $currentUser['id'];
    }

    /**
     * Obtiene datos del dashboard para el período solicitado
     * 
     * @param string $periodo Período predefinido (hoy, semana, mes)
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

        // Obtener datos específicos del vendedor
        $datos = $this->obtenerDatosVendedor($fechaInicio, $fechaFin, $limite);

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
     * Obtiene datos específicos del vendedor
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @param int $limite Límite de elementos en las listas
     * @return array Datos específicos del vendedor
     */
    private function obtenerDatosVendedor($fechaInicio, $fechaFin, $limite = 5)
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
                     AND DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin
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
                     AND DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin
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
                     AND DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin
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
                     AND DATE(v.fechacreacion) BETWEEN :fechaInicio AND :fechaFin
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
        // Función para formatear fechas consistentemente
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
            case 'personalizado':
                return 'Período personalizado (' . $formatoFecha($fechaInicio) . ' - ' . $formatoFecha($fechaFin) . ')';
            default:
                return 'Período desconocido';
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
