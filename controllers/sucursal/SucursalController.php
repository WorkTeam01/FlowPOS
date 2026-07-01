<?php

/**
 * Controlador de Sucursales
 * 
 * Gestiona las operaciones relacionadas con las sucursales de la empresa
 * 
 * @version 1.0
 */

class SucursalController
{
    /**
     * Modelo de Sucursal
     * @var Sucursal
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Sucursal
        require_once __DIR__ . '/../../models/Sucursal.php';
        $this->modelo = new Sucursal();
    }

    /**
     * Muestra la lista de sucursales
     * 
     * @param bool $soloActivas Si es true, solo muestra sucursales activas
     * @return array Lista de sucursales
     */
    public function index($soloActivas = false)
    {
        $sucursales = $this->modelo->getAll($soloActivas);
        
        // Agregar conteo de usuarios a cada sucursal
        foreach ($sucursales as &$sucursal) {
            $sucursal['total_usuarios'] = $this->modelo->contarUsuarios($sucursal['idsucursal']);
        }
        
        return $sucursales;
    }

    /**
     * Prepara los datos de la sucursal desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosSucursal($post_data)
    {
        return [
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'idempresa' => isset($post_data['idempresa']) ? (int)$post_data['idempresa'] : 0,
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1
        ];
    }

    /**
     * Crea una sucursal vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function crearAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Preparar datos de la sucursal
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosSucursal($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Guardar sucursal usando el modelo
        if ($this->modelo->crear($datos)) {
            return [
                'success' => true,
                'message' => 'Sucursal creada correctamente',
                'sucursal' => [
                    'idsucursal' => $this->modelo->getLastInsertId(),
                    'nombre' => $datos['nombre'],
                    'idempresa' => $datos['idempresa'],
                    'estado' => $datos['estado'],
                    'total_usuarios' => 0 // Nuevas sucursales no tienen usuarios
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Error al crear la sucursal: ' . $this->modelo->getLastError()];
        }
    }

    /**
     * Obtiene una sucursal por su ID
     * 
     * @param int $id ID de la sucursal
     * @return array|bool Datos de la sucursal o false si no existe
     */
    public function getById($id)
    {
        $sucursal = $this->modelo->getById($id);
        if ($sucursal) {
            $sucursal['total_usuarios'] = $this->modelo->contarUsuarios($id);
        }
        return $sucursal;
    }

    /**
     * Actualiza una sucursal vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function actualizarAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Obtener ID de la sucursal
        $id = isset($_POST['idsucursal']) ? (int)$_POST['idsucursal'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de sucursal no válido'];
        }

        // Obtener datos actuales de la sucursal
        $sucursal_actual = $this->modelo->getById($id);
        if (!$sucursal_actual) {
            return ['success' => false, 'message' => 'Sucursal no encontrada para actualizar'];
        }

        // Preparar datos de la sucursal
        $datos = $this->prepararDatosSucursal($_POST);

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo (excluyendo la sucursal actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Actualizar sucursal
        if ($this->modelo->actualizar($id, $datos)) {
            return [
                'success' => true,
                'message' => 'Sucursal actualizada correctamente',
                'sucursal' => [
                    'idsucursal' => $id,
                    'nombre' => $datos['nombre'],
                    'idempresa' => $datos['idempresa'],
                    'estado' => $datos['estado'],
                    'total_usuarios' => $this->modelo->contarUsuarios($id)
                ]
            ];
        } else {
            $error_message = 'Error al actualizar la sucursal: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message];
        }
    }

    /**
     * Cambia el estado de una sucursal vía AJAX
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
            return ['success' => false, 'message' => 'Datos inválidos para cambiar el estado de la sucursal'];
        }

        // Verificar si la sucursal tiene usuarios antes de desactivar
        if ($estado_actual == 1 && $this->modelo->contarUsuarios($id) > 0) {
            return [
                'success' => false,
                'message' => 'No se puede desactivar la sucursal porque tiene usuarios asociados'
            ];
        }

        // El nuevo estado es el opuesto al actual
        $nuevo_estado = $estado_actual == 1 ? 0 : 1;

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $mensaje = $nuevo_estado == 1 ? 'activada' : 'desactivada';
            return [
                'success' => true,
                'message' => "Sucursal $mensaje correctamente",
                'nuevo_estado' => $nuevo_estado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado de la sucursal: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Obtiene las sucursales para usar en selects
     * 
     * @param bool $soloActivas Si es true, solo devuelve sucursales activas
     * @return array Lista de sucursales en formato id => nombre
     */
    public function getParaSelect($soloActivas = true)
    {
        return $this->modelo->getParaSelect($soloActivas);
    }

    /**
     * Obtiene las sucursales por empresa
     * 
     * @param int $idEmpresa ID de la empresa
     * @param bool $soloActivas Si es true, solo devuelve sucursales activas
     * @return array Lista de sucursales
     */
    public function getPorEmpresa($idEmpresa, $soloActivas = true)
    {
        $sucursales = $this->modelo->getPorEmpresa($idEmpresa, $soloActivas);
        
        // Agregar conteo de usuarios a cada sucursal
        foreach ($sucursales as &$sucursal) {
            $sucursal['total_usuarios'] = $this->modelo->contarUsuarios($sucursal['idsucursal']);
        }
        
        return $sucursales;
    }

    /**
     * Obtiene estadísticas de sucursales
     * 
     * @return array Estadísticas de sucursales
     */
    public function getEstadisticas()
    {
        $estadisticas = $this->modelo->getEstadisticas();
        
        // Agregar conteo de usuarios a las estadísticas
        $estadisticas['usuarios_por_sucursal'] = [];
        $sucursales = $this->modelo->getAll();
        
        foreach ($sucursales as $sucursal) {
            $estadisticas['usuarios_por_sucursal'][] = [
                'nombre' => $sucursal['nombre'],
                'total_usuarios' => $this->modelo->contarUsuarios($sucursal['idsucursal'])
            ];
        }
        
        return $estadisticas;
    }

    /**
     * Obtiene el número de usuarios asociados a una sucursal
     * 
     * @param int $idSucursal ID de la sucursal
     * @return int Número de usuarios activos
     */
    public function contarUsuarios($idSucursal)
    {
        return $this->modelo->contarUsuarios($idSucursal);
    }
}