<?php

/**
 * Controlador de Empresas
 * 
 * Gestiona las operaciones relacionadas con las empresas
 * 
 * @version 1.0
 */

class EmpresaController
{
    /**
     * Modelo de Empresa
     * @var Empresa
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Empresa
        require_once __DIR__ . '/../../models/Empresa.php';
        $this->modelo = new Empresa();
    }

    /**
     * Muestra la lista de empresas
     * 
     * @param bool $soloActivas Si es true, solo muestra empresas activas
     * @return array Lista de empresas
     */
    public function index($soloActivas = false)
    {
        $empresas = $this->modelo->getAll($soloActivas);
        
        // Agregar conteo de sucursales a cada empresa
        foreach ($empresas as &$empresa) {
            $empresa['total_sucursales'] = $this->modelo->contarSucursales($empresa['idempresa']);
        }
        
        return $empresas;
    }

    /**
     * Prepara los datos de la empresa desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosEmpresa($post_data)
    {
        return [
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'nit' => isset($post_data['nit']) ? trim($post_data['nit']) : '',
            'direccion' => isset($post_data['direccion']) ? trim($post_data['direccion']) : '',
            'telefono' => isset($post_data['telefono']) ? trim($post_data['telefono']) : '',
            'email' => isset($post_data['email']) ? trim($post_data['email']) : '',
            'imagen' => isset($post_data['imagen']) ? trim($post_data['imagen']) : '',
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1
        ];
    }

    /**
     * Crea una empresa vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function crearAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Preparar datos de la empresa
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosEmpresa($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Guardar empresa usando el modelo
        if ($this->modelo->crear($datos)) {
            return [
                'success' => true,
                'message' => 'Empresa creada correctamente',
                'empresa' => [
                    'idempresa' => $this->modelo->getLastInsertId(),
                    'nombre' => $datos['nombre'],
                    'nit' => $datos['nit'],
                    'direccion' => $datos['direccion'],
                    'telefono' => $datos['telefono'],
                    'email' => $datos['email'],
                    'imagen' => $datos['imagen'],
                    'estado' => $datos['estado'],
                    'total_sucursales' => 0 // Nueva empresa no tiene sucursales
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Error al crear la empresa: ' . $this->modelo->getLastError()];
        }
    }

    /**
     * Obtiene una empresa por su ID
     * 
     * @param int $id ID de la empresa
     * @return array|bool Datos de la empresa o false si no existe
     */
    public function getById($id)
    {
        $empresa = $this->modelo->getById($id);
        if ($empresa) {
            $empresa['total_sucursales'] = $this->modelo->contarSucursales($id);
        }
        return $empresa;
    }

    /**
     * Actualiza una empresa vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function actualizarAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Obtener ID de la empresa
        $id = isset($_POST['idempresa']) ? (int)$_POST['idempresa'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de empresa no válido'];
        }

        // Obtener datos actuales de la empresa
        $empresa_actual = $this->modelo->getById($id);
        if (!$empresa_actual) {
            return ['success' => false, 'message' => 'Empresa no encontrada para actualizar'];
        }

        // Preparar datos de la empresa
        $datos = $this->prepararDatosEmpresa($_POST);

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo (excluyendo la empresa actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Actualizar empresa
        if ($this->modelo->actualizar($id, $datos)) {
            return [
                'success' => true,
                'message' => 'Empresa actualizada correctamente',
                'empresa' => [
                    'idempresa' => $id,
                    'nombre' => $datos['nombre'],
                    'nit' => $datos['nit'],
                    'direccion' => $datos['direccion'],
                    'telefono' => $datos['telefono'],
                    'email' => $datos['email'],
                    'imagen' => $datos['imagen'],
                    'estado' => $datos['estado'],
                    'total_sucursales' => $this->modelo->contarSucursales($id)
                ]
            ];
        } else {
            $error_message = 'Error al actualizar la empresa: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message];
        }
    }

    /**
     * Cambia el estado de una empresa vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function cambiarEstadoAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $estado_actual = isset($_POST['estado_actual']) ? (int)$_POST['estado_actual'] : null;

        if (!$id || $estado_actual === null) {
            return ['success' => false, 'message' => 'Datos inválidos para cambiar el estado de la empresa'];
        }

        // Verificar si la empresa tiene sucursales antes de desactivar
        if ($estado_actual == 1 && $this->modelo->contarSucursales($id) > 0) {
            return [
                'success' => false,
                'message' => 'No se puede desactivar la empresa porque tiene sucursales asociadas'
            ];
        }

        // El nuevo estado es el opuesto al actual
        $nuevo_estado = $estado_actual == 1 ? 0 : 1;

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $mensaje = $nuevo_estado == 1 ? 'activada' : 'desactivada';
            return [
                'success' => true,
                'message' => "Empresa $mensaje correctamente",
                'nuevo_estado' => $nuevo_estado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado de la empresa: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Obtiene las empresas para usar en selects
     * 
     * @param bool $soloActivas Si es true, solo devuelve empresas activas
     * @return array Lista de empresas en formato id => nombre
     */
    public function getParaSelect($soloActivas = true)
    {
        return $this->modelo->getParaSelect($soloActivas);
    }

    /**
     * Obtiene estadísticas de empresas
     * 
     * @return array Estadísticas de empresas
     */
    public function getEstadisticas()
    {
        $estadisticas = $this->modelo->getEstadisticas();
        
        // Agregar conteo de sucursales a las estadísticas
        $estadisticas['sucursales_por_empresa'] = [];
        $empresas = $this->modelo->getAll();
        
        foreach ($empresas as $empresa) {
            $estadisticas['sucursales_por_empresa'][] = [
                'nombre' => $empresa['nombre'],
                'total_sucursales' => $this->modelo->contarSucursales($empresa['idempresa'])
            ];
        }
        
        return $estadisticas;
    }

    /**
     * Obtiene el número de sucursales asociadas a una empresa
     * 
     * @param int $idEmpresa ID de la empresa
     * @return int Número de sucursales activas
     */
    public function contarSucursales($idEmpresa)
    {
        return $this->modelo->contarSucursales($idEmpresa);
    }
}