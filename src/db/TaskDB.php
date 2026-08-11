<?php
// ============================================
// TaskFlow — Couche données : tasks
// ============================================

require_once __DIR__ . '/../../config/db.php';

class TaskDB
{
    /**
     * Créer une tâche
     */
    public static function create(int $projectId, int $ownerId, string $title, string $description, string $priority, ?string $dueDate = null): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO TACHE (TAC_PRO_ID, TAC_UTI_ID, TAC_TITRE, TAC_DESCRIPTION, TAC_PRIORITE, TAC_DATEECHEANCE, TAC_STATUT, TAC_ESTACTIVE)
             VALUES (:project_id, :user_id, :title, :description, :priority, :due_date, :status, 1)'
        );

        $stmt->execute([
            'project_id' => $projectId,
            'user_id'    => $ownerId,
            'title'      => $title,
            'description' => $description,
            'priority'   => $priority,
            'due_date'   => $dueDate,
            'status'     => 'a_faire',
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Récupérer une tâche par ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT TAC_ID AS id, TAC_PRO_ID AS project_id, TAC_UTI_ID AS user_id, TAC_TITRE AS title, TAC_DESCRIPTION AS description, TAC_PRIORITE AS priority, TAC_STATUT AS status, TAC_DATEECHEANCE AS due_date, TAC_ESTACTIVE AS is_active, TAC_DATECREATION AS created_at
             FROM TACHE
             WHERE TAC_ID = :id AND TAC_ESTACTIVE = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Récupérer les tâches d'un projet avec pagination
     */
    public static function findByProjectId(int $projectId, int $page = 1, int $perPage = 20, ?string $status = null, ?string $priority = null): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $whereClause = 'WHERE TAC_PRO_ID = :project_id AND TAC_ESTACTIVE = 1';
        $params = ['project_id' => $projectId];

        if ($status) {
            $whereClause .= ' AND TAC_STATUT = :status';
            $params['status'] = $status;
        }

        if ($priority) {
            $whereClause .= ' AND TAC_PRIORITE = :priority';
            $params['priority'] = $priority;
        }

        $stmt = $pdo->prepare(
            "SELECT TAC_ID AS id, TAC_PRO_ID AS project_id, TAC_UTI_ID AS user_id, TAC_TITRE AS title, TAC_DESCRIPTION AS description, TAC_PRIORITE AS priority, TAC_STATUT AS status, TAC_DATEECHEANCE AS due_date, TAC_ESTACTIVE AS is_active, TAC_DATECREATION AS created_at
             FROM TACHE
             {$whereClause}
             ORDER BY TAC_DATEECHEANCE ASC, TAC_DATECREATION DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Compter les tâches d'un projet
     */
    public static function countByProjectId(int $projectId, ?string $status = null, ?string $priority = null): int
    {
        $pdo = Database::getConnection();

        $whereClause = 'WHERE TAC_PRO_ID = :project_id AND TAC_ESTACTIVE = 1';
        $params = ['project_id' => $projectId];

        if ($status) {
            $whereClause .= ' AND TAC_STATUT = :status';
            $params['status'] = $status;
        }

        if ($priority) {
            $whereClause .= ' AND TAC_PRIORITE = :priority';
            $params['priority'] = $priority;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TACHE {$whereClause}");
        $stmt->execute($params);

        $row = $stmt->fetch();

        return (int) ($row['count'] ?? 0);
    }

    /**
     * Compter les tâches visibles pour un utilisateur sur ses projets
     */
    public static function countByUser(int $userId, ?string $status = null): int
    {
        $pdo = Database::getConnection();

        $whereClause = 'WHERE pm.PRM_UTI_ID = :user_id AND t.TAC_ESTACTIVE = 1';
        $params = ['user_id' => $userId];

        if ($status) {
            $whereClause .= ' AND t.TAC_STATUT = :status';
            $params['status'] = $status;
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(DISTINCT t.TAC_ID) AS count
             FROM TACHE t
             INNER JOIN PROJET_MEMBRE pm ON t.TAC_PRO_ID = pm.PRM_PRO_ID
             ' . $whereClause
        );
        $stmt->execute($params);

        $row = $stmt->fetch();

        return (int) ($row['count'] ?? 0);
    }

    /**
     * Mettre à jour une tâche
     */
    public static function update(int $id, string $title, string $description, string $priority, string $status, ?string $dueDate = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE TACHE SET TAC_TITRE = :title, TAC_DESCRIPTION = :description, TAC_PRIORITE = :priority, TAC_STATUT = :status, TAC_DATEECHEANCE = :due_date
             WHERE TAC_ID = :id'
        );

        return $stmt->execute([
            'id'          => $id,
            'title'       => $title,
            'description' => $description,
            'priority'    => $priority,
            'status'      => $status,
            'due_date'    => $dueDate,
        ]);
    }

    /**
     * Archiver (soft delete) une tâche
     */
    public static function archive(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE TACHE SET TAC_ESTACTIVE = 0 WHERE TAC_ID = :id');

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Assigner une tâche à un utilisateur
     */
    public static function assignTo(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE TACHE SET TAC_UTI_ID = :user_id WHERE TAC_ID = :id');

        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    /**
     * Récupérer les tâches assignées à un utilisateur dans un projet
     */
    public static function findByUserAndProject(int $userId, int $projectId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            'SELECT TAC_ID AS id, TAC_PRO_ID AS project_id, TAC_UTI_ID AS user_id, TAC_TITRE AS title, TAC_DESCRIPTION AS description, TAC_PRIORITE AS priority, TAC_STATUT AS status, TAC_DATEECHEANCE AS due_date, TAC_ESTACTIVE AS is_active, TAC_DATECREATION AS created_at
             FROM TACHE
             WHERE TAC_UTI_ID = :user_id AND TAC_PRO_ID = :project_id AND TAC_ESTACTIVE = 1
             ORDER BY TAC_DATEECHEANCE ASC, TAC_DATECREATION DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $stmt->execute(['user_id' => $userId, 'project_id' => $projectId]);

        return $stmt->fetchAll();
    }
}
