<?php
// ============================================
// TaskFlow — Gestion des erreurs unifiée
// ============================================

class ErrorHandler
{
    private static string $logFile = __DIR__ . '/../../logs/error.log';

    /**
     * Initialiser le gestionnaire d'erreurs et exceptions
     */
    public static function init(): void
    {
        // Créer le dossier logs s'il n'existe pas
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    /**
     * Gérer les erreurs PHP
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $message = sprintf(
            "[%s] %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $errstr,
            basename($errfile),
            $errline
        );

        self::log($message, 'ERROR');
        return true; // Continuer l'exécution
    }

    /**
     * Gérer les exceptions
     */
    public static function handleException(Throwable $exception): void
    {
        $message = sprintf(
            "[%s] %s: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            basename($exception->getFile()),
            $exception->getLine()
        );

        self::log($message, 'EXCEPTION');

        // En développement : afficher l'erreur
        if ((getenv('APP_ENV') ?: 'development') === 'development') {
            http_response_code(500);
            echo '<pre style="background: #f0f0f0; padding: 20px; font-family: monospace;">';
            echo htmlspecialchars($message);
            echo "\n\nStack Trace:\n";
            echo htmlspecialchars($exception->getTraceAsString());
            echo '</pre>';
        } else {
            // En production : message générique
            http_response_code(500);
            echo 'Une erreur est survenue. Veuillez réessayer plus tard.';
        }

        exit;
    }

    /**
     * Enregistrer un message d'erreur/info
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        $formatted = sprintf(
            "[%s] %-10s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );

        error_log($formatted, 3, self::$logFile);
    }
}
