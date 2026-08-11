<?php
// ============================================
// TaskFlow — Couche données : commentaires
// ============================================

require_once __DIR__ . '/../../config/db.php';

class CommentDB
{
    /**
     * Créer un commentaire
     */
    public static function create(int $taskId, int $userId, string $content): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO COMMENTAIRE (COM_TAC_ID, COM_UTI_ID, COM_CONTENU)
             VALUES (:task_id, :user_id, :content)'
        );

        $stmt->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
            'content' => $content,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Récupérer un commentaire par ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COM_ID AS id, COM_TAC_ID AS task_id, COM_UTI_ID AS user_id, COM_CONTENU AS content, COM_DATECREATION AS created_at
             FROM COMMENTAIRE
             WHERE COM_ID = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Récupérer les commentaires d'une tâche avec pagination
     */
    public static function findByTaskId(int $taskId, int $page = 1, int $perPage = 50): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            'SELECT c.COM_ID AS id, c.COM_TAC_ID AS task_id, c.COM_UTI_ID AS user_id, c.COM_CONTENU AS content, c.COM_DATECREATION AS created_at,
                u.UTI_NOMUTILISATEUR AS username
             FROM COMMENTAIRE c
             INNER JOIN UTILISATEUR u ON c.COM_UTI_ID = u.UTI_ID
             WHERE c.COM_TAC_ID = :task_id
             ORDER BY c.COM_DATECREATION ASC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $stmt->execute(['task_id' => $taskId]);

        return $stmt->fetchAll();
    }

    /**
     * Compter les commentaires d'une tâche
     */
    public static function countByTaskId(int $taskId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM COMMENTAIRE WHERE COM_TAC_ID = :task_id');
        $stmt->execute(['task_id' => $taskId]);

        $row = $stmt->fetch();

        return (int) ($row['count'] ?? 0);
    }

    /**
     * Mettre à jour un commentaire
     */
    public static function update(int $id, string $content): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE COMMENTAIRE SET COM_CONTENU = :content WHERE COM_ID = :id');

        return $stmt->execute(['id' => $id, 'content' => $content]);
    }

    /**
     * Supprimer un commentaire
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM COMMENTAIRE WHERE COM_ID = :id');

        return $stmt->execute(['id' => $id]);
    }
}
