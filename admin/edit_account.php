<?php
/**
 * Edit Bank Account (Admin)
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$errors = [];
$success = false;

// Get account ID
$account_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($account_id <= 0) {
    header('Location: /admin/accounts.php?error=Invalid account ID');
    exit;
}

// Fetch account data
$account_sql = "SELECT a.*, u.full_name, u.email, u.username FROM accounts a 
                LEFT JOIN users u ON a.user_id = u.user_id 
                WHERE a.account_id = ?";
$account_result = executeQuery($account_sql, [$account_id]);
$account = fetchOne($account_result);

if (!$account) {
    header('Location: /admin/accounts.php?error=Account not found');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        // Sanitize inputs
        $account_name = sanitize($_POST['account_name'] ?? '');
        $branch_name = sanitize($_POST['branch_name'] ?? '');
        $account_type = sanitize($_POST['account_type'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        
        // Validation
        if (empty($account_name)) {
            $errors[] = "Account name is required";
        }
        
        if (empty($branch_name)) {
            $errors[] = "Branch name is required";
        }
        
        if (!in_array($account_type, ['savings', 'checking', 'fixed_deposit'])) {
            $errors[] = "Invalid account type";
        }
        
        if (!in_array($status, ['active', 'inactive', 'frozen', 'closed'])) {
            $errors[] = "Invalid status";
        }
        
        // Check for duplicate account name for same user (excluding current account)
        if (empty($errors)) {
            $check_sql = "SELECT account_id FROM accounts WHERE user_id = ? AND account_name = ? AND account_id != ?";
            $check_result = executeQuery($check_sql, [$account['user_id'], $account_name, $account_id]);
            if (fetchOne($check_result)) {
                $errors[] = "Account name already exists for this customer";
            }
        }
        
        // If no errors, update account
        if (empty($errors)) {
            $sql = "UPDATE accounts SET account_name = ?, branch_name = ?, account_type = ?, status = ? WHERE account_id = ?";
            executeQuery($sql, [$account_name, $branch_name, $account_type, $status, $account_id]);
            
            // Log action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'UPDATE', 'accounts', $account_id, json_encode(['account_name' => $account_name, 'status' => $status])]);
            
            $success = "Account updated successfully";
            
            // Refresh account data
            $account_result = executeQuery($account_sql, [$account_id]);
            $account = fetchOne($account_result);
        }
    }
    
    // Delete account
    if ($action === 'delete') {
        // Check if account has transactions
        $txn_check_sql = "SELECT COUNT(*) as count FROM transactions WHERE from_account_id = ? OR to_account_id = ?";
        $txn_check_result = executeQuery($txn_check_sql, [$account_id, $account_id]);
        $txn_count = fetchOne($txn_check_result)['count'];
        
        if ($txn_count > 0) {
            $errors[] = "Cannot delete account with transaction history. Consider closing the account instead.";
        } else {
            $sql = "DELETE FROM accounts WHERE account_id = ?";
            executeQuery($sql, [$account_id]);
            
            // Log action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'DELETE', 'accounts', $account_id]);
            
            header('Location: /admin/accounts.php?success=Account deleted successfully');
            exit;
        }
    }
}

// Get all customers for potential ownership transfer
$customers_sql = "SELECT user_id, username, full_name, email FROM users WHERE role = 'customer' AND status = 'active' ORDER BY full_name";
$customers_result = executeQuery($customers_sql);
$customers = fetchAll($customers_result);
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-sidebar-header">
            <h2><i class="fas fa-user-shield"></i> Admin Panel</h2>
        </div>
        <nav class="dashboard-sidebar-menu">
            <ul>
                <li><a href="/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/admin/users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="/admin/accounts.php" class="active"><i class="fas fa-university"></i> All Accounts</a></li>
                <li><a href="/admin/transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="/admin/audit_logs.php"><i class="fas fa-history"></i> Audit Logs</a></li>
                <li><a href="/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="/admin/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="/admin/departments.php"><i class="fas fa-building"></i> Departments</a></li>
                <li><a href="/admin/designations.php"><i class="fas fa-id-badge"></i> Designations</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-edit"></i> Edit Account</h1>
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

        <!-- Account Info Card -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Account Information</h2>
            </div>
            <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <small class="text-muted">Account Number</small>
                    <div><code><?php echo htmlspecialchars($account['account_number']); ?></code></div>
                </div>
                <div>
                    <small class="text-muted">Owner</small>
                    <div><strong><?php echo htmlspecialchars($account['full_name']); ?></strong></div>
                    <small><?php echo htmlspecialchars($account['email']); ?></small>
                </div>
                <div>
                    <small class="text-muted">Current Balance</small>
                    <div><strong>$<?php echo number_format($account['balance'], 2); ?></strong></div>
                </div>
                <div>
                    <small class="text-muted">Created</small>
                    <div><?php echo date('M d, Y', strtotime($account['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="table-container">
            <div class="table-header">
                <h2>Edit Account Details</h2>
                <a href="/admin/accounts.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Accounts
                </a>
            </div>
            
            <form method="POST" style="padding: 2rem;">
                <input type="hidden" name="action" value="update">
                
                <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <!-- Account Name -->
                    <div class="form-group">
                        <label for="account_name">Account Name <span class="required">*</span></label>
                        <input type="text" id="account_name" name="account_name" class="form-control" 
                               value="<?php echo htmlspecialchars($account['account_name']); ?>" 
                               required maxlength="100">
                    </div>
                    
                    <!-- Branch Name -->
                    <div class="form-group">
                        <label for="branch_name">Branch Name <span class="required">*</span></label>
                        <input type="text" id="branch_name" name="branch_name" class="form-control" 
                               value="<?php echo htmlspecialchars($account['branch_name']); ?>" 
                               required maxlength="100">
                    </div>
                    
                    <!-- Account Type -->
                    <div class="form-group">
                        <label for="account_type">Account Type <span class="required">*</span></label>
                        <select id="account_type" name="account_type" class="form-control" required>
                            <option value="savings" <?php echo $account['account_type'] === 'savings' ? 'selected' : ''; ?>>Savings</option>
                            <option value="checking" <?php echo $account['account_type'] === 'checking' ? 'selected' : ''; ?>>Checking</option>
                            <option value="fixed_deposit" <?php echo $account['account_type'] === 'fixed_deposit' ? 'selected' : ''; ?>>Fixed Deposit</option>
                        </select>
                    </div>
                    
                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" <?php echo $account['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $account['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="frozen" <?php echo $account['status'] === 'frozen' ? 'selected' : ''; ?>>Frozen</option>
                            <option value="closed" <?php echo $account['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Account
                    </button>
                    <a href="/admin/accounts.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="action" value="delete" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to delete this account? This action cannot be undone if there are no transactions.');">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
