<?php
/**
 * dashboard.php - User Dashboard
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/database.php';
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

requireLogin();

$auth = new Auth($pdo);
$user = getUserById($_SESSION['user_id'], $pdo);
$balance = getWalletBalance($user['id'], $pdo);
$transactions = getUserTransactions($user['id'], $pdo, 10);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] ?? $user['email']); ?></h1>
    <p>Balance: <?php echo formatCurrency($balance); ?></p>

    <nav>
        <a href="/dashboard.php">Dashboard</a> |
        <a href="/airtime.php">Airtime</a> |
        <a href="/cable.php">Cable</a> |
        <a href="/electricity.php">Electricity</a> |
        <a href="/wallet.php">Wallet</a> |
        <a href="/transactions.php">Transactions</a> |
        <a href="/profile.php">Profile</a> |
        <a href="/logout.php">Logout</a>
    </nav>

    <section>
        <h2>Recent Transactions</h2>
        <ul>
        <?php foreach ($transactions as $t): ?>
            <li><?php echo htmlspecialchars($t['type']); ?> - <?php echo formatCurrency($t['amount']); ?> - <?php echo htmlspecialchars($t['status']); ?> - <?php echo htmlspecialchars($t['created_at']); ?></li>
        <?php endforeach; ?>
        </ul>
    </section>

</body>
</html>
