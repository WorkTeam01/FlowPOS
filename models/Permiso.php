<?php

/**
 * Modelo Permiso
 * 
 * Gestiona las operaciones relacionadas con los permisos del sistema
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Permiso
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de permisos en la base de datos
     * @var string
     */
    private $tabla = 'permiso';

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
     * Obtiene todos los permisos
     * 
     * @return array Lista de permisos
     */
    public function getAll()
    {
        try {
            $query = "SELECT * FROM {$this->tabla} ORDER BY nombre ASC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene un permiso por su ID
     * 
     * @param int $id ID del permiso
     * @return array|bool Datos del permiso o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idpermiso = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
