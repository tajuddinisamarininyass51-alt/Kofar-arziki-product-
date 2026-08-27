<?php
/**
 * User Login Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

session_start();

// Redirect if already logged in
if (isAuthenticated()) {
    header('Location: /dashboard.php');
    exit;
}

$errors = [];
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    if (empty($errors)) {
        $db = new Database();
        $pdo = $db->getPDO();
        $auth = new Auth($pdo);

        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);

        $result = $auth->login($email, $password, $remember_me);

        if ($result['success']) {
            header('Location: /dashboard.php');
            exit;
        } else {
            $errors = $result['errors'] ?? ['Login failed'];
        }
    }
}

$csrf_token = generateCSRFToken();
$session_expired = isset($_GET['session']) && $_GET['session'] === 'expired';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KofarArziki Data VTU</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-content narrow">
            <!-- Login Form -->
            <div class="form-header">
                <h1 class="logo">
                    <span class="logo-icon">📱</span>
                    KofarArziki
                </h1>
                <p>Login to your account</p>
            </div>

            <?php if ($session_expired): ?>
                <div class="alert alert-warning">
                    Your session expired. Please login again.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p>• <?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autofocus
                           value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group checkbox">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Remember me for 30 days</label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="/register.php">Create one now</a></p>
                <p><a href="/forgot-password.php" class="text-muted">Forgot your password?</a></p>
            </div>
        </div>
    </div>

    <script src="/assets/js/validation.js"></script>
</body>
</html>
