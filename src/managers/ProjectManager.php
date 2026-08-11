<?php
// ============================================
// TaskFlow — Couche métier : projets
// ============================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../db/ProjectDB.php';
require_once __DIR__ . '/../db/ProjectMemberDB.php';
require_once __DIR__ . '/../db/UserDB.php';

class ProjectManager
{
    public static function createProject(
        string $name,
        string $description,
        int $ownerId,
        array $memberIds
    ): array {
        $errors = [];

        $name = trim($name);
        $description = trim($description);

        if ($name === '') {
            $errors['name'] = 'Le nom du projet est requis.';
        } elseif (strlen($name) > 100) {
            $errors['name'] = '100 caractères maximum.';
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $projectId = ProjectDB::create($name, $description, $ownerId);
            ProjectMemberDB::addMember($projectId, $ownerId, 'owner');

            foreach ($memberIds as $memberId) {
                $memberId = (int) $memberId;

                if ($memberId === $ownerId || $memberId <= 0) {
                    continue;
                }

                if (UserDB::findById($memberId) === null) {
                    continue;
                }

                ProjectMemberDB::addMember($projectId, $memberId, 'member');
            }

            $pdo->commit();

            return ['project_id' => $projectId];
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Project creation failed: ' . $e->getMessage());

            return ['errors' => ['general' => 'Impossible de créer le projet.']];
        }
    }

    public static function addMembersToProject(int $projectId, int $userId, array $memberIds): array
    {
        $project = ProjectDB::findById($projectId);

        if ($project === null) {
            return ['errors' => ['general' => 'Projet non trouvé.']];
        }

        if ((int) $project['owner_id'] !== $userId) {
            return ['errors' => ['general' => 'Seul le propriétaire peut ajouter des membres.']];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $addedCount = 0;
            $uniqueMemberIds = array_unique(array_map('intval', $memberIds));

            foreach ($uniqueMemberIds as $memberId) {
                if ($memberId <= 0 || $memberId === $userId) {
                    continue;
                }

                if (UserDB::findById($memberId) === null) {
                    continue;
                }

                if (ProjectMemberDB::isMember($projectId, $memberId)) {
                    continue;
                }

                if (ProjectMemberDB::addMember($projectId, $memberId, 'member')) {
                    $addedCount++;
                }
            }

            $pdo->commit();

            return ['success' => true, 'added_count' => $addedCount];
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Project member add failed: ' . $e->getMessage());

            return ['errors' => ['general' => 'Impossible d\'ajouter les membres.']];
        }
    }

    public static function getProjectWithMembers(int $projectId): ?array
    {
        $project = ProjectDB::findById($projectId);

        if ($project === null) {
            return null;
        }

        return [
            'project' => $project,
            'members' => ProjectMemberDB::getMembersByProject($projectId),
        ];
    }

    public static function getAvailableMembersForProject(int $projectId): array
    {
        return ProjectMemberDB::getAvailableUsersForProject($projectId);
    }

    public static function getUserProjects(int $userId): array
    {
        return ProjectDB::findAllByUser($userId);
    }

    public static function canAccess(int $projectId, int $userId): bool
    {
        return ProjectMemberDB::isMember($projectId, $userId);
    }
}
