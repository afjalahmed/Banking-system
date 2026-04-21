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

// Get recent users
$recent_users_sql = "SELECT user_id, username, email, full_name, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 10";
$recent_users_result = executeQuery($recent_users_sql);
$recent_users = fetchAll($recent_users_result);

// Get recent audit logs
$recent_logs_sql = "SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY al.created_at DESC LIMIT 10";
$recent_logs_result = executeQuery($recent_logs_sql);
$recent_logs = fetchAll($recent_logs_result);

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
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics Cards -->
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

        <!-- Recent Audit Logs -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-history"></i> Recent Audit Logs</h2>
                <a href="/admin/audit_logs.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>IP Address</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td><?php echo $log['log_id']; ?></td>
                        <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                        <td>
                            <span class="badge badge-secondary">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
