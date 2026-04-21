<?php
/**
 * Admin Accounts Management
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'freeze' || $action === 'unfreeze') {
        $account_id = (int)$_POST['account_id'];
        $new_status = $action === 'freeze' ? 'frozen' : 'active';
        
        $update_sql = "UPDATE accounts SET status = ? WHERE account_id = ?";
        executeQuery($update_sql, [$new_status, $account_id]);
        
        // Log the action
        $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values) VALUES (?, ?, ?, ?, ?, ?)";
        executeQuery($log_sql, [$_SESSION['user_id'], 'UPDATE', 'accounts', $account_id, null, json_encode(['status' => $new_status])]);
        
        header('Location: /admin/accounts.php?success=Account status updated');
        exit;
    }
    
    if ($action === 'close') {
        $account_id = (int)$_POST['account_id'];
        
        $update_sql = "UPDATE accounts SET status = 'closed' WHERE account_id = ?";
        executeQuery($update_sql, [$account_id]);
        
        // Log the action
        $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
        executeQuery($log_sql, [$_SESSION['user_id'], 'UPDATE', 'accounts', $account_id, json_encode(['status' => 'closed'])]);
        
        header('Location: /admin/accounts.php?success=Account closed');
        exit;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if ($type_filter !== 'all') {
    $where_conditions[] = "a.account_type = ?";
    $params[] = $type_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all accounts with user information
$accounts_sql = "SELECT a.*, u.username, u.full_name, u.email 
                 FROM accounts a 
                 LEFT JOIN users u ON a.user_id = u.user_id 
                 $where_clause 
                 ORDER BY a.created_at DESC";
$accounts_result = executeQuery($accounts_sql, $params);
$accounts = fetchAll($accounts_result);

// Get account statistics
$total_accounts_sql = "SELECT COUNT(*) as count FROM accounts";
$total_accounts_result = executeQuery($total_accounts_sql);
$total_accounts = fetchOne($total_accounts_result)['count'];

$total_balance_sql = "SELECT SUM(balance) as total FROM accounts WHERE status = 'active'";
$total_balance_result = executeQuery($total_balance_sql);
$total_balance = fetchOne($total_balance_result)['total'] ?? 0;

$frozen_accounts_sql = "SELECT COUNT(*) as count FROM accounts WHERE status = 'frozen'";
$frozen_accounts_result = executeQuery($frozen_accounts_sql);
$frozen_accounts = fetchOne($frozen_accounts_result)['count'];

$accounts_by_type_sql = "SELECT account_type, COUNT(*) as count FROM accounts GROUP BY account_type";
$accounts_by_type_result = executeQuery($accounts_by_type_sql);
$accounts_by_type = fetchAll($accounts_by_type_result);
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
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-university"></i> All Accounts</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-university"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Accounts</h3>
                    <div class="stat-value"><?php echo number_format($total_accounts); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Balance</h3>
                    <div class="stat-value">$<?php echo number_format($total_balance, 2); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-snowflake"></i>
                </div>
                <div class="stat-info">
                    <h3>Frozen Accounts</h3>
                    <div class="stat-value"><?php echo number_format($frozen_accounts); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stat-info">
                    <h3>Account Types</h3>
                    <div class="stat-value"><?php echo count($accounts_by_type); ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-container">
            <div class="table-header">
                <h2>Account List</h2>
                <div class="filter-controls">
                    <form method="GET" class="filter-form">
                        <select name="type" onchange="this.form.submit()">
                            <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="savings" <?php echo $type_filter === 'savings' ? 'selected' : ''; ?>>Savings</option>
                            <option value="checking" <?php echo $type_filter === 'checking' ? 'selected' : ''; ?>>Checking</option>
                            <option value="fixed_deposit" <?php echo $type_filter === 'fixed_deposit' ? 'selected' : ''; ?>>Fixed Deposit</option>
                        </select>
                        <select name="status" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="frozen" <?php echo $status_filter === 'frozen' ? 'selected' : ''; ?>>Frozen</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </form>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Account Number</th>
                        <th>Account Name</th>
                        <th>Owner</th>
                        <th>Type</th>
                        <th>Balance</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?php echo $account['account_id']; ?></td>
                        <td><code><?php echo htmlspecialchars($account['account_number']); ?></code></td>
                        <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($account['full_name'] ?? 'Unknown'); ?></strong>
                                <br>
                                <small><?php echo htmlspecialchars($account['username'] ?? 'N/A'); ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo ucfirst($account['account_type']); ?>
                            </span>
                        </td>
                        <td><strong>$<?php echo number_format($account['balance'], 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($account['currency']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $account['status'] === 'active' ? 'success' : ($account['status'] === 'frozen' ? 'warning' : ($account['status'] === 'closed' ? 'danger' : 'secondary')); ?>">
                                <?php echo ucfirst($account['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($account['created_at'])); ?></td>
                        <td class="actions">
                            <?php if ($account['status'] === 'active'): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Freeze this account?');">
                                <input type="hidden" name="action" value="freeze">
                                <input type="hidden" name="account_id" value="<?php echo $account['account_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-warning" title="Freeze Account">
                                    <i class="fas fa-snowflake"></i>
                                </button>
                            </form>
                            <?php elseif ($account['status'] === 'frozen'): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Unfreeze this account?');">
                                <input type="hidden" name="action" value="unfreeze">
                                <input type="hidden" name="account_id" value="<?php echo $account['account_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success" title="Unfreeze Account">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if ($account['status'] !== 'closed'): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Close this account? This action cannot be undone.');">
                                <input type="hidden" name="action" value="close">
                                <input type="hidden" name="account_id" value="<?php echo $account['account_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Close Account">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted">Closed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($accounts)): ?>
            <div class="empty-state">
                <i class="fas fa-university"></i>
                <p>No accounts found matching the selected filters.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
