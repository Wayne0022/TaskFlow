<?php
// ============================================
// TaskFlow — Couche métier : authentification
// ============================================

require_once __DIR__ . '/../db/UserDB.php';

class AuthManager
{
    public static function login(string $email, string $password): bool
    {
        $user = UserDB::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id'          => $user['id'],
            'username'    => $user['username'],
            'email'       => $user['email'],
        ];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function register(string $username, string $email, string $password): array
    {
        $errors = [];

        $username = trim($username);
        $email = trim($email);

        if ($username === '') {
            $errors['username'] = 'Le nom d\'utilisateur est requis.';
        } elseif (strlen($username) > 50) {
            $errors['username'] = '50 caractères maximum.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Lettres, chiffres et underscore uniquement.';
        } elseif (UserDB::findByUsername($username)) {
            $errors['username'] = 'Ce nom d\'utilisateur est déjà pris.';
        }

        if ($email === '') {
            $errors['email'] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        } elseif (strlen($email) > 100) {
            $errors['email'] = '100 caractères maximum.';
        } elseif (UserDB::findByEmail($email)) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }

        if ($password === '') {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = '8 caractères minimum.';
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        UserDB::create($username, $email, $passwordHash);

        return ['success' => true];
    }

    public static function requireLogin(): void
    {
        if (self::currentUser() === null) {
            header('Location: login.php');
            exit;
        }
    }

    public static function currentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $user = UserDB::findById((int) $_SESSION['user_id']);

        if ($user === null) {
            self::logout();
            return null;
        }

        unset($user['password']);

        return $user;
    }
}
