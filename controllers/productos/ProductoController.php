<?php

/**
 * Controlador de Productos
 * 
 * Gestiona las operaciones relacionadas con los productos
 * 
 * @version 1.0
 */

// Incluir el servicio de imágenes
require_once __DIR__ . '/../../services/ImagenService.php';

class ProductoController
{
    /**
     * Modelo de Producto
     * @var Producto
     */
    private $modelo;

    /**
     * Servicio de imágenes
     * @var ImagenService
     */
    private $imagenService;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Producto
        require_once __DIR__ . '/../../models/Producto.php';
        $this->modelo = new Producto();

        // Inicializar el servicio de imágenes
        $this->imagenService = new ImagenService(__DIR__ . '/../../public/uploads/productos/');
    }

    /**
     * Muestra la lista de productos
     */
    public function index()
    {
        // Obtener todos los productos
        return $this->modelo->getAll();
    }

    /**
     * Muestra el formulario para crear un nuevo producto
     */
    public function crear()
    {
        // Incluir la vista del formulario
        require_once __DIR__ . '/../../views/productos/create.php';
    }

    /**
     * Prepara los datos del producto desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosProducto($post_data)
    {
        $datos = [
            'idcategoria' => isset($post_data['idcategoria']) ? (int)$post_data['idcategoria'] : 0,
            'codigo' => isset($post_data['codigo']) && !empty($post_data['codigo']) ? trim($post_data['codigo']) : null,
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'descripcion' => isset($post_data['descripcion']) && !empty($post_data['descripcion']) ? trim($post_data['descripcion']) : null,
            'stock' => isset($post_data['stock']) ? (int)$post_data['stock'] : 0,
            'stockminimo' => isset($post_data['stockminimo']) ? (int)$post_data['stockminimo'] : 0,
            'stockmaximo' => isset($post_data['stockmaximo']) ? max(0, (int)$post_data['stockmaximo']) : 0,
            'precioventa' => isset($post_data['precioventa']) ? (float)$post_data['precioventa'] : 0,
            'preciocompra' => isset($post_data['preciocompra']) ? (float)$post_data['preciocompra'] : 0,
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1,
            'imagen' => null // Se establece más tarde
        ];

        // Máximo opcional en el formulario, pero la columna es NOT NULL con
        // CHECK (stockmaximo >= stockminimo): sin valor se usa el mayor entre
        // el mínimo y el stock inicial
        if ($datos['stockmaximo'] <= 0) {
            $datos['stockmaximo'] = max($datos['stockminimo'], $datos['stock']);
        }

        return $datos;
    }

    /**
     * Procesa el formulario para guardar un nuevo producto
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos del producto
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosProducto($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // El código es opcional en el formulario pero NOT NULL UNIQUE en la tabla
        if (empty($datos['codigo'])) {
            $datos['codigo'] = $this->modelo->generarCodigo();
        }

        // Procesar imagen usando el servicio
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $imagen_path = $this->imagenService->procesarImagen($_FILES['imagen']);
            if ($imagen_path) {
                $datos['imagen'] = $imagen_path;
            } else {
                return ['success' => false, 'message' => 'Error al procesar la imagen. Verifique el formato y tamaño.', 'icon' => 'error', 'redirect' => 'create.php'];
            }
        }

        // Guardar producto usando el modelo
        if ($this->modelo->crear($datos)) {
            return ['success' => true, 'message' => 'Producto creado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al crear el producto: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Muestra el formulario para editar un producto
     * 
     * @param int $id ID del producto
     * @return array|null Datos del producto o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de producto no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/productos');
            exit;
        }

        // Obtener datos del producto
        $producto = $this->modelo->getById($id);

        if (!$producto) {
            global $URL;
            $_SESSION['mensaje'] = 'Producto no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/productos');
            exit;
        }

        // Devolver los datos del producto
        return $producto;
    }

    /**
     * Procesa el formulario para actualizar un producto
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID del producto
        $id = isset($_POST['idproducto']) ? (int)$_POST['idproducto'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de producto no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Obtener datos actuales del producto
        $producto_actual = $this->modelo->getById($id);
        if (!$producto_actual) {
            return ['success' => false, 'message' => 'Producto no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Guardar imagen actual para posible eliminación posterior
        $imagen_antigua = $producto_actual['imagen'];

        // Preparar datos del producto
        $datos = $this->prepararDatosProducto($_POST);

        // Asegurarse de que los campos obligatorios estén presentes incluso si no se modificaron
        $campos_obligatorios = ['idcategoria', 'nombre', 'precioventa', 'preciocompra'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo]) && isset($producto_actual[$campo])) {
                $datos[$campo] = $producto_actual[$campo];
            }
        }

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Establecer la imagen anterior por defecto
        $datos['imagen'] = $imagen_antigua;

        // Validar datos en el modelo (excluyendo el producto actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Código vacío en la edición: conservar el existente en vez de escribir NULL
        if (empty($datos['codigo'])) {
            $datos['codigo'] = $producto_actual['codigo'];
        }

        // El stock se aplica como diferencia respecto al valor con que se abrió el
        // formulario, para no pisar ventas/compras ocurridas mientras estaba abierto
        if (isset($_POST['stock_original']) && is_numeric($_POST['stock_original'])) {
            $datos['stock_delta'] = (int)$datos['stock'] - (int)$_POST['stock_original'];
        }

        // Procesar nueva imagen si se subió
        $nueva_imagen_path = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $nueva_imagen_path = $this->imagenService->procesarImagen($_FILES['imagen']);
            if ($nueva_imagen_path) {
                $datos['imagen'] = $nueva_imagen_path;
            } else {
                return ['success' => false, 'message' => 'Error al procesar la nueva imagen. Verifique el formato y tamaño.', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
            }
        }

        // Actualizar producto
        if ($this->modelo->actualizar($id, $datos)) {
            // La imagen anterior se borra recién con el UPDATE confirmado: si
            // fallara, el registro seguiría apuntando a un fichero borrado.
            // producto_default.png es compartido por todos los productos, no
            // se elimina aunque figure como valor de una fila.
            if ($nueva_imagen_path && $imagen_antigua && $imagen_antigua !== 'producto_default.png' && $imagen_antigua !== $nueva_imagen_path) {
                $this->imagenService->eliminarImagen($imagen_antigua);
            }
            return ['success' => true, 'message' => 'Producto actualizado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            // El UPDATE no se aplicó: se descarta la recién subida para no
            // dejarla huérfana en disco.
            if ($nueva_imagen_path) {
                $this->imagenService->eliminarImagen($nueva_imagen_path);
            }
            $error_message = 'Error al actualizar el producto: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Cambia el estado de un producto (activa/desactiva)
     * 
     * @param int $id ID del producto
     * @param int $estado_actual Estado actual del producto (1 para activo, 0 para inactivo)
     * @return array Resultado de la operación
     */
    public function cambiarEstadoProducto($id = null, $estado_actual = null)
    {
        if ($id === null || $estado_actual === null) {
            return ['success' => false, 'message' => 'ID de producto o estado no válido', 'icon' => 'error'];
        }

        $nuevo_estado = $estado_actual == 1 ? 0 : 1; // Cambia el estado

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $accion = $nuevo_estado == 1 ? 'activado' : 'desactivado';
            return ['success' => true, 'message' => "Producto $accion correctamente", 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al cambiar el estado del producto: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Obtiene productos con stock bajo (para notificaciones)
     * 
     * @return array Lista de productos con stock bajo
     */
    public function obtenerStockBajo()
    {
        return $this->modelo->getStockBajo();
    }

    /**
     * Obtiene productos por categoría (para menús o filtros)
     * 
     * @param int $id_categoria ID de la categoría
     * @return array Lista de productos en la categoría
     */
    public function obtenerPorCategoria($id_categoria)
    {
        return $this->modelo->getByCategoria($id_categoria);
    }

    /**
     * Obtiene los últimos movimientos de un producto (ventas y compras)
     * 
     * @param int $idproducto ID del producto
     * @param int $limite Número máximo de movimientos a obtener
     * @return array Array con los últimos movimientos del producto
     */
    public function getUltimosMovimientos($idproducto, $limite = 5)
    {
        return $this->modelo->getUltimosMovimientos($idproducto, $limite);
    }
}