<?php
/**
 * View Customers (Employee)
 * Banking & Transaction System
 */

require_once '../includes/header.php';
require_once 'auth.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = sanitize($_GET['search'] ?? '');

// Build query with filters
$where_conditions = ["role = 'customer'"];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get all customers
$customers_sql = "SELECT user_id, username, email, full_name, phone, status, created_at, last_login 
                FROM users $where_clause ORDER BY created_at DESC";
$customers_result = executeQuery($customers_sql, $params);
$customers = fetchAll($customers_result);

// Get statistics
$total_customers_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
$total_customers_result = executeQuery($total_customers_sql);
$total_customers = fetchOne($total_customers_result)['count'];

$active_customers_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND status = 'active'";
$active_customers_result = executeQuery($active_customers_sql);
$active_customers = fetchOne($active_customers_result)['count'];

$new_this_month_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$new_this_month_result = executeQuery($new_this_month_sql);
$new_this_month = fetchOne($new_this_month_result)['count'];
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-sidebar-header">
            <h2><i class="fas fa-user-tie"></i> Employee Panel</h2>
        </div>
        <nav class="dashboard-sidebar-menu">
            <ul>
                <li><a href="/employee/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/employee/add_customer.php"><i class="fas fa-user-plus"></i> Add Customer</a></li>
                <li><a href="/employee/create_account.php"><i class="fas fa-university"></i> Create Account</a></li>
                <li><a href="/employee/accounts.php"><i class="fas fa-list"></i> All Accounts</a></li>
                <li><a href="/employee/deposit.php"><i class="fas fa-arrow-down"></i> Deposit</a></li>
                <li><a href="/employee/withdraw.php"><i class="fas fa-arrow-up"></i> Withdraw</a></li>
                <li><a href="/employee/transactions.php"><i class="fas fa-exchange-alt"></i> Transaction History</a></li>
                <li><a href="/employee/customers.php" class="active"><i class="fas fa-users"></i> View Customers</a></li>
                <li><a href="/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-users"></i> Customers</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Customers</h3>
                    <div class="stat-value"><?php echo number_format($total_customers); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Customers</h3>
                    <div class="stat-value"><?php echo number_format($active_customers); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="stat-info">
                    <h3>New This Month</h3>
                    <div class="stat-value"><?php echo number_format($new_this_month); ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-container">
            <div class="table-header">
                <h2>Customer List</h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <a href="/employee/add_customer.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Customer
                    </a>
                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <input type="text" name="search" class="form-control" placeholder="Search..." 
                               value="<?php echo htmlspecialchars($search); ?>" style="width: 200px;">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <?php if (!empty($search)): ?>
                        <a href="/employee/customers.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo $customer['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($customer['username']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $customer['status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($customer['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                        <td><?php echo $customer['last_login'] ? date('M d, Y H:i', strtotime($customer['last_login'])) : 'Never'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No customers found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
