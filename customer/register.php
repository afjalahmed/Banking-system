<?php
/**
 * Registration Page
 * Banking & Transaction System
 */

require_once '../includes/header.php';

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name) || empty($phone)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format.';
    } else {
        // Check if username already exists
        $check_username = "SELECT user_id FROM users WHERE username = ?";
        $stmt = executeQuery($check_username, [$username]);
        if (fetchOne($stmt)) {
            $_SESSION['error'] = 'Username already exists. Please choose another.';
        } else {
            // Check if email already exists
            $check_email = "SELECT user_id FROM users WHERE email = ?";
            $stmt = executeQuery($check_email, [$email]);
            if (fetchOne($stmt)) {
                $_SESSION['error'] = 'Email already registered. Please use another email.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_sql = "INSERT INTO users (username, email, password, full_name, phone, address, role, status) VALUES (?, ?, ?, ?, ?, ?, 'customer', 'active')";
                $stmt = executeQuery($insert_sql, [$username, $email, $hashed_password, $full_name, $phone, $address]);
                
                if ($stmt) {
                    // Log the registration
                    $user_id = $conn->insert_id;
                    $audit_sql = "INSERT INTO audit_logs (user_id, action, table_name, ip_address, user_agent) VALUES (?, 'REGISTER', 'users', ?, ?)";
                    executeQuery($audit_sql, [$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                    
                    $_SESSION['success'] = 'Registration successful! Please login with your credentials.';
                    header('Location: /customer/login.php');
                    exit();
                } else {
                    $_SESSION['error'] = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>

<div class="register-container">
    <div class="register-box">
        <div class="register-header">
            <h1><i class="fas fa-user-plus"></i> Register</h1>
            <p>Create your banking account</p>
        </div>
        
        <form method="POST" action="" class="register-form">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username *
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Choose a username" 
                    required 
                    autocomplete="username"
                    pattern="[a-zA-Z0-9_]{3,50}"
                    title="Username must be 3-50 characters, letters, numbers, and underscores only"
                >
            </div>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address *
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
                <label for="full_name">
                    <i class="fas fa-id-card"></i> Full Name *
                </label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    placeholder="Enter your full name" 
                    required 
                    autocomplete="name"
                >
            </div>
            
            <div class="form-group">
                <label for="phone">
                    <i class="fas fa-phone"></i> Phone Number *
                </label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    placeholder="Enter your phone number" 
                    required 
                    autocomplete="tel"
                >
            </div>
            
            <div class="form-group">
                <label for="address">
                    <i class="fas fa-map-marker-alt"></i> Address
                </label>
                <textarea 
                    id="address" 
                    name="address" 
                    placeholder="Enter your address" 
                    rows="3"
                ></textarea>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password *
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Create a password (min 6 characters)" 
                    required 
                    autocomplete="new-password"
                    minlength="6"
                >
            </div>
            
            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-lock"></i> Confirm Password *
                </label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="Confirm your password" 
                    required 
                    autocomplete="new-password"
                    minlength="6"
                >
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </div>
        </form>
        
        <div class="register-footer">
            <p>Already have an account? <a href="/customer/login.php">Login here</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
