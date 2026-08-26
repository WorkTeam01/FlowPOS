<?php

/**
 * Servicio de Autorización
 * 
 * Gestiona los permisos y autorización de usuarios
 * 
 * @version 1.0
 */
class AuthorizationService
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Memo-cache estático por request: idusuario => es_admin (bool).
     * header.php invoca esAdministrador() ~20 veces por render; evita
     * repetir el JOIN usuarios->rol en cada llamada.
     * @var array<int,bool>
     */
    private static $cacheEsAdmin = [];

    /**
     * Memo-cache estático por request: idusuario => idrol (int|null).
     * @var array<int,int|null>
     */
    private static $cacheIdRolUsuario = [];

    /**
     * Memo-cache estático por request: idrol => lista de idpermiso.
     * @var array<int,array<int>>
     */
    private static $cachePermisosRol = [];

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        require_once __DIR__ . '/../config/conexion.php';
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Obtiene el idrol de un usuario (con memo-cache por request)
     *
     * @param int $idusuario ID del usuario
     * @return int|null idrol del usuario, o null si no tiene rol asignado
     */
    private function obtenerIdRolUsuario($idusuario)
    {
        if (array_key_exists($idusuario, self::$cacheIdRolUsuario)) {
            return self::$cacheIdRolUsuario[$idusuario];
        }

        try {
            $query = "SELECT idrol FROM usuarios WHERE idusuario = :idusuario AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            $idrol = $stmt->fetchColumn();
            $idrol = ($idrol !== false && $idrol !== null) ? (int) $idrol : null;

            return self::$cacheIdRolUsuario[$idusuario] = $idrol;
        } catch (PDOException $e) {
            error_log('Error al obtener idrol del usuario: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene los idpermiso asignados a un rol, con memo-cache por request.
     * Envuelve obtenerPermisosRol() para las verificaciones internas de
     * autorización (tienePermiso/tienePermisoNombre), que se invocan muchas
     * veces por request sobre el mismo rol.
     *
     * @param int $idrol ID del rol
     * @return array<int> Lista de idpermiso asignados al rol
     */
    private function obtenerPermisosRolCacheado($idrol)
    {
        if (!array_key_exists($idrol, self::$cachePermisosRol)) {
            self::$cachePermisosRol[$idrol] = $this->obtenerPermisosRol($idrol);
        }

        return self::$cachePermisosRol[$idrol];
    }

    /**
     * Verifica si un usuario tiene un permiso específico
     *
     * Los permisos se resuelven por el rol del usuario (rolpermiso), no por
     * asignación individual — permisousuario queda deprecado desde la fase 5
     * del RBAC (docs/plan-rbac-permisos.md).
     *
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso a verificar
     * @return bool True si tiene permiso, False en caso contrario
     */
    public function tienePermiso($idusuario, $idpermiso)
    {
        // Verificar si el usuario es administrador (bypass total)
        if ($this->esAdministrador($idusuario)) {
            return true;
        }

        $idrol = $this->obtenerIdRolUsuario($idusuario);
        if (!$idrol) {
            return false;
        }

        return in_array((int) $idpermiso, $this->obtenerPermisosRolCacheado($idrol), true);
    }

    /**
     * Verifica si un usuario tiene un permiso por su nombre
     * 
     * @param int $idusuario ID del usuario
     * @param string $nombre_permiso Nombre del permiso a verificar
     * @return bool True si tiene permiso, False en caso contrario
     */
    public function tienePermisoNombre($idusuario, $nombre_permiso)
    {
        try {
            // Si el usuario es administrador, tiene todos los permisos
            if ($this->esAdministrador($idusuario)) {
                return true;
            }

            // Si el permiso es 'admin_completo', solo los administradores lo tienen
            if ($nombre_permiso === 'admin_completo') {
                return $this->esAdministrador($idusuario);
            }

            // Obtener el ID del permiso por su nombre
            $query = "SELECT idpermiso FROM permiso WHERE nombre = :nombre AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $nombre_permiso, PDO::PARAM_STR);
            $stmt->execute();

            $idpermiso = $stmt->fetchColumn();

            if (!$idpermiso) {
                return false; // El permiso no existe
            }

            return $this->tienePermiso($idusuario, $idpermiso);
        } catch (PDOException $e) {
            error_log('Error al verificar permiso por nombre: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un usuario es administrador (rol.es_admin), con memo-cache
     * por request. Reemplaza la comparación de string sobre usuarios.cargo
     * (fase 5 del RBAC, docs/plan-rbac-permisos.md) — desacopla el bypass
     * total de permisos del nombre del rol.
     *
     * @param int $idusuario ID del usuario
     * @return bool True si es administrador, False en caso contrario
     */
    public function esAdministrador($idusuario)
    {
        if (array_key_exists($idusuario, self::$cacheEsAdmin)) {
            return self::$cacheEsAdmin[$idusuario];
        }

        try {
            $query = "SELECT r.es_admin
                     FROM usuarios u
                     JOIN rol r ON u.idrol = r.idrol
                     WHERE u.idusuario = :idusuario AND u.estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            $es_admin = (bool) $stmt->fetchColumn();

            return self::$cacheEsAdmin[$idusuario] = $es_admin;
        } catch (PDOException $e) {
            error_log('Error al verificar si es administrador: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los permisos de un usuario, resueltos por su rol
     * (rolpermiso) — no por asignación individual (permisousuario, deprecado
     * desde la fase 5 del RBAC).
     *
     * @param int $idusuario ID del usuario
     * @return array Lista de permisos
     */
    public function obtenerPermisosUsuario($idusuario)
    {
        try {
            // Si es administrador, devolver todos los permisos
            if ($this->esAdministrador($idusuario)) {
                $query = "SELECT idpermiso, nombre FROM permiso WHERE estado = 1";
                $stmt = $this->conexion->prepare($query);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $idrol = $this->obtenerIdRolUsuario($idusuario);
            if (!$idrol) {
                return [];
            }

            $query = "SELECT p.idpermiso, p.nombre
                     FROM rolpermiso rp
                     JOIN permiso p ON rp.idpermiso = p.idpermiso
                     WHERE rp.idrol = :idrol AND p.estado = 1";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idrol', $idrol, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Registrar error
            error_log('Error al obtener permisos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Asigna un permiso a un usuario
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si se asignó correctamente, False en caso contrario
      *
     * @deprecated Fase 5 del RBAC (docs/plan-rbac-permisos.md): los permisos
     * se administran por rol (rolpermiso), no por usuario individual.
     * permisousuario queda sin lectores activos en el sistema.
     */
    public function asignarPermiso($idusuario, $idpermiso)
    {
        try {
            // Verificar si ya existe la asignación
            $query = "SELECT COUNT(*) FROM permisousuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                // Ya existe, no hacer nada
                return true;
            } else {
                // No existe, crear nueva asignación
                $query = "INSERT INTO permisousuario (idpermiso, idusuario) 
                         VALUES (:idpermiso, :idusuario)";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
                $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
                return $stmt->execute();
            }
        } catch (PDOException $e) {
            error_log('Error al asignar permiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoca un permiso a un usuario
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si se revocó correctamente, False en caso contrario
      *
     * @deprecated Fase 5 del RBAC (docs/plan-rbac-permisos.md): los permisos
     * se administran por rol (rolpermiso), no por usuario individual.
     * permisousuario queda sin lectores activos en el sistema.
     */
    public function revocarPermiso($idusuario, $idpermiso)
    {
        try {
            // Verificar si existe la asignación
            $query = "SELECT COUNT(*) FROM permisousuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                // Si existe, eliminar la asignación
                $query = "DELETE FROM permisousuario 
                         WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
                $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
                return $stmt->execute();
            }

            return true; // Si no existe, no hay que revocar nada
        } catch (PDOException $e) {
            error_log('Error al revocar permiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Categoría visual de cada permiso, usada solo para agrupar el checklist
     * en tabs (no restringe qué permisos puede tener un cargo: en FlowPOS
     * cualquier permiso es asignable a cualquier usuario).
     *
     * @return array<string,string> nombre de permiso => categoría
     */
    private static function categoriasPermisos()
    {
        return [
            'ventas' => 'Ventas',
            'clientes' => 'Ventas',
            'compras' => 'Compras',
            'productos' => 'Catálogo',
            'categorias' => 'Catálogo',
            'usuarios' => 'Administración',
            'permisos' => 'Administración',
            'empresa' => 'Administración',
            'sucursales' => 'Administración',
            'sesiones' => 'Administración',
            'perfil' => 'Perfil',
        ];
    }

    /**
     * Agrupa una lista de permisos (idpermiso, nombre) en categorías visuales
     * para renderizarlas como tabs. Los permisos sin categoría definida caen
     * en "Otros".
     *
     * @param array $permisos Lista de permisos (con al menos 'nombre')
     * @return array<string,array> categoría => lista de permisos
     */
    public static function agruparPermisosPorCategoria($permisos)
    {
        $categorias_permisos = self::categoriasPermisos();
        $grupos = [];

        foreach ($permisos as $permiso) {
            $categoria = $categorias_permisos[$permiso['nombre']] ?? 'Otros';
            $grupos[$categoria][] = $permiso;
        }

        return $grupos;
    }

    /**
     * Obtiene todos los permisos disponibles en el sistema
     *
     * @return array Lista de todos los permisos
     */
    public function obtenerTodosLosPermisos()
    {
        try {
            $query = "SELECT idpermiso, nombre FROM permiso WHERE estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error al obtener todos los permisos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un usuario tiene asignado un permiso específico
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si tiene el permiso asignado, False en caso contrario
      *
     * @deprecated Fase 5 del RBAC (docs/plan-rbac-permisos.md): los permisos
     * se administran por rol (rolpermiso), no por usuario individual.
     * permisousuario queda sin lectores activos en el sistema.
     */
    public function tienePermisoAsignado($idusuario, $idpermiso)
    {
        try {
            $query = "SELECT COUNT(*) FROM permisousuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error al verificar permiso asignado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza los permisos de un usuario (elimina todos y asigna los nuevos)
     * 
     * @param int $idusuario ID del usuario
     * @param array $permisos Lista de IDs de permisos a asignar
     * @return bool True si se actualizaron correctamente, False en caso contrario
      *
     * @deprecated Fase 5 del RBAC (docs/plan-rbac-permisos.md): los permisos
     * se administran por rol (rolpermiso), no por usuario individual.
     * permisousuario queda sin lectores activos en el sistema.
     */
    public function actualizarPermisosUsuario($idusuario, $permisos)
    {
        try {
            // Iniciar transacción
            $this->conexion->beginTransaction();

            // Eliminar todos los permisos actuales del usuario
            $query = "DELETE FROM permisousuario WHERE idusuario = :idusuario";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            // Asignar los nuevos permisos
            foreach ($permisos as $idpermiso) {
                $query = "INSERT INTO permisousuario (idpermiso, idusuario) 
                         VALUES (:idpermiso, :idusuario)";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
                $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Confirmar transacción
            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            // Revertir cambios en caso de error
            $this->conexion->rollBack();
            error_log('Error al actualizar permisos de usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los permisos asignados a un usuario específico
     * 
     * @param int $idusuario ID del usuario
     * @return array Lista de IDs de permisos asignados
      *
     * @deprecated Fase 5 del RBAC (docs/plan-rbac-permisos.md): los permisos
     * se administran por rol (rolpermiso), no por usuario individual.
     * permisousuario queda sin lectores activos en el sistema.
     */
    public function obtenerPermisosAsignados($idusuario)
    {
        try {
            $query = "SELECT idpermiso FROM permisousuario WHERE idusuario = :idusuario";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            $permisos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permisos[] = $row['idpermiso'];
            }

            return $permisos;
        } catch (PDOException $e) {
            error_log('Error al obtener permisos asignados: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los IDs de permisos asignados a un rol
     *
     * @param int $idrol ID del rol
     * @return array Lista de IDs de permisos asignados al rol
     */
    public function obtenerPermisosRol($idrol)
    {
        try {
            $query = "SELECT idpermiso FROM rolpermiso WHERE idrol = :idrol";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idrol', $idrol, PDO::PARAM_INT);
            $stmt->execute();

            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (PDOException $e) {
            error_log('Error al obtener permisos del rol: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualiza los permisos de un rol (elimina todos y asigna los nuevos)
     *
     * Los IDs recibidos deben venir ya intersectados contra el catálogo real
     * de permisos (obtenerTodosLosPermisos()) por el llamador — este método
     * no valida su procedencia.
     *
     * @param int $idrol ID del rol
     * @param array $idpermisos Lista de IDs de permisos a asignar
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarPermisosRol($idrol, $idpermisos)
    {
        try {
            $this->conexion->beginTransaction();

            $query = "DELETE FROM rolpermiso WHERE idrol = :idrol";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idrol', $idrol, PDO::PARAM_INT);
            $stmt->execute();

            $query = "INSERT INTO rolpermiso (idrol, idpermiso) VALUES (:idrol, :idpermiso)";
            $stmt = $this->conexion->prepare($query);
            foreach ($idpermisos as $idpermiso) {
                $stmt->bindParam(':idrol', $idrol, PDO::PARAM_INT);
                $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            error_log('Error al actualizar permisos del rol: ' . $e->getMessage());
            return false;
        }
    }
}
