<?php
/**
 * KofarArziki Data VTU - Main Configuration
 * 
 * This file contains all configuration settings for the application.
 * Store sensitive credentials only on the server (cPanel environment variables or .env)
 * 
 * DO NOT expose API keys or database credentials in frontend files.
 */

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable showing errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Timezone
date_default_timezone_set('Africa/Lagos');

// Application Settings
define('APP_NAME', 'KofarArziki Data');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production'); // 'development' or 'production'

// Database Configuration
// On cPanel, these should be stored in environment variables or .env file
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'kofararz_user');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'kofararz_db');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('SESSION_NAME', 'kofararz_session');
define('SECURE_COOKIE', true); // Set to false if not using HTTPS during development
define('HTTPONLY_COOKIE', true);

// Security
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);

// Wallet & Transaction Settings
define('MIN_DEPOSIT', 1000);
define('MAX_DEPOSIT', 1000000);
define('MIN_WITHDRAWAL', 5000);
define('MAX_WITHDRAWAL', 500000);
define('WITHDRAWAL_FEE_PERCENT', 1);

// Referral Settings
define('REFERRAL_BONUS_REGISTRATION', 500);
define('REFERRAL_BONUS_TRANSACTION_PERCENT', 2);

// VTU API Configuration
// Store these in environment variables on cPanel
define('VTU_API_BASE_URL', getenv('VTU_API_BASE_URL') ?: 'https://api.vtu-provider.com/v1');
define('VTU_API_KEY', getenv('VTU_API_KEY') ?: 'your-api-key-here');
define('VTU_API_SECRET', getenv('VTU_API_SECRET') ?: 'your-api-secret-here');
define('VTU_TEST_MODE', true); // Set to false in production

// Payment Gateway Settings (Paystack, Flutterwave, etc.)
define('PAYMENT_GATEWAY', getenv('PAYMENT_GATEWAY') ?: 'paystack');
define('PAYSTACK_PUBLIC_KEY', getenv('PAYSTACK_PUBLIC_KEY') ?: '');
define('PAYSTACK_SECRET_KEY', getenv('PAYSTACK_SECRET_KEY') ?: '');

// Email Configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@kofararziki.com');
define('SMTP_FROM_NAME', APP_NAME);

// SMS Configuration
define('SMS_PROVIDER', getenv('SMS_PROVIDER') ?: 'termii');
define('SMS_API_KEY', getenv('SMS_API_KEY') ?: '');
define('SMS_API_URL', getenv('SMS_API_URL') ?: 'https://api.ng.termii.com/api/sms/send');

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ALLOWED_UPLOAD_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// URLs
define('BASE_URL', getenv('BASE_URL') ?: 'https://kofararziki.com/');
define('ADMIN_URL', BASE_URL . 'admin/');
define('API_URL', BASE_URL . 'api/');

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/cache/');

// Logging
define('LOG_DIR', __DIR__ . '/logs/');
define('LOG_LEVEL', APP_ENV === 'production' ? 'error' : 'debug');

// Ensure required directories exist
$requiredDirs = [
    __DIR__ . '/logs',
    __DIR__ . '/cache',
    __DIR__ . '/uploads',
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Development/Test Mode Checks
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

?>
