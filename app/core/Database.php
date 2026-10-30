<?php
/**
 * Conexión única (patrón Singleton) a MySQL mediante PDO.
 * - Excepciones activadas para detectar errores.
 * - Sentencias preparadas reales (sin emulación) contra inyección SQL.
 */
class Database
{
    private static ?PDO $conexion = null;

    public static function conexion(): PDO
    {
        if (self::$conexion === null) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
            self::$conexion = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Misma zona horaria en PHP y MySQL: CURDATE() y date('Y-m-d') coinciden
            self::$conexion->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$conexion;
    }
}
