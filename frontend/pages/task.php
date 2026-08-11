<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/managers/AuthManager.php';
require_once __DIR__ . '/../../src/managers/ProjectManager.php';
require_once __DIR__ . '/../../src/db/TaskDB.php';
require_once __DIR__ . '/../../src/db/CommentDB.php';

AuthManager::requireLogin();

$user = AuthManager::currentUser();
$projectId = (int) ($_GET['project_id'] ?? 0);
$taskId = (int) ($_GET['id'] ?? 0);

// Vérifier que l'utilisateur a accès au projet
if (!ProjectManager::canAccess($projectId, (int) $user['id'])) {
    die('Accès refusé.');
}

$task = TaskDB::findById($taskId);
if (!$task || $task['project_id'] !== $projectId) {
    die('Tâche non trouvée.');
}

$errors = [];
$commentErrors = [];

// Gérer la mise à jour de la tâche
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_task') {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors['general'] = 'Token de sécurité invalide.';
        }

        if (empty($errors)) {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $priority = $_POST['priority'] ?? 'moyenne';
            $status = $_POST['status'] ?? 'a_faire';
            $dueDate = $_POST['due_date'] ?? null;

            $titleErrors = SecurityHelper::validateString($title, 1, 150);
            if ($titleErrors) {
                $errors['title'] = implode(' ', $titleErrors);
            }

            $priorityErrors = SecurityHelper::validateEnum($priority, ['faible', 'moyenne', 'haute']);
            if ($priorityErrors) {
                $errors['priority'] = implode(' ', $priorityErrors);
            }

            $statusErrors = SecurityHelper::validateEnum($status, ['a_faire', 'en_cours', 'terminee']);
            if ($statusErrors) {
                $errors['status'] = implode(' ', $statusErrors);
            }

            if (empty($errors)) {
                TaskDB::update($taskId, $title, $description, $priority, $status, $dueDate);
                header('Location: task.php?project_id=' . $projectId . '&id=' . $taskId . '&updated=1');
                exit;
            }
        }
    } elseif ($_POST['action'] === 'add_comment') {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $commentErrors['general'] = 'Token de sécurité invalide.';
        }

        if (empty($commentErrors)) {
            $content = trim($_POST['content'] ?? '');

            $contentErrors = SecurityHelper::validateString($content, 1, 5000);
            if ($contentErrors) {
                $commentErrors['content'] = implode(' ', $contentErrors);
            }

            if (empty($commentErrors)) {
                CommentDB::create($taskId, (int) $user['id'], $content);
                header('Location: task.php?project_id=' . $projectId . '&id=' . $taskId . '&comment_added=1');
                exit;
            }
        }
    } elseif ($_POST['action'] === 'archive_task') {
        if (SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            TaskDB::archive($taskId);
            header('Location: project.php?id=' . $projectId);
            exit;
        }
    } elseif ($_POST['action'] === 'assign_task') {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors['general'] = 'Token de sécurité invalide.';
        }

        if (empty($errors)) {
            $assigneeId = (int) ($_POST['assignee_id'] ?? 0);

            if ($assigneeId > 0) {
                TaskDB::assignTo($taskId, $assigneeId);
                header('Location: task.php?project_id=' . $projectId . '&id=' . $taskId . '&assigned=1');
                exit;
            }
        }
    }
}

// Rechargé la tâche pour afficher les mises à jour
$task = TaskDB::findById($taskId);
$comments = CommentDB::findByTaskId($taskId, 1, 50);
$project = ProjectManager::getProjectWithMembers($projectId);

$statusLabels = [
    'a_faire' => 'À faire',
    'en_cours' => 'En cours',
    'terminee' => 'Terminée',
];

