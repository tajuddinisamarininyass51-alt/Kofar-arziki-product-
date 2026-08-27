<?php
session_start();
require 'config.php';
require 'auth.php';
require 'csrf.php';
require 'functions.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user's current wallet balance
$stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$wallet_balance = $user['wallet_balance'] ?? 0;

// Handle airtime purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $phone = trim($_POST['phone'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $network = trim($_POST['network'] ?? '');
        
        // Validate input
        if (empty($phone) || empty($amount) || empty($network)) {
            $error = 'All fields are required.';
        } elseif ($amount <= 0) {
            $error = 'Amount must be greater than 0.';
        } elseif ($amount > $wallet_balance) {
            $error = 'Insufficient wallet balance. Current balance: ₦' . number_format($wallet_balance, 2);
        } elseif (!preg_match('/^[0-9]{10,11}$/', str_replace(['+234', '234', '0'], '', $phone))) {
            $error = 'Invalid phone number format.';
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Process airtime purchase through VTU API (server-side)
                $api_response = purchaseAirtime($phone, $amount, $network);
                
                if ($api_response['success']) {
                    // Deduct from wallet
                    $stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?');
                    $stmt->execute([$amount, $user_id]);
                    
                    // Record transaction
                    $stmt = $pdo->prepare('
                        INSERT INTO transactions (user_id, type, service, amount, phone, network, status, reference)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([
                        $user_id,
                        'airtime',
                        'airtime',
                        $amount,
                        $phone,
                        $network,
                        'completed',
                        $api_response['reference'] ?? 'TRX' . time()
                    ]);
                    
                    $pdo->commit();
                    $message = 'Airtime purchased successfully! ₦' . number_format($amount, 2) . ' has been added to ' . $phone;
                    $wallet_balance -= $amount;
                } else {
                    $pdo->rollBack();
                    $error = $api_response['message'] ?? 'Failed to process airtime purchase. Please try again.';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'An error occurred. Please try again later.';
                error_log($e->getMessage());
            }
        }
    }
}

// Get airtime networks
$networks = [
    'mtn' => 'MTN',
    'glo' => 'Glo',
    'airtel' => 'Airtel',
    '9mobile' => '9Mobile'
];

// Get quick amounts
$quick_amounts = [500, 1000, 2000, 5000, 10000];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Airtime - KofarArziki</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0066cc 0%, #00cc66 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #0066cc; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #666; font-size: 14px; }
        .balance-box { background: linear-gradient(135deg, #0066cc 0%, #00cc66 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 25px; text-align: center; }
        .balance-box .label { font-size: 12px; opacity: 0.9; }
        .balance-box .amount { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 14px; }
        input, select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: border-color 0.3s; }
        input:focus, select:focus { outline: none; border-color: #0066cc; }
        .quick-amounts { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 10px; }
        .quick-amount-btn { padding: 10px; border: 2px solid #e0e0e0; background: white; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600; color: #333; transition: all 0.3s; }
        .quick-amount-btn:hover { border-color: #0066cc; color: #0066cc; }
        .quick-amount-btn.selected { background: #0066cc; color: white; border-color: #0066cc; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #f5c6cb; }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,102,204,0.3); }
        .btn-primary:active { transform: translateY(0); }
        .back-link { display: inline-block; margin-bottom: 20px; color: white; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
        .phone-input-group { position: relative; }
        .phone-prefix { position: absolute; left: 12px; top: 38px; font-weight: 600; color: #666; pointer-events: none; }
        .phone-input-group input { padding-left: 40px; }
        .network-select-group { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px; }
        .network-option { padding: 12px; border: 2px solid #e0e0e0; background: white; border-radius: 8px; cursor: pointer; text-align: center; font-weight: 600; color: #333; transition: all 0.3s; }
        .network-option:hover { border-color: #0066cc; }
        .network-option.selected { background: #0066cc; color: white; border-color: #0066cc; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <div class="card">
            <div class="header">
                <h1>📱 Buy Airtime</h1>
                <p>Top up any mobile network instantly</p>
            </div>

            <div class="balance-box">
                <div class="label">Wallet Balance</div>
                <div class="amount">₦<?php echo number_format($wallet_balance, 2); ?></div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="form-group">
                    <label>Mobile Network</label>
                    <div class="network-select-group" id="networkGroup">
                        <?php foreach ($networks as $key => $name): ?>
                            <div class="network-option" data-network="<?php echo $key; ?>">
                                <?php echo htmlspecialchars($name); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="network" name="network" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <div class="phone-input-group">
                        <span class="phone-prefix">+234</span>
                        <input type="text" id="phone" name="phone" placeholder="8012345678" pattern="[0-9]{10,11}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Amount (₦)</label>
                    <input type="number" id="amount" name="amount" placeholder="Enter amount" min="50" step="50" required>
                    <div class="quick-amounts">
                        <?php foreach ($quick_amounts as $amt): ?>
                            <button type="button" class="quick-amount-btn" data-amount="<?php echo $amt; ?>">
                                ₦<?php echo number_format($amt, 0); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Purchase Airtime</button>
            </form>
        </div>
    </div>

    <script>
        // Network selection
        document.querySelectorAll('.network-option').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.network-option').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('network').value = this.dataset.network;
            });
        });

        // Quick amount selection
        document.querySelectorAll('.quick-amount-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('amount').value = this.dataset.amount;
                document.querySelectorAll('.quick-amount-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        // Format phone number
        document.getElementById('phone').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
