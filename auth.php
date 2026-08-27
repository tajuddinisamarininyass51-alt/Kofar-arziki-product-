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
    public function register($email, $phone, $password, $confirmPassword, $firstName = '', $lastName = '') {
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
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
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

            // Create wallet
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

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();

        // Remember me cookie (expires in 30 days)
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
            
            // Store remember token in database
            $stmt = $this->pdo->prepare(
                'UPDATE users SET remember_token = ?, remember_token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?'
            );
            $stmt->execute([$token, $user['id']]);
        }

        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
    }

    /**
     * Logout user
     */
    public function logout() {
        // Clear session
        session_destroy();
        
        // Clear remember token cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        
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
