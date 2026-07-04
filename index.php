<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/src/managers/AuthManager.php';

if (AuthManager::currentUser() !== null) {
    header('Location: frontend/pages/dashboard.php');
    exit;
}

header('Location: frontend/pages/register.php');
exit;
