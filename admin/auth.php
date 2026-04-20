<?php
/**
 * Admin Session Protection
 * Banking & Transaction System
 * Include this file at the top of all admin pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error'] = 'You must be logged in to access this page.';
    header('Location: /customer/login.php');
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    header('Location: /customer/login.php');
    exit();
}

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['admin_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['admin_regenerated'] = true;
}
?>
