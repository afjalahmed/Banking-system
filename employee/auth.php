<?php
/**
 * Employee Session Protection
 * Banking & Transaction System
 * Include this file at the top of all employee pages
 * 
 * ACCESS LEVEL: Limited Access
 * - Can create and manage customers
 * - Can create and manage accounts
 * - Can process transactions
 * - Cannot delete records or access admin settings
 */

require_once __DIR__ . '/../includes/access_control.php';

// Protect page - Employee and Admin
protectPage(['employee', 'admin']);

// Additional permission checks for specific actions
$current_page = basename($_SERVER['PHP_SELF']);

// Employees cannot access certain pages even within employee folder
$restricted_pages = [
    'user_form.php',      // Only admin can create/edit users with roles
    'delete_user.php',    // No delete access
    'settings.php',       // No settings access
];

if (in_array($current_page, $restricted_pages) && getUserRole() !== 'admin') {
    logAccessDenied($_SERVER['REQUEST_URI']);
    $_SESSION['error'] = 'Access denied. This action requires admin privileges.';
    header('Location: /employee/dashboard.php');
    exit();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['employee_regenerated']) || 
    (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800)) {
    session_regenerate_id(true);
    $_SESSION['employee_regenerated'] = true;
}

// Set last activity timestamp
$_SESSION['last_activity'] = time();
?>
