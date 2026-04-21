<?php
/**
 * Add Customer (Employee)
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        // Sanitize inputs
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $full_name = sanitize($_POST['full_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
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
        
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        
        // Check for duplicate username/email
        if (empty($errors)) {
            $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
            $check_result = executeQuery($check_sql, [$username, $email]);
            if (fetchOne($check_result)) {
                $errors[] = "Username or email already exists";
            }
        }
        
        // If no errors, create customer
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, full_name, phone, address, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'customer', 'active')";
            executeQuery($sql, [$username, $email, $hashed_password, $full_name, $phone, $address]);
            
            $new_user_id = $conn->insert_id;
            
            // Log the action
            $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values) 
                       VALUES (?, ?, ?, ?, ?)";
            executeQuery($log_sql, [$_SESSION['user_id'], 'CREATE_CUSTOMER', 'users', $new_user_id, 
                       json_encode(['username' => $username, 'created_by' => 'employee'])]);
            
            $success = "Customer created successfully! Customer ID: $new_user_id";
            
            // Clear form
            $_POST = [];
        }
    }
}
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
                <li><a href="/employee/add_customer.php" class="active"><i class="fas fa-user-plus"></i> Add Customer</a></li>
                <li><a href="/employee/create_account.php"><i class="fas fa-university"></i> Create Account</a></li>
                <li><a href="/employee/accounts.php"><i class="fas fa-list"></i> All Accounts</a></li>
                <li><a href="/employee/deposit.php"><i class="fas fa-arrow-down"></i> Deposit</a></li>
                <li><a href="/employee/withdraw.php"><i class="fas fa-arrow-up"></i> Withdraw</a></li>
                <li><a href="/employee/transactions.php"><i class="fas fa-exchange-alt"></i> Transaction History</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-user-plus"></i> Add Customer</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
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

        <!-- Form -->
        <div class="table-container">
            <div class="table-header">
                <h2>New Customer Details</h2>
            </div>
            
            <form method="POST" style="padding: 2rem;">
                <input type="hidden" name="action" value="create">
                
                <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required minlength="3" maxlength="50">
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                               required>
                    </div>
                    
                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">Phone <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                               required>
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="form-control" 
                               required minlength="6">
                        <small class="text-muted">Must be at least 6 characters</small>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                
                <!-- Address -->
                <div class="form-group" style="margin-top: 1rem;">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <!-- Buttons -->
                <div class="form-actions" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Customer
                    </button>
                    <a href="/employee/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
