<?php
/**
 * View All Accounts (Employee)
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$search = sanitize($_GET['search'] ?? '');

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

if (!empty($search)) {
    $where_conditions[] = "(a.account_number LIKE ? OR a.account_name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all accounts with user information
$accounts_sql = "SELECT a.*, u.username, u.full_name, u.email, u.phone 
                 FROM accounts a 
                 LEFT JOIN users u ON a.user_id = u.user_id 
                 $where_clause 
                 ORDER BY a.created_at DESC";
$accounts_result = executeQuery($accounts_sql, $params);
$accounts = fetchAll($accounts_result);

// Get statistics
$total_accounts_sql = "SELECT COUNT(*) as count FROM accounts";
$total_accounts_result = executeQuery($total_accounts_sql);
$total_accounts = fetchOne($total_accounts_result)['count'];

$total_balance_sql = "SELECT SUM(balance) as total FROM accounts WHERE status = 'active'";
$total_balance_result = executeQuery($total_balance_sql);
$total_balance = fetchOne($total_balance_result)['total'] ?? 0;

$active_accounts_sql = "SELECT COUNT(*) as count FROM accounts WHERE status = 'active'";
$active_accounts_result = executeQuery($active_accounts_sql);
$active_accounts = fetchOne($active_accounts_result)['count'];
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
                <li><a href="/employee/accounts.php" class="active"><i class="fas fa-list"></i> All Accounts</a></li>
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
            <h1><i class="fas fa-university"></i> All Accounts</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics -->
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
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Accounts</h3>
                    <div class="stat-value"><?php echo number_format($active_accounts); ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-container">
            <div class="table-header">
                <h2>Account List</h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <a href="/employee/create_account.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Account
                    </a>
                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <input type="text" name="search" class="form-control" placeholder="Search..." 
                               value="<?php echo htmlspecialchars($search); ?>" style="width: 200px;">
                        <select name="type" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="savings" <?php echo $type_filter === 'savings' ? 'selected' : ''; ?>>Savings</option>
                            <option value="checking" <?php echo $type_filter === 'checking' ? 'selected' : ''; ?>>Checking</option>
                            <option value="fixed_deposit" <?php echo $type_filter === 'fixed_deposit' ? 'selected' : ''; ?>>Fixed Deposit</option>
                        </select>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="frozen" <?php echo $status_filter === 'frozen' ? 'selected' : ''; ?>>Frozen</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <?php if (!empty($search)): ?>
                        <a href="/employee/accounts.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Account Number</th>
                        <th>Account Name</th>
                        <th>Owner</th>
                        <th>Type</th>
                        <th>Branch</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($account['account_number']); ?></code></td>
                        <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($account['full_name']); ?></strong>
                                <br>
                                <small><?php echo htmlspecialchars($account['email']); ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo ucfirst($account['account_type']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($account['branch_name'] ?? 'N/A'); ?></td>
                        <td><strong>$<?php echo number_format($account['balance'], 2); ?></strong></td>
                        <td>
                            <span class="badge badge-<?php echo $account['status'] === 'active' ? 'success' : ($account['status'] === 'frozen' ? 'warning' : ($account['status'] === 'closed' ? 'danger' : 'secondary')); ?>">
                                <?php echo ucfirst($account['status']); ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="/employee/edit_account.php?id=<?php echo $account['account_id']; ?>" 
                               class="btn btn-sm btn-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($accounts)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No accounts found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
