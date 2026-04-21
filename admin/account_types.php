<?php
/**
 * Account Types Management
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$errors = [];
$success = false;
$edit_mode = false;
$edit_id = 0;

// Handle form submission (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_name = sanitize($_POST['type_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');
    $edit_id = intval($_POST['edit_id'] ?? 0);
    
    // Validation
    if (empty($type_name)) {
        $errors[] = 'Account type name is required.';
    } elseif (strlen($type_name) < 2 || strlen($type_name) > 50) {
        $errors[] = 'Account type name must be between 2 and 50 characters.';
    }
    
    if (empty($errors)) {
        if ($edit_id > 0) {
            // Update existing account type
            $check_sql = "SELECT id FROM account_types WHERE type_name = ? AND id != ?";
            $check_result = executeQuery($check_sql, [$type_name, $edit_id]);
            
            if (fetchOne($check_result)) {
                $errors[] = 'Another account type with this name already exists.';
            } else {
                $update_sql = "UPDATE account_types SET type_name = ?, description = ?, status = ? WHERE id = ?";
                executeQuery($update_sql, [$type_name, $description, $status, $edit_id]);
                
                // Log the action
                $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) 
                           VALUES (?, ?, ?, ?, ?)";
                executeQuery($log_sql, [$_SESSION['user_id'], 'UPDATE', 'account_types', $edit_id, 
                           json_encode(['type_name' => $type_name, 'status' => $status])]);
                
                $success = 'Account type updated successfully.';
                $edit_id = 0;
            }
        } else {
            // Create new account type
            $check_sql = "SELECT id FROM account_types WHERE type_name = ?";
            $check_result = executeQuery($check_sql, [$type_name]);
            
            if (fetchOne($check_result)) {
                $errors[] = 'An account type with this name already exists.';
            } else {
                $insert_sql = "INSERT INTO account_types (type_name, description, status) VALUES (?, ?, ?)";
                $result = executeQuery($insert_sql, [$type_name, $description, $status]);
                $new_id = $conn->insert_id;
                
                // Log the action
                $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) 
                           VALUES (?, ?, ?, ?, ?)";
                executeQuery($log_sql, [$_SESSION['user_id'], 'CREATE', 'account_types', $new_id, 
                           json_encode(['type_name' => $type_name, 'status' => $status])]);
                
                $success = 'Account type created successfully.';
            }
        }
    }
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    // Check if any accounts are using this type
    $check_sql = "SELECT COUNT(*) as count FROM accounts WHERE account_type_id = ?";
    $check_result = executeQuery($check_sql, [$delete_id]);
    $check_data = fetchOne($check_result);
    
    if ($check_data['count'] > 0) {
        $errors[] = 'Cannot delete: This account type is assigned to ' . $check_data['count'] . ' account(s). Please reassign those accounts first.';
    } else {
        // Get info for logging
        $info_sql = "SELECT type_name FROM account_types WHERE id = ?";
        $info_result = executeQuery($info_sql, [$delete_id]);
        $info = fetchOne($info_result);
        
        if ($info) {
            $delete_sql = "DELETE FROM account_types WHERE id = ?";
            executeQuery($delete_sql, [$delete_id]);
            
            // Log the action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values) 
                       VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'DELETE', 'account_types', $delete_id, 
                       json_encode(['type_name' => $info['type_name']])]);
            
            $success = 'Account type deleted successfully.';
        }
    }
}

// Handle edit mode
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $edit_sql = "SELECT * FROM account_types WHERE id = ?";
    $edit_result = executeQuery($edit_sql, [$edit_id]);
    $edit_data = fetchOne($edit_result);
    
    if ($edit_data) {
        $edit_mode = true;
    }
}

// Get all account types with account count
$types_sql = "SELECT at.*, COUNT(a.account_id) as account_count 
              FROM account_types at
              LEFT JOIN accounts a ON at.id = a.account_type_id
              GROUP BY at.id, at.type_name, at.description, at.status, at.created_at, at.updated_at
              ORDER BY at.type_name";
$types_result = executeQuery($types_sql);
$account_types = fetchAll($types_result);

// Get total counts
$total_sql = "SELECT COUNT(*) as total FROM account_types";
$total_result = executeQuery($total_sql);
$total_types = fetchOne($total_result)['total'];

$active_sql = "SELECT COUNT(*) as total FROM account_types WHERE status = 'active'";
$active_result = executeQuery($active_sql);
$active_types = fetchOne($active_result)['total'];
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
                <li><a href="/admin/create_account.php"><i class="fas fa-plus-circle"></i> Create Account</a></li>
                <li><a href="/admin/account_types.php" class="active"><i class="fas fa-tags"></i> Account Types</a></li>
                <li><a href="/admin/transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="/admin/pending_transactions.php"><i class="fas fa-clock"></i> Pending Approval</a></li>
                <li><a href="/admin/audit_logs.php"><i class="fas fa-history"></i> Audit Logs</a></li>
                <li><a href="/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-tags"></i> Account Types</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
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

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Types</h3>
                    <div class="stat-value"><?php echo number_format($total_types); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Active</h3>
                    <div class="stat-value"><?php echo number_format($active_types); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-info">
                    <h3>Inactive</h3>
                    <div class="stat-value"><?php echo number_format($total_types - $active_types); ?></div>
                </div>
            </div>
        </div>

        <div class="content-grid" style="grid-template-columns: 1fr 2fr; margin-top: 2rem;">
            <!-- Form Card -->
            <div class="form-card">
                <h2><?php echo $edit_mode ? 'Edit Account Type' : 'Create Account Type'; ?></h2>
                <form method="POST" action="">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="type_name">
                                <i class="fas fa-tag"></i> Type Name *
                            </label>
                            <input 
                                type="text" 
                                id="type_name" 
                                name="type_name" 
                                value="<?php echo $edit_mode ? htmlspecialchars($edit_data['type_name']) : ''; ?>"
                                placeholder="e.g., Savings, Current, Fixed Deposit"
                                required
                                maxlength="50"
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="description">
                                <i class="fas fa-comment"></i> Description
                            </label>
                            <textarea 
                                id="description" 
                                name="description" 
                                placeholder="Enter description (optional)"
                                rows="3"
                            ><?php echo $edit_mode ? htmlspecialchars($edit_data['description']) : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">
                                <i class="fas fa-toggle-on"></i> Status
                            </label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo ($edit_mode && $edit_data['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($edit_mode && $edit_data['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?>"></i>
                            <?php echo $edit_mode ? 'Update Account Type' : 'Create Account Type'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                        <a href="/admin/account_types.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="table-container">
                <div class="table-header">
                    <h2>All Account Types</h2>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Type Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Accounts</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($account_types as $type): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($type['type_name']); ?></strong></td>
                            <td><?php echo $type['description'] ? htmlspecialchars(substr($type['description'], 0, 50)) . (strlen($type['description']) > 50 ? '...' : '') : '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $type['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($type['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($type['account_count'] > 0): ?>
                                <a href="/admin/accounts.php?account_type_id=<?php echo $type['id']; ?>" class="badge badge-info">
                                    <?php echo number_format($type['account_count']); ?> account(s)
                                </a>
                                <?php else: ?>
                                <span class="text-muted">0 accounts</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($type['created_at'])); ?></td>
                            <td class="actions">
                                <a href="/admin/account_types.php?action=edit&id=<?php echo $type['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($type['account_count'] == 0): ?>
                                <a href="/admin/account_types.php?action=delete&id=<?php echo $type['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this account type?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php else: ?>
                                <button class="btn btn-sm btn-danger" disabled title="Cannot delete: Has associated accounts">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($account_types)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 3rem;">
                                <i class="fas fa-inbox" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 1rem; display: block;"></i>
                                No account types found. Create one to get started.
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
