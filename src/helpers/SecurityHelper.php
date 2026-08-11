<?php
// ============================================
// TaskFlow — Sécurité : CSRF, validation
// ============================================

class SecurityHelper
{
    /**
     * Générer un token CSRF et le sauvegarder en session
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valider un token CSRF reçu du formulaire
     */
    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Valider une chaîne de caractères (longueur, format)
     */
    public static function validateString(string $value, int $minLen = 1, int $maxLen = 255, ?string $pattern = null): array
    {
        $errors = [];

        $value = trim($value);

        if (strlen($value) < $minLen) {
            $errors[] = "Minimum {$minLen} caractères requis.";
        }

        if (strlen($value) > $maxLen) {
            $errors[] = "{$maxLen} caractères maximum.";
        }

        if ($pattern && !preg_match($pattern, $value)) {
            $errors[] = "Format invalide.";
        }

        return $errors;
    }

    /**
     * Valider un email
     */
    public static function validateEmail(string $email): array
    {
        $errors = [];
        $email = trim($email);

        if (empty($email)) {
            $errors[] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide.";
        } elseif (strlen($email) > 100) {
            $errors[] = "100 caractères maximum.";
        }

        return $errors;
    }

    /**
     * Valider un mot de passe
     */
    public static function validatePassword(string $password, int $minLen = 8): array
    {
        $errors = [];

        if (empty($password)) {
            $errors[] = "Le mot de passe est requis.";
        } elseif (strlen($password) < $minLen) {
            $errors[] = "{$minLen} caractères minimum.";
        }

        return $errors;
    }

    /**
     * Valider un entier
     */
    public static function validateInteger($value, int $minVal = 0, int $maxVal = PHP_INT_MAX): array
    {
        $errors = [];

        if (!is_numeric($value) || (int)$value != $value) {
            $errors[] = "Nombre entier requis.";
        } elseif ((int)$value < $minVal) {
            $errors[] = "Minimum {$minVal} requis.";
        } elseif ((int)$value > $maxVal) {
            $errors[] = "Maximum {$maxVal} autorisé.";
        }

        return $errors;
    }

    /**
     * Valider une énumération
     */
    public static function validateEnum(string $value, array $allowedValues): array
    {
        $errors = [];

        if (!in_array($value, $allowedValues, true)) {
            $errors[] = "Valeur non autorisée.";
        }

        return $errors;
    }

    /**
     * Sanitiser une chaîne pour l'affichage HTML
     */
    public static function sanitizeForDisplay(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
