<?php
/**
 * Customer - My Accounts
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$user_id = $_SESSION['user_id'];

// Get customer's accounts
$accounts_sql = "SELECT * FROM accounts WHERE user_id = ? ORDER BY created_at DESC";
$accounts_result = executeQuery($accounts_sql, [$user_id]);
$accounts = fetchAll($accounts_result);

// Calculate totals
$total_balance = 0;
$active_accounts = 0;
foreach ($accounts as $account) {
    if ($account['status'] === 'active') {
        $total_balance += $account['balance'];
        $active_accounts++;
    }
}

// Get account type distribution
$types_sql = "SELECT account_type, COUNT(*) as count, SUM(balance) as total 
              FROM accounts WHERE user_id = ? GROUP BY account_type";
$types_result = executeQuery($types_sql, [$user_id]);
$type_distribution = fetchAll($types_result);
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
                <li><a href="/customer/accounts.php" class="active"><i class="fas fa-university"></i> My Accounts</a></li>
                <li><a href="/customer/transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
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
            <h1><i class="fas fa-university"></i> My Accounts</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
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
                    <div class="stat-value"><?php echo $active_accounts; ?></div>
                </div>
            </div>
        </div>

        <!-- Account Type Distribution -->
        <?php if (!empty($type_distribution)): ?>
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2><i class="fas fa-chart-pie"></i> Account Summary by Type</h2>
            </div>
            <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?php foreach ($type_distribution as $type): ?>
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="text-transform: capitalize; font-weight: 600; color: var(--primary-color);">
                        <?php echo $type['account_type']; ?> Accounts
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-top: 0.5rem;">
                        <?php echo $type['count']; ?> <small style="font-size: 0.8rem; font-weight: 400;">accounts</small>
                    </div>
                    <div style="color: var(--success-color); margin-top: 0.25rem;">
                        $<?php echo number_format($type['total'], 2); ?> total
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Accounts List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Account Details</h2>
            </div>

            <?php if (empty($accounts)): ?>
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-university" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <p>You don't have any accounts yet.</p>
                <p class="text-muted">Please contact customer service to open an account.</p>
            </div>
            <?php else: ?>
            <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
                <?php foreach ($accounts as $account): ?>
                <div style="border: 1px solid #e0e0e0; border-radius: 12px; padding: 1.5rem; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin: 0; color: var(--primary-color);"><?php echo htmlspecialchars($account['account_name']); ?></h3>
                            <code style="font-size: 0.85rem;"><?php echo htmlspecialchars($account['account_number']); ?></code>
                        </div>
                        <span class="badge badge-<?php echo $account['status'] === 'active' ? 'success' : ($account['status'] === 'frozen' ? 'warning' : 'secondary'); ?>">
                            <?php echo ucfirst($account['status']); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--dark-color);">
                            $<?php echo number_format($account['balance'], 2); ?>
                        </div>
                        <small style="color: var(--gray-color);">Available Balance</small>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.9rem; margin-bottom: 1rem;">
                        <div>
                            <small class="text-muted">Type</small>
                            <div style="text-transform: capitalize;"><?php echo $account['account_type']; ?></div>
                        </div>
                        <div>
                            <small class="text-muted">Currency</small>
                            <div><?php echo $account['currency']; ?></div>
                        </div>
                        <div>
                            <small class="text-muted">Branch</small>
                            <div><?php echo htmlspecialchars($account['branch_name'] ?? 'Main Branch'); ?></div>
                        </div>
                        <div>
                            <small class="text-muted">Opened</small>
                            <div><?php echo date('M d, Y', strtotime($account['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($account['status'] === 'active'): ?>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="/customer/transfer.php" class="btn btn-sm btn-primary" style="flex: 1;">
                            <i class="fas fa-paper-plane"></i> Transfer
                        </a>
                        <a href="/customer/withdraw.php" class="btn btn-sm btn-danger" style="flex: 1;">
                            <i class="fas fa-arrow-up"></i> Withdraw
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
