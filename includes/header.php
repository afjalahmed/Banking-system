<?php
/**
 * Header File
 * Banking & Transaction System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user role
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /customer/login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isLoggedIn() || getUserRole() !== 'admin') {
        header('Location: /customer/login.php');
        exit();
    }
}

// Redirect if not employee
function requireEmployee() {
    if (!isLoggedIn() || (getUserRole() !== 'employee' && getUserRole() !== 'admin')) {
        header('Location: /customer/login.php');
        exit();
    }
}

// Get current page name
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Banking & Transaction System</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="/index.php">
                    <i class="fas fa-university"></i>
                    <span>Banking System</span>
                </a>
            </div>
            <div class="nav-menu">
                <ul class="nav-links">
                    <li><a href="/index.php" class="<?php echo getCurrentPage() === 'index.php' ? 'active' : ''; ?>">Home</a></li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (getUserRole() === 'admin'): ?>
                            <li><a href="/admin/dashboard.php" class="<?php echo strpos(getCurrentPage(), 'admin') !== false ? 'active' : ''; ?>">Admin Dashboard</a></li>
                        <?php elseif (getUserRole() === 'employee'): ?>
                            <li><a href="/employee/dashboard.php" class="<?php echo strpos(getCurrentPage(), 'employee') !== false ? 'active' : ''; ?>">Employee Dashboard</a></li>
                        <?php elseif (getUserRole() === 'customer'): ?>
                            <li><a href="/customer/dashboard.php" class="<?php echo strpos(getCurrentPage(), 'customer') !== false ? 'active' : ''; ?>">Dashboard</a></li>
                            <li><a href="/customer/transactions.php">Transactions</a></li>
                            <li><a href="/customer/transfer.php">Transfer</a></li>
                        <?php endif; ?>
                        <li><a href="/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="/customer/login.php" class="<?php echo getCurrentPage() === 'login.php' ? 'active' : ''; ?>">Login</a></li>
                        <li><a href="/customer/register.php" class="<?php echo getCurrentPage() === 'register.php' ? 'active' : ''; ?>">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content">
