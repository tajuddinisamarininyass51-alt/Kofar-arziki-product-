<?php
/**
 * KofarArziki Data - Admin Panel
 * Secure Admin Entry
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/*
 * Admin authentication
 */
if (!function_exists('checkAdmin')) {
    http_response_code(500);
    exit('Admin security function is missing.');
}

checkAdmin();

/*
 * Safe HTML escaping
 */
function admin_e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$pageTitle = 'KofarArziki Admin Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= admin_e($pageTitle) ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0b0f0d;
            color: #ffffff;
        }

        .header {
            background: #111814;
            border-bottom: 1px solid #26352c;
            padding: 18px;
        }

        .header h1 {
            margin: 0;
            color: #39ff88;
            font-size: 24px;
        }

        .header p {
            margin: 6px 0 0;
            color: #aeb8b2;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
        }

        .welcome {
            background: #121a15;
            border: 1px solid #26352c;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .welcome h2 {
            margin-top: 0;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .card {
            background: #121a15;
            border: 1px solid #26352c;
            border-radius: 14px;
            padding: 20px;
        }

        .card h3 {
            margin-top: 0;
            color: #39ff88;
        }

        .card p {
            color: #aeb8b2;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            background: #19a854;
            color: #ffffff;
            padding: 11px 16px;
            border-radius: 9px;
            margin-top: 8px;
        }

        .btn:hover {
            background: #128c45;
        }

        .logout {
            background: #b3261e;
        }

        .logout:hover {
            background: #8f1d18;
        }

        footer {
            text-align: center;
            color: #7f8a83;
            padding: 30px 10px;
        }
    </style>
</head>

<body>

<header class="header">
    <h1>KofarArziki Admin</h1>
    <p>VTU Management Panel</p>
</header>

<main class="container">

    <section class="welcome">
        <h2>Admin Dashboard</h2>
        <p>
            Welcome to the KofarArziki administration panel.
        </p>
    </section>

    <section class="grid">

        <div class="card">
            <h3>Users</h3>
            <p>Manage registered users.</p>
        </div>

        <div class="card">
            <h3>Wallet</h3>
            <p>Manage wallet and balance operations.</p>
        </div>

        <div class="card">
            <h3>Transactions</h3>
            <p>View transaction records.</p>
        </div>

        <div class="card">
            <h3>Airtime</h3>
            <p>Monitor airtime purchases.</p>
        </div>

        <div class="card">
            <h3>Data</h3>
            <p>Monitor data purchases.</p>
        </div>

        <div class="card">
            <h3>Security</h3>
            <p>Admin access is protected by authentication and CSRF controls.</p>
        </div>

    </section>

    <div style="margin-top:25px;">
        <a class="btn logout" href="logout.php">
            Logout
        </a>
    </div>

</main>

<footer>
    &copy; <?= date('Y') ?> KofarArziki Data
</footer>

</body>
</html>
