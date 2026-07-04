<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/managers/AuthManager.php';

if (AuthManager::currentUser() !== null) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $errors['email'] = 'L\'email est requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email invalide.';
    }

    if ($password === '') {
        $errors['password'] = 'Le mot de passe est requis.';
    }

    if ($errors === [] && !AuthManager::login($email, $password)) {
        $errors['general'] = 'Email ou mot de passe incorrect.';
    }

    if ($errors === []) {
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — TaskFlow</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main class="auth-page">
        <div class="auth-card">
            <h1>TaskFlow</h1>
            <p class="auth-subtitle">Connectez-vous à votre compte</p>

            <?php if (isset($_GET['registered'])): ?>
                <p class="form-success">Compte créé. Vous pouvez vous connecter.</p>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <p class="form-error"><?= htmlspecialchars($errors['general']) ?></p>
            <?php endif; ?>

            <form method="post" action="login.php" novalidate>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        autocomplete="email"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['password']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Se connecter</button>
            </form>

            <p class="auth-footer">
                Pas encore de compte ?
                <a href="register.php"><strong>S'inscrire</strong></a>
            </p>
        </div>
    </main>
</body>
</html>
