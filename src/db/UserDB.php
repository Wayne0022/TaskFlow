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
        $stmt = $pdo->prepare('SELECT UTI_ID AS id, UTI_NOMUTILISATEUR AS username, UTI_EMAIL AS email, UTI_MOTDEPASSE AS password, UTI_DATECREATION AS created_at FROM UTILISATEUR WHERE UTI_EMAIL = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT UTI_ID AS id, UTI_NOMUTILISATEUR AS username, UTI_EMAIL AS email, UTI_MOTDEPASSE AS password, UTI_DATECREATION AS created_at FROM UTILISATEUR WHERE UTI_ID = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT UTI_ID AS id, UTI_NOMUTILISATEUR AS username, UTI_EMAIL AS email, UTI_MOTDEPASSE AS password, UTI_DATECREATION AS created_at FROM UTILISATEUR WHERE UTI_NOMUTILISATEUR = :username LIMIT 1');
        $stmt->execute(['username' => $username]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(string $username, string $email, string $passwordHash): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO UTILISATEUR (UTI_NOMUTILISATEUR, UTI_EMAIL, UTI_MOTDEPASSE)
             VALUES (:username, :email, :password)'
        );
        $stmt->execute([
            'username'    => $username,
            'email'       => $email,
            'password'    => $passwordHash,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function findAllExcept(int $excludeUserId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT UTI_ID AS id, UTI_NOMUTILISATEUR AS username, UTI_EMAIL AS email
             FROM UTILISATEUR
             WHERE UTI_ID != :exclude_id
             ORDER BY UTI_NOMUTILISATEUR ASC'
        );
        $stmt->execute(['exclude_id' => $excludeUserId]);

        return $stmt->fetchAll();
    }
}
