<?php
/**
 * Customer Dashboard
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$user_id = $_SESSION['user_id'];

// Get user's accounts
$accounts_sql = "SELECT * FROM accounts WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC";
$accounts_result = executeQuery($accounts_sql, [$user_id]);
$accounts = fetchAll($accounts_result);

// Calculate total balance
$total_balance = 0;
foreach ($accounts as $account) {
    $total_balance += $account['balance'];
}

// Get recent transactions
$recent_transactions_sql = "SELECT t.*, fa.account_number as from_account, ta.account_number as to_account FROM transactions t LEFT JOIN accounts fa ON t.from_account_id = fa.account_id LEFT JOIN accounts ta ON t.to_account_id = ta.account_id WHERE t.from_account_id IN (SELECT account_id FROM accounts WHERE user_id = ?) OR t.to_account_id IN (SELECT account_id FROM accounts WHERE user_id = ?) ORDER BY t.created_at DESC LIMIT 10";
$recent_transactions_result = executeQuery($recent_transactions_sql, [$user_id, $user_id]);
$recent_transactions = fetchAll($recent_transactions_result);

// Get transaction statistics
$total_deposits_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE transaction_type = 'deposit' AND (from_account_id IN (SELECT account_id FROM accounts WHERE user_id = ?) OR to_account_id IN (SELECT account_id FROM accounts WHERE user_id = ?))";
$total_deposits_result = executeQuery($total_deposits_sql, [$user_id, $user_id]);
$total_deposits = fetchOne($total_deposits_result)['total'];

$total_withdrawals_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE transaction_type = 'withdrawal' AND (from_account_id IN (SELECT account_id FROM accounts WHERE user_id = ?) OR to_account_id IN (SELECT account_id FROM accounts WHERE user_id = ?))";
$total_withdrawals_result = executeQuery($total_withdrawals_sql, [$user_id, $user_id]);
$total_withdrawals = fetchOne($total_withdrawals_result)['total'];
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-sidebar-header">
            <h2><i class="fas fa-user"></i> My Account</h2>
        </div>
        <nav class="dashboard-sidebar-menu">
            <ul>
                <li><a href="/customer/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/customer/accounts.php"><i class="fas fa-university"></i> My Accounts</a></li>
                <li><a href="/customer/transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="/customer/transfer.php"><i class="fas fa-paper-plane"></i> Transfer</a></li>
                <li><a href="/customer/deposit.php"><i class="fas fa-arrow-down"></i> Deposit</a></li>
                <li><a href="/customer/withdraw.php"><i class="fas fa-arrow-up"></i> Withdraw</a></li>
                <li><a href="/customer/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/customer/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Customer Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Balance</h3>
                    <div class="stat-value">$<?php echo number_format($total_balance, 2); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-university"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Accounts</h3>
                    <div class="stat-value"><?php echo count($accounts); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Deposits</h3>
                    <div class="stat-value">$<?php echo number_format($total_deposits, 2); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Withdrawals</h3>
                    <div class="stat-value">$<?php echo number_format($total_withdrawals, 2); ?></div>
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
                    <a href="/customer/transfer.php" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Transfer Money
                    </a>
                    <a href="/customer/deposit.php" class="btn btn-success">
                        <i class="fas fa-arrow-down"></i> Deposit
                    </a>
                    <a href="/customer/withdraw.php" class="btn btn-danger">
                        <i class="fas fa-arrow-up"></i> Withdraw
                    </a>
                    <a href="/customer/transactions.php" class="btn btn-secondary">
                        <i class="fas fa-history"></i> View History
                    </a>
                </div>
            </div>
        </div>

        <!-- My Accounts -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-university"></i> My Accounts</h2>
                <a href="/customer/accounts.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <?php if (empty($accounts)): ?>
            <div style="padding: 2rem; text-align: center; color: var(--gray-color);">
                <i class="fas fa-university" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>You don't have any accounts yet.</p>
                <p class="mt-2">Please contact customer service to create an account.</p>
            </div>
            <?php else: ?>
            <div style="padding: 1.5rem;">
                <?php foreach ($accounts as $account): ?>
                <div class="account-card">
                    <h3><?php echo htmlspecialchars($account['account_name']); ?></h3>
                    <div class="account-number"><?php echo htmlspecialchars($account['account_number']); ?></div>
                    <div class="account-balance">$<?php echo number_format($account['balance'], 2); ?></div>
                    <div>
                        <span class="account-type"><?php echo ucfirst($account['account_type']); ?></span>
                        <span class="badge badge-success" style="margin-left: 0.5rem;"><?php echo ucfirst($account['status']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-exchange-alt"></i> Recent Transactions</h2>
                <a href="/customer/transactions.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <?php if (empty($recent_transactions)): ?>
            <div style="padding: 2rem; text-align: center; color: var(--gray-color);">
                <i class="fas fa-exchange-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>No transactions yet.</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>From/To Account</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_transactions as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['transaction_reference']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $transaction['transaction_type'] === 'deposit' ? 'success' : ($transaction['transaction_type'] === 'withdrawal' ? 'danger' : 'info'); ?>">
                                <?php echo ucfirst($transaction['transaction_type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($transaction['from_account']): ?>
                                From: <?php echo htmlspecialchars($transaction['from_account']); ?>
                            <?php elseif ($transaction['to_account']): ?>
                                To: <?php echo htmlspecialchars($transaction['to_account']); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($transaction['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
