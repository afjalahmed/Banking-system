<?php
/**
 * Customer Session Protection
 * Banking & Transaction System
 * Include this file at the top of all customer pages
 * 
 * ACCESS LEVEL: View Only (Own Data)
 * - Can view own accounts
 * - Can view own transactions
 * - Can update own profile
 * - Can perform transfers (from own accounts)
 * - Cannot access other customers' data
 * - Cannot access admin or employee pages
 */

require_once __DIR__ . '/../includes/access_control.php';

// Protect page - Customer only
protectPage(['customer']);

// Additional security: Verify user can only access own data
// This is enforced at the data query level in each page

// Regenerate session ID periodically for security
if (!isset($_SESSION['customer_regenerated']) || 
    (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800)) {
    session_regenerate_id(true);
    $_SESSION['customer_regenerated'] = true;
}

// Set last activity timestamp
$_SESSION['last_activity'] = time();

// Set security flag for additional data filtering
$_SESSION['data_access_level'] = 'own_only';
?>
