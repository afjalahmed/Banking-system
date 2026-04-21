<?php
/**
 * Admin Users Management
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'activate' || $action === 'deactivate') {
        $user_id = (int)$_POST['user_id'];
        $new_status = $action === 'activate' ? 'active' : 'inactive';
        
        $update_sql = "UPDATE users SET status = ? WHERE user_id = ?";
        executeQuery($update_sql, [$new_status, $user_id]);
        
        // Log the action
        $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values) VALUES (?, ?, ?, ?, ?, ?)";
        executeQuery($log_sql, [$_SESSION['user_id'], 'UPDATE', 'users', $user_id, null, json_encode(['status' => $new_status])]);
        
        header('Location: /admin/users.php?success=User status updated');
        exit;
    }
    
    if ($action === 'delete') {
        $user_id = (int)$_POST['user_id'];
        
        // Prevent deleting own account
        if ($user_id === $_SESSION['user_id']) {
            header('Location: /admin/users.php?error=Cannot delete your own account');
            exit;
        }
        
        $delete_sql = "DELETE FROM users WHERE user_id = ?";
        executeQuery($delete_sql, [$user_id]);
        
        // Log the action
        $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)";
        executeQuery($log_sql, [$_SESSION['user_id'], 'DELETE', 'users', $user_id]);
        
        header('Location: /admin/users.php?success=User deleted');
        exit;
    }
}

// Get filter parameters
$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Build query with filters
$where_conditions = [];
$params = [];

if ($role_filter !== 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all users
$users_sql = "SELECT user_id, username, email, full_name, phone, role, status, created_at, last_login FROM users $where_clause ORDER BY created_at DESC";
$users_result = executeQuery($users_sql, $params);
$users = fetchAll($users_result);

// Get user counts by role
$role_counts_sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$role_counts_result = executeQuery($role_counts_sql);
$role_counts = fetchAll($role_counts_result);
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
                <li><a href="/admin/users.php" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="/admin/accounts.php"><i class="fas fa-university"></i> All Accounts</a></li>
                <li><a href="/admin/transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
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
            <h1><i class="fas fa-users"></i> Manage Users</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-container">
            <div class="table-header">
                <h2>All Users</h2>
                <div class="filter-controls" style="display: flex; gap: 1rem; align-items: center;">
                    <a href="/admin/user_form.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create User
                    </a>
                    <form method="GET" class="filter-form">
                        <select name="role" onchange="this.form.submit()">
                            <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="employee" <?php echo $role_filter === 'employee' ? 'selected' : ''; ?>>Employee</option>
                            <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        </select>
                        <select name="status" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-summary">
                <?php foreach ($role_counts as $rc): ?>
                <div class="stat-summary-item">
                    <span class="stat-label"><?php echo ucfirst($rc['role']); ?>s:</span>
                    <span class="stat-value"><?php echo number_format($rc['count']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
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
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                        <td class="actions">
                            <a href="/admin/user_form.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                <?php if ($user['status'] === 'active'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Deactivate this user?');">
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning" title="Deactivate">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Activate this user?');">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success" title="Activate">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this user? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <p>No users found matching the selected filters.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
