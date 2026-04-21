<?php
/**
 * Access Control System
 * Banking & Transaction System
 * 
 * Role Hierarchy:
 * - Admin: Full access to all pages and actions
 * - Employee: Access to customer management, accounts, transactions
 * - Customer: View-only access to own data
 */

// Define role permissions matrix
$ROLE_PERMISSIONS = [
    'admin' => [
        'pages' => [
            'admin/*' => true,           // Full admin panel access
            'employee/*' => true,        // Can access employee pages
            'customer/*' => true,        // Can access customer pages (for support)
            'profile.php' => true,
        ],
        'actions' => [
            'create' => true,
            'read' => true,
            'update' => true,
            'delete' => true,
            'manage_users' => true,
            'manage_accounts' => true,
            'manage_transactions' => true,
            'view_audit_logs' => true,
            'manage_settings' => true,
        ]
    ],
    'employee' => [
        'pages' => [
            'employee/*' => true,        // Employee panel access
            'customer/dashboard.php' => true,  // Can view customer view
            'profile.php' => true,
        ],
        'actions' => [
            'create' => ['customer', 'account'],  // Can create customers and accounts
            'read' => true,
            'update' => ['customer', 'account', 'transaction'],
            'delete' => [],  // Cannot delete
            'process_transactions' => true,
            'view_customers' => true,
            'view_accounts' => true,
            'view_transactions' => true,
        ]
    ],
    'customer' => [
        'pages' => [
            'customer/*' => true,        // Customer panel only
            'profile.php' => true,
        ],
        'actions' => [
            'create' => [],  // Cannot create anything
            'read' => ['own_data'],  // Can only read own data
            'update' => ['own_profile', 'own_password'],  // Can only update own profile
            'delete' => [],  // Cannot delete anything
            'transfer' => true,
            'view_own_accounts' => true,
            'view_own_transactions' => true,
        ]
    ]
];

/**
 * Check if current user has access to a specific page
 * 
 * @param string $page_path The page path to check
 * @return bool True if access granted, false otherwise
 */
function hasPageAccess($page_path = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = getUserRole();
    if (!$role || !isset($GLOBALS['ROLE_PERMISSIONS'][$role])) {
        return false;
    }
    
    // Get current page if not specified
    if ($page_path === null) {
        $page_path = $_SERVER['REQUEST_URI'];
    }
    
    // Clean the path
    $page_path = ltrim($page_path, '/');
    $permissions = $GLOBALS['ROLE_PERMISSIONS'][$role]['pages'];
    
    // Check direct match first
    if (isset($permissions[$page_path])) {
        return $permissions[$page_path];
    }
    
    // Check wildcard patterns
    foreach ($permissions as $pattern => $allowed) {
        if (strpos($pattern, '*') !== false) {
            $regex = '#^' . str_replace('*', '.*', $pattern) . '$#';
            if (preg_match($regex, $page_path)) {
                return $allowed;
            }
        }
    }
    
    return false;
}

/**
 * Check if user has permission for a specific action
 * 
 * @param string $action The action to check
 * @param string $resource Optional resource type
 * @return bool True if permission granted
 */
function hasPermission($action, $resource = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = getUserRole();
    if (!$role || !isset($GLOBALS['ROLE_PERMISSIONS'][$role])) {
        return false;
    }
    
    $actions = $GLOBALS['ROLE_PERMISSIONS'][$role]['actions'];
    
    // Admin has all permissions
    if ($role === 'admin' && isset($actions[$action]) && $actions[$action] === true) {
        return true;
    }
    
    // Check specific action
    if (!isset($actions[$action])) {
        return false;
    }
    
    // If action permission is boolean
    if (is_bool($actions[$action])) {
        return $actions[$action];
    }
    
    // If action permission is array (specific resources allowed)
    if (is_array($actions[$action]) && $resource !== null) {
        return in_array($resource, $actions[$action]);
    }
    
    return false;
}

/**
 * Enforce page access - redirect if not authorized
 * 
 * @param string $required_role Optional specific role required
 */
