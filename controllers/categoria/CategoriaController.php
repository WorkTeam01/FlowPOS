<?php

/**
 * Controlador de Categorías
 * 
 * Gestiona las operaciones relacionadas con las categorías de productos
 * 
 * @version 1.0
 */

class CategoriaController
{
    /**
     * Modelo de Categoria
     * @var Categoria
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Categoria
        require_once __DIR__ . '/../../models/Categoria.php';
        $this->modelo = new Categoria();
    }

    /**
     * Muestra la lista de categorías
     * 
     * @param bool $soloActivas Si es true, solo muestra categorías activas
     * @return array Lista de categorías
     */
    public function index($soloActivas = false)
    {
        $categorias = $this->modelo->getAll($soloActivas);
        
        // Agregar conteo de productos a cada categoría
        foreach ($categorias as &$categoria) {
            $categoria['total_productos'] = $this->modelo->contarProductos($categoria['idcategoria']);
        }
        
        return $categorias;
    }

    /**
     * Prepara los datos de la categoría desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosCategoria($post_data)
    {
        return [
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1
        ];
    }

    /**
     * Crea una categoría vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function crearAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Preparar datos de la categoría
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosCategoria($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Guardar categoría usando el modelo
        if ($this->modelo->crear($datos)) {
            return [
                'success' => true,
                'message' => 'Categoría creada correctamente',
                'categoria' => [
                    'idcategoria' => $this->modelo->getLastInsertId(),
                    'nombre' => $datos['nombre'],
                    'estado' => $datos['estado'],
                    'total_productos' => 0 // Nuevas categorías no tienen productos
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Error al crear la categoría: ' . $this->modelo->getLastError()];
        }
    }

    /**
     * Obtiene una categoría por su ID
     * 
     * @param int $id ID de la categoría
     * @return array|bool Datos de la categoría o false si no existe
     */
    public function getById($id)
    {
        $categoria = $this->modelo->getById($id);
        if ($categoria) {
            $categoria['total_productos'] = $this->modelo->contarProductos($id);
        }
        return $categoria;
    }

    /**
     * Actualiza una categoría vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function actualizarAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Obtener ID de la categoría
        $id = isset($_POST['idcategoria']) ? (int)$_POST['idcategoria'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de categoría no válido'];
        }

        // Obtener datos actuales de la categoría
        $categoria_actual = $this->modelo->getById($id);
        if (!$categoria_actual) {
            return ['success' => false, 'message' => 'Categoría no encontrada para actualizar'];
        }

        // Preparar datos de la categoría
        $datos = $this->prepararDatosCategoria($_POST);

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo (excluyendo la categoría actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Actualizar categoría
        if ($this->modelo->actualizar($id, $datos)) {
            return [
                'success' => true,
                'message' => 'Categoría actualizada correctamente',
                'categoria' => [
                    'idcategoria' => $id,
                    'nombre' => $datos['nombre'],
                    'estado' => $datos['estado'],
                    'total_productos' => $this->modelo->contarProductos($id)
                ]
            ];
        } else {
            $error_message = 'Error al actualizar la categoría: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message];
        }
    }

    /**
     * Cambia el estado de una categoría vía AJAX
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
            return ['success' => false, 'message' => 'Datos inválidos para cambiar el estado de la categoría'];
        }

        // Verificar si la categoría tiene productos antes de desactivar
        if ($estado_actual == 1 && $this->modelo->contarProductos($id) > 0) {
            return [
                'success' => false,
                'message' => 'No se puede desactivar la categoría porque tiene productos asociados'
            ];
        }

        // El nuevo estado es el opuesto al actual
        $nuevo_estado = $estado_actual == 1 ? 0 : 1;

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $mensaje = $nuevo_estado == 1 ? 'activada' : 'desactivada';
            return [
                'success' => true,
                'message' => "Categoría $mensaje correctamente",
                'nuevo_estado' => $nuevo_estado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado de la categoría: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Obtiene las categorías para usar en selects
     * 
     * @param bool $soloActivas Si es true, solo devuelve categorías activas
     * @return array Lista de categorías en formato id => nombre
     */
    public function getParaSelect($soloActivas = true)
    {
        return $this->modelo->getParaSelect($soloActivas);
    }

    /**
     * Obtiene estadísticas de categorías
     * 
     * @return array Estadísticas de categorías
     */
    public function getEstadisticas()
    {
        $estadisticas = $this->modelo->getEstadisticas();
        
        // Agregar conteo de productos a las estadísticas
        $estadisticas['productos_por_categoria'] = [];
        $categorias = $this->modelo->getAll();
        
        foreach ($categorias as $categoria) {
            $estadisticas['productos_por_categoria'][] = [
                'nombre' => $categoria['nombre'],
                'total_productos' => $this->modelo->contarProductos($categoria['idcategoria'])
            ];
        }
        
        return $estadisticas;
    }

    /**
     * Obtiene el número de productos asociados a una categoría
     * 
     * @param int $idCategoria ID de la categoría
     * @return int Número de productos activos
     */
    public function contarProductos($idCategoria)
    {
        return $this->modelo->contarProductos($idCategoria);
    }
}