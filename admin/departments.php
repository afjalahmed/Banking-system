<?php
/**
 * Departments Management (CRUD)
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$errors = [];
$success = false;
$edit_mode = false;
$edit_department = null;

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CREATE
    if ($action === 'create') {
        $department_name = sanitize($_POST['department_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        
        // Validation
        if (empty($department_name)) {
            $errors[] = "Department name is required";
        }
        
        // Check for duplicate
        if (empty($errors)) {
            $check_sql = "SELECT department_id FROM departments WHERE department_name = ?";
            $check_result = executeQuery($check_sql, [$department_name]);
            if (fetchOne($check_result)) {
                $errors[] = "Department name already exists";
            }
        }
        
        if (empty($errors)) {
            $sql = "INSERT INTO departments (department_name, description, status) VALUES (?, ?, ?)";
            executeQuery($sql, [$department_name, $description, $status]);
            
            // Log action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'CREATE', 'departments', $conn->insert_id, json_encode(['department_name' => $department_name])]);
            
            $success = "Department created successfully";
        }
    }
    
    // UPDATE
    if ($action === 'update') {
        $department_id = (int)$_POST['department_id'];
        $department_name = sanitize($_POST['department_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        
        // Validation
        if (empty($department_name)) {
            $errors[] = "Department name is required";
        }
        
        // Check for duplicate (excluding current)
        if (empty($errors)) {
            $check_sql = "SELECT department_id FROM departments WHERE department_name = ? AND department_id != ?";
            $check_result = executeQuery($check_sql, [$department_name, $department_id]);
            if (fetchOne($check_result)) {
                $errors[] = "Department name already exists";
            }
        }
        
        if (empty($errors)) {
            $sql = "UPDATE departments SET department_name = ?, description = ?, status = ? WHERE department_id = ?";
            executeQuery($sql, [$department_name, $description, $status, $department_id]);
            
            // Log action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'UPDATE', 'departments', $department_id, json_encode(['department_name' => $department_name])]);
            
            $success = "Department updated successfully";
        }
    }
    
    // DELETE
    if ($action === 'delete') {
        $department_id = (int)$_POST['department_id'];
        
        // Check if department has employees
        $check_sql = "SELECT COUNT(*) as count FROM users WHERE department_id = ?";
        $check_result = executeQuery($check_sql, [$department_id]);
        $employee_count = fetchOne($check_result)['count'];
        
        if ($employee_count > 0) {
            $errors[] = "Cannot delete department. $employee_count employee(s) are assigned to this department.";
        } else {
            $sql = "DELETE FROM departments WHERE department_id = ?";
            executeQuery($sql, [$department_id]);
            
            // Log action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'DELETE', 'departments', $department_id]);
            
            $success = "Department deleted successfully";
        }
    }
}

// Check for edit mode
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_sql = "SELECT * FROM departments WHERE department_id = ?";
    $edit_result = executeQuery($edit_sql, [$edit_id]);
    $edit_department = fetchOne($edit_result);
    if ($edit_department) {
        $edit_mode = true;
    }
}

// Get all departments with employee count
$departments_sql = "SELECT d.*, COUNT(u.user_id) as employee_count 
                    FROM departments d 
                    LEFT JOIN users u ON d.department_id = u.department_id 
                    GROUP BY d.department_id 
                    ORDER BY d.department_name";
$departments_result = executeQuery($departments_sql);
$departments = fetchAll($departments_result);

// Get total departments
$total_departments = count($departments);

// Get active/inactive counts
$active_count = count(array_filter($departments, fn($d) => $d['status'] === 'active'));
$inactive_count = count(array_filter($departments, fn($d) => $d['status'] === 'inactive'));
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
                <li><a href="/admin/transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="/admin/audit_logs.php"><i class="fas fa-history"></i> Audit Logs</a></li>
                <li><a href="/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="/admin/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="/admin/departments.php" class="active"><i class="fas fa-building"></i> Departments</a></li>
                <li><a href="/admin/designations.php"><i class="fas fa-id-badge"></i> Designations</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-building"></i> Departments Management</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Departments</h3>
                    <div class="stat-value"><?php echo $total_departments; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Active</h3>
                    <div class="stat-value"><?php echo $active_count; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-pause-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Inactive</h3>
                    <div class="stat-value"><?php echo $inactive_count; ?></div>
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

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <!-- Form Section -->
            <div class="table-container">
                <div class="table-header">
                    <h2><?php echo $edit_mode ? 'Edit Department' : 'Create Department'; ?></h2>
                </div>
                
                <form method="POST" style="padding: 1.5rem;">
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'create'; ?>">
                    <?php if ($edit_mode): ?>
                    <input type="hidden" name="department_id" value="<?php echo $edit_department['department_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="department_name">Department Name <span class="required">*</span></label>
                        <input type="text" id="department_name" name="department_name" class="form-control" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_department['department_name']) : ''; ?>" 
                               required maxlength="100">
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo $edit_mode ? htmlspecialchars($edit_department['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?php echo ($edit_mode && $edit_department['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_mode && $edit_department['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions" style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?>"></i>
                            <?php echo $edit_mode ? 'Update' : 'Create'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                        <a href="/admin/departments.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- List Section -->
            <div class="table-container">
                <div class="table-header">
                    <h2>All Departments</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Employees</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td><?php echo $dept['department_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                            <td><?php echo $dept['description'] ? htmlspecialchars(substr($dept['description'], 0, 50)) . '...' : '-'; ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $dept['employee_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $dept['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($dept['status']); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="/admin/departments.php?edit=<?php echo $dept['department_id']; ?>" 
                                   class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Delete this department?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="department_id" value="<?php echo $dept['department_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No departments found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
