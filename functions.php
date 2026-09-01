<?php
/**
 * Reusable Functions for KofarArziki Application
 */

require_once __DIR__ . '/database.php';

// Backwards compatibility wrappers for older function names
if (!function_exists('isUserLoggedIn')) {
    function isUserLoggedIn() {
        return isAuthenticated();
    }
}
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        return generateCSRFToken();
    }
}
if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token) {
        return verifyCSRFToken($token);
    }
}

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
    if ((strlen($phone) === 13 || strlen($phone) === 14) && substr($phone, 0, 4) === '+234') {
        return true;
    }
    if (strlen($phone) === 12 && substr($phone, 0, 3) === '234') {
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
    if (!isset($_SESSION)) session_start();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION)) session_start();
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION) && isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
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
        // Redirect to login using BASE_URL if available
        $loginUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/login.php' : '/login.php';
        header('Location: ' . $loginUrl);
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
    // Prefer wallet table; fallback to users.wallet_balance if present
    try {
        $stmt = $pdo->prepare('SELECT balance FROM wallet WHERE user_id = ?');
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        if ($result && isset($result['balance'])) {
            return (float)$result['balance'];
        }
    } catch (Exception $e) {
        // ignore and fallback
    }

    $stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ? (float)$result['wallet_balance'] : 0.00;
}

/**
 * Add transaction record (simple version)
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
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
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
    if (substr($phone, 0, 1) === '+') {
        $phone = ltrim($phone, '+');
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
    // Amount must be within range and be a multiple of 1 (no fractional kobo here)
    return $amount >= $min && $amount <= $max && ($amount == floor($amount));
}

/**
 * Purchase airtime via VTU provider (test-mode friendly)
 * Returns ['success' => bool, 'reference' => string, 'message' => string]
 */
function purchaseAirtime($phone, $amount, $network) {
    // In test mode we simulate a successful response
    if (defined('VTU_TEST_MODE') && VTU_TEST_MODE) {
        $ref = generateReference('VTU');
        $resp = [
            'success' => true,
            'reference' => $ref,
            'message' => 'Test mode: airtime purchase simulated.'
        ];
        logAPICall('purchaseAirtime', compact('phone','amount','network'), $resp, true);
        return $resp;
    }

    // Production: call real VTU API (caller must ensure VTU_API_KEY etc are set)
    $apiUrl = rtrim(VTU_API_BASE_URL, '/') . '/airtime';
    $payload = [
        'phone' => formatPhoneForAPI($phone),
        'amount' => (int)$amount,
        'network' => $network,
        'reference' => generateReference('VTU')
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . VTU_API_KEY
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($result === false) {
        $resp = ['success' => false, 'message' => 'API request failed: ' . $err];
        logAPICall('purchaseAirtime', $payload, $resp, false);
        return $resp;
    }

    $decoded = json_decode($result, true);
    // This depends on VTU provider response structure
    if ($httpCode >= 200 && $httpCode < 300 && isset($decoded['status']) && in_array(strtolower($decoded['status']), ['success','ok'])) {
        $resp = ['success' => true, 'reference' => $payload['reference'], 'message' => $decoded['message'] ?? 'Airtime purchase successful'];
        logAPICall('purchaseAirtime', $payload, $decoded, true);
        return $resp;
    }

    $resp = ['success' => false, 'message' => $decoded['message'] ?? 'VTU provider error', 'raw' => $decoded];
    logAPICall('purchaseAirtime', $payload, $decoded, false);
    return $resp;
}
?>