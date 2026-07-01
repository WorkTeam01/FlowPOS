<?php

/**
 * Controlador de Clientes
 * 
 * Gestiona las operaciones relacionadas con los clientes
 * 
 * @version 1.0
 */

// Incluir el servicio de imágenes
require_once __DIR__ . '/../../services/ImagenService.php';

class ClienteController
{
    /**
     * Modelo de Cliente
     * @var Cliente
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
        // Incluir el modelo de Cliente
        require_once __DIR__ . '/../../models/Cliente.php';
        $this->modelo = new Cliente();

        // Inicializar el servicio de imágenes
        $this->imagenService = new ImagenService(__DIR__ . '/../../public/uploads/clientes/');
    }

    /**
     * Muestra la lista de clientes
     */
    public function index()
    {
        // Obtener todos los clientes
        return $this->modelo->getAll();
    }

    /**
     * Muestra el formulario para crear un nuevo cliente
     */
    public function crear()
    {
        // Incluir la vista del formulario
        require_once __DIR__ . '/../../views/clientes/create.php';
    }

    /**
     * Prepara los datos del cliente desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosCliente($post_data)
    {
        $datos = [
            'nombres' => isset($post_data['nombres']) ? trim($post_data['nombres']) : '',
            'apellidopaterno' => isset($post_data['apellidopaterno']) ? trim($post_data['apellidopaterno']) : '',
            'apellidomaterno' => isset($post_data['apellidomaterno']) ? trim($post_data['apellidomaterno']) : null,
            'tipodocumento' => isset($post_data['tipodocumento']) ? trim($post_data['tipodocumento']) : '',
            'numdocumento' => isset($post_data['numdocumento']) ? trim($post_data['numdocumento']) : '',
            'direccion' => isset($post_data['direccion']) && !empty($post_data['direccion']) ? trim($post_data['direccion']) : null,
            'celular' => isset($post_data['celular']) && !empty($post_data['celular']) ? trim($post_data['celular']) : null,
            'email' => isset($post_data['email']) && !empty($post_data['email']) ? trim($post_data['email']) : null,
            'genero' => isset($post_data['genero']) && !empty($post_data['genero']) ? trim($post_data['genero']) : '',
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1
        ];

        return $datos;
    }

    /**
     * Procesa el formulario para guardar un nuevo cliente
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos del cliente
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosCliente($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Guardar cliente usando el modelo
        if ($this->modelo->crear($datos)) {
            return ['success' => true, 'message' => 'Cliente registrado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar el cliente: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Muestra el formulario para editar un cliente
     * 
     * @param int $id ID del cliente
     * @return array|null Datos del cliente o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de cliente no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/clientes');
            exit;
        }

        // Obtener datos del cliente
        $cliente = $this->modelo->getById($id);

        if (!$cliente) {
            global $URL;
            $_SESSION['mensaje'] = 'Cliente no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/clientes');
            exit;
        }

        // Devolver los datos del cliente
        return $cliente;
    }

    /**
     * Procesa el formulario para actualizar un cliente
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID del cliente
        $id = isset($_POST['idcliente']) ? (int)$_POST['idcliente'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de cliente no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Obtener datos actuales del cliente
        $cliente_actual = $this->modelo->getById($id);
        if (!$cliente_actual) {
            return ['success' => false, 'message' => 'Cliente no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Preparar datos del cliente
        $datos = $this->prepararDatosCliente($_POST);

        // Asegurarse de que los campos obligatorios estén presentes incluso si no se modificaron
        $campos_obligatorios = ['nombres', 'apellidopaterno', 'tipodocumento', 'numdocumento', 'genero'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo]) && isset($cliente_actual[$campo])) {
                $datos[$campo] = $cliente_actual[$campo];
            }
        }

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo (excluyendo el cliente actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Actualizar cliente
        if ($this->modelo->actualizar($id, $datos)) {
            return ['success' => true, 'message' => 'Cliente actualizado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            $error_message = 'Error al actualizar el cliente: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Cambia el estado de un cliente (activa/desactiva)
     * 
     * @param int $id ID del cliente
     * @param int $estado_actual Estado actual del cliente (1 para activo, 0 para inactivo)
     * @return array Resultado de la operación
     */
    public function cambiarEstado($id = null, $estado_actual = null)
    {
        if ($id === null || $estado_actual === null) {
            return ['success' => false, 'message' => 'ID de cliente o estado no válido', 'icon' => 'error'];
        }

        $nuevo_estado = $estado_actual == 1 ? 0 : 1; // Cambia el estado

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $accion = $nuevo_estado == 1 ? 'activado' : 'desactivado';
            return ['success' => true, 'message' => "Cliente $accion correctamente", 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al cambiar el estado del cliente: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Busca clientes según criterios
     * 
     * @param array $criterios Criterios de búsqueda
     * @return array Resultados de la búsqueda
     */
    public function buscar($criterios)
    {
        return $this->modelo->buscar($criterios);
    }
}