function enforceAccess($required_role = null) {
    // Check if logged in
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'You must be logged in to access this page.';
        header('Location: /customer/login.php');
        exit();
    }
    
    $role = getUserRole();
    
    // Check specific role requirement
    if ($required_role !== null && $role !== $required_role) {
        // Admin can access everything
        if ($role !== 'admin') {
            $_SESSION['error'] = 'Access denied. Insufficient privileges.';
            
            // Redirect to appropriate dashboard
            switch ($role) {
                case 'employee':
                    header('Location: /employee/dashboard.php');
                    break;
                case 'customer':
                    header('Location: /customer/dashboard.php');
                    break;
                default:
                    header('Location: /customer/login.php');
            }
            exit();
        }
    }
    
    // Check page access permissions
    if (!hasPageAccess()) {
        $_SESSION['error'] = 'Access denied. You do not have permission to view this page.';
        
        // Redirect to appropriate dashboard based on role
        switch ($role) {
            case 'admin':
                header('Location: /admin/dashboard.php');
                break;
            case 'employee':
                header('Location: /employee/dashboard.php');
                break;
            case 'customer':
                header('Location: /customer/dashboard.php');
                break;
            default:
                header('Location: /customer/login.php');
        }
        exit();
    }
}

/**
 * Check if user can access specific resource (own data only for customers)
 * 
 * @param string $resource_type Type of resource (account, transaction, etc.)
 * @param int $resource_owner_id Owner ID of the resource
 * @return bool True if user can access this resource
 */
function canAccessResource($resource_type, $resource_owner_id) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = getUserRole();
    $user_id = $_SESSION['user_id'];
    
    // Admin can access everything
    if ($role === 'admin') {
        return true;
    }
    
    // Employee can access customer resources
    if ($role === 'employee') {
        return true; // Employees manage customers
    }
    
    // Customer can only access their own resources
    if ($role === 'customer') {
        return $resource_owner_id == $user_id;
    }
    
    return false;
}

/**
 * Get accessible dashboard URL for current user
 * 
 * @return string Dashboard URL
 */
function getDashboardUrl() {
    $role = getUserRole();
    
    switch ($role) {
        case 'admin':
            return '/admin/dashboard.php';
        case 'employee':
            return '/employee/dashboard.php';
        case 'customer':
            return '/customer/dashboard.php';
        default:
            return '/customer/login.php';
    }
}

/**
 * Log access denied attempt for security monitoring
 * 
 * @param string $attempted_page Page user tried to access
 */
function logAccessDenied($attempted_page) {
    if (isLoggedIn()) {
        $log_sql = "INSERT INTO audit_logs (user_id, action, table_name, new_values, ip_address) VALUES (?, ?, ?, ?, ?)";
        $new_values = json_encode([
            'attempted_page' => $attempted_page,
            'user_role' => getUserRole(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Use global connection
        global $conn;
        if ($conn) {
            $stmt = $conn->prepare($log_sql);
            $action = 'ACCESS_DENIED';
            $table = 'security';
            $stmt->bind_param('issss', $_SESSION['user_id'], $action, $table, $new_values, $ip);
            $stmt->execute();
        }
    }
}

/**
 * Middleware function to protect pages - use at top of pages
 * 
 * @param array $allowed_roles Array of allowed roles
 * @param array $options Additional options
 */
function protectPage($allowed_roles = [], $options = []) {
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check login
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please log in to access this page.';
        header('Location: /customer/login.php');
        exit();
    }
    
    $role = getUserRole();
    
    // If specific roles specified, check them
    if (!empty($allowed_roles)) {
        // Admin always has access
        if ($role !== 'admin' && !in_array($role, $allowed_roles)) {
            logAccessDenied($_SERVER['REQUEST_URI']);
            $_SESSION['error'] = 'Access denied. You do not have permission to view this page.';
            header('Location: ' . getDashboardUrl());
            exit();
        }
    }
    
    // Check page access using permission matrix
    if (!hasPageAccess()) {
        logAccessDenied($_SERVER['REQUEST_URI']);
        $_SESSION['error'] = 'Access denied. This page is not accessible with your role.';
        header('Location: ' . getDashboardUrl());
        exit();
    }
    
    // Session security - regenerate ID periodically
    $session_lifetime = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_lifetime)) {
        // Session expired
        session_unset();
        session_destroy();
        $_SESSION['error'] = 'Your session has expired. Please log in again.';
        header('Location: /customer/login.php');
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}
