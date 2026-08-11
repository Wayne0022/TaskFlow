<?php
// ============================================
// TaskFlow — Chargeur de variables d'environnement
// ============================================
// Appelé UNE FOIS en haut de db.php et app.php
// Lit le fichier .env et charge les variables via putenv()

function loadEnv(string $path): void {
    if (!file_exists($path)) {
        die('.env introuvable. Copie .env.example en .env et remplis les valeurs.');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignore les commentaires
        if (str_starts_with(trim($line), '#')) continue;

        // Parse KEY=VALUE
        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Ignore les commentaires en fin de ligne : KEY=value # commentaire
        $value = preg_replace('/\s+#.*$/', '', $value);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        // Ne pas écraser une variable déjà définie (priorité au système)
        if (!getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Chargement automatique dès l'inclusion de ce fichier
loadEnv(__DIR__ . '/.env');
