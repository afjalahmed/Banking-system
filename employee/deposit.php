<?php
/**
 * Employee Deposit Page
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

// Get all accounts for dropdown
$accounts_sql = "SELECT a.account_id, a.account_number, a.account_name, u.full_name FROM accounts a JOIN users u ON a.user_id = u.user_id WHERE a.status = 'active' ORDER BY a.account_number";
$accounts_result = executeQuery($accounts_sql);
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
    } elseif ($amount > 1000000) {
        $_SESSION['error'] = 'Amount exceeds maximum limit of $1,000,000.';
    } else {
        // Check if account exists and is active
        $check_account_sql = "SELECT account_id, account_number, balance, user_id FROM accounts WHERE account_id = ? AND status = 'active'";
        $stmt = executeQuery($check_account_sql, [$account_id]);
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
                
                // Insert transaction record
                $insert_transaction_sql = "INSERT INTO transactions (transaction_reference, from_account_id, to_account_id, transaction_type, amount, description, status, processed_at, processed_by) VALUES (?, NULL, ?, 'deposit', ?, ?, 'completed', NOW(), ?)";
                $stmt = executeQuery($insert_transaction_sql, [$transaction_reference, $account_id, $amount, $description, $_SESSION['user_id']]);
                
                // Commit transaction
                $conn->commit();
                
                $_SESSION['success'] = 'Deposit of $' . number_format($amount, 2) . ' to account ' . $account['account_number'] . ' was successful.';
                header('Location: /employee/dashboard.php');
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
            <h2><i class="fas fa-user-tie"></i> Employee Panel</h2>
        </div>
        <nav class="dashboard-sidebar-menu">
            <ul>
                <li><a href="/employee/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/employee/add_customer.php"><i class="fas fa-user-plus"></i> Add Customer</a></li>
                <li><a href="/employee/create_account.php"><i class="fas fa-university"></i> Create Account</a></li>
                <li><a href="/employee/deposit.php" class="active"><i class="fas fa-arrow-down"></i> Deposit</a></li>
                <li><a href="/employee/withdraw.php"><i class="fas fa-arrow-up"></i> Withdraw</a></li>
                <li><a href="/employee/pending_transactions.php"><i class="fas fa-clock"></i> Pending Transactions</a></li>
                <li><a href="/employee/customers.php"><i class="fas fa-users"></i> View Customers</a></li>
                <li><a href="/employee/transactions.php"><i class="fas fa-exchange-alt"></i> Transaction History</a></li>
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

        <div class="form-card">
            <h2>Make a Deposit</h2>
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
                                <?php echo htmlspecialchars($account['account_name']); ?> - 
                                <?php echo htmlspecialchars($account['full_name']); ?>
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
                            max="1000000"
                        >
                        <small>Maximum: $1,000,000</small>
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
                        <i class="fas fa-arrow-down"></i> Deposit
                    </button>
                    <a href="/employee/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
