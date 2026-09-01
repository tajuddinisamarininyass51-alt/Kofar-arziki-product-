<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Secure session settings
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_TIMEOUT,
    'path' => '/',
    'domain' => parse_url(BASE_URL, PHP_URL_HOST) ?: '',
    'secure' => SECURE_COOKIE,
    'httponly' => HTTPONLY_COOKIE,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) session_start();

$db = new Database();
$pdo = $db->getPDO();
$auth = new Auth($pdo);

// Perform logout
$result = $auth->logout();

// Redirect to login with message
$loginUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/login.php?logout=success' : '/login.php?logout=success';
header('Location: ' . $loginUrl);
exit;
?>