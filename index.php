<?php
/**
 * KofarArziki Data VTU - Main Entry Point
 * Handles routing based on authentication status
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Start secure session
session_start();

// Set secure session cookies
ini_set('session.cookie_httponly', HTTPONLY_COOKIE ? '1' : '0');
ini_set('session.cookie_secure', SECURE_COOKIE ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');

// Check session timeout
if (isset($_SESSION['login_time']) && time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
    session_destroy();
    header('Location: /login.php?session=expired');
    exit;
}

// Update login time
if (isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// If user is logged in, redirect to dashboard
if (isAuthenticated()) {
    header('Location: /dashboard.php');
    exit;
}

// Show login/register page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KofarArziki Data VTU - Buy Airtime & Data</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-content">
            <!-- Logo -->
            <div class="logo-section">
                <h1 class="logo">
                    <span class="logo-icon">📱</span>
                    KofarArziki
                </h1>
                <p class="tagline">Easy Data, Airtime & Bill Payments</p>
            </div>

            <!-- Featured Cards -->
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">💳</div>
                    <h3>Instant Service</h3>
                    <p>Get data and airtime instantly</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure</h3>
                    <p>Your data is protected</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Best Rates</h3>
                    <p>Competitive pricing</p>
                </div>
            </div>

            <!-- Login Link -->
            <div class="auth-links">
                <p>Already have an account?</p>
                <a href="/login.php" class="btn btn-primary btn-block">Login Now</a>
                <hr class="divider">
                <p>New here?</p>
                <a href="/register.php" class="btn btn-success btn-block">Create Account</a>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
