<?php
// ============================================
// TaskFlow — Configuration application & sessions
// ============================================

require_once __DIR__ . '/../env.php';
require_once __DIR__ . '/../src/helpers/ErrorHandler.php';
require_once __DIR__ . '/../src/helpers/SecurityHelper.php';

define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/taskflow', '/'));
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');

// Initialiser la gestion des erreurs
ErrorHandler::init();

function isProduction(): bool
{
    return APP_ENV === 'production';
}

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isProduction(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function redirect(string $path): void
{
    header('Location: ' . APP_URL . $path);
    exit;
}

startSession();
