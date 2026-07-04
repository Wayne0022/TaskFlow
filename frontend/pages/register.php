<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/managers/AuthManager.php';

if (AuthManager::currentUser() !== null) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($passwordConfirm === '') {
        $errors['password_confirm'] = 'Confirmez votre mot de passe.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
    }

    if (!isset($errors['password_confirm'])) {
        $result = AuthManager::register($username, $email, $password);

        if (isset($result['errors'])) {
            $errors = array_merge($errors, $result['errors']);
        }
    }

    if ($errors === []) {
        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — TaskFlow</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main class="auth-page">
        <div class="auth-card">
            <h1>TaskFlow</h1>
            <p class="auth-subtitle">Créez votre compte pour accéder à TaskFlow</p>

            <?php if (isset($_GET['error'])): ?>
                <p class="form-error">Une erreur est survenue. Réessayez.</p>
            <?php endif; ?>

            <form method="post" action="register.php" novalidate>
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($username) ?>"
                        autocomplete="username"
                        maxlength="50"
                        required
                    >
                    <?php if (isset($errors['username'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['username']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        autocomplete="email"
                        maxlength="100"
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
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['password']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmer le mot de passe</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                    <?php if (isset($errors['password_confirm'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['password_confirm']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">S'inscrire</button>
            </form>

            <p class="auth-footer">
                Déjà un compte ?
                <a href="login.php">Se connecter</a>
            </p>
        </div>
    </main>
</body>
</html>
