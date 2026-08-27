<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

session_start();

$db = new Database();
$pdo = $db->getPDO();
$auth = new Auth($pdo);

// Perform logout
$result = $auth->logout();

// Redirect to home/login
header('Location: /login.php?logout=success');
exit;
