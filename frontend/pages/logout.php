<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/managers/AuthManager.php';

AuthManager::logout();
header('Location: login.php');
exit;
