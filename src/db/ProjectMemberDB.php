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

        // Map generic role names to schema-specific enum values
        if ($role === 'owner') {
            $dbRole = 'proprietaire';
        } elseif ($role === 'member') {
            $dbRole = 'membre';
        } else {
            $dbRole = $role;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO PROJET_MEMBRE (PRM_PRO_ID, PRM_UTI_ID, PRM_ROLE)
             VALUES (:project_id, :user_id, :role)'
        );

        return $stmt->execute([
            'project_id' => $projectId,
            'user_id'    => $userId,
            'role'       => $dbRole,
        ]);
    }

    public static function removeMember(int $projectId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM PROJET_MEMBRE
             WHERE PRM_PRO_ID = :project_id AND PRM_UTI_ID = :user_id'
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
            'SELECT pm.PRM_ID AS id, pm.PRM_PRO_ID AS project_id, pm.PRM_UTI_ID AS user_id, pm.PRM_ROLE AS role, pm.PRM_DATEADHESION AS joined_at,
                u.UTI_NOMUTILISATEUR AS username, u.UTI_EMAIL AS email
             FROM PROJET_MEMBRE pm
             INNER JOIN UTILISATEUR u ON pm.PRM_UTI_ID = u.UTI_ID
             WHERE pm.PRM_PRO_ID = :project_id
             ORDER BY pm.PRM_ROLE DESC, u.UTI_NOMUTILISATEUR ASC'
        );
        $stmt->execute(['project_id' => $projectId]);

        return $stmt->fetchAll();
    }

    public static function getAvailableUsersForProject(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT u.UTI_ID AS id, u.UTI_NOMUTILISATEUR AS username, u.UTI_EMAIL AS email
             FROM UTILISATEUR u
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM PROJET_MEMBRE pm
                 WHERE pm.PRM_PRO_ID = :project_id
                   AND pm.PRM_UTI_ID = u.UTI_ID
             )
             ORDER BY u.UTI_NOMUTILISATEUR ASC'
        );
        $stmt->execute(['project_id' => $projectId]);

        return $stmt->fetchAll();
    }

    public static function isMember(int $projectId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT 1 FROM PROJET_MEMBRE
             WHERE PRM_PRO_ID = :project_id AND PRM_UTI_ID = :user_id
             LIMIT 1'
        );
        $stmt->execute([
            'project_id' => $projectId,
            'user_id'    => $userId,
        ]);

        return (bool) $stmt->fetch();
    }
}
