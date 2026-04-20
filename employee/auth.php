<?php
/**
 * Employee Session Protection
 * Banking & Transaction System
 * Include this file at the top of all employee pages
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

// Check if user has employee or admin role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'employee' && $_SESSION['role'] !== 'admin')) {
    $_SESSION['error'] = 'Access denied. Employee privileges required.';
    header('Location: /customer/login.php');
    exit();
}

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['employee_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['employee_regenerated'] = true;
}
?>
