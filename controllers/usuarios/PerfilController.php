<?php

/**
 * Controlador de Perfil
 * 
 * Gestiona las operaciones relacionadas con el perfil de usuario
 * 
 * @version 1.0
 */

// Incluir el servicio de imágenes
require_once __DIR__ . '/../../services/ImagenService.php';
require_once __DIR__ . '/../../models/Usuario.php';

class PerfilController
{
    /**
     * Modelo de Usuario
     * @var Usuario
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
        // Inicializar el modelo de Usuario
        $this->modelo = new Usuario();

        // Inicializar el servicio de imágenes
        $this->imagenService = new ImagenService(__DIR__ . '/../../public/uploads/usuarios/');
    }

    /**
     * Obtiene los datos del perfil del usuario actual
     * 
     * @return array|bool Datos del usuario o false si no existe
     */
    public function obtenerPerfil()
    {
        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }

        $id = $_SESSION['usuario_id'];
        return $this->modelo->getById($id);
    }

    /**
     * Procesa el formulario para actualizar la imagen del perfil
     * 
     * @return array Resultado de la operación
     */
    public function actualizarImagenPerfil()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'views/usuarios/perfil.php'];
        }

        if (!isset($_SESSION['usuario_id'])) {
            return ['success' => false, 'message' => 'Sesión no iniciada.', 'icon' => 'error', 'redirect' => 'views/login/login.php'];
        }

        $id = $_SESSION['usuario_id'];

        // Obtener datos actuales del usuario
        $usuario_actual = $this->modelo->getById($id);
        if (!$usuario_actual) {
            return ['success' => false, 'message' => 'Usuario no encontrado', 'icon' => 'error', 'redirect' => 'views/usuarios/perfil.php'];
        }

        // Guardar imagen actual
        $imagen_antigua = $usuario_actual['imagen'];

        // Verificar si se ha subido una nueva imagen
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] != 0) {
            return ['success' => false, 'message' => 'No se ha seleccionado ninguna imagen válida.', 'icon' => 'warning', 'redirect' => 'views/usuarios/perfil.php'];
        }

        // Procesar la nueva imagen
        $nueva_imagen = $this->imagenService->procesarImagen($_FILES['imagen']);
        if (!$nueva_imagen) {
            return ['success' => false, 'message' => 'Error al procesar la imagen. Verifique el formato y tamaño.', 'icon' => 'error', 'redirect' => 'views/usuarios/perfil.php'];
        }

        // Eliminar imagen anterior si no es la predeterminada
        if ($imagen_antigua && $imagen_antigua !== 'user_default.jpg' && $imagen_antigua !== $nueva_imagen) {
            $this->imagenService->eliminarImagen($imagen_antigua);
        }

        // Actualizar solo la imagen del perfil
        if ($this->modelo->actualizarImagen($id, $nueva_imagen)) {
            // Actualizar la imagen en la sesión
            $_SESSION['usuario_imagen'] = $nueva_imagen;
            return ['success' => true, 'message' => 'Imagen de perfil actualizada correctamente', 'icon' => 'success', 'redirect' => 'views/usuarios/perfil.php'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar la imagen de perfil: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'views/usuarios/perfil.php'];
        }
    }

    /**
     * Procesa el formulario para cambiar la contraseña
     * 
     * @return array Resultado de la operación
     */
    public function cambiarClave()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.'];
        }

        if (!isset($_SESSION['usuario_id'])) {
            return ['success' => false, 'message' => 'Sesión no iniciada.'];
        }

        $id = $_SESSION['usuario_id'];

        // Validar datos
        $clave_actual = isset($_POST['clave_actual']) ? trim($_POST['clave_actual']) : '';
        $nueva_clave = isset($_POST['nueva_clave']) ? trim($_POST['nueva_clave']) : '';
        $confirmar_nueva_clave = isset($_POST['confirmar_nueva_clave']) ? trim($_POST['confirmar_nueva_clave']) : '';

        if (empty($clave_actual) || empty($nueva_clave) || empty($confirmar_nueva_clave)) {
            return ['success' => false, 'message' => 'Todos los campos de contraseña son obligatorios.'];
        }

        if ($nueva_clave !== $confirmar_nueva_clave) {
            return ['success' => false, 'message' => 'Las nuevas contraseñas no coinciden.'];
        }

        if (strlen($nueva_clave) < 6) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.'];
        }

        // Verificar la contraseña actual
        if (!$this->modelo->verificarContrasenaActual($id, $clave_actual)) {
            return ['success' => false, 'message' => 'La contraseña actual es incorrecta.'];
        }

        // Actualizar la contraseña
        if ($this->modelo->actualizarClave($id, $nueva_clave)) {
            return [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente.'
            ];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar la contraseña: ' . $this->modelo->getLastError()];
        }
    }
}
