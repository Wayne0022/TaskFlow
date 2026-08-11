<?php
// ============================================
// TaskFlow — Couche données : projects
// ============================================

require_once __DIR__ . '/../../config/db.php';

class ProjectDB
{
    public static function create(string $name, string $description, int $ownerId): int
    {
        $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO PROJET (PRO_NOM, PRO_DESCRIPTION, PRO_PROPRIETAIRE_ID)
                 VALUES (:name, :description, :owner_id)'
            );
            $stmt->execute([
                'name'        => $name,
                'description' => $description,
                'owner_id'    => $ownerId,
            ]);

        return (int) $pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT PRO_ID AS id, PRO_NOM AS name, PRO_DESCRIPTION AS description, PRO_PROPRIETAIRE_ID AS owner_id, PRO_DATECREATION AS created_at FROM PROJET WHERE PRO_ID = :id LIMIT 1');
            $stmt->execute(['id' => $id]);

            $row = $stmt->fetch();

            return $row ?: null;
    }

    public static function findAllByUser(int $userId): array
    {
        $pdo = Database::getConnection();
            // refactored to local implementation matching schema
            $stmt = $pdo->prepare(
                'SELECT pr.PRO_ID AS id, pr.PRO_NOM AS name, pr.PRO_DESCRIPTION AS description, pr.PRO_PROPRIETAIRE_ID AS owner_id, pr.PRO_DATECREATION AS created_at
                 FROM PROJET pr
                 INNER JOIN PROJET_MEMBRE pm ON pr.PRO_ID = pm.PRM_PRO_ID
                 WHERE pm.PRM_UTI_ID = :user_id
                 ORDER BY pr.PRO_DATECREATION DESC'
            );
            $stmt->execute(['user_id' => $userId]);

            return $stmt->fetchAll();
    }

    /**
     * Récupérer les projets d'un utilisateur avec pagination et recherche
     */
    public static function findAllByUserWithPagination(int $userId, int $page = 1, int $perPage = 20, ?string $search = null): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $whereClause = '';
        $params = ['user_id' => $userId];

        if ($search) {
            $whereClause = ' AND (pr.PRO_NOM LIKE :search OR pr.PRO_DESCRIPTION LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $stmt = $pdo->prepare(
            'SELECT pr.PRO_ID AS id, pr.PRO_NOM AS name, pr.PRO_DESCRIPTION AS description, pr.PRO_PROPRIETAIRE_ID AS owner_id, pr.PRO_DATECREATION AS created_at
             FROM PROJET pr
             INNER JOIN PROJET_MEMBRE pm ON pr.PRO_ID = pm.PRM_PRO_ID
             WHERE pm.PRM_UTI_ID = :user_id' . $whereClause . '
             ORDER BY pr.PRO_DATECREATION DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Compter les projets d'un utilisateur
     */
    public static function countByUser(int $userId, ?string $search = null): int
    {
        $pdo = Database::getConnection();

        $whereClause = '';
        $params = ['user_id' => $userId];

        if ($search) {
            $whereClause = ' AND (pr.PRO_NOM LIKE :search OR pr.PRO_DESCRIPTION LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) as count
             FROM PROJET pr
             INNER JOIN PROJET_MEMBRE pm ON pr.PRO_ID = pm.PRM_PRO_ID
             WHERE pm.PRM_UTI_ID = :user_id' . $whereClause
        );
        $stmt->execute($params);

        $row = $stmt->fetch();

        return (int) ($row['count'] ?? 0);
    }

    public static function update(int $id, string $name, string $description): bool
    {
        $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'UPDATE PROJET SET PRO_NOM = :name, PRO_DESCRIPTION = :description WHERE PRO_ID = :id'
            );

            return $stmt->execute([
                'id'          => $id,
                'name'        => $name,
                'description' => $description,
            ]);
    }
}
