<?php
/**
 * Transaction History - Admin
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

// Get filter parameters
$filters = [
    'search' => sanitize($_GET['search'] ?? ''),
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'amount_min' => $_GET['amount_min'] ?? '',
    'amount_max' => $_GET['amount_max'] ?? '',
    'type' => $_GET['type'] ?? 'all',
    'status' => $_GET['status'] ?? 'all',
    'account_id' => (int)($_GET['account_id'] ?? 0)
];

// Build query with filters
$where_conditions = [];
$params = [];

// Search filter (LIKE on transaction_reference, description)
if (!empty($filters['search'])) {
    $where_conditions[] = "(t.transaction_reference LIKE ? OR t.description LIKE ? OR fa.account_number LIKE ? OR ta.account_number LIKE ?)";
    $search_param = "%{$filters['search']}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

// Date range filter
if (!empty($filters['date_from'])) {
    $where_conditions[] = "t.created_at >= ?";
    $params[] = $filters['date_from'] . ' 00:00:00';
}
if (!empty($filters['date_to'])) {
    $where_conditions[] = "t.created_at <= ?";
    $params[] = $filters['date_to'] . ' 23:59:59';
}

// Amount range filter
if (!empty($filters['amount_min'])) {
    $where_conditions[] = "t.amount >= ?";
    $params[] = (float)$filters['amount_min'];
}
if (!empty($filters['amount_max'])) {
    $where_conditions[] = "t.amount <= ?";
    $params[] = (float)$filters['amount_max'];
}

// Type filter
if ($filters['type'] !== 'all') {
    $where_conditions[] = "t.transaction_type = ?";
    $params[] = $filters['type'];
}

// Status filter
if ($filters['status'] !== 'all') {
    $where_conditions[] = "t.status = ?";
    $params[] = $filters['status'];
}

// Account filter
if ($filters['account_id'] > 0) {
    $where_conditions[] = "(t.from_account_id = ? OR t.to_account_id = ?)";
    $params[] = $filters['account_id'];
    $params[] = $filters['account_id'];
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get transactions with joins
$transactions_sql = "SELECT t.*, 
                       fa.account_number as from_account, fa.account_name as from_account_name,
                       ta.account_number as to_account, ta.account_name as to_account_name,
                       fu.full_name as from_user, tu.full_name as to_user,
                       pu.full_name as processed_by_name
                FROM transactions t
                LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                LEFT JOIN users fu ON fa.user_id = fu.user_id
                LEFT JOIN users tu ON ta.user_id = tu.user_id
                LEFT JOIN users pu ON t.processed_by = pu.user_id
                $where_clause
                ORDER BY t.created_at DESC";

$transactions_result = executeQuery($transactions_sql, $params);
$transactions = fetchAll($transactions_result);

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total_count,
                SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
                SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END) as total_withdrawals,
                SUM(CASE WHEN transaction_type = 'transfer' THEN amount ELSE 0 END) as total_transfers,
                SUM(amount) as total_amount
              FROM transactions t
              LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
              LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
              $where_clause";
$stats_result = executeQuery($stats_sql, $params);
$stats = fetchOne($stats_result);

// Get accounts for dropdown
$accounts_sql = "SELECT a.account_id, a.account_number, a.account_name, u.full_name 
                 FROM accounts a 
                 LEFT JOIN users u ON a.user_id = u.user_id 
                 ORDER BY a.account_number";
$accounts_result = executeQuery($accounts_sql);
$accounts = fetchAll($accounts_result);
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
                <li><a href="/admin/accounts.php"><i class="fas fa-university"></i> All Accounts</a></li>
                <li><a href="/admin/transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
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
            <h1><i class="fas fa-exchange-alt"></i> Transaction History</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Transactions</h3>
                    <div class="stat-value"><?php echo number_format($stats['total_count'] ?? 0); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Deposits</h3>
                    <div class="stat-value">$<?php echo number_format($stats['total_deposits'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Withdrawals</h3>
                    <div class="stat-value">$<?php echo number_format($stats['total_withdrawals'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Transfers</h3>
                    <div class="stat-value">$<?php echo number_format($stats['total_transfers'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-container" style="margin-bottom: 1rem;">
            <div class="table-header">
                <h2><i class="fas fa-filter"></i> Filters</h2>
                <a href="/admin/transactions.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-undo"></i> Reset Filters
                </a>
            </div>
            <form method="GET" style="padding: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <!-- Search -->
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['search']); ?>"
                               placeholder="Reference, description, account...">
                    </div>
                    
                    <!-- Account -->
                    <div class="form-group">
                        <label for="account_id">Account</label>
                        <select id="account_id" name="account_id" class="form-control">
                            <option value="0">All Accounts</option>
                            <?php foreach ($accounts as $acc): ?>
                            <option value="<?php echo $acc['account_id']; ?>" 
                                    <?php echo $filters['account_id'] == $acc['account_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($acc['account_number'] . ' - ' . $acc['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Type -->
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" class="form-control">
                            <option value="all">All Types</option>
                            <option value="deposit" <?php echo $filters['type'] === 'deposit' ? 'selected' : ''; ?>>Deposit</option>
                            <option value="withdrawal" <?php echo $filters['type'] === 'withdrawal' ? 'selected' : ''; ?>>Withdrawal</option>
                            <option value="transfer" <?php echo $filters['type'] === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                            <option value="fee" <?php echo $filters['type'] === 'fee' ? 'selected' : ''; ?>>Fee</option>
                            <option value="interest" <?php echo $filters['type'] === 'interest' ? 'selected' : ''; ?>>Interest</option>
                        </select>
                    </div>
                    
                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="all">All Status</option>
                            <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $filters['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <!-- Date From -->
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                    </div>
                    
                    <!-- Date To -->
                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                    </div>
                    
                    <!-- Amount Min -->
                    <div class="form-group">
                        <label for="amount_min">Min Amount</label>
                        <input type="number" id="amount_min" name="amount_min" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['amount_min']); ?>"
                               step="0.01" placeholder="0.00">
                    </div>
                    
                    <!-- Amount Max -->
                    <div class="form-group">
                        <label for="amount_max">Max Amount</label>
                        <input type="number" id="amount_max" name="amount_max" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['amount_max']); ?>"
                               step="0.01" placeholder="0.00">
                    </div>
                </div>
                
                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="/admin/transactions.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Transaction List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Transactions</h2>
                <span class="text-muted">Showing <?php echo count($transactions); ?> results</span>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="min-width: 1200px;">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>From Account</th>
                            <th>To Account</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($txn['transaction_reference']); ?></code></td>
                            <td><?php echo date('M d, Y H:i', strtotime($txn['created_at'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $txn['transaction_type'] === 'deposit' ? 'success' : ($txn['transaction_type'] === 'withdrawal' ? 'danger' : 'info'); ?>">
                                    <?php echo ucfirst($txn['transaction_type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($txn['from_account']): ?>
                                    <small><strong><?php echo htmlspecialchars($txn['from_account']); ?></strong></small>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($txn['from_user'] ?? 'N/A'); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($txn['to_account']): ?>
                                    <small><strong><?php echo htmlspecialchars($txn['to_account']); ?></strong></small>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($txn['to_user'] ?? 'N/A'); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><strong>$<?php echo number_format($txn['amount'], 2); ?></strong></td>
                            <td><?php echo $txn['description'] ? htmlspecialchars(substr($txn['description'], 0, 50)) : '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $txn['status'] === 'completed' ? 'success' : ($txn['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($txn['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $txn['processed_by_name'] ? htmlspecialchars($txn['processed_by_name']) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 3rem;">
                                <i class="fas fa-exchange-alt" style="font-size: 2rem; color: #ccc; margin-bottom: 1rem; display: block;"></i>
                                No transactions found matching the filters.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
