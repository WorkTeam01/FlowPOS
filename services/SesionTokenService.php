<?php

/**
 * Servicio de tokens de revocación de sesiones
 *
 * El token que identifica a cada sesión PHP vive en claro solo dentro de
 * $_SESSION; en la tabla sesionusuario se persiste únicamente su SHA-256.
 * Validar ese token en cada request autenticado es lo que permite cerrar
 * una sesión de verdad desde el panel de administración.
 *
 * @version 1.0
 */
class SesionTokenService
{
    /**
     * Clave de $_SESSION donde vive el token en claro
     * @var string
     */
    const CLAVE_SESION = 'sesion_revocation_token';

    /**
     * ID de la fila de auditoría en $_SESSION: permite cerrarla aunque la
     * sesión haya quedado sin token (p. ej. creada antes de un despliegue).
     */
    const CLAVE_FILA = 'sesion_fila_id';

    const MOTIVO_LOGOUT = 'logout';
    const MOTIVO_TIMEOUT = 'timeout';
    const MOTIVO_SECURITY = 'security';
    const MOTIVO_ADMIN_FILA = 'admin_row';
    const MOTIVO_ADMIN_USUARIO = 'admin_user';
    const MOTIVO_MIGRACION = 'migration';

    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Memo-cache por request del resultado de estadoValidacion():
     * isAuthenticated() se invoca varias veces por request (punto de entrada,
     * header, controllers), evita repetir el SELECT del token.
     * @var string|null
     */
    private static $memoEstado = null;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        require_once __DIR__ . '/../config/conexion.php';
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Genera un token de revocación aleatorio (256 bits, 64 hex)
     *
     * @return string Token en claro
     */
    public static function generar(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hash persistible del token: SHA-256 en hex (64 caracteres)
     *
     * @param string $token Token en claro
     * @return string Hash hex de 64 caracteres
     */
    public static function hashear(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Registra el inicio de una sesión y devuelve su token de revocación.
     *
     * @param int $idusuario ID del usuario
     * @param string $ip IP del cliente
     * @param string $navegador User-Agent completo
     * @return string|null Token en claro, o null si no se pudo registrar
     *                     (el login debe abortar: sin fila no hay sesión)
     */
    public function registrar(int $idusuario, string $ip, string $navegador)
    {
        try {
            $token = self::generar();

            $sql = "INSERT INTO sesionusuario (idusuario, ipusuario, navegador, token_hash)
                    VALUES (:idusuario, :ipusuario, :navegador, :token_hash)";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':idusuario' => $idusuario,
                ':ipusuario' => $ip,
                ':navegador' => mb_substr($navegador, 0, 255, 'UTF-8'),
                ':token_hash' => self::hashear($token),
            ]);

            self::$memoEstado = null;
            $_SESSION[self::CLAVE_FILA] = (int) $this->conexion->lastInsertId();
            return $token;
        } catch (PDOException $e) {
            error_log('Error al registrar la sesión: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Estado del token de la sesión actual:
     * ok → token presente y con fila activa; ausente → la sesión no tiene token;
     * revocada → la fila ya no está activa; error → no se pudo consultar
     * (el resultado se interpreta como falla cerrada salvo 'ok').
     *
     * @return string 'ok' | 'ausente' | 'revocada' | 'error'
     */
    public function estadoValidacion(): string
    {
        if (self::$memoEstado !== null) {
            return self::$memoEstado;
        }

        $token = $_SESSION[self::CLAVE_SESION] ?? null;
        if (!is_string($token) || $token === '') {
            return self::$memoEstado = 'ausente';
        }

        try {
            $sql = "SELECT 1 FROM sesionusuario
                     WHERE token_hash = :token_hash AND estado = 1
                     LIMIT 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':token_hash' => self::hashear($token)]);

            $existe = $stmt->fetchColumn() !== false;
            return self::$memoEstado = $existe ? 'ok' : 'revocada';
        } catch (PDOException $e) {
            error_log('Error al validar la sesión: ' . $e->getMessage());
            return self::$memoEstado = 'error';
        }
    }

    /**
     * Cierra la sesión de la que pertenece el token guardado en la sesión PHP
     *
     * @param string $motivo Motivo de cierre
     * @return bool true si se ejecutó (0 filas afectadas también es éxito)
     */
    public function cerrarPorToken(string $motivo): bool
    {
        $token = $_SESSION[self::CLAVE_SESION] ?? null;
        if (!is_string($token) || $token === '') {
            return false;
        }

        return $this->cerrar('token_hash = :token', [':token' => self::hashear($token)], $motivo);
    }

    /**
     * Cierra una sesión por su ID de fila
     *
     * @param int $idsesion ID de la sesión
     * @param string $motivo Motivo de cierre
     * @return bool
     */
    public function cerrarPorId(int $idsesion, string $motivo): bool
    {
        return $this->cerrar('idsesion = :idsesion', [':idsesion' => $idsesion], $motivo);
    }

    /**
     * Cierra todas las sesiones activas de un usuario
     *
     * @param int $idusuario ID del usuario
     * @param string $motivo Motivo de cierre
     * @return bool
     */
    public function cerrarPorUsuario(int $idusuario, string $motivo): bool
    {
        return $this->cerrar('idusuario = :idusuario', [':idusuario' => $idusuario], $motivo);
    }

    /**
     * Ejecuta el cierre filtrando por estado = 1: es idempotente y el primer
     * motivo registrado es el que queda (una fila ya cerrada no se reabre
     * ni se sobrescribe su motivo).
     *
     * @param string $condicion WHERE sin la condición de estado
     * @param array $params Parámetros de la condición
     * @param string $motivo Motivo de cierre
     * @return bool
     */
    private function cerrar(string $condicion, array $params, string $motivo): bool
    {
        try {
            $sql = "UPDATE sesionusuario
                       SET horasalida = NOW(), estado = 0, motivo_cierre = :motivo
                     WHERE {$condicion} AND estado = 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(array_merge($params, [':motivo' => $motivo]));

            return true;
        } catch (PDOException $e) {
            error_log('Error al cerrar la sesión: ' . $e->getMessage());
            return false;
        }
    }
}