$priorityLabels = [
    'faible' => 'Faible',
    'moyenne' => 'Moyenne',
    'haute' => 'Haute',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SecurityHelper::sanitizeForDisplay($task['title']) ?> — TaskFlow</title>
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
        <a href="project.php?id=<?= $projectId ?>" class="page-back-link"><i class="fa-solid fa-arrow-left"></i> Retour au projet</a>

        <section class="hero-card">
            <h1><?= SecurityHelper::sanitizeForDisplay($task['title']) ?></h1>
            <div class="project-meta-row">
                <span class="status-badge <?= htmlspecialchars($task['status']) ?>"><i class="fa-solid fa-flag"></i> <?= htmlspecialchars($statusLabels[$task['status']] ?? $task['status']) ?></span>
                <span class="priority-badge <?= htmlspecialchars($task['priority']) ?>"><i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($priorityLabels[$task['priority']] ?? $task['priority']) ?></span>
                <?php if ($task['due_date']): ?>
                    <span class="task-meta"><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($task['due_date'])) ?></span>
                <?php endif; ?>
            </div>
        </section>

        <?php if (isset($_GET['updated'])): ?>
            <p class="form-success"><i class="fa-solid fa-circle-check"></i> Tâche mise à jour avec succès.</p>
        <?php endif; ?>

        <?php if (isset($_GET['comment_added'])): ?>
            <p class="form-success"><i class="fa-solid fa-circle-check"></i> Commentaire ajouté.</p>
        <?php endif; ?>

        <?php if (isset($_GET['assigned'])): ?>
            <p class="form-success"><i class="fa-solid fa-circle-check"></i> Tâche assignée.</p>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <p class="form-error"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <div class="task-card">
            <form method="post" action="" class="form-card">
                <input type="hidden" name="action" value="update_task">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">

                <div class="form-group">
                    <label for="title">Titre</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($task['title']) ?>" maxlength="150" required>
                    <?php if (isset($errors['title'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['title']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="6"><?= htmlspecialchars($task['description']) ?></textarea>
                </div>

                <div class="stack-grid">
                    <div class="form-group">
                        <label for="priority">Priorité</label>
                        <select id="priority" name="priority">
                            <option value="faible" <?= $task['priority'] === 'faible' ? 'selected' : '' ?>>Faible</option>
                            <option value="moyenne" <?= $task['priority'] === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                            <option value="haute" <?= $task['priority'] === 'haute' ? 'selected' : '' ?>>Haute</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Statut</label>
                        <select id="status" name="status">
                            <option value="a_faire" <?= $task['status'] === 'a_faire' ? 'selected' : '' ?>>À faire</option>
                            <option value="en_cours" <?= $task['status'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="terminee" <?= $task['status'] === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="due_date">Date d'échéance</label>
                    <input type="date" id="due_date" name="due_date" value="<?= $task['due_date'] ?? '' ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
                </div>
            </form>

            <hr>

            <div class="section-heading">
                <h3>Assigner à un membre</h3>
            </div>
            <form method="post" action="" class="task-inline-form" style="grid-template-columns: minmax(0, 1fr) auto; align-items: end;">
                <input type="hidden" name="action" value="assign_task">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">

                <div class="form-group" style="margin: 0;">
                    <label for="assignee_id">Membre</label>
                    <select id="assignee_id" name="assignee_id">
                        <option value="">-- Non assignée --</option>
                        <?php foreach ($project['members'] as $member): ?>
                            <option value="<?= $member['user_id'] ?>" <?= $task['user_id'] === $member['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($member['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-user-plus"></i> Assigner</button>
            </form>

            <hr>

            <div class="section-heading">
                <h3>Commentaires</h3>
            </div>

            <?php if ($commentErrors): ?>
                <?php if (isset($commentErrors['general'])): ?>
                    <p class="form-error"><?= htmlspecialchars($commentErrors['general']) ?></p>
                <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="" class="form-card">
                <input type="hidden" name="action" value="add_comment">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">

                <div class="form-group">
                    <label for="content">Ajouter un commentaire</label>
                    <textarea id="content" name="content" rows="4" placeholder="Votre commentaire..." required></textarea>
                    <?php if (isset($commentErrors['content'])): ?>
                        <span class="field-error"><?= htmlspecialchars($commentErrors['content']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Poster</button>
            </form>

            <?php if ($comments): ?>
                <div class="comments-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <strong class="comment-author"><?= htmlspecialchars($comment['username']) ?></strong>
                                <small class="comment-date"><?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></small>
                            </div>
                            <p class="comment-content"><?= htmlspecialchars($comment['content']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-state" style="margin-top: 1rem;">Aucun commentaire pour le moment.</p>
            <?php endif; ?>

            <hr>

            <form method="post" action="" style="display: inline;">
                <input type="hidden" name="action" value="archive_task">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Archiver cette tâche ?')"><i class="fa-solid fa-box-archive"></i> Archiver la tâche</button>
            </form>
        </div>
    </main>
</body>
</html>
