<?php
/**
 * Authentication Handler
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Register new user
     */
    public function register($email, $phone, $password, $confirmPassword, $firstName = '', $lastName = '', $referrerCode = '') {
        $errors = [];

        // Validation
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!isValidEmail($email)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($phone)) {
            $errors[] = 'Phone number is required';
        } elseif (!isValidPhone($phone)) {
            $errors[] = 'Invalid phone number format';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        // Check if email already exists
        if (getUserByEmail($email, $this->pdo)) {
            $errors[] = 'Email already registered';
        }

        // Check if phone already exists
        if (getUserByPhone($phone, $this->pdo)) {
            $errors[] = 'Phone number already registered';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Insert user
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (email, phone, password, first_name, last_name, role, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $email,
                $phone,
                hashPassword($password),
                $firstName,
                $lastName,
                'user',
                'active'
            ]);

            $userId = $this->pdo->lastInsertId();

            // Create wallet (canonical)
            $stmt = $this->pdo->prepare(
                'INSERT INTO wallet (user_id, balance, created_at) VALUES (?, 0, NOW())'
            );
            $stmt->execute([$userId]);

            // Commit transaction
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Registration successful. Please log in.',
                'user_id' => $userId
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Registration error: ' . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Registration failed. Please try again.']
            ];
        }
    }

    /**
     * Login user
     */
    public function login($email, $password, $rememberMe = false) {
        $errors = [];

        if (empty($email)) {
            $errors[] = 'Email is required';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $user = getUserByEmail($email, $this->pdo);

        if (!$user) {
            return ['success' => false, 'errors' => ['Email or password incorrect']];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'errors' => ['Your account is not active']];
        }

        if (!verifyPassword($password, $user['password'])) {
            return ['success' => false, 'errors' => ['Email or password incorrect']];
        }

        // Regenerate session id to prevent fixation
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();

        // Remember me cookie (expires in 30 days) — store hashed token server-side
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $cookieOptions = [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'domain' => parse_url(BASE_URL, PHP_URL_HOST) ?: '',
                'secure' => SECURE_COOKIE,
                'httponly' => HTTPONLY_COOKIE,
                'samesite' => 'Lax'
            ];
            setcookie('remember_token', $token, $cookieOptions);

            // Store a hashed token in DB for verification
            $hashed = password_hash($token, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare(
                'UPDATE users SET remember_token = ?, remember_token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?'
            );
            $stmt->execute([$hashed, $user['id']]);
        }

        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
    }

    /**
     * Logout user
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Unset all session variables
        $_SESSION = [];

        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();

        // Clear remember token cookie
        setcookie('remember_token', '', time() - 3600, '/', parse_url(BASE_URL, PHP_URL_HOST) ?: '', SECURE_COOKIE, HTTPONLY_COOKIE);

        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        $errors = [];

        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }

        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $user = getUserById($userId, $this->pdo);

        if (!$user) {
            return ['success' => false, 'errors' => ['User not found']];
        }

        if (!verifyPassword($currentPassword, $user['password'])) {
            return ['success' => false, 'errors' => ['Current password is incorrect']];
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([hashPassword($newPassword), $userId]);

            return ['success' => true, 'message' => 'Password changed successfully'];
        } catch (PDOException $e) {
            error_log('Password change error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Password change failed']];
        }
    }
}
?>