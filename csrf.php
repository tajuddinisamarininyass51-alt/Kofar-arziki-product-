<?php
/**
 * CSRF Protection Handler
 */

session_start();

/**
 * Generate and return CSRF token
 */
function getCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from request
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token for forms
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCSRFToken()) . '">';
}

/**
 * Check CSRF token in POST/PUT/DELETE requests
 */
function checkCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token || !validateCSRFToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }
}
?>
