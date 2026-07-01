<?php

/**
 * Controlador de Permisos
 * 
 * Gestiona las operaciones relacionadas con los permisos del sistema
 * 
 * @version 1.0
 */

class PermisoController
{
    /**
     * Modelo de Permiso
     * @var Permiso
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Permiso
        require_once __DIR__ . '/../../models/Permiso.php';
        $this->modelo = new Permiso();
    }

    /**
     * Muestra la lista de permisos
     * 
     * @return array Lista de permisos
     */
    public function index()
    {
        return $this->modelo->getAll();
    }

    /**
     * Obtiene un permiso por su ID
     * 
     * @param int $id ID del permiso
     * @return array|bool Datos del permiso o false si no existe
     */
    public function getById($id)
    {
        return $this->modelo->getById($id);
    }
}
