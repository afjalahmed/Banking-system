<?php
/**
 * Transaction History - Employee
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
    'status' => $_GET['status'] ?? 'all'
];

// Build query with filters
$where_conditions = [];
$params = [];

// Search filter
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
                ORDER BY t.created_at DESC
                LIMIT 500";

$transactions_result = executeQuery($transactions_sql, $params);
$transactions = fetchAll($transactions_result);

// Get statistics for today
$today = date('Y-m-d');
$today_stats_sql = "SELECT 
                COUNT(*) as count,
                SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END) as deposits,
                SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END) as withdrawals
              FROM transactions 
              WHERE DATE(created_at) = ? AND status = 'APPROVED'";
$today_stats_result = executeQuery($today_stats_sql, [$today]);
$today_stats = fetchOne($today_stats_result);
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
                <li><a href="/employee/accounts.php"><i class="fas fa-list"></i> All Accounts</a></li>
                <li><a href="/employee/deposit.php"><i class="fas fa-arrow-down"></i> Deposit</a></li>
                <li><a href="/employee/withdraw.php"><i class="fas fa-arrow-up"></i> Withdraw</a></li>
                <li><a href="/employee/transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
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

        <!-- Today's Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>Today's Transactions</h3>
                    <div class="stat-value"><?php echo number_format($today_stats['count'] ?? 0); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-info">
                    <h3>Today's Deposits</h3>
                    <div class="stat-value">$<?php echo number_format($today_stats['deposits'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-info">
                    <h3>Today's Withdrawals</h3>
                    <div class="stat-value">$<?php echo number_format($today_stats['withdrawals'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-container" style="margin-bottom: 1rem;">
            <div class="table-header">
                <h2><i class="fas fa-filter"></i> Filters</h2>
                <a href="/employee/transactions.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-undo"></i> Reset
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
                    
                    <!-- Type -->
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" class="form-control">
                            <option value="all">All Types</option>
                            <option value="deposit" <?php echo $filters['type'] === 'deposit' ? 'selected' : ''; ?>>Deposit</option>
                            <option value="withdrawal" <?php echo $filters['type'] === 'withdrawal' ? 'selected' : ''; ?>>Withdrawal</option>
                            <option value="transfer" <?php echo $filters['type'] === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                        </select>
                    </div>
                    
                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="all">All Status</option>
                            <option value="PENDING" <?php echo $filters['status'] === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                            <option value="APPROVED" <?php echo $filters['status'] === 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                            <option value="REJECTED" <?php echo $filters['status'] === 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
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
                    <a href="/employee/transactions.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Transaction List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Transactions</h2>
                <span class="text-muted">Showing <?php echo count($transactions); ?> results (max 500)</span>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="min-width: 1100px;">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>From Account</th>
                            <th>To Account</th>
                            <th>Amount</th>
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
                            <td>
                                <span class="badge badge-<?php echo $txn['status'] === 'APPROVED' ? 'success' : ($txn['status'] === 'PENDING' ? 'warning' : 'danger'); ?>">
                                    <?php echo $txn['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $txn['processed_by_name'] ? htmlspecialchars($txn['processed_by_name']) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 3rem;">
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
