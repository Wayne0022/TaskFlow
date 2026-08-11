<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/managers/AuthManager.php';
require_once __DIR__ . '/../../src/managers/ProjectManager.php';
require_once __DIR__ . '/../../src/db/UserDB.php';

AuthManager::requireLogin();

$user = AuthManager::currentUser();
$availableUsers = UserDB::findAllExcept((int) $user['id']);

$errors = [];
$name = '';
$description = '';
$selectedMembers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valider le token CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!SecurityHelper::validateCsrfToken($csrfToken)) {
        $errors['general'] = 'Token de sécurité invalide.';
    }

    if (empty($errors)) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $selectedMembers = array_map('intval', $_POST['members'] ?? []);

        $result = ProjectManager::createProject(
            $name,
            $description,
            (int) $user['id'],
            $selectedMembers
        );

        if (isset($result['errors'])) {
            $errors = $result['errors'];
        } else {
            header('Location: project.php?id=' . $result['project_id']);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau projet — TaskFlow</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="../js/theme.js" defer></script>
</head>
<body>
    <header class="page-header">
        <div class="page-header-inner">
            <a href="dashboard.php" class="logo">TaskFlow</a>
            <nav>
                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Basculer le thème" aria-pressed="false">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <span class="nav-user"><?= htmlspecialchars($user['username']) ?></span>
                <a href="logout.php" class="btn btn-secondary"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
            </nav>
        </div>
    </header>

    <main class="page-content">
        <h1>Nouveau projet</h1>

        <?php if (isset($errors['general'])): ?>
            <p class="form-error"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <form method="post" action="project_create.php" class="form-card" novalidate>
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
            <div class="form-group">
                <label for="name">Nom du projet</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($name) ?>"
                    maxlength="100"
                    required
                >
                <?php if (isset($errors['name'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>
            </div>

            <?php if ($availableUsers !== []): ?>
                <fieldset class="form-group">
                    <legend>Ajouter des membres</legend>
                    <ul class="member-checklist">
                        <?php foreach ($availableUsers as $member): ?>
                            <li>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="members[]"
                                        value="<?= (int) $member['id'] ?>"
                                        <?= in_array((int) $member['id'], $selectedMembers, true) ? 'checked' : '' ?>
                                    >
                                    <?= htmlspecialchars($member['username']) ?>
                                    <span class="member-email">(<?= htmlspecialchars($member['email']) ?>)</span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </fieldset>
            <?php else: ?>
                <p class="form-hint">Aucun autre utilisateur disponible. Vous serez ajouté automatiquement comme propriétaire.</p>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-folder-plus"></i> Créer le projet</button>
                <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Annuler</a>
            </div>
        </form>
    </main>
</body>
</html>
