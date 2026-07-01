<?php

/**
 * Controlador de Compras
 * 
 * Gestiona las operaciones relacionadas con las compras
 * 
 * @version 1.0
 */

class CompraController
{
    /**
     * Modelo de Compra
     * @var Compra
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Compra
        require_once __DIR__ . '/../../models/Compra.php';
        $this->modelo = new Compra();
    }

    /**
     * Muestra la lista de compras
     */
    public function index()
    {
        // Obtener todas las compras
        return $this->modelo->getAll();
    }

    /**
     * Muestra el formulario para crear una nueva compra
     */
    public function crear()
    {
        // Incluir modelos necesarios
        require_once __DIR__ . '/../../models/Producto.php';
        require_once __DIR__ . '/../../models/Usuario.php';
        
        // Obtener productos y usuarios para los selects
        $productoModel = new Producto();
        $usuarioModel = new Usuario();
        
        $datos = [
            'productos' => $productoModel->getAll(),
            'usuarios' => $usuarioModel->getAll()
        ];
        
        return $datos;
    }

    /**
     * Prepara los datos de la compra desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosCompra($post_data)
    {
        $datos = [
            'idusuario' => isset($post_data['idusuario']) ? (int)$post_data['idusuario'] : 0,
            'totalcompra' => isset($post_data['totalcompra']) ? (float)$post_data['totalcompra'] : 0,
            'fechacompra' => isset($post_data['fechacompra']) ? trim($post_data['fechacompra']) : date('Y-m-d H:i:s'),
            'estado' => 1, // Por defecto activa (1)
            'observaciones' => isset($post_data['observaciones']) && !empty($post_data['observaciones']) ? trim($post_data['observaciones']) : null,
            'detalles' => []
        ];

        // Procesar detalles de la compra
        if (isset($post_data['productos']) && is_array($post_data['productos'])) {
            foreach ($post_data['productos'] as $key => $idProducto) {
                if (!empty($idProducto)) {
                    $datos['detalles'][] = [
                        'idproducto' => (int)$idProducto,
                        'cantidad' => isset($post_data['cantidades'][$key]) ? (int)$post_data['cantidades'][$key] : 1,
                        'preciocompra' => isset($post_data['precios'][$key]) ? (float)$post_data['precios'][$key] : 0
                    ];
                }
            }
        }

        return $datos;
    }

    /**
     * Procesa el formulario para guardar una nueva compra
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos de la compra
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosCompra($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Guardar compra usando el modelo
        $idCompra = $this->modelo->crear($datos);
        
        if ($idCompra) {
            return ['success' => true, 'message' => 'Compra registrada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar la compra: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Procesa el formulario para guardar una nueva compra (versión alternativa)
     */
    public function guardarr()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'ingresar.php'];
        }

        // Preparar datos de la compra
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosCompra($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'ingresar.php'];
        }

        // Guardar compra usando el modelo
        $idCompra = $this->modelo->crear($datos);
        
        if ($idCompra) {
            return ['success' => true, 'message' => 'Compra registrada correctamente', 'icon' => 'success', 'redirect' => 'ingresar.php'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar la compra: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'ingresar.php'];
        }
    }

    /**
     * Muestra los detalles de una compra
     * 
     * @param int $id ID de la compra
     * @return array|null Datos de la compra o redirige en caso de error
     */
    public function ver($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de compra no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/compras');
            exit;
        }

        // Obtener datos de la compra con detalles
        $compra = $this->modelo->getById($id);

        if (!$compra) {
            global $URL;
            $_SESSION['mensaje'] = 'Compra no encontrada';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/compras');
            exit;
        }

        // Devolver los datos de la compra
        return $compra;
    }

    /**
     * Cancela una compra
     * 
     * @param int $id ID de la compra
     * @return array Resultado de la operación
     */
    public function cancelar($id = null)
    {
        if (!$id) {
            return ['success' => false, 'message' => 'ID de compra no válido', 'icon' => 'error'];
        }

        if ($this->modelo->cancelar($id)) {
            return ['success' => true, 'message' => 'Compra cancelada correctamente', 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al cancelar la compra: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Completa una compra (cambia estado a activa)
     * 
     * @param int $id ID de la compra
     * @return array Resultado de la operación
     */
    public function completar($id = null)
    {
        if (!$id) {
            return ['success' => false, 'message' => 'ID de compra no válido', 'icon' => 'error'];
        }

        if ($this->modelo->actualizarEstado($id, 1)) {
            return ['success' => true, 'message' => 'Compra marcada como completada', 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al completar la compra: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Obtiene compras por rango de fechas
     * 
     * @param string $fechaInicio Fecha de inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha de fin (YYYY-MM-DD)
     * @return array Lista de compras en el rango
     */
    public function obtenerPorRangoFechas($fechaInicio, $fechaFin)
    {
        return $this->modelo->getPorRangoFechas($fechaInicio, $fechaFin);
    }

    /**
     * Obtiene compras por estado
     * 
     * @param int $estado Estado de las compras (1 = activa, 0 = cancelada)
     * @return array Lista de compras con el estado especificado
     */
    public function obtenerPorEstado($estado)
    {
        return $this->modelo->getPorEstado($estado);
    }

    /**
     * Obtiene compras por usuario
     * 
     * @param int $idUsuario ID del usuario
     * @return array Lista de compras del usuario
     */
    public function obtenerPorUsuario($idUsuario)
    {
        return $this->modelo->getPorUsuario($idUsuario);
    }
}