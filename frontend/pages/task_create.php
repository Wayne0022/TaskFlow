<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/managers/AuthManager.php';
require_once __DIR__ . '/../../src/managers/ProjectManager.php';
require_once __DIR__ . '/../../src/db/TaskDB.php';

AuthManager::requireLogin();

$user = AuthManager::currentUser();
$projectId = (int) ($_GET['project_id'] ?? 0);

// Vérifier que l'utilisateur a accès au projet
if (!ProjectManager::canAccess($projectId, (int) $user['id'])) {
    die('Accès refusé.');
}

$project = ProjectManager::getProjectWithMembers($projectId);
if (!$project) {
    die('Projet non trouvé.');
}

$errors = [];
$title = '';
$description = '';
$priority = 'moyenne';
$dueDate = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Token de sécurité invalide.';
    }

    if (empty($errors)) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'moyenne';
        $dueDate = $_POST['due_date'] ?? null;

        $titleErrors = SecurityHelper::validateString($title, 1, 150);
        if ($titleErrors) {
            $errors['title'] = implode(' ', $titleErrors);
        }

        $priorityErrors = SecurityHelper::validateEnum($priority, ['faible', 'moyenne', 'haute']);
        if ($priorityErrors) {
            $errors['priority'] = implode(' ', $priorityErrors);
        }

        if (empty($errors)) {
            $taskId = TaskDB::create($projectId, (int) $user['id'], $title, $description, $priority, $dueDate);
            header('Location: task.php?project_id=' . $projectId . '&id=' . $taskId . '&created=1');
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
    <title>Nouvelle tâche — TaskFlow</title>
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
        <a href="project.php?id=<?= $projectId ?>">&larr; Retour au projet</a>

        <h1>Nouvelle tâche</h1>

        <?php if (isset($errors['general'])): ?>
            <p class="form-error"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <form method="post" action="task_create.php?project_id=<?= $projectId ?>" class="form-card" novalidate>
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">

            <div class="form-group">
                <label for="title">Titre</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?= htmlspecialchars($title) ?>"
                    maxlength="150"
                    required
                >
                <?php if (isset($errors['title'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['title']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="6"><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="priority">Priorité</label>
                    <select id="priority" name="priority">
                        <option value="faible" <?= $priority === 'faible' ? 'selected' : '' ?>>Faible</option>
                        <option value="moyenne" <?= $priority === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                        <option value="haute" <?= $priority === 'haute' ? 'selected' : '' ?>>Haute</option>
                    </select>
                    <?php if (isset($errors['priority'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['priority']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="due_date">Date d'échéance</label>
                    <input type="date" id="due_date" name="due_date" value="<?= $dueDate ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-circle-plus"></i> Créer la tâche</button>
                <a href="project.php?id=<?= $projectId ?>" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Annuler</a>
            </div>
        </form>
    </main>
</body>
</html>
