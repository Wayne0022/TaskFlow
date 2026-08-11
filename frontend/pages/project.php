<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/managers/AuthManager.php';
require_once __DIR__ . '/../../src/managers/ProjectManager.php';
require_once __DIR__ . '/../../src/db/TaskDB.php';

AuthManager::requireLogin();

$user = AuthManager::currentUser();
$projectId = (int) ($_GET['id'] ?? 0);

// Vérifier que l'utilisateur a accès au projet
if (!ProjectManager::canAccess($projectId, (int) $user['id'])) {
    die('Accès refusé.');
}

$project = ProjectManager::getProjectWithMembers($projectId);
if (!$project) {
    die('Projet non trouvé.');
}

// Récupérer les paramètres de pagination et filtres
$page = (int) ($_GET['page'] ?? 1);
$status = $_GET['status'] ?? null;
$priority = $_GET['priority'] ?? null;
$search = trim($_GET['search'] ?? '');
$perPage = 20;

// Récupérer les tâches
$tasks = TaskDB::findByProjectId($projectId, $page, $perPage, $status, $priority);
$taskCount = TaskDB::countByProjectId($projectId, $status, $priority);
$totalPages = ceil($taskCount / $perPage);

// Filtre de recherche côté client (simple)
if ($search) {
    $tasks = array_filter($tasks, function($task) use ($search) {
        return stripos($task['title'], $search) !== false || stripos($task['description'], $search) !== false;
    });
}

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
    <title><?= htmlspecialchars($project['project']['name']) ?> — TaskFlow</title>
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
        <a href="dashboard.php" class="page-back-link"><i class="fa-solid fa-arrow-left"></i> Retour au dashboard</a>

        <section class="hero-card">
            <h1><?= htmlspecialchars($project['project']['name']) ?></h1>

            <?php if ($project['project']['description']): ?>
                <p><?= htmlspecialchars($project['project']['description']) ?></p>
            <?php endif; ?>

            <div class="project-meta-row">
                <span class="project-meta"><i class="fa-regular fa-user"></i> <?= count($project['members']) ?> membre(s)</span>
                <span class="project-meta"><i class="fa-regular fa-calendar"></i> Créé le <?= date('d/m/Y', strtotime($project['project']['created_at'])) ?></span>
            </div>
        </section>

        <section class="form-card">
            <div class="section-heading">
                <h3>Membres du projet</h3>
            </div>
            <div class="stack-grid">
                <?php foreach ($project['members'] as $member): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <span class="comment-author"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($member['username']) ?></span>
                            <span class="badge badge-neutral"><?= htmlspecialchars($member['role']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="section-heading">
            <h2>Tâches</h2>
            <a href="task_create.php?project_id=<?= $projectId ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle tâche</a>
        </div>

        <section class="filters-panel">
            <form method="get" action="project.php" class="filters-form">
                <input type="hidden" name="id" value="<?= $projectId ?>">

                <div class="form-group" style="margin: 0;">
                    <label for="search">Rechercher</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Titre ou description...">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label for="status">Statut</label>
                    <select id="status" name="status">
                        <option value="">-- Tous --</option>
                        <option value="a_faire" <?= $status === 'a_faire' ? 'selected' : '' ?>>À faire</option>
                        <option value="en_cours" <?= $status === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="terminee" <?= $status === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label for="priority">Priorité</label>
                    <select id="priority" name="priority">
                        <option value="">-- Tous --</option>
                        <option value="faible" <?= $priority === 'faible' ? 'selected' : '' ?>>Faible</option>
                        <option value="moyenne" <?= $priority === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                        <option value="haute" <?= $priority === 'haute' ? 'selected' : '' ?>>Haute</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filtrer</button>
            </form>
        </section>

        <?php if ($tasks): ?>
            <ul class="task-list">
                <?php foreach ($tasks as $task): ?>
                    <li class="task-item">
                        <div class="task-meta-row" style="margin-bottom: 0.85rem;">
                            <h4 style="margin: 0;">
                                <a href="task.php?project_id=<?= $projectId ?>&id=<?= $task['id'] ?>">
                                    <?= htmlspecialchars($task['title']) ?>
                                </a>
                            </h4>
                            <span class="status-badge <?= htmlspecialchars($task['status']) ?>">
                                <i class="fa-solid fa-flag"></i> <?= htmlspecialchars($statusLabels[$task['status']] ?? $task['status']) ?>
                            </span>
                        </div>

                        <?php if ($task['description']): ?>
                            <p>
                                <?= htmlspecialchars(substr($task['description'], 0, 100)) ?>
                                <?= strlen($task['description']) > 100 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>

                        <div class="task-meta-row">
                            <span class="priority-badge <?= htmlspecialchars($task['priority']) ?>">
                                <i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($priorityLabels[$task['priority']] ?? $task['priority']) ?>
                            </span>
                            <?php if ($task['due_date']): ?>
                                <span class="task-meta">
                                    <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($totalPages > 1): ?>
                <div class="pagination-links">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p === $page): ?>
                            <strong><?= $p ?></strong>
                        <?php else: ?>
                            <a href="project.php?id=<?= $projectId ?>&page=<?= $p ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $priority ? '&priority=' . urlencode($priority) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="empty-state">Aucune tâche correspondant à vos critères.</p>
        <?php endif; ?>
    </main>
</body>
</html>
