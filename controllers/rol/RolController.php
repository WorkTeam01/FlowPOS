<?php

/**
 * Controlador de Roles
 *
 * Gestiona las operaciones relacionadas con los roles del sistema
 *
 * @version 1.0
 */

class RolController
{
    /**
     * Modelo de Rol
     * @var Rol
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        require_once __DIR__ . '/../../models/Rol.php';
        $this->modelo = new Rol();
    }

    /**
     * Muestra la lista de roles
     *
     * @param bool $soloActivos Si es true, solo muestra roles activos
     * @return array Lista de roles
     */
    public function index($soloActivos = false)
    {
        $roles = $this->modelo->getAll($soloActivos);

        foreach ($roles as &$rol) {
            $rol['total_usuarios'] = $this->modelo->contarUsuarios($rol['idrol']);
        }

        return $roles;
    }

    /**
     * Prepara los datos del rol desde $_POST
     *
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosRol($post_data)
    {
        return [
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'descripcion' => isset($post_data['descripcion']) ? trim($post_data['descripcion']) : '',
            'dashboard' => isset($post_data['dashboard']) ? trim($post_data['dashboard']) : 'dashboard_general.php',
        ];
    }

    /**
     * Crea un rol vía AJAX
     *
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function crearAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        $datos = $this->modelo->sanitizarDatos($this->prepararDatosRol($_POST));

        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        if ($this->modelo->crear($datos)) {
            return [
                'success' => true,
                'message' => 'Rol creado correctamente',
                'rol' => [
                    'idrol' => $this->modelo->getLastInsertId(),
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'],
                    'dashboard' => $datos['dashboard'],
                    'es_sistema' => 0,
                    'es_admin' => 0,
                    'estado' => 1,
                    'total_usuarios' => 0
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Error al crear el rol: ' . $this->modelo->getLastError()];
        }
    }

    /**
     * Obtiene un rol por su ID
     *
     * @param int $id ID del rol
     * @return array|bool Datos del rol o false si no existe
     */
    public function getById($id)
    {
        $rol = $this->modelo->getById($id);
        if ($rol) {
            $rol['total_usuarios'] = $this->modelo->contarUsuarios($id);
        }
        return $rol;
    }

    /**
     * Actualiza un rol vía AJAX
     *
     * Regla de seguridad: un rol con es_sistema=1 no puede renombrarse (nombre
     * y dashboard quedan fijos), solo su descripción es editable. es_admin no
     * es editable desde esta fase (sin matriz todavía).
     *
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function actualizarAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        $id = isset($_POST['idrol']) ? (int)$_POST['idrol'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de rol no válido'];
        }

        $rol_actual = $this->modelo->getById($id);
        if (!$rol_actual) {
            return ['success' => false, 'message' => 'Rol no encontrado para actualizar'];
        }

        $datos = $this->modelo->sanitizarDatos($this->prepararDatosRol($_POST));

        // Un rol de sistema no puede renombrarse ni cambiar su dashboard,
        // aunque el POST traiga otro valor (protege contra manipulación directa).
        if ($rol_actual['es_sistema'] == 1) {
            $datos['nombre'] = $rol_actual['nombre'];
            $datos['dashboard'] = $rol_actual['dashboard'];
        }

        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        if ($this->modelo->actualizar($id, $datos)) {
            return [
                'success' => true,
                'message' => 'Rol actualizado correctamente',
                'rol' => [
                    'idrol' => $id,
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'],
                    'dashboard' => $datos['dashboard'],
                    'es_sistema' => $rol_actual['es_sistema'],
                    'es_admin' => $rol_actual['es_admin'],
                    'estado' => $rol_actual['estado'],
                    'total_usuarios' => $this->modelo->contarUsuarios($id)
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar el rol: ' . $this->modelo->getLastError()];
        }
    }

    /**
     * Cambia el estado de un rol vía AJAX
     *
     * Regla de seguridad: no se puede desactivar el último rol admin activo
     * (evita lockout total del sistema).
     *
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function cambiarEstadoAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $estado_actual = isset($_POST['estado_actual']) ? (int)$_POST['estado_actual'] : null;

        if (!$id || $estado_actual === null) {
            return ['success' => false, 'message' => 'Datos inválidos para cambiar el estado del rol'];
        }

        $rol = $this->modelo->getById($id);
        if (!$rol) {
            return ['success' => false, 'message' => 'Rol no encontrado'];
        }

        if ($estado_actual == 1 && $rol['es_admin'] == 1 && $this->modelo->contarRolesAdminActivos($id) === 0) {
            return [
                'success' => false,
                'message' => 'No se puede desactivar el último rol administrador activo'
            ];
        }

        if ($estado_actual == 1 && $this->modelo->contarUsuarios($id) > 0) {
            return [
                'success' => false,
                'message' => 'No se puede desactivar el rol porque tiene usuarios asignados'
            ];
        }

        $nuevo_estado = $estado_actual == 1 ? 0 : 1;

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $mensaje = $nuevo_estado == 1 ? 'activado' : 'desactivado';
            return [
                'success' => true,
                'message' => "Rol $mensaje correctamente",
                'nuevo_estado' => $nuevo_estado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del rol: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Elimina un rol vía AJAX
     *
     * Reglas de seguridad: un rol de sistema no es eliminable; un rol con
     * usuarios asignados tampoco (evita usuarios huérfanos aunque la FK ya
     * lo bloquee, para dar un mensaje claro en vez de un error de BD).
     *
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function eliminarAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de rol no válido'];
        }

        $rol = $this->modelo->getById($id);
        if (!$rol) {
            return ['success' => false, 'message' => 'Rol no encontrado'];
        }

        if ($rol['es_sistema'] == 1) {
            return ['success' => false, 'message' => 'No se puede eliminar un rol de sistema'];
        }

        $total_usuarios = $this->modelo->contarUsuarios($id);
        if ($total_usuarios > 0) {
            return [
                'success' => false,
                'message' => "No se puede eliminar el rol porque tiene $total_usuarios usuario(s) asignado(s)"
            ];
        }

        if ($this->modelo->eliminar($id)) {
            return ['success' => true, 'message' => 'Rol eliminado correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al eliminar el rol: ' . $this->modelo->getLastError()];
        }
    }

    /**
     * Obtiene los roles para usar en selects
     *
     * @param bool $soloActivos Si es true, solo devuelve roles activos
     * @return array Lista de roles en formato id => nombre
     */
    public function getParaSelect($soloActivos = true)
    {
        return $this->modelo->getParaSelect($soloActivos);
    }

