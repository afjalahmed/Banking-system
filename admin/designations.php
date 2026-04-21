<?php
/**
 * Designations Management (CRUD)
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$errors = [];
$success = false;
$edit_mode = false;
$edit_designation = null;

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CREATE
    if ($action === 'create') {
        $designation_name = sanitize($_POST['designation_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        
        // Validation
        if (empty($designation_name)) {
            $errors[] = "Designation name is required";
        }
        
        // Check for duplicate
        if (empty($errors)) {
            $check_sql = "SELECT designation_id FROM designations WHERE designation_name = ?";
            $check_result = executeQuery($check_sql, [$designation_name]);
            if (fetchOne($check_result)) {
                $errors[] = "Designation name already exists";
            }
        }
        
        if (empty($errors)) {
            $sql = "INSERT INTO designations (designation_name, description, status) VALUES (?, ?, ?)";
            executeQuery($sql, [$designation_name, $description, $status]);
            
            // Log action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'CREATE', 'designations', $conn->insert_id, json_encode(['designation_name' => $designation_name])]);
            
            $success = "Designation created successfully";
        }
    }
    
    // UPDATE
    if ($action === 'update') {
        $designation_id = (int)$_POST['designation_id'];
        $designation_name = sanitize($_POST['designation_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        
        // Validation
        if (empty($designation_name)) {
            $errors[] = "Designation name is required";
        }
        
        // Check for duplicate (excluding current)
        if (empty($errors)) {
            $check_sql = "SELECT designation_id FROM designations WHERE designation_name = ? AND designation_id != ?";
            $check_result = executeQuery($check_sql, [$designation_name, $designation_id]);
            if (fetchOne($check_result)) {
                $errors[] = "Designation name already exists";
            }
        }
        
        if (empty($errors)) {
            $sql = "UPDATE designations SET designation_name = ?, description = ?, status = ? WHERE designation_id = ?";
            executeQuery($sql, [$designation_name, $description, $status, $designation_id]);
            
            // Log action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'UPDATE', 'designations', $designation_id, json_encode(['designation_name' => $designation_name])]);
            
            $success = "Designation updated successfully";
        }
    }
    
    // DELETE
    if ($action === 'delete') {
        $designation_id = (int)$_POST['designation_id'];
        
        // Check if designation has employees
        $check_sql = "SELECT COUNT(*) as count FROM users WHERE designation_id = ?";
        $check_result = executeQuery($check_sql, [$designation_id]);
        $employee_count = fetchOne($check_result)['count'];
        
        if ($employee_count > 0) {
            $errors[] = "Cannot delete designation. $employee_count employee(s) have this designation.";
        } else {
            $sql = "DELETE FROM designations WHERE designation_id = ?";
            executeQuery($sql, [$designation_id]);
            
            // Log action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'DELETE', 'designations', $designation_id]);
            
            $success = "Designation deleted successfully";
        }
    }
}

// Check for edit mode
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_sql = "SELECT * FROM designations WHERE designation_id = ?";
    $edit_result = executeQuery($edit_sql, [$edit_id]);
    $edit_designation = fetchOne($edit_result);
    if ($edit_designation) {
        $edit_mode = true;
    }
}

// Get all designations with employee count
$designations_sql = "SELECT d.*, COUNT(u.user_id) as employee_count 
                      FROM designations d 
                      LEFT JOIN users u ON d.designation_id = u.designation_id 
                      GROUP BY d.designation_id 
                      ORDER BY d.designation_name";
$designations_result = executeQuery($designations_sql);
$designations = fetchAll($designations_result);

// Get total designations
$total_designations = count($designations);

// Get active/inactive counts
$active_count = count(array_filter($designations, fn($d) => $d['status'] === 'active'));
$inactive_count = count(array_filter($designations, fn($d) => $d['status'] === 'inactive'));
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
                <li><a href="/admin/account_types.php"><i class="fas fa-tags"></i> Account Types</a></li>
                <li><a href="/admin/transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="/admin/audit_logs.php"><i class="fas fa-history"></i> Audit Logs</a></li>
                <li><a href="/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="/admin/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="/admin/departments.php"><i class="fas fa-building"></i> Departments</a></li>
                <li><a href="/admin/designations.php" class="active"><i class="fas fa-id-badge"></i> Designations</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-id-badge"></i> Designations Management</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-id-badge"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Designations</h3>
                    <div class="stat-value"><?php echo $total_designations; ?></div>
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
                    <h2><?php echo $edit_mode ? 'Edit Designation' : 'Create Designation'; ?></h2>
                </div>
                
                <form method="POST" style="padding: 1.5rem;">
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'create'; ?>">
                    <?php if ($edit_mode): ?>
                    <input type="hidden" name="designation_id" value="<?php echo $edit_designation['designation_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="designation_name">Designation Name <span class="required">*</span></label>
                        <input type="text" id="designation_name" name="designation_name" class="form-control" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_designation['designation_name']) : ''; ?>" 
                               required maxlength="100">
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo $edit_mode ? htmlspecialchars($edit_designation['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?php echo ($edit_mode && $edit_designation['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_mode && $edit_designation['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions" style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?>"></i>
                            <?php echo $edit_mode ? 'Update' : 'Create'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                        <a href="/admin/designations.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- List Section -->
            <div class="table-container">
                <div class="table-header">
                    <h2>All Designations</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Designation Name</th>
                            <th>Description</th>
                            <th>Employees</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($designations as $desg): ?>
                        <tr>
                            <td><?php echo $desg['designation_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($desg['designation_name']); ?></strong></td>
                            <td><?php echo $desg['description'] ? htmlspecialchars(substr($desg['description'], 0, 50)) . '...' : '-'; ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $desg['employee_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $desg['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($desg['status']); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="/admin/designations.php?edit=<?php echo $desg['designation_id']; ?>" 
                                   class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Delete this designation?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="designation_id" value="<?php echo $desg['designation_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($designations)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No designations found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
