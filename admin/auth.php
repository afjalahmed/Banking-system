<?php
/**
 * Admin Session Protection
 * Banking & Transaction System
 * Include this file at the top of all admin pages
 * 
 * ACCESS LEVEL: Full Access
 * - Can manage users, accounts, transactions
 * - Can view audit logs and reports
 * - Can access all system settings
 */

require_once __DIR__ . '/../includes/access_control.php';

// Protect page - Admin only
protectPage(['admin']);

// Regenerate session ID periodically for security
if (!isset($_SESSION['admin_regenerated']) || 
    (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800)) {
    session_regenerate_id(true);
    $_SESSION['admin_regenerated'] = true;
}

// Set last activity timestamp
$_SESSION['last_activity'] = time();
?>
