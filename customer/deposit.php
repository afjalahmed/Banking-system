<?php
/**
 * Customer Deposit Page
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$user_id = $_SESSION['user_id'];

// Get user's accounts for dropdown
$accounts_sql = "SELECT account_id, account_number, account_name, balance FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY account_number";
$accounts_result = executeQuery($accounts_sql, [$user_id]);
$accounts = fetchAll($accounts_result);

// Process deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_id = intval($_POST['account_id']);
    $amount = floatval($_POST['amount']);
    $description = sanitize($_POST['description']);
    
    // Validate input
    if ($account_id <= 0) {
        $_SESSION['error'] = 'Please select a valid account.';
    } elseif ($amount <= 0) {
        $_SESSION['error'] = 'Amount must be greater than 0.';
    } elseif ($amount > 100000) {
        $_SESSION['error'] = 'Amount exceeds maximum limit of $100,000.';
    } else {
        // Check if account exists, is active, and belongs to the user
        $check_account_sql = "SELECT account_id, account_number, balance, user_id FROM accounts WHERE account_id = ? AND user_id = ? AND status = 'active'";
        $stmt = executeQuery($check_account_sql, [$account_id, $user_id]);
        $account = fetchOne($stmt);
        
        if (!$account) {
            $_SESSION['error'] = 'Invalid account selected.';
        } else {
            // Generate transaction reference
            $transaction_reference = 'DEP' . date('YmdHis') . rand(1000, 9999);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update account balance
                $new_balance = $account['balance'] + $amount;
                $update_balance_sql = "UPDATE accounts SET balance = ? WHERE account_id = ?";
                $stmt = executeQuery($update_balance_sql, [$new_balance, $account_id]);
                
                // Insert transaction record (pending - requires employee approval)
                $insert_transaction_sql = "INSERT INTO transactions (transaction_reference, from_account_id, to_account_id, transaction_type, amount, description, status, processed_at, processed_by) VALUES (?, NULL, ?, 'deposit', ?, ?, 'pending', NULL, NULL)";
                $stmt = executeQuery($insert_transaction_sql, [$transaction_reference, $account_id, $amount, $description]);
                
                // Commit transaction
                $conn->commit();
                
                $_SESSION['success'] = 'Deposit request of $' . number_format($amount, 2) . ' to account ' . $account['account_number'] . ' submitted successfully. Awaiting employee approval.';
                header('Location: /customer/dashboard.php');
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = 'Deposit failed: ' . $e->getMessage();
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
                <li><a href="/customer/transfer.php"><i class="fas fa-paper-plane"></i> Transfer</a></li>
                <li><a href="/customer/deposit.php" class="active"><i class="fas fa-arrow-down"></i> Deposit</a></li>
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
            <h1><i class="fas fa-arrow-down"></i> Deposit Money</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Deposits require employee approval before being processed.
        </div>

        <div class="form-card">
            <h2>Request a Deposit</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="account_id">
                            <i class="fas fa-university"></i> Select Account *
                        </label>
                        <select id="account_id" name="account_id" required>
                            <option value="">-- Select Account --</option>
                            <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo $account['account_id']; ?>">
                                <?php echo htmlspecialchars($account['account_number']); ?> - 
                                <?php echo htmlspecialchars($account['account_name']); ?> 
                                (Balance: $<?php echo number_format($account['balance'], 2); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
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
                            placeholder="Enter amount" 
                            required 
                            min="0.01" 
                            step="0.01" 
                            max="100000"
                        >
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
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-arrow-down"></i> Submit Deposit Request
                    </button>
                    <a href="/customer/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
