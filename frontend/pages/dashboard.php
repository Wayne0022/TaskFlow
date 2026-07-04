<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/managers/AuthManager.php';
require_once __DIR__ . '/../../src/managers/ProjectManager.php';

AuthManager::requireLogin();

$user = AuthManager::currentUser();
$projects = ProjectManager::getUserProjects((int) $user['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — TaskFlow</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="page-header">
        <div class="page-header-inner">
            <a href="dashboard.php" class="logo">TaskFlow</a>
            <nav>
                <span class="nav-user"><?= htmlspecialchars($user['username']) ?></span>
                <a href="project_create.php" class="btn btn-primary">Nouveau projet</a>
                <a href="logout.php" class="btn btn-secondary">Déconnexion</a>
            </nav>
        </div>
    </header>

    <main class="page-content">
        <h1>Mes projets</h1>

        <?php if ($projects === []): ?>
            <p class="empty-state">
                Aucun projet pour le moment.
                <a href="project_create.php">Créer votre premier projet</a>
            </p>
        <?php else: ?>
            <ul class="project-list">
                <?php foreach ($projects as $project): ?>
                    <li class="project-card">
                        <a href="project.php?id=<?= (int) $project['id'] ?>">
                            <h2><?= htmlspecialchars($project['name']) ?></h2>
                            <?php if ($project['description']): ?>
                                <p><?= htmlspecialchars($project['description']) ?></p>
                            <?php endif; ?>
                            <span class="project-meta">
                                Créé le <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>
</body>
</html>
