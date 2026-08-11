<?php
// ============================================
// TaskFlow — Connexion PDO (singleton)
// ============================================

require_once __DIR__ . '/../env.php';

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '8889';
            $name = getenv('DB_NAME') ?: 'taskflow';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: 'root';

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());

                $message = (getenv('APP_ENV') ?: 'development') === 'development'
                    ? 'Erreur de connexion à la base de données.'
                    : 'Une erreur est survenue. Veuillez réessayer plus tard.';

                die($message);
            }
        }

        return self::$pdo;
    }
}
