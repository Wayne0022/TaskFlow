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
            'INSERT INTO projects (name, description, owner_id)
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
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findAllByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT p.*
             FROM projects p
             INNER JOIN project_members pm ON p.id = pm.project_id
             WHERE pm.user_id = :user_id
             ORDER BY p.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function update(int $id, string $name, string $description): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE projects SET name = :name, description = :description WHERE id = :id'
        );

        return $stmt->execute([
            'id'          => $id,
            'name'        => $name,
            'description' => $description,
        ]);
    }
}
