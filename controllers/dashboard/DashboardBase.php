<?php

/**
 * Base de los dashboards por rol (vendedor y supervisor).
 *
 * Reúne lo que no cambia entre roles: resolver y validar el período, delegar la
 * consulta en el rol y envolver el resultado. Cada rol aporta solo sus datos
 * en `obtenerDatosRol()`.
 */
abstract class DashboardBase
{
    /**
     * @var Dashboard
     */
    protected $modelo;

    public function __construct()
    {
        require_once __DIR__ . '/../../models/Dashboard.php';
        $this->modelo = new Dashboard();
    }

    /**
     * Datos propios del rol para el rango ya validado.
     *
     * @param string $fechaInicio YYYY-MM-DD
     * @param string $fechaFin YYYY-MM-DD
     * @param int $limite Límite de elementos en las listas
     * @return array
     */
    abstract protected function obtenerDatosRol($fechaInicio, $fechaFin, $limite = 5);

    /**
     * Obtiene los datos del dashboard para el período solicitado
     *
     * @param string $periodo Período predefinido (hoy, semana, mes, anio)
     * @param string|null $fechaInicio Fecha de inicio personalizada
     * @param string|null $fechaFin Fecha de fin personalizada
     * @param int $limite Límite de elementos en las listas
     * @return array
     */
    public function getDatosDashboard($periodo = 'hoy', $fechaInicio = null, $fechaFin = null, $limite = 5)
    {
        if (!$fechaInicio || !$fechaFin) {
            $fechas = $this->modelo->getFechasPeriodo($periodo);
            $fechaInicio = $fechas['fechaInicio'];
            $fechaFin = $fechas['fechaFin'];
        }

        if (!$this->validarFechas($fechaInicio, $fechaFin)) {
            return [
                'success' => false,
                'message' => 'Las fechas proporcionadas no son válidas',
                'icon' => 'error'
            ];
        }

        $datos = $this->obtenerDatosRol($fechaInicio, $fechaFin, $limite);

        if (empty($datos) && $this->modelo->getLastError()) {
            return [
                'success' => false,
                'message' => mensajeErrorSeguro('Error al obtener datos', $this->modelo->getLastError()),
                'icon' => 'error'
            ];
        }

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
     * @return PDO
     */
    protected function getConnection()
    {
        return $this->modelo->getConnection();
    }

    /**
     * Verifica que ambas fechas tengan formato YYYY-MM-DD, existan y estén en orden
     */
    protected function validarFechas($fechaInicio, $fechaFin)
    {
        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)
        ) {
            return false;
        }

        $inicio = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        $fin = DateTime::createFromFormat('Y-m-d', $fechaFin);

        if (!$inicio || !$fin) {
            return false;
        }

        return $inicio <= $fin;
    }

    /**
     * Días del rango, contando ambos extremos
     */
    protected function calcularDiferenciaDias($fechaInicio, $fechaFin)
    {
        $datetime1 = new DateTime($fechaInicio);
        $datetime2 = new DateTime($fechaFin);
        return $datetime1->diff($datetime2)->days + 1;
    }

    /**
     * Descripción legible del período seleccionado
     */
    protected function getDescripcionPeriodo($periodo, $fechaInicio, $fechaFin)
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
}
