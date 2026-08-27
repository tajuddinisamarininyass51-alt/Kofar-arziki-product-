<?php
/**
 * Reusable Functions for KofarArziki Application
 */

require_once __DIR__ . '/database.php';

/**
 * Sanitize user input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (Nigerian format)
 */
function isValidPhone($phone) {
    // Accept 11 digit Nigerian numbers starting with 0, or with +234
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
        return true;
    }
    if (strlen($phone) === 13 && substr($phone, 0, 4) === '+234') {
        return true;
    }
    return false;
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isAuthenticated()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Redirect to admin login if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Get user by ID
 */
function getUserById($userId, $pdo) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get user by email
 */
function getUserByEmail($email, $pdo) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Get user by phone
 */
function getUserByPhone($phone, $pdo) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = ?');
    $stmt->execute([$phone]);
    return $stmt->fetch();
}

/**
 * Get user wallet balance
 */
function getWalletBalance($userId, $pdo) {
    $stmt = $pdo->prepare('SELECT balance FROM wallet WHERE user_id = ?');
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ? $result['balance'] : 0;
}

/**
 * Add transaction record
 */
function addTransaction($userId, $type, $amount, $status, $reference, $pdo, $description = '') {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO transactions (user_id, type, amount, status, reference, description, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $type, $amount, $status, $reference, $description]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get user transactions
 */
function getUserTransactions($userId, $pdo, $limit = 50) {
    $stmt = $pdo->prepare(
        'SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
    );
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

/**
 * Generate unique reference
 */
function generateReference($prefix = '') {
    return $prefix . time() . rand(1000, 9999);
}

/**
 * Format phone for API calls
 */
function formatPhoneForAPI($phone) {
    // Convert to 234 format without +
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '234' . substr($phone, 1);
    }
    return $phone;
}

/**
 * Log API calls for debugging
 */
function logAPICall($action, $data, $response, $success = true) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'success' => $success,
        'data' => $data,
        'response' => $response
    ];
    
    $logFile = __DIR__ . '/logs/api_calls.log';
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents($logFile, json_encode($log) . PHP_EOL, FILE_APPEND);
}

/**
 * Send JSON response
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Validate amount
 */
function isValidAmount($amount, $min = 50, $max = 1000000) {
    $amount = (float)$amount;
    return $amount >= $min && $amount <= $max && $amount === floor($amount);
}
?>
