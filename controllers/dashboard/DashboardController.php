<?php

/**
 * Controlador de Dashboard
 * 
 * Gestiona la lógica para mostrar y filtrar los datos del dashboard
 * 
 * @version 1.0
 */

class DashboardController
{
    /**
     * Modelo de Dashboard
     * @var Dashboard
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Dashboard
        require_once __DIR__ . '/../../models/Dashboard.php';
        $this->modelo = new Dashboard();
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
        if ($periodo !== 'personalizado' || !$fechaInicio || !$fechaFin) {
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

        // Obtener datos del dashboard
        $datos = $this->modelo->getDatosDashboard($fechaInicio, $fechaFin, $limite);

        // Verificar si hubo algún error en el modelo
        if (empty($datos['estadisticas']) && $this->modelo->getLastError()) {
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
     * Genera datos para un endpoint AJAX que alimentará el dashboard
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
        $datos = $resultado['data'];

        // Simplificar y transformar datos para la visualización
        $respuesta = [
            'success' => true,
            'periodo' => $datos['periodo'],
            'kpis' => [
                'ventasTotales' => $datos['estadisticas']['ventasTotales'],
                'gananciasNetas' => $datos['estadisticas']['gananciasNetas'],
                'totalTransacciones' => $datos['estadisticas']['totalTransacciones'],
                'ventaPromedio' => $datos['estadisticas']['ventaPromedio']
            ],
            'tendencias' => $datos['estadisticas']['tendencias'],
            'metodosPago' => $datos['metodosPago'],
            'productosMasVendidos' => $datos['productosMasVendidos'],
            'ventasPorCategoria' => $datos['ventasPorCategoria'],
            'productosAgotar' => $datos['productosAgotar'],
            'ultimasVentas' => $datos['ultimasVentas']
        ];

        return $respuesta;
    }
}
