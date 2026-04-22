<?php
/**
 * User Form (Create/Edit)
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $user_id > 0;

// Initialize variables
$user = [
    'user_id' => '',
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'address' => '',
    'role' => 'customer',
    'status' => 'active',
    'department_id' => null,
    'designation_id' => null
];
$errors = [];

// If editing, fetch user data
if ($is_edit) {
    $sql = "SELECT user_id, username, email, full_name, phone, address, role, status, department_id, designation_id FROM users WHERE user_id = ?";
    $result = executeQuery($sql, [$user_id]);
    $user_data = fetchOne($result);
    
    if (!$user_data) {
        header('Location: /admin/users.php?error=User not found');
        exit;
    }
    
    $user = array_merge($user, $user_data);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Sanitize inputs
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $role = sanitize($_POST['role'] ?? 'customer');
    $status = sanitize($_POST['status'] ?? 'active');
    $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $designation_id = !empty($_POST['designation_id']) ? (int)$_POST['designation_id'] : null;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (!in_array($role, ['admin', 'employee', 'customer'])) {
        $errors[] = "Invalid role selected";
    }
    
    if (!in_array($status, ['active', 'inactive', 'suspended'])) {
        $errors[] = "Invalid status selected";
    }
    
    // Password validation for new users
    if (!$is_edit && empty($password)) {
        $errors[] = "Password is required for new users";
    }
    
    // Password confirmation check
    if (!empty($password) && $password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check for duplicate username/email (excluding current user when editing)
    if (empty($errors)) {
        if ($is_edit) {
            $check_sql = "SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?";
            $check_result = executeQuery($check_sql, [$username, $email, $user_id]);
        } else {
            $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
            $check_result = executeQuery($check_sql, [$username, $email]);
        }
        
        if (fetchOne($check_result)) {
            $errors[] = "Username or email already exists";
        }
    }
    
    // If no errors, proceed with save
    if (empty($errors)) {
        if ($is_edit) {
            // Update existing user
            if (!empty($password)) {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, email = ?, password = ?, full_name = ?, phone = ?, address = ?, role = ?, status = ?, department_id = ?, designation_id = ? WHERE user_id = ?";
                executeQuery($sql, [$username, $email, $hashed_password, $full_name, $phone, $address, $role, $status, $department_id, $designation_id, $user_id]);
            } else {
                // Update without changing password
                $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, address = ?, role = ?, status = ?, department_id = ?, designation_id = ? WHERE user_id = ?";
                executeQuery($sql, [$username, $email, $full_name, $phone, $address, $role, $status, $department_id, $designation_id, $user_id]);
            }
            
            // Log the action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'UPDATE', 'users', $user_id, json_encode(['action' => 'user_updated'])]);
            
            header('Location: /admin/users.php?success=User updated successfully');
            exit;
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, full_name, phone, address, role, status, department_id, designation_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            executeQuery($sql, [$username, $email, $hashed_password, $full_name, $phone, $address, $role, $status, $department_id, $designation_id]);
            
            // Get the new user ID
            $new_user_id = $conn->insert_id;
            
            // Log the action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'CREATE', 'users', $new_user_id, json_encode(['username' => $username, 'role' => $role])]);
            
            header('Location: /admin/users.php?success=User created successfully');
            exit;
        }
    }
    
    // Repopulate form with submitted data on error
    $user['username'] = $username;
    $user['email'] = $email;
    $user['full_name'] = $full_name;
    $user['phone'] = $phone;
    $user['address'] = $address;
    $user['role'] = $role;
    $user['status'] = $status;
    $user['department_id'] = $department_id;
    $user['designation_id'] = $designation_id;
}

// Fetch departments and designations for dropdowns
$dept_sql = "SELECT department_id, department_name FROM departments WHERE status = 'active' ORDER BY department_name";
$dept_result = executeQuery($dept_sql);
$departments = fetchAll($dept_result);

$desg_sql = "SELECT designation_id, designation_name FROM designations WHERE status = 'active' ORDER BY designation_name";
$desg_result = executeQuery($desg_sql);
$designations = fetchAll($desg_result);

$page_title = $is_edit ? 'Edit User' : 'Create User';
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
                <li><a href="/admin/account_types.php"><i class="fas fa-tags"></i> Account Types</a></li>
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
            <h1><i class="fas fa-user"></i> <?php echo $page_title; ?></h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Error Messages -->
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

        <!-- Form -->
        <div class="table-container">
            <div class="table-header">
                <h2><?php echo $page_title; ?></h2>
                <a href="/admin/users.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
            
            <form method="POST" class="form-container" style="padding: 2rem;">
                <input type="hidden" name="action" value="save">
                
                <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" 
                               required minlength="3" maxlength="50"
                               placeholder="Enter username">
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                               required
                               placeholder="Enter email address">
                    </div>
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                               required
                               placeholder="Enter full name">
                    </div>
                    
                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">Phone <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>" 
                               required
                               placeholder="Enter phone number">
                    </div>
                    
                    <!-- Role -->
                    <div class="form-group">
                        <label for="role">Role <span class="required">*</span></label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="employee" <?php echo $user['role'] === 'employee' ? 'selected' : ''; ?>>Employee</option>
                            <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        </select>
                    </div>
                    
                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    
                    <!-- Department -->
                    <div class="form-group">
                        <label for="department_id">Department *</label>
                        <select id="department_id" name="department_id" required class="form-control">
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" 
                                    <?php echo ($user['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Designation -->
                    <div class="form-group">
                        <label for="designation_id">Designation *</label>
                        <select id="designation_id" name="designation_id" required class="form-control">
                            <option value="">-- Select Designation --</option>
                            <?php foreach ($designations as $desg): ?>
                            <option value="<?php echo $desg['designation_id']; ?>" 
                                    <?php echo ($user['designation_id'] == $desg['designation_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($desg['designation_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Address -->
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"
                              placeholder="Enter address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                
                <!-- Password Section -->
                <div class="form-section" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e0e0e0;">
                    <h3><i class="fas fa-lock"></i> Password</h3>
                    
                    <?php if ($is_edit): ?>
                    <p class="text-muted" style="margin-bottom: 1rem;">
                        <i class="fas fa-info-circle"></i> Leave password fields empty to keep the current password.
                    </p>
                    <?php endif; ?>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div class="form-group">
                            <label for="password">
                                Password
                                <?php if (!$is_edit): ?>
                                <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   <?php echo $is_edit ? '' : 'required'; ?>
                                   placeholder="<?php echo $is_edit ? 'Enter new password (optional)' : 'Enter password'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                Confirm Password
                                <?php if (!$is_edit): ?>
                                <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   <?php echo $is_edit ? '' : 'required'; ?>
                                   placeholder="Confirm password">
                        </div>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $is_edit ? 'Update User' : 'Create User'; ?>
                    </button>
                    <a href="/admin/users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
