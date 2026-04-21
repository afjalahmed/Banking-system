<?php
/**
 * Admin Dashboard
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

// Get statistics
$total_users_sql = "SELECT COUNT(*) as count FROM users";
$total_users_result = executeQuery($total_users_sql);
$total_users = fetchOne($total_users_result)['count'];

$total_accounts_sql = "SELECT COUNT(*) as count FROM accounts";
$total_accounts_result = executeQuery($total_accounts_sql);
$total_accounts = fetchOne($total_accounts_result)['count'];

$total_transactions_sql = "SELECT COUNT(*) as count FROM transactions";
$total_transactions_result = executeQuery($total_transactions_sql);
$total_transactions = fetchOne($total_transactions_result)['count'];

$total_balance_sql = "SELECT SUM(balance) as total FROM accounts WHERE status = 'active'";
$total_balance_result = executeQuery($total_balance_sql);
$total_balance = fetchOne($total_balance_result)['total'] ?? 0;

// Get balance statistics
$balance_stats_sql = "SELECT 
    AVG(balance) as avg_balance,
    MAX(balance) as max_balance,
    MIN(balance) as min_balance,
    COUNT(*) as active_account_count
FROM accounts WHERE status = 'active'";
$balance_stats_result = executeQuery($balance_stats_sql);
$balance_stats = fetchOne($balance_stats_result);

// Get recent users
$recent_users_sql = "SELECT user_id, username, email, full_name, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 10";
$recent_users_result = executeQuery($recent_users_sql);
$recent_users = fetchAll($recent_users_result);

// Get recent audit logs
$recent_logs_sql = "SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY al.created_at DESC LIMIT 10";
$recent_logs_result = executeQuery($recent_logs_sql);
$recent_logs = fetchAll($recent_logs_result);

// Get recent transactions
$recent_transactions_sql = "SELECT t.*, 
    fa.account_number as from_account, 
    ta.account_number as to_account,
    fu.full_name as from_user,
    tu.full_name as to_user
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
LEFT JOIN users fu ON fa.user_id = fu.user_id
LEFT JOIN users tu ON ta.user_id = tu.user_id
ORDER BY t.created_at DESC LIMIT 10";
$recent_transactions_result = executeQuery($recent_transactions_sql);
$recent_transactions = fetchAll($recent_transactions_result);

// Get user distribution by role
$role_distribution_sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$role_distribution_result = executeQuery($role_distribution_sql);
$role_distribution = fetchAll($role_distribution_result);
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-sidebar-header">
            <h2><i class="fas fa-user-shield"></i> Admin Panel</h2>
        </div>
        <nav class="dashboard-sidebar-menu">
            <ul>
                <li><a href="/admin/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/admin/users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="/admin/accounts.php"><i class="fas fa-university"></i> All Accounts</a></li>
                <li><a href="/admin/create_account.php"><i class="fas fa-plus-circle"></i> Create Account</a></li>
                <li><a href="/admin/transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="/admin/pending_transactions.php"><i class="fas fa-clock"></i> Pending Approval</a></li>
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
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Primary Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <div class="stat-value"><?php echo number_format($total_users); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-university"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Accounts</h3>
                    <div class="stat-value"><?php echo number_format($total_accounts); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Transactions</h3>
                    <div class="stat-value"><?php echo number_format($total_transactions); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Balance</h3>
                    <div class="stat-value">$<?php echo number_format($total_balance, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Balance Statistics Cards -->
        <div class="stats-grid" style="margin-top: 1rem;">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>Average Balance</h3>
                    <div class="stat-value">$<?php echo number_format($balance_stats['avg_balance'] ?? 0, 2); ?></div>
                    <small class="text-muted">Per active account</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-info">
                    <h3>Highest Balance</h3>
                    <div class="stat-value">$<?php echo number_format($balance_stats['max_balance'] ?? 0, 2); ?></div>
                    <small class="text-muted">Maximum in system</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #fd7e14);">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-info">
                    <h3>Lowest Balance</h3>
                    <div class="stat-value">$<?php echo number_format($balance_stats['min_balance'] ?? 0, 2); ?></div>
                    <small class="text-muted">Minimum in system</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #6f42c1, #e83e8c);">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Accounts</h3>
                    <div class="stat-value"><?php echo number_format($balance_stats['active_account_count'] ?? 0); ?></div>
                    <small class="text-muted">With balance data</small>
                </div>
            </div>
        </div>

        <!-- Role Distribution -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-chart-pie"></i> User Distribution by Role</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($role_distribution as $role): ?>
                    <tr>
                        <td>
                            <span class="badge badge-info">
                                <?php echo ucfirst($role['role']); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($role['count']); ?></td>
                        <td><?php echo round(($role['count'] / $total_users) * 100, 2); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Users -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-users"></i> Recent Users</h2>
                <a href="/admin/users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'inactive' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <!-- Recent Transactions -->
            <div class="table-container">
                <div class="table-header">
                    <h2><i class="fas fa-exchange-alt"></i> Recent Transactions</h2>
                    <a href="/admin/transactions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div style="overflow-x: auto;">
                    <table style="min-width: 500px;">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $txn): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars(substr($txn['transaction_reference'], -12)); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $txn['transaction_type'] === 'deposit' ? 'success' : ($txn['transaction_type'] === 'withdrawal' ? 'danger' : 'info'); ?>">
                                        <?php echo ucfirst($txn['transaction_type']); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($txn['amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $txn['status'] === 'completed' ? 'success' : ($txn['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($txn['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, H:i', strtotime($txn['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No recent transactions</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Audit Logs -->
            <div class="table-container">
                <div class="table-header">
                    <h2><i class="fas fa-history"></i> Recent Audit Logs</h2>
                    <a href="/admin/audit_logs.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div style="overflow-x: auto;">
                    <table style="min-width: 500px;">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Table</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                <td>
                                    <span class="badge badge-secondary">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                                <td><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_logs)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No recent logs</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
