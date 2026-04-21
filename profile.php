<?php
/**
 * User Profile - Edit own profile
 * Banking & Transaction System
 */

require_once 'includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch current user data with department and designation
$sql = "SELECT u.*, d.department_name, des.designation_name, r.role_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        LEFT JOIN designations des ON u.designation_id = des.designation_id 
        LEFT JOIN roles r ON u.role = r.role_slug
        WHERE u.user_id = ?";
$result = executeQuery($sql, [$user_id]);
$user = fetchOne($result);

if (!$user) {
    header('Location: /customer/login.php?error=User not found');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update profile info
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        
        // Validation
        if (empty($full_name)) {
            $errors[] = "Full name is required";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }
        
        if (empty($phone)) {
            $errors[] = "Phone number is required";
        }
        
        // Check for duplicate email (excluding current user)
        if (empty($errors)) {
            $check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            $check_result = executeQuery($check_sql, [$email, $user_id]);
            
            if (fetchOne($check_result)) {
                $errors[] = "Email address is already in use by another account";
            }
        }
        
        // Update profile if no errors
        if (empty($errors)) {
            $update_sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
            executeQuery($update_sql, [$full_name, $email, $phone, $address, $user_id]);
            
            // Update session data
            $_SESSION['full_name'] = $full_name;
            
            // Log the action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$user_id, 'UPDATE_PROFILE', 'users', $user_id, json_encode(['action' => 'profile_updated'])]);
            
            $success = 'Profile updated successfully';
            
            // Refresh user data
            $result = executeQuery($sql, [$user_id]);
            $user = fetchOne($result);
        }
    }
    
    if ($action === 'change_password') {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($current_password)) {
            $errors[] = "Current password is required";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        // Verify current password
        if (empty($errors)) {
            $pass_sql = "SELECT password FROM users WHERE user_id = ?";
            $pass_result = executeQuery($pass_sql, [$user_id]);
            $pass_data = fetchOne($pass_result);
            
            if (!$pass_data || !password_verify($current_password, $pass_data['password'])) {
                $errors[] = "Current password is incorrect";
            }
        }
        
        // Update password if no errors
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            executeQuery($update_sql, [$hashed_password, $user_id]);
            
            // Log the action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$user_id, 'CHANGE_PASSWORD', 'users', $user_id, json_encode(['action' => 'password_changed'])]);
            
            $success = 'Password changed successfully';
        }
    }
}

// Determine dashboard link based on role
$dashboard_link = '/customer/dashboard.php';
if ($_SESSION['role'] === 'admin') {
    $dashboard_link = '/admin/dashboard.php';
} elseif ($_SESSION['role'] === 'employee') {
    $dashboard_link = '/employee/dashboard.php';
}
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-sidebar-header">
            <h2><i class="fas fa-user"></i> My Profile</h2>
        </div>
        <nav class="dashboard-sidebar-menu">
            <ul>
                <li><a href="<?php echo $dashboard_link; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/profile.php" class="active"><i class="fas fa-user-cog"></i> Edit Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-user-cog"></i> Edit Profile</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

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

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Profile Information -->
            <div class="table-container">
                <div class="table-header">
                    <h2><i class="fas fa-user"></i> Profile Information</h2>
                </div>
                
                <form method="POST" style="padding: 2rem;">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    
                    <?php if ($user['department_name'] || $user['designation_name']): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <?php if ($user['department_name']): ?>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Department</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['department_name']); ?>" disabled>
                        </div>
                        <?php endif; ?>
                        <?php if ($user['designation_name']): ?>
                        <div class="form-group" style="margin-top: 0.5rem;">
                            <label><i class="fas fa-id-badge"></i> Designation</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['designation_name']); ?>" disabled>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="phone">Phone <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                    
                    <div class="form-actions" style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="table-container">
                <div class="table-header">
                    <h2><i class="fas fa-lock"></i> Change Password</h2>
                </div>
                
                <form method="POST" style="padding: 2rem;">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password <span class="required">*</span></label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="new_password">New Password <span class="required">*</span></label>
                        <input type="password" id="new_password" name="new_password" class="form-control" 
                               required minlength="6">
                        <small class="text-muted">Must be at least 6 characters</small>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <div class="form-actions" style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/footer.php'; ?>
