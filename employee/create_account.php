<?php
/**
 * Create Bank Account
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        // Sanitize inputs
        $user_id = (int)($_POST['user_id'] ?? 0);
        $account_type = sanitize($_POST['account_type'] ?? 'savings');
        $account_name = sanitize($_POST['account_name'] ?? '');
        $branch_name = sanitize($_POST['branch_name'] ?? '');
        $initial_balance = floatval($_POST['initial_balance'] ?? 0);
        $currency = sanitize($_POST['currency'] ?? 'USD');
        
        // Validation
        if ($user_id <= 0) {
            $errors[] = "Please select a customer";
        }
        
        if (empty($account_name)) {
            $errors[] = "Account name is required";
        }
        
        if (empty($branch_name)) {
            $errors[] = "Branch name is required";
        }
        
        if (!in_array($account_type, ['savings', 'checking', 'fixed_deposit'])) {
            $errors[] = "Invalid account type";
        }
        
        if ($initial_balance < 0) {
            $errors[] = "Initial balance cannot be negative";
        }
        
        // Verify user exists and is a customer
        if (empty($errors) && $user_id > 0) {
            $user_sql = "SELECT user_id, role FROM users WHERE user_id = ? AND status = 'active'";
            $user_result = executeQuery($user_sql, [$user_id]);
            $user_data = fetchOne($user_result);
            
            if (!$user_data) {
                $errors[] = "Selected customer not found or inactive";
            }
        }
        
        // Check for duplicate account name for same user
        if (empty($errors)) {
            $check_sql = "SELECT account_id FROM accounts WHERE user_id = ? AND account_name = ?";
            $check_result = executeQuery($check_sql, [$user_id, $account_name]);
            if (fetchOne($check_result)) {
                $errors[] = "Account name already exists for this customer";
            }
        }
        
        // Generate unique account number
        $account_number = 'ACC' . date('Y') . strtoupper(substr(uniqid(), -6));
        
        // If no errors, create account
        if (empty($errors)) {
            $sql = "INSERT INTO accounts (account_number, user_id, account_type, account_name, branch_name, balance, currency, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
            executeQuery($sql, [$account_number, $user_id, $account_type, $account_name, $branch_name, $initial_balance, $currency]);
            
            $new_account_id = $conn->insert_id;
            
            // If initial balance > 0, create a deposit transaction
            if ($initial_balance > 0) {
                $txn_ref = 'TXN' . date('Ymd') . strtoupper(substr(uniqid(), -6));
                $txn_sql = "INSERT INTO transactions (transaction_reference, to_account_id, transaction_type, amount, description, status, processed_at, processed_by) 
                           VALUES (?, ?, 'deposit', ?, 'Initial deposit', 'completed', NOW(), ?)";
                executeQuery($txn_sql, [$txn_ref, $new_account_id, $initial_balance, $_SESSION['user_id']]);
            }
            
            // Log action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'CREATE', 'accounts', $new_account_id, json_encode(['account_number' => $account_number, 'user_id' => $user_id])]);
            
            $success = "Account created successfully. Account Number: $account_number";
            
            // Clear form
            $_POST = [];
        }
    }
}

// Get list of active customers for dropdown
$customers_sql = "SELECT user_id, username, full_name, email FROM users WHERE role = 'customer' AND status = 'active' ORDER BY full_name";
$customers_result = executeQuery($customers_sql);
$customers = fetchAll($customers_result);
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
                <li><a href="/employee/create_account.php" class="active"><i class="fas fa-university"></i> Create Account</a></li>
                <li><a href="/employee/accounts.php"><i class="fas fa-list"></i> All Accounts</a></li>
                <li><a href="/employee/deposit.php"><i class="fas fa-arrow-down"></i> Deposit</a></li>
                <li><a href="/employee/withdraw.php"><i class="fas fa-arrow-up"></i> Withdraw</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-university"></i> Create Bank Account</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="table-container">
            <div class="table-header">
                <h2>New Account Details</h2>
                <a href="/employee/accounts.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Accounts
                </a>
            </div>
            
            <form method="POST" style="padding: 2rem;">
                <input type="hidden" name="action" value="create">
                
                <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <!-- Customer Selection -->
                    <div class="form-group">
                        <label for="user_id">Customer <span class="required">*</span></label>
                        <select id="user_id" name="user_id" class="form-control" required>
                            <option value="">-- Select Customer --</option>
                            <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['user_id']; ?>" 
                                    <?php echo (isset($_POST['user_id']) && $_POST['user_id'] == $customer['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['email'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Account Type -->
                    <div class="form-group">
                        <label for="account_type">Account Type <span class="required">*</span></label>
                        <select id="account_type" name="account_type" class="form-control" required>
                            <option value="savings" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'savings') ? 'selected' : ''; ?>>Savings</option>
                            <option value="checking" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'checking') ? 'selected' : ''; ?>>Checking</option>
                            <option value="fixed_deposit" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'fixed_deposit') ? 'selected' : ''; ?>>Fixed Deposit</option>
                        </select>
                    </div>
                    
                    <!-- Account Name -->
                    <div class="form-group">
                        <label for="account_name">Account Name <span class="required">*</span></label>
                        <input type="text" id="account_name" name="account_name" class="form-control" 
                               value="<?php echo isset($_POST['account_name']) ? htmlspecialchars($_POST['account_name']) : ''; ?>" 
                               required maxlength="100" placeholder="e.g., John Savings Account">
                    </div>
                    
                    <!-- Branch Name -->
                    <div class="form-group">
                        <label for="branch_name">Branch Name <span class="required">*</span></label>
                        <input type="text" id="branch_name" name="branch_name" class="form-control" 
                               value="<?php echo isset($_POST['branch_name']) ? htmlspecialchars($_POST['branch_name']) : ''; ?>" 
                               required maxlength="100" placeholder="e.g., Main Branch">
                    </div>
                    
                    <!-- Initial Balance -->
                    <div class="form-group">
                        <label for="initial_balance">Initial Balance</label>
                        <input type="number" id="initial_balance" name="initial_balance" class="form-control" 
                               value="<?php echo isset($_POST['initial_balance']) ? htmlspecialchars($_POST['initial_balance']) : '0.00'; ?>" 
                               min="0" step="0.01" placeholder="0.00">
                    </div>
                    
                    <!-- Currency -->
                    <div class="form-group">
                        <label for="currency">Currency</label>
                        <select id="currency" name="currency" class="form-control">
                            <option value="USD" <?php echo (isset($_POST['currency']) && $_POST['currency'] === 'USD') ? 'selected' : ''; ?>>USD - US Dollar</option>
                            <option value="EUR" <?php echo (isset($_POST['currency']) && $_POST['currency'] === 'EUR') ? 'selected' : ''; ?>>EUR - Euro</option>
                            <option value="GBP" <?php echo (isset($_POST['currency']) && $_POST['currency'] === 'GBP') ? 'selected' : ''; ?>>GBP - British Pound</option>
                            <option value="BDT" <?php echo (isset($_POST['currency']) && $_POST['currency'] === 'BDT') ? 'selected' : ''; ?>>BDT - Bangladeshi Taka</option>
                        </select>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div class="form-actions" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Account
                    </button>
                    <a href="/employee/accounts.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
