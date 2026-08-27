<?php
/**
 * User Registration Page
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
$success = false;
$showForm = true;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    if (empty($errors)) {
        // Get database connection
        $db = new Database();
        $pdo = $db->getPDO();
        $auth = new Auth($pdo);

        // Sanitize inputs
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $referrer_code = sanitize($_POST['referrer_code'] ?? '');

        // Attempt registration
        $result = $auth->register($email, $phone, $password, $confirm_password, $first_name, $last_name, $referrer_code);

        if ($result['success']) {
            $success = true;
            $showForm = false;
            $_SESSION['registration_success'] = true;
        } else {
            $errors = $result['errors'] ?? ['Registration failed'];
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - KofarArziki Data VTU</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-content">
            <?php if ($success): ?>
                <!-- Success Message -->
                <div class="success-card">
                    <div class="success-icon">✓</div>
                    <h2>Registration Successful!</h2>
                    <p>Your account has been created. You can now login with your email and password.</p>
                    <a href="/login.php" class="btn btn-primary btn-block">Proceed to Login</a>
                </div>
            <?php else: ?>
                <!-- Registration Form -->
                <div class="form-header">
                    <h1>Create Account</h1>
                    <p>Join KofarArziki and start saving on data and airtime</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <p>• <?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required maxlength="50"
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required maxlength="50"
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <small>We'll use this to send you updates and receipts</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="e.g., 08012345678" required
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        <small>Nigerian number format (11 digits)</small>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="8">
                        <small>At least 8 characters (mix letters, numbers, symbols)</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>

                    <div class="form-group">
                        <label for="referrer_code">Referral Code (Optional)</label>
                        <input type="text" id="referrer_code" name="referrer_code" maxlength="20"
                               placeholder="Leave blank if you don't have one"
                               value="<?php echo htmlspecialchars($_POST['referrer_code'] ?? ''); ?>">
                        <small>Get ₦500 bonus when you use a referral code</small>
                    </div>

                    <div class="form-group checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the Terms of Service and Privacy Policy</label>
                    </div>

                    <button type="submit" class="btn btn-success btn-block btn-lg">Create Account</button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="/login.php">Login here</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/assets/js/validation.js"></script>
</body>
</html>
