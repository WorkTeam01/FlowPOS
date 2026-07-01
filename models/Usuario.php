<?php

/**
 * Modelo Usuario
 * 
 * Gestiona las operaciones relacionadas con los usuarios en la base de datos
 * 
 * @version 1.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

class Usuario
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de usuarios en la base de datos
     * @var string
     */
    private $tabla = 'usuarios';

    /**
     * Último error ocurrido
     * @var string
     */
    private $lastError = '';

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Obtiene el último error ocurrido
     * 
     * @return string Mensaje de error
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Sanitiza los datos de entrada para prevenir inyección SQL y XSS
     * 
     * @param array $datos Datos a sanitizar
     * @return array Datos sanitizados
     */
    public function sanitizarDatos($datos)
    {
        $sanitized = [];
        foreach ($datos as $key => $value) {
            if (is_string($value)) {
                // Eliminar espacios adicionales y caracteres potencialmente peligrosos
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Obtiene todos los usuarios
     * 
     * @return array Lista de usuarios
     */
    public function getAll()
    {
        try {
            $query = "SELECT * FROM {$this->tabla} ORDER BY idusuario DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene un usuario por su ID
     * 
     * @param int $id ID del usuario
     * @return array|bool Datos del usuario o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idusuario = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Crea un nuevo usuario
     * 
     * @param array $datos Datos del usuario
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            // Encriptar la contraseña
            $clave_hash = password_hash($datos['clave'], PASSWORD_DEFAULT);

            $query = "INSERT INTO {$this->tabla} (nombre, apellidopaterno, apellidomaterno, tipodocumento, numdocumento, 
                      direccion, telefono, correo, cargo, clave, imagen, idsucursal, estado) 
                      VALUES (:nombre, :apellidopaterno, :apellidomaterno, :tipodocumento, :numdocumento, 
                      :direccion, :telefono, :correo, :cargo, :clave, :imagen, :idsucursal, :estado)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':apellidopaterno', $datos['apellidopaterno'], PDO::PARAM_STR);
            $stmt->bindParam(':apellidomaterno', $datos['apellidomaterno'], PDO::PARAM_STR);
            $stmt->bindParam(':tipodocumento', $datos['tipodocumento'], PDO::PARAM_STR);
            $stmt->bindParam(':numdocumento', $datos['numdocumento'], PDO::PARAM_STR);

            // Manejar valores nulos correctamente
            if (empty($datos['direccion'])) {
                $stmt->bindValue(':direccion', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
            }

            if (empty($datos['telefono'])) {
                $stmt->bindValue(':telefono', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
            }

            if (empty($datos['correo'])) {
                $stmt->bindValue(':correo', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
            }

            if (empty($datos['cargo'])) {
                $stmt->bindValue(':cargo', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':cargo', $datos['cargo'], PDO::PARAM_STR);
            }

            if (empty($datos['imagen'])) {
                $stmt->bindValue(':imagen', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':imagen', $datos['imagen'], PDO::PARAM_STR);
            }

            if (empty($datos['idsucursal'])) {
                $stmt->bindValue(':idsucursal', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':idsucursal', $datos['idsucursal'], PDO::PARAM_INT);
            }

            $stmt->bindParam(':clave', $clave_hash, PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza un usuario existente
     * 
     * @param int $id ID del usuario
     * @param array $datos Datos del usuario
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                      nombre = :nombre, 
                      apellidopaterno = :apellidopaterno, 
                      apellidomaterno = :apellidomaterno, 
                      tipodocumento = :tipodocumento, 
                      numdocumento = :numdocumento, 
                      direccion = :direccion, 
                      telefono = :telefono, 
                      correo = :correo, 
                      cargo = :cargo, 
                      imagen = :imagen, 
                      idsucursal = :idsucursal,
                      estado = :estado 
                      WHERE idusuario = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':apellidopaterno', $datos['apellidopaterno'], PDO::PARAM_STR);
            $stmt->bindParam(':apellidomaterno', $datos['apellidomaterno'], PDO::PARAM_STR);
            $stmt->bindParam(':tipodocumento', $datos['tipodocumento'], PDO::PARAM_STR);
            $stmt->bindParam(':numdocumento', $datos['numdocumento'], PDO::PARAM_STR);

            // Manejar valores nulos correctamente para campos opcionales
            if (empty($datos['direccion'])) {
                $stmt->bindValue(':direccion', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
            }

            if (empty($datos['telefono'])) {
                $stmt->bindValue(':telefono', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
            }

            if (empty($datos['correo'])) {
                $stmt->bindValue(':correo', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
            }

            if (empty($datos['cargo'])) {
                $stmt->bindValue(':cargo', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':cargo', $datos['cargo'], PDO::PARAM_STR);
            }

            // Para la imagen, también es bueno manejar el null explícitamente
            if (empty($datos['imagen'])) {
                $stmt->bindValue(':imagen', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':imagen', $datos['imagen'], PDO::PARAM_STR);
            }

            if (empty($datos['idsucursal'])) {
                $stmt->bindValue(':idsucursal', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':idsucursal', $datos['idsucursal'], PDO::PARAM_INT);
            }

            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza la contraseña de un usuario
     * 
     * @param int $id ID del usuario
     * @param string $clave Nueva contraseña
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarClave($id, $clave)
    {
        try {
            // Encriptar la nueva contraseña
            $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

            $query = "UPDATE {$this->tabla} SET clave = :clave WHERE idusuario = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':clave', $clave_hash, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de un usuario
     * 
     * @param int $id ID del usuario
     * @param int $estado Nuevo estado (1: activo, 0: inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idusuario = :idusuario";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':idusuario', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Activa un usuario
     * 
     * @param int $id ID del usuario
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function activar($id)
    {
        return $this->actualizarEstado($id, 1);
    }

    /**
     * Desactiva un usuario
     * 
     * @param int $id ID del usuario
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function desactivar($id)
    {
        return $this->actualizarEstado($id, 0);
    }

    /**
     * Verifica si existe un usuario con el correo especificado
     * 
     * @param string $correo Correo electrónico
     * @param int $id_excluir ID del usuario a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeCorreo($correo, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE correo = :correo AND idusuario != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE correo = :correo";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Obtiene el estado de un usuario por su correo
     * 
     * @param string $correo Correo del usuario
     * @return int|null Estado del usuario (1: activo, 0: inactivo) o null si no existe
     */
    public function obtenerEstadoPorCorreo($correo)
    {
        try {
            $query = "SELECT estado FROM {$this->tabla} WHERE correo = :correo";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    /**
     * Verifica si existe un usuario con el número de documento dado
     * 
     * @param string $numDocumento Número de documento a verificar
     * @return bool True si existe, False en caso contrario
     */
    public function existeNumDocumento($numDocumento)
    {
        try {
            $query = "SELECT COUNT(*) FROM usuarios WHERE numdocumento = :numdocumento";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':numdocumento', $numDocumento, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Obtiene el ID de un usuario por su número de documento
     * 
     * @param string $numDocumento Número de documento del usuario
     * @return int|null ID del usuario o null si no existe
     */
    public function obtenerIdPorNumDocumento($numDocumento)
    {
        try {
            $query = "SELECT idusuario FROM usuarios WHERE numdocumento = :numdocumento";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':numdocumento', $numDocumento, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    /**
     * Obtiene el ID de un usuario por su correo electrónico
     * 
     * @param string $correo Correo electrónico del usuario
     * @return int|null ID del usuario o null si no existe
     */
    public function obtenerIdPorCorreo($correo)
    {
        try {
            $query = "SELECT idusuario FROM usuarios WHERE correo = :correo";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    /**
     * Obtiene el estado de un usuario por su ID
     * 
     * @param int $id ID del usuario
     * @return int|null Estado del usuario (1: activo, 0: inactivo) o null si no existe
     */
    public function obtenerEstadoPorId($id)
    {
        try {
            $query = "SELECT estado FROM usuarios WHERE idusuario = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    /**
     * Verifica las credenciales de un usuario por correo electrónico
     * 
     * @param string $correo Correo electrónico del usuario
     * @param string $clave Contraseña del usuario
     * @return array|bool Datos del usuario si las credenciales son correctas, False en caso contrario
     */
    public function loginPorCorreo($correo, $clave)
    {
        try {
            $query = "SELECT * FROM usuarios WHERE correo = :correo AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($clave, $usuario['clave'])) {
                return $usuario;
            }

            return false;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Verifica las credenciales de un usuario por número de documento
     * 
     * @param string $numDocumento Número de documento del usuario
     * @param string $clave Contraseña del usuario
     * @return array|bool Datos del usuario si las credenciales son correctas, False en caso contrario
     */
    public function loginPorNumDocumento($numDocumento, $clave)
    {
        try {
            $query = "SELECT * FROM usuarios WHERE numdocumento = :numdocumento AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':numdocumento', $numDocumento, PDO::PARAM_STR);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($clave, $usuario['clave'])) {
                return $usuario;
            }

            return false;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Verifica si existe un usuario con el mismo tipo y número de documento
     * 
     * @param string $tipodocumento Tipo de documento
     * @param string $numdocumento Número de documento
     * @param int $id_excluir ID del usuario a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeTipoDocumento($tipodocumento, $numdocumento, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE tipodocumento = :tipodocumento AND numdocumento = :numdocumento AND idusuario != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':tipodocumento', $tipodocumento, PDO::PARAM_STR);
                $stmt->bindParam(':numdocumento', $numdocumento, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE tipodocumento = :tipodocumento AND numdocumento = :numdocumento";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':tipodocumento', $tipodocumento, PDO::PARAM_STR);
                $stmt->bindParam(':numdocumento', $numdocumento, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Valida los datos del usuario antes de crear o actualizar
     * 
     * @param array $datos Datos del usuario
     * @param int $id_excluir ID del usuario a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        // Determinar si es una creación o actualización
        $es_actualizacion = ($id_excluir !== null);

        // Validar campos obligatorios (más estricto para creación que para actualización)
        if (!$es_actualizacion) {
            // Validación para creación de usuario
            if (
                empty($datos['nombre']) ||
                empty($datos['apellidopaterno']) ||
                empty($datos['tipodocumento']) ||
                empty($datos['numdocumento']) ||
                empty($datos['correo']) ||
                empty($datos['cargo']) ||
                empty($datos['clave'])
            ) {
                $errores[] = 'Todos los campos obligatorios deben estar completos';
            }
        } else {
            // Validación para actualización de usuario
            // Solo validar los campos que están presentes y no vacíos
            $campos_obligatorios = ['nombre', 'apellidopaterno', 'tipodocumento', 'numdocumento', 'correo', 'cargo'];
            $campos_faltantes = [];

            foreach ($campos_obligatorios as $campo) {
                if (isset($datos[$campo]) && empty($datos[$campo])) {
                    $campos_faltantes[] = $campo;
                }
            }

            if (!empty($campos_faltantes)) {
                $errores[] = 'Los siguientes campos obligatorios no pueden estar vacíos: ' . implode(', ', $campos_faltantes);
            }
        }

        // Validar correo único si se proporcionó
        if (!empty($datos['correo'])) {
            if ($this->existeCorreo($datos['correo'], $id_excluir)) {
                $errores[] = 'El correo electrónico ya está registrado';
            }

            // Validar formato de correo
            if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El formato del correo electrónico no es válido';
            }
        }

        // Validar documento único si se proporcionaron ambos campos
        if (!empty($datos['tipodocumento']) && !empty($datos['numdocumento'])) {
            if ($this->existeTipoDocumento($datos['tipodocumento'], $datos['numdocumento'], $id_excluir)) {
                $errores[] = 'Ya existe un usuario con este tipo y número de documento';
            }
        }

        // Validar contraseña si se está creando un nuevo usuario o si se proporcionó una nueva contraseña
        if ((!$es_actualizacion && isset($datos['clave'])) || (!empty($datos['clave']))) {
            if (strlen($datos['clave']) < 6) {
                $errores[] = 'La contraseña debe tener al menos 6 caracteres';
            }
        }

        return $errores;
    }

    /**
     * Obtiene el ID del último usuario insertado
     * 
     * @return int ID del último usuario insertado
     */
    public function getLastInsertId()
    {
        try {
            return $this->conexion->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return 0;
        }
    }

    /**
     * Actualiza solo la imagen de un usuario
     * 
     * @param int $id ID del usuario
     * @param string $imagen Nombre del archivo de imagen
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarImagen($id, $imagen)
    {
        try {
            $query = "UPDATE {$this->tabla} SET imagen = :imagen WHERE idusuario = :id";
            $stmt = $this->conexion->prepare($query);

            if ($imagen === null) {
                $stmt->bindValue(':imagen', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':imagen', $imagen, PDO::PARAM_STR);
            }

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Verifica si la contraseña actual de un usuario es correcta
     * 
     * @param int $id ID del usuario
     * @param string $clave_actual Contraseña actual a verificar
     * @return bool True si la contraseña es correcta, False en caso contrario
     */
    public function verificarContrasenaActual($id, $clave_actual)
    {
        try {
            $query = "SELECT clave FROM {$this->tabla} WHERE idusuario = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $hash_clave = $stmt->fetchColumn();

            return password_verify($clave_actual, $hash_clave);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
