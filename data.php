<?php
/**
 * data.php - Mobile Data Purchase Page
 * Allows users to purchase data bundles for various networks
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

$user = getUserById($_SESSION['user_id'], $pdo);
$balance = getWalletBalance($user['id'], $pdo);
$error = '';
$success = '';

// Data plans by network
$dataPlans = [
    'MTN' => [
        ['name' => '500MB', 'price' => 100, 'duration' => '7 days'],
        ['name' => '1GB', 'price' => 200, 'duration' => '7 days'],
        ['name' => '2GB', 'price' => 400, 'duration' => '30 days'],
        ['name' => '5GB', 'price' => 900, 'duration' => '30 days'],
        ['name' => '10GB', 'price' => 1500, 'duration' => '30 days'],
    ],
    'AIRTEL' => [
        ['name' => '500MB', 'price' => 100, 'duration' => '7 days'],
        ['name' => '1GB', 'price' => 200, 'duration' => '7 days'],
        ['name' => '2GB', 'price' => 400, 'duration' => '30 days'],
        ['name' => '5GB', 'price' => 900, 'duration' => '30 days'],
    ],
    'GLO' => [
        ['name' => '500MB', 'price' => 90, 'duration' => '7 days'],
        ['name' => '1GB', 'price' => 180, 'duration' => '7 days'],
        ['name' => '2GB', 'price' => 350, 'duration' => '30 days'],
        ['name' => '5GB', 'price' => 800, 'duration' => '30 days'],
    ],
    '9MOBILE' => [
        ['name' => '500MB', 'price' => 120, 'duration' => '7 days'],
        ['name' => '1GB', 'price' => 220, 'duration' => '7 days'],
        ['name' => '2GB', 'price' => 450, 'duration' => '30 days'],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF token validation failed';
    } else {
        $network = sanitize($_POST['network'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $plan = sanitize($_POST['plan'] ?? '');

        if (empty($network) || empty($phone) || empty($plan)) {
            $error = 'All fields are required';
        } elseif (!isValidPhone($phone)) {
            $error = 'Invalid phone number format';
        } else {
            // Find selected plan
            $selectedPlan = null;
            foreach ($dataPlans[$network] as $p) {
                if ($p['name'] === $plan) {
                    $selectedPlan = $p;
                    break;
                }
            }

            if (!$selectedPlan) {
                $error = 'Invalid plan selected';
            } elseif ($balance < $selectedPlan['price']) {
                $error = 'Insufficient wallet balance. Please fund your wallet.';
            } else {
                try {
                    $pdo->beginTransaction();

                    // Create transaction record
                    $reference = generateReference('DATA_');
                    $description = "{$network} {$selectedPlan['name']} - {$phone}";
                    $transactionId = addTransaction(
                        $user['id'],
                        'data',
                        $selectedPlan['price'],
                        'pending',
                        $reference,
                        $pdo,
                        $description
                    );

                    // Deduct from wallet
                    $stmt = $pdo->prepare('UPDATE wallet SET balance = balance - ? WHERE user_id = ?');
                    $stmt->execute([$selectedPlan['price'], $user['id']]);

                    // VTU API Call (simulated or real)
                    $apiResult = [
                        'success' => true,
                        'message' => "Data purchased successfully",
                        'transaction_id' => $reference,
                    ];

                    if ($apiResult['success']) {
                        // Update transaction status
                        $stmt = $pdo->prepare('UPDATE transactions SET status = ? WHERE id = ?');
                        $stmt->execute(['completed', $transactionId]);
                    } else {
                        // Refund if API fails
                        $stmt = $pdo->prepare('UPDATE wallet SET balance = balance + ? WHERE user_id = ?');
                        $stmt->execute([$selectedPlan['price'], $user['id']]);
                        $stmt = $pdo->prepare('UPDATE transactions SET status = ? WHERE id = ?');
                        $stmt->execute(['failed', $transactionId]);
                        throw new Exception('VTU API failed');
                    }

                    $pdo->commit();
                    $balance = getWalletBalance($user['id'], $pdo);
                    $success = "Data bundle purchased successfully! Reference: $reference";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Transaction failed. Please try again.';
                    error_log('Data purchase error: ' . $e->getMessage());
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Buy Data</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        header h1 { font-size: 28px; margin-bottom: 10px; }
        header p { font-size: 14px; opacity: 0.9; }
        .content { padding: 30px 20px; }
        .balance-card {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }
        .balance-card p { font-size: 14px; opacity: 0.9; margin-bottom: 8px; }
        .balance-card .amount { font-size: 32px; font-weight: bold; }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }
        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        select, input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        select:focus, input:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }
        .plans-section {
            margin: 25px 0;
        }
        .plans-section h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .plan-option {
            border: 2px solid #ddd;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }
        .plan-option:hover {
            border-color: #2a5298;
            background: rgba(42, 82, 152, 0.05);
        }
        .plan-option input[type="radio"] { display: none; }
        .plan-option input[type="radio"]:checked + div {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-color: #2a5298;
        }
        .plan-name { font-weight: 600; font-size: 14px; margin-bottom: 5px; }
        .plan-price { color: #4CAF50; font-weight: 600; font-size: 14px; }
        .plan-option input[type="radio"]:checked ~ .plan-name,
        .plan-option input[type="radio"]:checked ~ .plan-price {
            color: white;
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }
        .btn-primary:hover { box-shadow: 0 5px 20px rgba(42, 82, 152, 0.4); }
        .btn-primary:active { transform: translateY(2px); }
        .btn-secondary {
            background: white;
            color: #2a5298;
            border: 2px solid #2a5298;
        }
        .btn-secondary:hover { background: #f5f5f5; }
        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .nav-links a {
            flex: 1;
            min-width: 100px;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            color: white;
            background: #666;
            border-radius: 6px;
            font-size: 12px;
            transition: background 0.3s;
        }
        .nav-links a:hover { background: #444; }
        @media (max-width: 480px) {
            header h1 { font-size: 22px; }
            .balance-card .amount { font-size: 24px; }
            .plans-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Mobile Data Services</p>
        </header>

        <div class="content">
            <div class="balance-card">
                <p>Wallet Balance</p>
                <div class="amount"><?php echo formatCurrency($balance); ?></div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php csrfField(); ?>

                <div class="form-group">
                    <label for="network">Network Provider</label>
                    <select id="network" name="network" required onchange="updatePlans()">
                        <option value="">Select Network</option>
                        <option value="MTN">MTN</option>
                        <option value="AIRTEL">AIRTEL</option>
                        <option value="GLO">GLO</option>
                        <option value="9MOBILE">9MOBILE</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="0801234567" required>
                </div>

                <div class="form-group">
                    <label>Select Data Plan</label>
                    <div class="plans-section" id="plansContainer"></div>
                </div>

                <button type="submit" class="btn btn-primary">Purchase Data</button>
                <a href="/wallet.php" class="btn btn-secondary">Fund Wallet</a>
            </form>

            <div class="nav-links">
                <a href="/dashboard.php">Dashboard</a>
                <a href="/airtime.php">Airtime</a>
                <a href="/cable.php">Cable</a>
                <a href="/electricity.php">Electricity</a>
                <a href="/wallet.php">Wallet</a>
                <a href="/transactions.php">History</a>
                <a href="/profile.php">Profile</a>
                <a href="/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <script>
        const dataPlans = <?php echo json_encode($dataPlans); ?>;

        function updatePlans() {
            const network = document.getElementById('network').value;
            const container = document.getElementById('plansContainer');
            container.innerHTML = '';

            if (!network || !dataPlans[network]) return;

            const plans = dataPlans[network];
            let html = '<div class="plans-grid">';

            plans.forEach((plan, index) => {
                html += `
                    <label class="plan-option">
                        <input type="radio" name="plan" value="${plan.name}" required>
                        <div>
                            <div class="plan-name">${plan.name}</div>
                            <div class="plan-price">₦${plan.price}</div>
                        </div>
                    </label>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }
    </script>
</body>
</html>
