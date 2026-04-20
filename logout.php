<?php
/**
 * Logout Script
 * Banking & Transaction System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'config/db.php';
    
    $audit_sql = "INSERT INTO audit_logs (user_id, action, table_name, ip_address, user_agent) VALUES (?, 'LOGOUT', NULL, ?, ?)";
    executeQuery($audit_sql, [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: /customer/login.php');
exit();
?>
