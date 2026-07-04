-- TaskFlow — Schéma MySQL
-- Exécuter une fois pour créer la base et les tables

CREATE DATABASE IF NOT EXISTS taskflow
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE taskflow;

-- Utilisateurs
CREATE TABLE users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    email        VARCHAR(100) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    avatar_seed  VARCHAR(50)  DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Projets
CREATE TABLE projects (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    description  TEXT         DEFAULT NULL,
    owner_id     INT UNSIGNED NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

-- Classe d'association : users ↔ projects
CREATE TABLE project_members (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    role        ENUM('owner', 'member') NOT NULL DEFAULT 'member',
    joined_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    UNIQUE KEY unique_member (project_id, user_id)
);

-- Tâches (suppression logique via is_active)
CREATE TABLE tasks (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id   INT UNSIGNED NOT NULL,
    assigned_to  INT UNSIGNED DEFAULT NULL,
    title        VARCHAR(150) NOT NULL,
    description  TEXT         DEFAULT NULL,
    priority     ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status       ENUM('todo', 'in_progress', 'done') NOT NULL DEFAULT 'todo',
    deadline     DATE         DEFAULT NULL,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id)    ON DELETE SET NULL
);

-- Commentaires sur les tâches
CREATE TABLE comments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    content    TEXT         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
