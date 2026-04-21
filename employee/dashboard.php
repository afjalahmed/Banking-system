<?php
/**
 * Employee Dashboard
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

// Get statistics
$total_customers_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
$total_customers_result = executeQuery($total_customers_sql);
$total_customers = fetchOne($total_customers_result)['count'];

$total_accounts_sql = "SELECT COUNT(*) as count FROM accounts";
$total_accounts_result = executeQuery($total_accounts_sql);
$total_accounts = fetchOne($total_accounts_result)['count'];

$pending_transactions_sql = "SELECT COUNT(*) as count FROM transactions WHERE status = 'pending'";
$pending_transactions_result = executeQuery($pending_transactions_sql);
$pending_transactions = fetchOne($pending_transactions_result)['count'];

$today_deposits_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE transaction_type = 'deposit' AND DATE(created_at) = CURDATE()";
$today_deposits_result = executeQuery($today_deposits_sql);
$today_deposits = fetchOne($today_deposits_result)['total'];

// Get recent customers
$recent_customers_sql = "SELECT user_id, username, email, full_name, phone, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC LIMIT 10";
$recent_customers_result = executeQuery($recent_customers_sql);
$recent_customers = fetchAll($recent_customers_result);

// Get pending transactions
$pending_transactions_list_sql = "SELECT t.*, fa.account_number as from_account, ta.account_number as to_account FROM transactions t LEFT JOIN accounts fa ON t.from_account_id = fa.account_id LEFT JOIN accounts ta ON t.to_account_id = ta.account_id WHERE t.status = 'pending' ORDER BY t.created_at DESC LIMIT 10";
$pending_transactions_list_result = executeQuery($pending_transactions_list_sql);
$pending_transactions_list = fetchAll($pending_transactions_list_result);
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-sidebar-header">
            <h2><i class="fas fa-user-tie"></i> Employee Panel</h2>
        </div>
        <nav class="dashboard-sidebar-menu">
            <ul>
                <li><a href="/employee/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/employee/add_customer.php"><i class="fas fa-user-plus"></i> Add Customer</a></li>
                <li><a href="/employee/create_account.php"><i class="fas fa-university"></i> Create Account</a></li>
                <li><a href="/employee/deposit.php"><i class="fas fa-arrow-down"></i> Deposit</a></li>
                <li><a href="/employee/withdraw.php"><i class="fas fa-arrow-up"></i> Withdraw</a></li>
                <li><a href="/employee/pending_transactions.php"><i class="fas fa-clock"></i> Pending Transactions</a></li>
                <li><a href="/employee/customers.php"><i class="fas fa-users"></i> View Customers</a></li>
                <li><a href="/employee/transactions.php"><i class="fas fa-exchange-alt"></i> Transaction History</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Employee Dashboard</h1>
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
                    <h3>Total Customers</h3>
                    <div class="stat-value"><?php echo number_format($total_customers); ?></div>
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
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pending Transactions</h3>
                    <div class="stat-value"><?php echo number_format($pending_transactions); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>Today's Deposits</h3>
                    <div class="stat-value">$<?php echo number_format($today_deposits, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div style="padding: 1.5rem;">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="/employee/add_customer.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add Customer
                    </a>
                    <a href="/employee/create_account.php" class="btn btn-success">
                        <i class="fas fa-university"></i> Create Account
                    </a>
                    <a href="/employee/deposit.php" class="btn btn-warning">
                        <i class="fas fa-arrow-down"></i> Deposit
                    </a>
                    <a href="/employee/withdraw.php" class="btn btn-danger">
                        <i class="fas fa-arrow-up"></i> Withdraw
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Customers -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-users"></i> Recent Customers</h2>
                <a href="/employee/customers.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_customers as $customer): ?>
                    <tr>
                        <td><?php echo $customer['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($customer['username']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($customer['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pending Transactions -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-clock"></i> Pending Transactions</h2>
                <a href="/employee/pending_transactions.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>From Account</th>
                        <th>To Account</th>
                        <th>Amount</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_transactions_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No pending transactions</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($pending_transactions_list as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['transaction_reference']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo ucfirst($transaction['transaction_type']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($transaction['from_account'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($transaction['to_account'] ?? '-'); ?></td>
                        <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                        <td>
                            <a href="/employee/process_transaction.php?id=<?php echo $transaction['transaction_id']; ?>" class="btn btn-sm btn-success">Process</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
