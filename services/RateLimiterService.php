<?php

/**
 * Servicio de rate limiting para el login
 *
 * Controla intentos fallidos por identificador (cuenta) y por IP
 * usando una ventana deslizante sobre la tabla `intento_login`.
 *
 * @version 1.0
 */
class RateLimiterService
{
    const MAX_INTENTOS_IDENT = 5;
    const MAX_INTENTOS_IP    = 15;
    const VENTANA_MINUTOS    = 15;
    const RETENCION_DIAS     = 30;

    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        require_once __DIR__ . '/../config/conexion.php';
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Indica si el identificador o la IP están actualmente bloqueados
     * por exceso de intentos fallidos dentro de la ventana deslizante.
     *
     * @param string $identificador
     * @param string $ip
     * @return bool
     */
    public function estaBloqueado(string $identificador, string $ip): bool
    {
        return $this->contarFallosIdentificador($identificador) >= self::MAX_INTENTOS_IDENT
            || $this->contarFallosIp($ip) >= self::MAX_INTENTOS_IP;
    }

    /**
     * Minutos restantes hasta que expire el bloqueo vigente (0 si no está bloqueado).
     * Se calcula a partir del intento fallido más antiguo dentro de la ventana
     * que provoca el bloqueo (el que primero saldrá de la ventana).
     *
     * @param string $identificador
     * @param string $ip
     * @return int
     */
    public function minutosRestantes(string $identificador, string $ip): int
    {
        $minutos = 0;

        if ($this->contarFallosIdentificador($identificador) >= self::MAX_INTENTOS_IDENT) {
            $minutos = max($minutos, $this->minutosHastaExpirar('identificador', $identificador, self::MAX_INTENTOS_IDENT));
        }

        if ($this->contarFallosIp($ip) >= self::MAX_INTENTOS_IP) {
            $minutos = max($minutos, $this->minutosHastaExpirar('ip', $ip, self::MAX_INTENTOS_IP));
        }

        return $minutos;
    }

    /**
     * Registra un intento de login fallido.
     *
     * @param string $identificador
     * @param string $ip
     * @return void
     */
    public function registrarFallo(string $identificador, string $ip): void
    {
        $stmt = $this->conexion->prepare(
            'INSERT INTO intento_login (identificador, ip, exito) VALUES (:identificador, :ip, 0)'
        );
        $stmt->execute([
            ':identificador' => $identificador,
            ':ip' => $ip,
        ]);
    }

    /**
     * Registra un login exitoso.
     *
     * @param string $identificador
     * @param string $ip
     * @return void
     */
    public function registrarExito(string $identificador, string $ip): void
    {
        $stmt = $this->conexion->prepare(
            'INSERT INTO intento_login (identificador, ip, exito) VALUES (:identificador, :ip, 1)'
        );
        $stmt->execute([
            ':identificador' => $identificador,
            ':ip' => $ip,
        ]);
    }

    /**
     * Elimina intentos con más de RETENCION_DIAS de antigüedad.
     *
     * @return void
     */
    public function purgar(): void
    {
        $dias = self::RETENCION_DIAS;
        $this->conexion->exec("DELETE FROM intento_login WHERE fecha < NOW() - INTERVAL {$dias} DAY");
    }

    /**
     * Cuenta los intentos fallidos recientes para un identificador dado.
     *
     * @param string $identificador
     * @return int
     */
    private function contarFallosIdentificador(string $identificador): int
    {
        $minutos = self::VENTANA_MINUTOS;
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*) FROM intento_login
             WHERE identificador = :identificador
               AND exito = 0
               AND fecha > NOW() - INTERVAL {$minutos} MINUTE"
        );
        $stmt->execute([':identificador' => $identificador]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta los intentos fallidos recientes para una IP dada.
     *
     * @param string $ip
     * @return int
     */
    private function contarFallosIp(string $ip): int
    {
        $minutos = self::VENTANA_MINUTOS;
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*) FROM intento_login
             WHERE ip = :ip
               AND exito = 0
               AND fecha > NOW() - INTERVAL {$minutos} MINUTE"
        );
        $stmt->execute([':ip' => $ip]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Calcula los minutos restantes hasta que el intento fallido que provoca
     * el bloqueo salga de la ventana deslizante (es decir, el N-ésimo más
     * reciente contando desde el límite del umbral).
     *
     * @param string $columna 'identificador' o 'ip'
     * @param string $valor
     * @param int $umbral
     * @return int
     */
    private function minutosHastaExpirar(string $columna, string $valor, int $umbral): int
    {
        $minutosVentana = self::VENTANA_MINUTOS;
        $stmt = $this->conexion->prepare(
            "SELECT fecha FROM intento_login
             WHERE {$columna} = :valor
               AND exito = 0
               AND fecha > NOW() - INTERVAL {$minutosVentana} MINUTE
             ORDER BY fecha DESC
             LIMIT 1 OFFSET :offset"
        );
        $stmt->bindValue(':valor', $valor);
        $stmt->bindValue(':offset', $umbral - 1, PDO::PARAM_INT);
        $stmt->execute();
        $fecha = $stmt->fetchColumn();

        if ($fecha === false) {
            return self::VENTANA_MINUTOS;
        }

        $expira = strtotime($fecha) + (self::VENTANA_MINUTOS * 60);
        $restante = (int) ceil(($expira - time()) / 60);

        return max($restante, 1);
    }
}