    /**
     * Obtiene estadísticas de roles
     *
     * @return array Estadísticas de roles
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }

    /**
     * Obtiene la matriz rol×permiso: roles activos, catálogo de permisos y,
     * por cada rol, la lista de idpermiso que tiene asignados.
     *
     * @return array{roles: array, permisos: array, asignaciones: array<int,array<int>>}
     */
    public function getMatriz()
    {
        require_once __DIR__ . '/../../services/AuthorizationService.php';
        $auth = new AuthorizationService();

        $roles = $this->modelo->getAll(true);
        $permisos = $auth->obtenerTodosLosPermisos();

        $asignaciones = [];
        foreach ($roles as $rol) {
            $asignaciones[$rol['idrol']] = $auth->obtenerPermisosRol($rol['idrol']);
        }

        return [
            'roles' => $roles,
            'permisos' => $permisos,
            'asignaciones' => $asignaciones,
        ];
    }

    /**
     * Guarda los permisos de un rol vía AJAX
     *
     * Reglas de seguridad: nadie edita los permisos de su propio rol (corta
     * la auto-escalada); los idpermiso recibidos se intersectan contra el
     * catálogo real antes de guardarse, nunca se insertan crudos.
     *
     * @param int $idusuario_actual ID del usuario autenticado que hace la petición
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function guardarPermisosAjax($idusuario_actual)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        $idrol = isset($_POST['idrol']) ? (int) $_POST['idrol'] : 0;

        if (!$idrol) {
            return ['success' => false, 'message' => 'ID de rol no válido'];
        }

        $rol = $this->modelo->getById($idrol);
        if (!$rol) {
            return ['success' => false, 'message' => 'Rol no encontrado'];
        }

        if ($this->modelo->getIdRolDeUsuario($idusuario_actual) === $idrol) {
            return ['success' => false, 'message' => 'No puede editar los permisos de su propio rol'];
        }

        require_once __DIR__ . '/../../services/AuthorizationService.php';
        $auth = new AuthorizationService();

        $catalogo = array_column($auth->obtenerTodosLosPermisos(), 'idpermiso');
        $recibidos = isset($_POST['idpermiso']) && is_array($_POST['idpermiso'])
            ? array_map('intval', $_POST['idpermiso'])
            : [];
        $idpermisos = array_values(array_intersect($recibidos, $catalogo));

        if ($auth->actualizarPermisosRol($idrol, $idpermisos)) {
            return [
                'success' => true,
                'message' => "Permisos del rol \"{$rol['nombre']}\" actualizados correctamente",
                'idpermisos' => $idpermisos,
            ];
        }

        return ['success' => false, 'message' => 'Error al guardar los permisos del rol'];
    }
}
