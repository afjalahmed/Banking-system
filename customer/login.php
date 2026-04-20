<?php
/**
 * Login Page
 * Banking & Transaction System
 */

require_once '../includes/header.php';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
    } else {
        // Check if user exists
        $sql = "SELECT user_id, username, email, password, full_name, role, status FROM users WHERE email = ?";
        $stmt = executeQuery($sql, [$email]);
        
        if ($stmt) {
            $user = fetchOne($stmt);
            
            if ($user) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Check account status
                    if ($user['status'] !== 'active') {
                        $_SESSION['error'] = 'Your account is ' . strtoupper($user['status']) . '. Please contact support.';
                    } else {
                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        
                        // Update last login
                        $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                        executeQuery($update_sql, [$user['user_id']]);
                        
                        // Log the login
                        $audit_sql = "INSERT INTO audit_logs (user_id, action, table_name, ip_address, user_agent) VALUES (?, 'LOGIN', NULL, ?, ?)";
                        executeQuery($audit_sql, [$user['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                        
                        // Redirect based on role
                        switch ($user['role']) {
                            case 'admin':
                                header('Location: /admin/dashboard.php');
                                exit();
                            case 'employee':
                                header('Location: /employee/dashboard.php');
                                exit();
                            case 'customer':
                                header('Location: /customer/dashboard.php');
                                exit();
                            default:
                                $_SESSION['error'] = 'Invalid role assigned.';
                                header('Location: /customer/login.php');
                                exit();
                        }
                    }
                } else {
                    $_SESSION['error'] = 'Invalid email or password.';
                }
            } else {
                $_SESSION['error'] = 'Invalid email or password.';
            }
        } else {
            $_SESSION['error'] = 'Database error. Please try again.';
        }
    }
}
?>

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h1><i class="fas fa-sign-in-alt"></i> Login</h1>
            <p>Access your banking account</p>
        </div>
        
        <form method="POST" action="" class="login-form">
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email" 
                    required 
                    autocomplete="email"
                >
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter your password" 
                    required 
                    autocomplete="current-password"
                >
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </div>
        </form>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="/customer/register.php">Register here</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
