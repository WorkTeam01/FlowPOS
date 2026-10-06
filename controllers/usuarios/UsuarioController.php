<?php

/**
 * Controlador de Usuarios
 * 
 * Gestiona las operaciones relacionadas con los usuarios
 * 
 * @version 1.0
 */

// Incluir el servicio de imágenes
require_once __DIR__ . '/../../services/ImagenService.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../services/SesionTokenService.php';

class UsuarioController
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
     * Servicio de autorización
     * @var AuthorizationService
     */
    private $authService;

    /**
     * Modelo de Rol
     * @var Rol
     */
    private $rolModelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Usuario
        require_once __DIR__ . '/../../models/Usuario.php';
        $this->modelo = new Usuario();

        // Inicializar el servicio de imágenes
        $this->imagenService = new ImagenService(__DIR__ . '/../../public/uploads/usuarios/');

        $this->authService = new AuthorizationService();

        require_once __DIR__ . '/../../models/Rol.php';
        $this->rolModelo = new Rol();
    }

    /**
     * Resuelve un idrol de $_POST contra la tabla rol, nunca confiando en
     * un nombre de cargo enviado crudo por el POST.
     *
     * @param mixed $post_idrol Valor crudo de $_POST['idrol']
     * @return array{idrol:int|null,rol:array|false} idrol resuelto (null si no existe/inactivo) y el registro del rol
     */
    private function resolverRolDesdePost($post_idrol)
    {
        $idrol = isset($post_idrol) ? (int) $post_idrol : 0;
        if (!$idrol) {
            return ['idrol' => null, 'rol' => false];
        }

        $rol = $this->rolModelo->getById($idrol);
        if (!$rol || (int) $rol['estado'] !== 1) {
            return ['idrol' => null, 'rol' => false];
        }

        return ['idrol' => $idrol, 'rol' => $rol];
    }

    /**
     * Determina si el rol (registro de la tabla rol) es administrador.
     *
     * @param array|false $rol Registro de rol, o false si no se resolvió
     * @return bool
     */
    private function esRolAdmin($rol)
    {
        return $rol && (int) $rol['es_admin'] === 1;
    }

    /**
     * Muestra la lista de usuarios
     */
    public function index()
    {
        // Obtener todos los usuarios
        return $this->modelo->getAll();
    }

    /**
     * Muestra el formulario para crear un nuevo usuario
     */
    public function crear()
    {
        // Incluir la vista del formulario
        require_once __DIR__ . '/../../views/usuarios/create.php';
    }

    /**
     * Prepara los datos del usuario desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosUsuario($post_data)
    {
        $datos = [
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'apellidopaterno' => isset($post_data['apellidopaterno']) ? trim($post_data['apellidopaterno']) : '',
            'apellidomaterno' => isset($post_data['apellidomaterno']) && !empty($post_data['apellidomaterno']) ? trim($post_data['apellidomaterno']) : null,
            'tipodocumento' => isset($post_data['tipodocumento']) ? trim($post_data['tipodocumento']) : '',
            'numdocumento' => isset($post_data['numdocumento']) ? trim($post_data['numdocumento']) : '',
            'direccion' => isset($post_data['direccion']) && !empty($post_data['direccion']) ? trim($post_data['direccion']) : null,
            'telefono' => isset($post_data['telefono']) && !empty($post_data['telefono']) ? trim($post_data['telefono']) : null,
            'correo' => isset($post_data['correo']) && !empty($post_data['correo']) ? trim($post_data['correo']) : '',
            'clave' => isset($post_data['clave']) ? trim($post_data['clave']) : '',
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1,
            'imagen' => null // Se establece más tarde
        ];

        return $datos;
    }

    /**
     * Procesa el formulario para guardar un nuevo usuario
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Resolver el rol solicitado contra la tabla rol
        $rolSeleccionado = $this->resolverRolDesdePost($_POST['idrol'] ?? null);
        if ($rolSeleccionado['idrol'] === null) {
            return ['success' => false, 'message' => 'Debe seleccionar un rol válido', 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Solo un administrador puede asignar un rol con es_admin=1
        if ($this->esRolAdmin($rolSeleccionado['rol']) && !$this->authService->esAdministrador($_SESSION['usuario_id'])) {
            return ['success' => false, 'message' => 'No tiene permisos para asignar un rol de Administrador', 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Preparar datos del usuario
        $datos = $this->prepararDatosUsuario($_POST);
        $datos['idrol'] = $rolSeleccionado['idrol'];
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Verificar que las contraseñas coincidan
        $confirmar_clave = isset($_POST['confirmar_clave']) ? trim($_POST['confirmar_clave']) : '';
        if ($datos['clave'] !== $confirmar_clave) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden', 'icon' => 'error', 'redirect' => 'create.php'];
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

        // Guardar usuario usando el modelo
        if ($this->modelo->crear($datos)) {
            return ['success' => true, 'message' => 'Usuario creado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => mensajeErrorSeguro('Error al crear el usuario', $this->modelo->getLastError()), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Muestra el formulario para editar un usuario
     * 
     * @param int $id ID del usuario
     * @return array|null Datos del usuario o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de usuario no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/usuarios');
            exit;
        }

        // Obtener datos del usuario
        $usuario = $this->modelo->getById($id);

        if (!$usuario) {
            global $URL;
            $_SESSION['mensaje'] = 'Usuario no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/usuarios');
            exit;
        }

        // Devolver los datos del usuario
        return $usuario;
    }

    /**
     * Procesa el formulario para actualizar un usuario
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID del usuario
        $id = isset($_POST['idusuario']) ? (int)$_POST['idusuario'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de usuario no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Obtener datos actuales del usuario
        $usuario_actual = $this->modelo->getById($id);
        if (!$usuario_actual) {
            return ['success' => false, 'message' => 'Usuario no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Solo un administrador puede modificar una cuenta cuyo rol ACTUAL ya es administrador
        $rolActual = $usuario_actual['idrol'] ? $this->rolModelo->getById($usuario_actual['idrol']) : false;
        if ($this->esRolAdmin($rolActual) && !$this->authService->esAdministrador($_SESSION['usuario_id'])) {
            return ['success' => false, 'message' => 'No tiene permisos para modificar una cuenta de Administrador', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Resolver el rol NUEVO solicitado
        $rolSeleccionado = $this->resolverRolDesdePost($_POST['idrol'] ?? null);
        if ($rolSeleccionado['idrol'] === null) {
            return ['success' => false, 'message' => 'Debe seleccionar un rol válido', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Solo un administrador puede asignar o conservar un rol con es_admin=1
        if ($this->esRolAdmin($rolSeleccionado['rol']) && !$this->authService->esAdministrador($_SESSION['usuario_id'])) {
            return ['success' => false, 'message' => 'No tiene permisos para asignar un rol de Administrador', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // El sistema no puede quedarse sin administradores activos
        if ($this->esRolAdmin($rolActual) && !$this->esRolAdmin($rolSeleccionado['rol'])
            && $this->modelo->contarOtrosAdministradoresActivos($id) === 0) {
            return ['success' => false, 'message' => 'No se puede quitar el rol al último administrador activo', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Guardar imagen actual para posible eliminación posterior
        $imagen_antigua = $usuario_actual['imagen'];

        // Preparar datos del usuario
        $datos = $this->prepararDatosUsuario($_POST);
        $datos['idrol'] = $rolSeleccionado['idrol'];

        // Asegurarse de que los campos obligatorios estén presentes incluso si no se modificaron
        $campos_obligatorios = ['nombre', 'apellidopaterno', 'tipodocumento', 'numdocumento', 'correo'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo]) && isset($usuario_actual[$campo])) {
                $datos[$campo] = $usuario_actual[$campo];
            }
        }

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Establecer la imagen anterior por defecto
        $datos['imagen'] = $imagen_antigua;

        // Validar datos en el modelo (excluyendo el usuario actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Validar la clave antes de tocar la BD: con el UPDATE primero, un error de
        // confirmación dejaba los datos guardados y al usuario viendo un fallo
        $clave = isset($_POST['clave']) ? trim($_POST['clave']) : '';
        $confirmar_clave = isset($_POST['confirmar_clave']) ? trim($_POST['confirmar_clave']) : '';
        if (!empty($clave) || !empty($confirmar_clave)) {
            if (empty($clave) || empty($confirmar_clave)) {
                return ['success' => false, 'message' => 'Para cambiar la contraseña, debe completar ambos campos', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
            }
            if ($clave !== $confirmar_clave) {
                return ['success' => false, 'message' => 'Las contraseñas no coinciden', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
            }
            if (strlen($clave) < 6) {
                return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
            }
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

        // Actualizar usuario
        $actualizado = $this->modelo->actualizar($id, $datos);

        // $_SESSION['usuario_rol'] se fija solo al iniciar sesión: sin cerrar sus
        // sesiones, un rol degradado conservaría los permisos del anterior. La
        // sesión propia se respeta (el administrador que se edita a sí mismo).
        if ($actualizado && (int) $usuario_actual['idrol'] !== (int) $datos['idrol'] && (int) $id !== (int) $_SESSION['usuario_id']) {
            (new SesionTokenService())->cerrarPorUsuario((int) $id, SesionTokenService::MOTIVO_SECURITY);
        }

        // El cambio de imagen recién se concreta acá, no antes: mientras el
        // UPDATE no confirme, el registro sigue apuntando a la imagen vieja y
        // borrarla dejaría al usuario con la foto rota. Si el UPDATE falló, lo
        // que se descarta es la recién subida para no dejarla huérfana.
        if ($nueva_imagen_path) {
            if ($actualizado) {
                if ($imagen_antigua && $imagen_antigua !== 'user_default.jpg' && $imagen_antigua !== $nueva_imagen_path) {
                    $this->imagenService->eliminarImagen($imagen_antigua);
                }
            } else {
                $this->imagenService->eliminarImagen($nueva_imagen_path);
            }
        }

        // Procesar cambio de contraseña (ya validada antes del UPDATE)
        $clave_actualizada = true; // Por defecto, asumimos que no hay cambio de clave

        if (!empty($clave) || !empty($confirmar_clave)) {
            // Actualizar la contraseña
            $clave_actualizada = $this->modelo->actualizarClave($id, $clave);

            if ($clave_actualizada) {
                // Invalidar todas las sesiones abiertas de ESA cuenta (no de la
                // actual, que es del administrador que ejecuta el cambio): con
                // una clave nueva, cualquier sesión existente deja de ser
                // confiable — mismo criterio que en el cambio de perfil propio.
                (new SesionTokenService())->cerrarPorUsuario($id, SesionTokenService::MOTIVO_CAMBIO_CLAVE);
            }
        }

        if ($actualizado && $clave_actualizada) {
            return ['success' => true, 'message' => 'Usuario actualizado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            $error_message = 'Error al actualizar el usuario.';
            if (!$actualizado) {
                $db_error = $this->modelo->getLastError();
                $error_message .= ' ' . mensajeErrorSeguro('Problema con datos del usuario', $db_error);
            }
            if (!$clave_actualizada) {
                $error_message .= ' Problema al actualizar contraseña.';
            }
            return ['success' => false, 'message' => $error_message, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Cambia el estado de un usuario (activa/desactiva)
     * 
     * @param int $id ID del usuario
     * @param int $estado_actual Estado actual del usuario (1 para activo, 0 para inactivo)
     * @return array Resultado de la operación
     */
    public function cambiarEstadoUsuario($id = null, $estado_actual = null)
    {
        if ($id === null || $estado_actual === null) {
            return ['success' => false, 'message' => 'ID de usuario o estado no válido', 'icon' => 'error'];
        }

        $usuario_objetivo = $this->modelo->getById($id);
        $rolObjetivo = $usuario_objetivo && $usuario_objetivo['idrol'] ? $this->rolModelo->getById($usuario_objetivo['idrol']) : false;
        if ($this->esRolAdmin($rolObjetivo) && !$this->authService->esAdministrador($_SESSION['usuario_id'])) {
            return ['success' => false, 'message' => 'No tiene permisos para cambiar el estado de una cuenta de Administrador', 'icon' => 'error'];
        }

        $nuevo_estado = $estado_actual == 1 ? 0 : 1; // Cambia el estado

        if ($nuevo_estado == 0 && $this->esRolAdmin($rolObjetivo) && $this->modelo->contarOtrosAdministradoresActivos((int) $id) === 0) {
            return ['success' => false, 'message' => 'No se puede desactivar al último administrador activo', 'icon' => 'error'];
        }

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $accion = $nuevo_estado == 1 ? 'activado' : 'desactivado';
            if ($nuevo_estado == 0) {
                // El timeout deslizante de 24 h dejaría operando a una cuenta ya desactivada
                (new SesionTokenService())->cerrarPorUsuario((int) $id, SesionTokenService::MOTIVO_ADMIN_USUARIO);
            }
            return ['success' => true, 'message' => "Usuario $accion correctamente", 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => mensajeErrorSeguro('Error al cambiar el estado del usuario', $this->modelo->getLastError()), 'icon' => 'error'];
        }
    }
}
