<?php
/**
 * Pending Transactions (Employee)
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$errors = [];
$success = false;

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $transaction_id = (int)($_POST['transaction_id'] ?? 0);
    
    if ($transaction_id > 0 && in_array($action, ['approve', 'reject'])) {
        // Get transaction details with full account info
        $txn_sql = "SELECT t.*, 
                       fa.balance as from_balance, fa.account_number as from_acc_num,
                       ta.balance as to_balance, ta.account_number as to_acc_num
                   FROM transactions t
                   LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                   LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                   WHERE t.transaction_id = ? AND t.status = 'PENDING'";
        $txn_result = executeQuery($txn_sql, [$transaction_id]);
        $transaction = fetchOne($txn_result);
        
        if ($transaction) {
            if ($action === 'approve') {
                // VALIDATION: Check accounts exist and are active
                $can_approve = true;
                
                // Validate source account for withdrawals and transfers
                if (in_array($transaction['transaction_type'], ['withdrawal', 'transfer'])) {
                    if (!$transaction['from_account_id']) {
                        $errors[] = "Cannot approve: Source account not found";
                        $can_approve = false;
                    } else {
                        // Check source account is active
                        $check_source_sql = "SELECT status FROM accounts WHERE account_id = ? AND status = 'active'";
                        $check_source_result = executeQuery($check_source_sql, [$transaction['from_account_id']]);
                        if (!fetchOne($check_source_result)) {
                            $errors[] = "Cannot approve: Source account " . $transaction['from_acc_num'] . " is not active";
                            $can_approve = false;
                        }
                    }
                }
                
                // Validate destination account for deposits and transfers
                if (in_array($transaction['transaction_type'], ['deposit', 'transfer'])) {
                    if (!$transaction['to_account_id']) {
                        $errors[] = "Cannot approve: Destination account not found";
                        $can_approve = false;
                    } else {
                        // Check destination account is active
                        $check_dest_sql = "SELECT status FROM accounts WHERE account_id = ? AND status = 'active'";
                        $check_dest_result = executeQuery($check_dest_sql, [$transaction['to_account_id']]);
                        if (!fetchOne($check_dest_result)) {
                            $errors[] = "Cannot approve: Destination account " . $transaction['to_acc_num'] . " is not active";
                            $can_approve = false;
                        }
                    }
                }
                
                // VALIDATION: Check sufficient balance for withdrawals and transfers
                if ($can_approve && $transaction['transaction_type'] === 'withdrawal' && $transaction['from_account_id']) {
                    if ($transaction['from_balance'] < $transaction['amount']) {
                        $errors[] = "Cannot approve: Insufficient balance in account " . $transaction['from_acc_num'] . 
                                   " (Available: $" . number_format($transaction['from_balance'], 2) . 
                                   ", Required: $" . number_format($transaction['amount'], 2) . ")";
                        $can_approve = false;
                    }
                    // Additional check: ensure balance won't go negative after update
                    $new_balance = $transaction['from_balance'] - $transaction['amount'];
                    if ($new_balance < 0) {
                        $errors[] = "Cannot approve: Transaction would result in negative balance";
                        $can_approve = false;
                    }
                } elseif ($can_approve && $transaction['transaction_type'] === 'transfer' && $transaction['from_account_id']) {
                    if ($transaction['from_balance'] < $transaction['amount']) {
                        $errors[] = "Cannot approve: Insufficient balance in source account " . $transaction['from_acc_num'] . 
                                   " (Available: $" . number_format($transaction['from_balance'], 2) . 
                                   ", Required: $" . number_format($transaction['amount'], 2) . ")";
                        $can_approve = false;
                    }
                    // Additional check: ensure balance won't go negative after update
                    $new_balance = $transaction['from_balance'] - $transaction['amount'];
                    if ($new_balance < 0) {
                        $errors[] = "Cannot approve: Transaction would result in negative balance";
                        $can_approve = false;
                    }
                }
                
                if ($can_approve) {
                    // Start database transaction for atomic updates
                    $conn->begin_transaction();
                    
                    try {
                        // Update transaction status to APPROVED
                        $update_sql = "UPDATE transactions SET status = 'APPROVED', processed_at = NOW(), processed_by = ? 
                                      WHERE transaction_id = ?";
                        executeQuery($update_sql, [$_SESSION['user_id'], $transaction_id]);
                        
                        // Update account balances based on transaction type
                        if ($transaction['transaction_type'] === 'deposit' && $transaction['to_account_id']) {
                            // Deposit: Add to destination account
                            $balance_sql = "UPDATE accounts SET balance = balance + ? WHERE account_id = ?";
                            executeQuery($balance_sql, [$transaction['amount'], $transaction['to_account_id']]);
                        } elseif ($transaction['transaction_type'] === 'withdrawal' && $transaction['from_account_id']) {
                            // Withdrawal: Deduct from source account
                            $balance_sql = "UPDATE accounts SET balance = balance - ? WHERE account_id = ?";
                            executeQuery($balance_sql, [$transaction['amount'], $transaction['from_account_id']]);
                        } elseif ($transaction['transaction_type'] === 'transfer') {
                            // Transfer: Duct from source, add to destination
                            if ($transaction['from_account_id']) {
                                $balance_sql = "UPDATE accounts SET balance = balance - ? WHERE account_id = ?";
                                executeQuery($balance_sql, [$transaction['amount'], $transaction['from_account_id']]);
                            }
                            if ($transaction['to_account_id']) {
                                $balance_sql = "UPDATE accounts SET balance = balance + ? WHERE account_id = ?";
                                executeQuery($balance_sql, [$transaction['amount'], $transaction['to_account_id']]);
                            }
                        }
                        
                        // Commit the database transaction
                        $conn->commit();
                        
                        $success = "Transaction approved and balances updated successfully";
                        
                        // Log the approval action
                        $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) 
                                   VALUES (?, ?, ?, ?, ?)";
                        executeQuery($log_sql, [$_SESSION['user_id'], 'APPROVE_TRANSACTION', 
                                   'transactions', $transaction_id, json_encode(['status' => 'APPROVED', 'type' => $transaction['transaction_type']])]);
                    } catch (Exception $e) {
                        $conn->rollback();
                        $errors[] = "Approval failed: " . $e->getMessage();
                    }
                }
            } else {
                // Reject transaction - no balance changes needed
                $update_sql = "UPDATE transactions SET status = 'REJECTED', processed_at = NOW(), processed_by = ? 
                              WHERE transaction_id = ?";
                executeQuery($update_sql, [$_SESSION['user_id'], $transaction_id]);
                $success = "Transaction rejected successfully";
                
                // Log the rejection
                $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) 
                           VALUES (?, ?, ?, ?, ?)";
                executeQuery($log_sql, [$_SESSION['user_id'], 'REJECT_TRANSACTION', 
                           'transactions', $transaction_id, json_encode(['status' => 'REJECTED'])]);
            }
        } else {
            $errors[] = "Transaction not found or already processed";
        }
    }
}

// Get pending transactions
$pending_sql = "SELECT t.*, 
                   fa.account_number as from_account, fa.account_name as from_account_name,
                   ta.account_number as to_account, ta.account_name as to_account_name,
                   fu.full_name as from_user, tu.full_name as to_user
                FROM transactions t
                LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                LEFT JOIN users fu ON fa.user_id = fu.user_id
                LEFT JOIN users tu ON ta.user_id = tu.user_id
                WHERE t.status = 'PENDING'
                ORDER BY t.created_at ASC";
$pending_result = executeQuery($pending_sql);
$pending_transactions = fetchAll($pending_result);

// Get statistics
$pending_count = count($pending_transactions);
$pending_type_count_sql = "SELECT transaction_type, COUNT(*) as count, SUM(amount) as total 
                    FROM transactions WHERE status = 'PENDING' GROUP BY transaction_type";
$pending_type_count_result = executeQuery($pending_type_count_sql);
$pending_type_count = fetchAll($pending_type_count_result);

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
                <li><a href="/employee/transactions.php"><i class="fas fa-exchange-alt"></i> Transaction History</a></li>
                <li><a href="/employee/pending_transactions.php" class="active"><i class="fas fa-clock"></i> Pending Transactions</a></li>
                <li><a href="/employee/customers.php"><i class="fas fa-users"></i> View Customers</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-clock"></i> Pending Transactions</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pending Count</h3>
                    <div class="stat-value"><?php echo number_format($pending_count); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>Pending Amount</h3>
                    <div class="stat-value">$<?php echo number_format($pending_amount, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Pending Transactions List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Transactions Awaiting Approval</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_transactions as $txn): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars(substr($txn['transaction_reference'], -12)); ?></code></td>
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
                                <span class="text-muted">External</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($txn['to_account']): ?>
                                <small><strong><?php echo htmlspecialchars($txn['to_account']); ?></strong></small>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($txn['to_user'] ?? 'N/A'); ?></small>
                            <?php else: ?>
                                <span class="text-muted">External</span>
                            <?php endif; ?>
                        </td>
                        <td><strong>$<?php echo number_format($txn['amount'], 2); ?></strong></td>
                        <td><?php echo $txn['description'] ? htmlspecialchars(substr($txn['description'], 0, 30)) : '-'; ?></td>
                        <td><?php echo date('M d, H:i', strtotime($txn['created_at'])); ?></td>
                        <td class="actions">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this transaction?');">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="transaction_id" value="<?php echo $txn['transaction_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Reject this transaction?');">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="transaction_id" value="<?php echo $txn['transaction_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($pending_transactions)): ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 3rem;">
                            <i class="fas fa-check-circle" style="font-size: 2rem; color: var(--success-color); margin-bottom: 1rem; display: block;"></i>
                            No pending transactions. All caught up!
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
