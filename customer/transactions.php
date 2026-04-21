<?php
/**
 * Transaction History - Customer
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$user_id = $_SESSION['user_id'];

// Get user's account IDs for filtering
$account_sql = "SELECT account_id FROM accounts WHERE user_id = ?";
$account_result = executeQuery($account_sql, [$user_id]);
$user_accounts = fetchAll($account_result);
$account_ids = array_column($user_accounts, 'account_id');

// If user has no accounts, show empty state
if (empty($account_ids)) {
    $transactions = [];
    $stats = ['count' => 0, 'deposits' => 0, 'withdrawals' => 0, 'transfers' => 0];
} else {
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
    
    // User's accounts only
    $placeholders = implode(',', array_fill(0, count($account_ids), '?'));
    $where_conditions[] = "(t.from_account_id IN ($placeholders) OR t.to_account_id IN ($placeholders))";
    $params = array_merge($params, $account_ids, $account_ids);
    
    // Search filter
    if (!empty($filters['search'])) {
        $where_conditions[] = "(t.transaction_reference LIKE ? OR t.description LIKE ?)";
        $search_param = "%{$filters['search']}%";
        $params = array_merge($params, [$search_param, $search_param]);
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
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get transactions
    $transactions_sql = "SELECT t.*, 
                           fa.account_number as from_account, fa.account_name as from_account_name,
                           ta.account_number as to_account, ta.account_name as to_account_name,
                           fu.full_name as from_user, tu.full_name as to_user,
                           CASE WHEN t.from_account_id IN ($placeholders) THEN 'debit' ELSE 'credit' END as direction
                    FROM transactions t
                    LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                    LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                    LEFT JOIN users fu ON fa.user_id = fu.user_id
                    LEFT JOIN users tu ON ta.user_id = tu.user_id
                    $where_clause
                    ORDER BY t.created_at DESC";
    
    // Add placeholders for direction check
    $params = array_merge($params, $account_ids);
    
    $transactions_result = executeQuery($transactions_sql, $params);
    $transactions = fetchAll($transactions_result);
    
    // Get statistics
    $stats_sql = "SELECT 
                    COUNT(*) as count,
                    SUM(CASE WHEN t.to_account_id IN ($placeholders) AND t.status = 'APPROVED' THEN amount ELSE 0 END) as deposits,
                    SUM(CASE WHEN t.from_account_id IN ($placeholders) AND t.status = 'APPROVED' THEN amount ELSE 0 END) as withdrawals
                  FROM transactions t
                  $where_clause";
    $stats_result = executeQuery($stats_sql, $params);
    $stats = fetchOne($stats_result);
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
                <li><a href="/customer/transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="/customer/transfer.php"><i class="fas fa-paper-plane"></i> Transfer</a></li>
                <li><a href="/customer/deposit.php"><i class="fas fa-arrow-down"></i> Deposit</a></li>
                <li><a href="/customer/withdraw.php"><i class="fas fa-arrow-up"></i> Withdraw</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-exchange-alt"></i> My Transaction History</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Received</h3>
                    <div class="stat-value">$<?php echo number_format($stats['deposits'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Sent</h3>
                    <div class="stat-value">$<?php echo number_format($stats['withdrawals'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-container" style="margin-bottom: 1rem;">
            <div class="table-header">
                <h2><i class="fas fa-filter"></i> Filters</h2>
                <a href="/customer/transactions.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
            <form method="GET" style="padding: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <!-- Search -->
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>"
                               placeholder="Reference or description...">
                    </div>
                    
                    <!-- Type -->
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" class="form-control">
                            <option value="all">All Types</option>
                            <option value="deposit" <?php echo (isset($filters['type']) && $filters['type'] === 'deposit') ? 'selected' : ''; ?>>Deposit</option>
                            <option value="withdrawal" <?php echo (isset($filters['type']) && $filters['type'] === 'withdrawal') ? 'selected' : ''; ?>>Withdrawal</option>
                            <option value="transfer" <?php echo (isset($filters['type']) && $filters['type'] === 'transfer') ? 'selected' : ''; ?>>Transfer</option>
                        </select>
                    </div>
                    
                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="all">All Status</option>
                            <option value="APPROVED" <?php echo (isset($filters['status']) && $filters['status'] === 'APPROVED') ? 'selected' : ''; ?>>Approved</option>
                            <option value="PENDING" <?php echo (isset($filters['status']) && $filters['status'] === 'PENDING') ? 'selected' : ''; ?>>Pending</option>
                            <option value="REJECTED" <?php echo (isset($filters['status']) && $filters['status'] === 'REJECTED') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <!-- Date From -->
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                    </div>
                    
                    <!-- Date To -->
                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                    </div>
                    
                    <!-- Amount Min -->
                    <div class="form-group">
                        <label for="amount_min">Min Amount</label>
                        <input type="number" id="amount_min" name="amount_min" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['amount_min'] ?? ''); ?>"
                               step="0.01" placeholder="0.00">
                    </div>
                    
                    <!-- Amount Max -->
                    <div class="form-group">
                        <label for="amount_max">Max Amount</label>
                        <input type="number" id="amount_max" name="amount_max" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['amount_max'] ?? ''); ?>"
                               step="0.01" placeholder="0.00">
                    </div>
                </div>
                
                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="/customer/transactions.php" class="btn btn-secondary">
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
            
            <?php if (empty($transactions)): ?>
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-exchange-alt" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <p>No transactions found.</p>
                <?php if (empty($user_accounts)): ?>
                <p class="text-muted">You don't have any accounts yet.</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>From/To</th>
                            <th>Amount</th>
                            <th>Direction</th>
                            <th>Status</th>
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
                                <?php if ($txn['direction'] === 'debit'): ?>
                                    <small>To: <strong><?php echo htmlspecialchars($txn['to_account'] ?? 'External'); ?></strong></small>
                                    <?php if ($txn['to_user'] && $txn['to_user'] !== $_SESSION['full_name']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($txn['to_user']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small>From: <strong><?php echo htmlspecialchars($txn['from_account'] ?? 'External'); ?></strong></small>
                                    <?php if ($txn['from_user'] && $txn['from_user'] !== $_SESSION['full_name']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($txn['from_user']); ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><strong>$<?php echo number_format($txn['amount'], 2); ?></strong></td>
                            <td>
                                <?php if ($txn['direction'] === 'credit'): ?>
                                <span class="badge badge-success"><i class="fas fa-arrow-down"></i> Received</span>
                                <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Sent</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $txn['status'] === 'APPROVED' ? 'success' : ($txn['status'] === 'PENDING' ? 'warning' : 'danger'); ?>">
                                    <?php echo $txn['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
