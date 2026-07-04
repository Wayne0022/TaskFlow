<?php
// ============================================
// TaskFlow — Couche données : users
// ============================================

require_once __DIR__ . '/../../config/db.php';

class UserDB
{
    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(string $username, string $email, string $passwordHash): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password, avatar_seed)
             VALUES (:username, :email, :password, :avatar_seed)'
        );
        $stmt->execute([
            'username'    => $username,
            'email'       => $email,
            'password'    => $passwordHash,
            'avatar_seed' => $username,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function findAllExcept(int $excludeUserId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, username, email, avatar_seed
             FROM users
             WHERE id != :exclude_id
             ORDER BY username ASC'
        );
        $stmt->execute(['exclude_id' => $excludeUserId]);

        return $stmt->fetchAll();
    }
}
