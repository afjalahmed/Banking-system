<?php
/**
 * Customer Transfer Page
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$user_id = $_SESSION['user_id'];

// Get user's accounts for dropdown
$accounts_sql = "SELECT account_id, account_number, account_name, balance FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY account_number";
$accounts_result = executeQuery($accounts_sql, [$user_id]);
$accounts = fetchAll($accounts_result);

// Process transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_account_id = intval($_POST['from_account_id']);
    $to_account_number = sanitize($_POST['to_account_number']);
    $amount = floatval($_POST['amount']);
    $description = sanitize($_POST['description']);
    
    // Validate input
    if ($from_account_id <= 0) {
        $_SESSION['error'] = 'Please select a valid source account.';
    } elseif (empty($to_account_number)) {
        $_SESSION['error'] = 'Please enter destination account number.';
    } elseif ($amount <= 0) {
        $_SESSION['error'] = 'Amount must be greater than 0.';
    } elseif ($amount > 100000) {
        $_SESSION['error'] = 'Amount exceeds maximum limit of $100,000.';
    } elseif ($to_account_number === '') {
        $_SESSION['error'] = 'Invalid destination account number.';
    } else {
        // Check if source account exists, is active, and belongs to the user
        $check_source_sql = "SELECT account_id, account_number, balance, user_id FROM accounts WHERE account_id = ? AND user_id = ? AND status = 'active'";
        $stmt = executeQuery($check_source_sql, [$from_account_id, $user_id]);
        $source_account = fetchOne($stmt);
        
        if (!$source_account) {
            $_SESSION['error'] = 'Invalid source account selected.';
        } elseif ($amount > $source_account['balance']) {
            $_SESSION['error'] = 'Insufficient balance. Available balance: $' . number_format($source_account['balance'], 2);
        } else {
            // Check if destination account exists and is active
            $check_dest_sql = "SELECT account_id, account_number, user_id, status FROM accounts WHERE account_number = ? AND status = 'active'";
            $stmt = executeQuery($check_dest_sql, [$to_account_number]);
            $dest_account = fetchOne($stmt);
            
            if (!$dest_account) {
                $_SESSION['error'] = 'Destination account not found or inactive.';
            } elseif ($dest_account['account_id'] === $source_account['account_id']) {
                $_SESSION['error'] = 'Cannot transfer to the same account.';
            } else {
                // Generate transaction reference
                $transaction_reference = 'TRF' . date('YmdHis') . rand(1000, 9999);
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Deduct from source account
                    $new_source_balance = $source_account['balance'] - $amount;
                    $update_source_sql = "UPDATE accounts SET balance = ? WHERE account_id = ?";
                    $stmt = executeQuery($update_source_sql, [$new_source_balance, $source_account['account_id']);
                    
                    // Add to destination account
                    $get_dest_balance_sql = "SELECT balance FROM accounts WHERE account_id = ?";
                    $stmt = executeQuery($get_dest_balance_sql, [$dest_account['account_id']]);
                    $dest_balance_result = fetchOne($stmt);
                    $new_dest_balance = $dest_balance_result['balance'] + $amount;
                    
                    $update_dest_sql = "UPDATE accounts SET balance = ? WHERE account_id = ?";
                    $stmt = executeQuery($update_dest_sql, [$new_dest_balance, $dest_account['account_id']]);
                    
                    // Insert transaction record
                    $insert_transaction_sql = "INSERT INTO transactions (transaction_reference, from_account_id, to_account_id, transaction_type, amount, description, status, processed_at, processed_by) VALUES (?, ?, ?, 'transfer', ?, ?, 'completed', NOW(), ?)";
                    $stmt = executeQuery($insert_transaction_sql, [$transaction_reference, $source_account['account_id'], $dest_account['account_id'], $amount, $description, $user_id]);
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $_SESSION['success'] = 'Transfer of $' . number_format($amount, 2) . ' from account ' . $source_account['account_number'] . ' to account ' . $dest_account['account_number'] . ' was successful.';
                    header('Location: /customer/dashboard.php');
                    exit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = 'Transfer failed: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-sidebar-header">
            <h2><i class="fas fa-user"></i> My Account</h2>
        </div>
        <nav class="dashboard-sidebar-menu">
            <ul>
                <li><a href="/customer/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/customer/accounts.php"><i class="fas fa-university"></i> My Accounts</a></li>
                <li><a href="/customer/transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="/customer/transfer.php" class="active"><i class="fas fa-paper-plane"></i> Transfer</a></li>
                <li><a href="/customer/deposit.php"><i class="fas fa-arrow-down"></i> Deposit</a></li>
                <li><a href="/customer/withdraw.php"><i class="fas fa-arrow-up"></i> Withdraw</a></li>
                <li><a href="/customer/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/customer/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-paper-plane"></i> Transfer Money</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="form-card">
            <h2>Transfer Funds</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="from_account_id">
                            <i class="fas fa-arrow-left"></i> From Account *
                        </label>
                        <select id="from_account_id" name="from_account_id" required onchange="updateBalanceDisplay()">
                            <option value="">-- Select Source Account --</option>
                            <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo $account['account_id']; ?>" data-balance="<?php echo $account['balance']; ?>">
                                <?php echo htmlspecialchars($account['account_number']); ?> - 
                                <?php echo htmlspecialchars($account['account_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="to_account_number">
                            <i class="fas fa-arrow-right"></i> To Account Number *
                        </label>
                        <input 
                            type="text" 
                            id="to_account_number" 
                            name="to_account_number" 
                            placeholder="Enter destination account number" 
                            required 
                            pattern="[A-Za-z0-9]{10,20}"
                            title="Account number must be 10-20 characters"
                        >
                        <small>Enter the recipient's account number</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="amount">
                            <i class="fas fa-dollar-sign"></i> Amount (USD) *
                        </label>
                        <input 
                            type="number" 
                            id="amount" 
                            name="amount" 
                            placeholder="Enter amount to transfer" 
                            required 
                            min="0.01" 
                            step="0.01" 
                            max="100000"
                        >
                        <small>Available Balance: <span id="balance-display">$0.00</span></small>
                        <small>Maximum: $100,000</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="description">
                            <i class="fas fa-comment"></i> Description
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            placeholder="Enter description (optional)" 
                            rows="3"
                        ></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Transfer
                    </button>
                    <a href="/customer/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function updateBalanceDisplay() {
    const select = document.getElementById('from_account_id');
    const balanceDisplay = document.getElementById('balance-display');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        const balance = parseFloat(selectedOption.getAttribute('data-balance'));
        balanceDisplay.textContent = '$' + balance.toFixed(2);
    } else {
        balanceDisplay.textContent = '$0.00';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
