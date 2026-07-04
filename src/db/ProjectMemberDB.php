<?php
// ============================================
// TaskFlow — Couche données : project_members
// ============================================

require_once __DIR__ . '/../../config/db.php';

class ProjectMemberDB
{
    public static function addMember(int $projectId, int $userId, string $role): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO project_members (project_id, user_id, role)
             VALUES (:project_id, :user_id, :role)'
        );

        return $stmt->execute([
            'project_id' => $projectId,
            'user_id'    => $userId,
            'role'       => $role,
        ]);
    }

    public static function removeMember(int $projectId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM project_members
             WHERE project_id = :project_id AND user_id = :user_id'
        );

        return $stmt->execute([
            'project_id' => $projectId,
            'user_id'    => $userId,
        ]);
    }

    public static function getMembersByProject(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT pm.id, pm.project_id, pm.user_id, pm.role, pm.joined_at,
                    u.username, u.email, u.avatar_seed
             FROM project_members pm
             INNER JOIN users u ON pm.user_id = u.id
             WHERE pm.project_id = :project_id
             ORDER BY pm.role DESC, u.username ASC'
        );
        $stmt->execute(['project_id' => $projectId]);

        return $stmt->fetchAll();
    }

    public static function isMember(int $projectId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT 1 FROM project_members
             WHERE project_id = :project_id AND user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([
            'project_id' => $projectId,
            'user_id'    => $userId,
        ]);

        return (bool) $stmt->fetch();
    }
}
