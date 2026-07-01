<?php

/**
 * Clase Conexion
 * 
 * Maneja la conexión a la base de datos utilizando el patrón Singleton
 * para asegurar una única instancia de conexión durante toda la aplicación.
 * 
 * @version 1.0
 */

class Conexion
{
    /**
     * Instancia única de la clase Conexion
     * 
     * @var Conexion
     */
    private static $instance = null;

    /**
     * Objeto PDO para la conexión a la base de datos
     * 
     * @var PDO
     */
    private $connection;

    /**
     * Constructor privado para evitar la creación directa de objetos
     */
    private function __construct()
    {
        try {
            // Intentar cargar la configuración desde config.php
            $config_file = __DIR__ . '/config.php';

            if (!file_exists($config_file)) {
                throw new Exception("El archivo de configuración no existe");
            }

            $config = require $config_file;

            // Verificar si la configuración es válida
            if (!is_array($config) || !isset($config['database']) || !is_array($config['database'])) {
                throw new Exception("La configuración de la base de datos no es válida");
            }

            $db_config = $config['database'];

            // Verificar que todos los parámetros necesarios estén presentes
            if (
                !isset($db_config['host']) || !isset($db_config['name']) ||
                !isset($db_config['user']) || !isset($db_config['pass'])
            ) {
                throw new Exception("Faltan parámetros de configuración de la base de datos");
            }

            // Usar utf8 en lugar de utf8mb4 para evitar problemas de compatibilidad
            $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, $db_config['user'], $db_config['pass'], $options);

            // Obtener zona horaria desde la configuración de la app
            $timezone = isset($config['app']['timezone']) ? $config['app']['timezone'] : 'America/La_Paz';

            // Establecer la zona horaria de MariaDB basada en la configuración
            $timezone_offset = $this->getTimezoneOffset($timezone);
            $this->connection->exec("SET time_zone = '{$timezone_offset}'");

            // Verificación opcional (se puede eliminar en producción)
            $stmt = $this->connection->query("SELECT @@session.time_zone");
            $set_tz = $stmt->fetchColumn();
            if ($set_tz !== $timezone_offset) {
                error_log("Advertencia: No se pudo establecer correctamente la zona horaria de MariaDB. Solicitada: {$timezone_offset}, Actual: {$set_tz}");
            }
        } catch (Exception $e) {
            die("Error de configuración: " . $e->getMessage());
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la instancia única de la clase Conexion
     * 
     * @return Conexion Instancia de la conexión
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene el objeto PDO de conexión
     * 
     * @return PDO Objeto de conexión PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Ejecuta una consulta SQL y devuelve el resultado
     * 
     * @param string $sql Consulta SQL a ejecutar
     * @param array $params Parámetros para la consulta preparada
     * @return PDOStatement Resultado de la consulta
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Convierte el nombre de zona horaria a formato de offset para MariaDB
     * 
     * @param string $timezone Nombre de la zona horaria (ej. America/La_Paz)
     * @return string Offset en formato '+HH:MM' o '-HH:MM'
     */
    private function getTimezoneOffset($timezone)
    {
        try {
            $dateTimeZone = new DateTimeZone($timezone);
            $dateTime = new DateTime('now', $dateTimeZone);
            $offset = $dateTimeZone->getOffset($dateTime);

            // Convertir segundos a formato +/-HH:MM
            $hours = intval(abs($offset) / 3600);
            $minutes = intval((abs($offset) % 3600) / 60);
            $sign = $offset < 0 ? '-' : '+';

            return $sign . sprintf('%02d:%02d', $hours, $minutes);
        } catch (Exception $e) {
            error_log("Error al calcular offset de zona horaria: " . $e->getMessage());
            return '-04:00'; // Valor por defecto para Bolivia en caso de error
        }
    }
}
