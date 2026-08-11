<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/managers/AuthManager.php';
require_once __DIR__ . '/../../src/managers/ProjectManager.php';
require_once __DIR__ . '/../../src/db/ProjectDB.php';
require_once __DIR__ . '/../../src/db/TaskDB.php';

AuthManager::requireLogin();

$user = AuthManager::currentUser();

// Récupérer les paramètres de pagination et recherche
$page = (int) ($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$perPage = 20;

// Récupérer les projets
$projects = ProjectDB::findAllByUserWithPagination((int) $user['id'], $page, $perPage, $search ?: null);
$projectCount = ProjectDB::countByUser((int) $user['id'], $search ?: null);
$totalTasks = TaskDB::countByUser((int) $user['id']);
$completedTasks = TaskDB::countByUser((int) $user['id'], 'terminee');
$pendingTasks = max(0, $totalTasks - $completedTasks);
$completionRate = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;
$totalPages = ceil($projectCount / $perPage);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — TaskFlow</title>
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
                <a href="project_create.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouveau projet</a>
                <a href="logout.php" class="btn btn-secondary"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
            </nav>
        </div>
    </header>

    <main class="page-content">
        <div class="hero-card">
            <h1>Mes projets</h1>
            <p class="dashboard-intro">Vue d'ensemble de vos projets, de l'avancement des tâches et des éléments en attente.</p>
        </div>

        <section class="stats-grid" aria-label="Statistiques du tableau de bord">
            <article class="stat-card primary">
                <div class="stat-icon"><i class="fa-solid fa-diagram-project"></i></div>
                <p class="stat-value"><?= (int) $projectCount ?></p>
                <p class="stat-label">Projets visibles</p>
            </article>
            <article class="stat-card secondary">
                <div class="stat-icon"><i class="fa-solid fa-list-check"></i></div>
                <p class="stat-value"><?= (int) $totalTasks ?></p>
                <p class="stat-label">Tâches totales</p>
            </article>
            <article class="stat-card success">
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <p class="stat-value"><?= (int) $completedTasks ?></p>
                <p class="stat-label">Tâches terminées</p>
            </article>
            <article class="stat-card warning">
                <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
                <p class="stat-value"><?= (int) $completionRate ?>%</p>
                <p class="stat-label"><?= (int) $pendingTasks ?> en attente</p>
            </article>
        </section>

        <div class="dashboard-toolbar">
            <form method="get" action="dashboard.php" class="dashboard-search">
                <div class="form-group" style="margin: 0;">
                    <label for="search">Rechercher</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nom ou description du projet...">
                </div>

                <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</button>
                <?php if ($search): ?>
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-rotate-left"></i> Réinitialiser</a>
                <?php endif; ?>
            </form>
        </div>

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
                                <p><?= htmlspecialchars(substr($project['description'], 0, 150)) ?>
                                   <?= strlen($project['description']) > 150 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            <div class="project-meta-row">
                                <span class="project-meta"><i class="fa-regular fa-calendar"></i> Créé le <?= date('d/m/Y', strtotime($project['created_at'])) ?></span>
                                <span class="badge badge-neutral"><i class="fa-solid fa-arrow-right"></i> Ouvrir</span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-links">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p === $page): ?>
                            <strong><?= $p ?></strong>
                        <?php else: ?>
                            <a href="dashboard.php?page=<?= $p ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
