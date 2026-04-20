-- ============================================
-- Banking & Transaction System - Advanced SQL Queries
-- ============================================

USE banking_system;

-- ============================================
-- 1. INNER JOIN (Users + Accounts)
-- Join users with their accounts
-- ============================================

-- Get all users with their account details
SELECT 
    u.user_id,
    u.username,
    u.email,
    u.full_name,
    u.role,
    a.account_id,
    a.account_number,
    a.account_type,
    a.balance,
    a.status AS account_status
FROM users u
INNER JOIN accounts a ON u.user_id = a.user_id
WHERE a.status = 'active'
ORDER BY u.full_name ASC, a.balance DESC;

-- Get customer accounts with user information
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    u.email,
    u.phone,
    a.account_number,
    a.account_name,
    a.account_type,
    a.balance,
    a.currency
FROM users u
INNER JOIN accounts a ON u.user_id = a.user_id
WHERE u.role = 'customer' AND a.status = 'active'
ORDER BY a.balance DESC;

-- ============================================
-- 2. LEFT JOIN
-- Get all users including those without accounts
-- ============================================

-- Get all users with their accounts (including users without accounts)
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    u.email,
    u.role,
    a.account_number,
    a.account_type,
    a.balance
FROM users u
LEFT JOIN accounts a ON u.user_id = a.user_id
ORDER BY u.user_id;

-- Get all customers and their account count
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    u.email,
    COUNT(a.account_id) AS account_count,
    COALESCE(SUM(a.balance), 0) AS total_balance
FROM users u
LEFT JOIN accounts a ON u.user_id = a.user_id
WHERE u.role = 'customer'
GROUP BY u.user_id, u.username, u.full_name, u.email
ORDER BY account_count DESC;

-- ============================================
-- 3. Search using LIKE
-- Pattern matching for text searches
-- ============================================

-- Search users by username pattern
SELECT 
    user_id,
    username,
    email,
    full_name,
    role,
    status
FROM users
WHERE username LIKE '%admin%'
ORDER BY username;

-- Search accounts by account number pattern
SELECT 
    account_id,
    account_number,
    account_name,
    account_type,
    balance,
    status
FROM accounts
WHERE account_number LIKE 'ACC001%'
ORDER BY account_number;

-- Search transactions by description
SELECT 
    t.transaction_id,
    t.transaction_reference,
    t.transaction_type,
    t.amount,
    t.description,
    t.status,
    t.created_at
FROM transactions t
WHERE t.description LIKE '%transfer%'
ORDER BY t.created_at DESC;

-- Case-insensitive search for users
SELECT 
    user_id,
    username,
    email,
    full_name,
    role
FROM users
WHERE LOWER(full_name) LIKE '%john%'
ORDER BY full_name;

-- ============================================
-- 4. Filter using BETWEEN
-- Range-based filtering
-- ============================================

-- Get accounts with balance between $5000 and $10000
SELECT 
    a.account_id,
    a.account_number,
    a.account_name,
    a.balance,
    u.full_name
FROM accounts a
INNER JOIN users u ON a.user_id = u.user_id
WHERE a.balance BETWEEN 5000 AND 10000
ORDER BY a.balance DESC;

-- Get transactions between specific dates
SELECT 
    t.transaction_id,
    t.transaction_reference,
    t.transaction_type,
    t.amount,
    t.status,
    t.created_at
FROM transactions t
WHERE t.created_at BETWEEN '2024-01-01' AND '2024-12-31'
ORDER BY t.created_at DESC;

-- Get users created in a specific date range
SELECT 
    user_id,
    username,
    email,
    full_name,
    role,
    created_at
FROM users
WHERE created_at BETWEEN '2024-01-15 00:00:00' AND '2024-01-20 23:59:59'
ORDER BY created_at DESC;

-- Get transactions with amount between $100 and $1000
SELECT 
    t.transaction_id,
    t.transaction_reference,
    t.transaction_type,
    t.amount,
    t.description,
    t.status
FROM transactions t
WHERE t.amount BETWEEN 100 AND 1000
ORDER BY t.amount DESC;

-- ============================================
-- 5. ORDER BY DESC (latest transactions)
-- Sorting records in descending order
-- ============================================

-- Get latest 10 transactions
SELECT 
    t.transaction_id,
    t.transaction_reference,
    t.transaction_type,
    t.amount,
    t.description,
    t.status,
    t.created_at
FROM transactions t
ORDER BY t.created_at DESC
LIMIT 10;

-- Get latest 5 users
SELECT 
    user_id,
    username,
    email,
    full_name,
    role,
    status,
    created_at
FROM users
ORDER BY created_at DESC
LIMIT 5;

-- Get accounts sorted by balance (highest first)
SELECT 
    a.account_id,
    a.account_number,
    a.account_name,
    a.account_type,
    a.balance,
    u.full_name
FROM accounts a
INNER JOIN users u ON a.user_id = u.user_id
WHERE a.status = 'active'
ORDER BY a.balance DESC, a.account_number ASC;

-- Get latest audit logs
SELECT 
    al.log_id,
    al.action,
    al.table_name,
    al.record_id,
    al.ip_address,
    al.created_at,
    u.username
FROM audit_logs al
LEFT JOIN users u ON al.user_id = u.user_id
ORDER BY al.created_at DESC
LIMIT 20;

-- ============================================
-- 6. GROUP BY
-- Grouping and aggregating data
-- ============================================

-- Count users by role
SELECT 
    role,
    COUNT(*) AS user_count,
    COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_users,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) AS inactive_users
FROM users
GROUP BY role
ORDER BY user_count DESC;

-- Count accounts by account type
SELECT 
    account_type,
    COUNT(*) AS account_count,
    SUM(balance) AS total_balance,
    AVG(balance) AS average_balance,
    MIN(balance) AS minimum_balance,
    MAX(balance) AS maximum_balance
FROM accounts
WHERE status = 'active'
GROUP BY account_type
ORDER BY account_count DESC;

-- Count transactions by type and status
SELECT 
    transaction_type,
    status,
    COUNT(*) AS transaction_count,
    SUM(amount) AS total_amount,
    AVG(amount) AS average_amount
FROM transactions
GROUP BY transaction_type, status
ORDER BY transaction_type, status;

-- Get daily transaction summary
SELECT 
    DATE(created_at) AS transaction_date,
    COUNT(*) AS total_transactions,
    SUM(amount) AS total_amount,
    COUNT(CASE WHEN transaction_type = 'deposit' THEN 1 END) AS deposit_count,
    SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END) AS total_deposits,
    COUNT(CASE WHEN transaction_type = 'withdrawal' THEN 1 END) AS withdrawal_count,
    SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END) AS total_withdrawals
FROM transactions
WHERE status = 'completed'
GROUP BY DATE(created_at)
ORDER BY transaction_date DESC;

-- Get user account summary
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    COUNT(a.account_id) AS account_count,
    COALESCE(SUM(a.balance), 0) AS total_balance
FROM users u
LEFT JOIN accounts a ON u.user_id = a.user_id
WHERE u.role = 'customer'
GROUP BY u.user_id, u.username, u.full_name
ORDER BY total_balance DESC;

-- ============================================
-- 7. DISTINCT
-- Remove duplicate values
-- ============================================

-- Get distinct account types
SELECT DISTINCT account_type
FROM accounts
ORDER BY account_type;

-- Get distinct user roles
SELECT DISTINCT role
FROM users
ORDER BY role;

-- Get distinct transaction types
SELECT DISTINCT transaction_type
FROM transactions
ORDER BY transaction_type;

-- Get distinct account statuses
SELECT DISTINCT status
FROM accounts
ORDER BY status;

-- Get distinct users who have performed transactions
SELECT DISTINCT u.user_id, u.username, u.full_name
FROM users u
INNER JOIN transactions t ON 
    (t.from_account_id IN (SELECT account_id FROM accounts WHERE user_id = u.user_id) OR
     t.to_account_id IN (SELECT account_id FROM accounts WHERE user_id = u.user_id))
ORDER BY u.full_name;

-- Get distinct currencies used
SELECT DISTINCT currency
FROM accounts
ORDER BY currency;

-- ============================================
-- 8. UNION and UNION ALL
-- Combine results from multiple queries
-- ============================================

-- UNION: Combine deposits and withdrawals (removes duplicates)
SELECT 
    transaction_reference,
    transaction_type,
    amount,
    created_at
FROM transactions
WHERE transaction_type = 'deposit'
UNION
SELECT 
    transaction_reference,
    transaction_type,
    amount,
    created_at
FROM transactions
WHERE transaction_type = 'withdrawal'
ORDER BY created_at DESC;

-- UNION ALL: Combine all transactions including duplicates
SELECT 
    'Deposit' AS operation_type,
    amount,
    created_at
FROM transactions
WHERE transaction_type = 'deposit'
UNION ALL
SELECT 
    'Withdrawal' AS operation_type,
    amount,
    created_at
FROM transactions
WHERE transaction_type = 'withdrawal'
UNION ALL
SELECT 
    'Transfer' AS operation_type,
    amount,
    created_at
FROM transactions
WHERE transaction_type = 'transfer'
ORDER BY created_at DESC;

-- Get all active and inactive users with their status
SELECT 
    user_id,
    username,
    full_name,
    'Active' AS status_category
FROM users
WHERE status = 'active'
UNION
SELECT 
    user_id,
    username,
    full_name,
    'Inactive' AS status_category
FROM users
WHERE status = 'inactive'
ORDER BY full_name;

-- Combine account balances from different account types
SELECT 
    account_type,
    SUM(balance) AS total_balance
FROM accounts
WHERE account_type = 'savings'
GROUP BY account_type
UNION ALL
SELECT 
    account_type,
    SUM(balance) AS total_balance
FROM accounts
WHERE account_type = 'checking'
GROUP BY account_type
UNION ALL
SELECT 
    account_type,
    SUM(balance) AS total_balance
FROM accounts
WHERE account_type = 'fixed_deposit'
GROUP BY account_type;

-- ============================================
-- 9. Aggregate Functions
-- SUM, COUNT, AVG, MAX, MIN
-- ============================================

-- SUM: Calculate total balance across all accounts
SELECT 
    SUM(balance) AS total_balance,
    COUNT(*) AS total_accounts
FROM accounts
WHERE status = 'active';

-- COUNT: Count users by status
SELECT 
    COUNT(*) AS total_users,
    COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_users,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) AS inactive_users,
    COUNT(CASE WHEN status = 'suspended' THEN 1 END) AS suspended_users
FROM users;

-- AVG: Calculate average account balance
SELECT 
    AVG(balance) AS average_balance,
    MIN(balance) AS minimum_balance,
    MAX(balance) AS maximum_balance
FROM accounts
WHERE status = 'active';

-- MAX: Find the highest account balance
SELECT 
    a.account_id,
    a.account_number,
    a.account_name,
    a.balance,
    u.full_name
FROM accounts a
INNER JOIN users u ON a.user_id = u.user_id
WHERE a.status = 'active'
ORDER BY a.balance DESC
LIMIT 1;

-- MIN: Find the lowest account balance
SELECT 
    a.account_id,
    a.account_number,
    a.account_name,
    a.balance,
    u.full_name
FROM accounts a
INNER JOIN users u ON a.user_id = u.user_id
WHERE a.status = 'active' AND a.balance > 0
ORDER BY a.balance ASC
LIMIT 1;

-- SUM with GROUP BY: Total deposits by day
SELECT 
    DATE(created_at) AS deposit_date,
    SUM(amount) AS total_deposits,
    COUNT(*) AS deposit_count
FROM transactions
WHERE transaction_type = 'deposit' AND status = 'completed'
GROUP BY DATE(created_at)
ORDER BY deposit_date DESC;

-- COUNT with JOIN: Count transactions per account
SELECT 
    a.account_number,
    a.account_name,
    COUNT(t.transaction_id) AS transaction_count,
    SUM(t.amount) AS total_transaction_amount
FROM accounts a
LEFT JOIN transactions t ON a.account_id = t.from_account_id OR a.account_id = t.to_account_id
WHERE a.status = 'active'
GROUP BY a.account_id, a.account_number, a.account_name
ORDER BY transaction_count DESC;

-- AVG with WHERE: Average transaction amount by type
SELECT 
    transaction_type,
    AVG(amount) AS average_amount,
    MIN(amount) AS minimum_amount,
    MAX(amount) AS maximum_amount,
    COUNT(*) AS transaction_count
FROM transactions
WHERE status = 'completed'
GROUP BY transaction_type
ORDER BY average_amount DESC;

-- Complex aggregate query: User financial summary
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    COUNT(DISTINCT a.account_id) AS account_count,
    COALESCE(SUM(a.balance), 0) AS total_balance,
    COALESCE(AVG(a.balance), 0) AS average_balance_per_account,
    COALESCE(MAX(a.balance), 0) AS highest_account_balance,
    COALESCE(MIN(a.balance), 0) AS lowest_account_balance
FROM users u
LEFT JOIN accounts a ON u.user_id = a.user_id
WHERE u.role = 'customer'
GROUP BY u.user_id, u.username, u.full_name
ORDER BY total_balance DESC;

-- ============================================
-- Combined Complex Query Example
-- ============================================

-- Comprehensive transaction report
SELECT 
    DATE(t.created_at) AS transaction_date,
    t.transaction_type,
    t.status,
    COUNT(*) AS transaction_count,
    SUM(t.amount) AS total_amount,
    AVG(t.amount) AS average_amount,
    MIN(t.amount) AS minimum_amount,
    MAX(t.amount) AS maximum_amount
FROM transactions t
WHERE t.created_at BETWEEN '2024-01-01' AND '2024-12-31'
GROUP BY DATE(t.created_at), t.transaction_type, t.status
ORDER BY transaction_date DESC, total_amount DESC;

-- ============================================
-- Performance Optimization Tips
-- ============================================

-- Always use indexes on frequently queried columns:
-- CREATE INDEX idx_users_role ON users(role);
-- CREATE INDEX idx_accounts_status ON accounts(status);
-- CREATE INDEX idx_transactions_date ON transactions(created_at);
-- CREATE INDEX idx_transactions_type ON transactions(transaction_type);

-- Use EXPLAIN to analyze query performance:
-- EXPLAIN SELECT * FROM users WHERE role = 'customer';

-- Limit result sets when possible:
-- SELECT * FROM transactions ORDER BY created_at DESC LIMIT 100;

-- Avoid SELECT * when you only need specific columns:
-- SELECT username, email FROM users WHERE role = 'customer';

-- Use proper data types for better performance:
-- Use DECIMAL for monetary values
-- Use DATETIME for timestamps
-- Use ENUM for fixed set of values

-- ============================================
-- End of Advanced Queries
-- ============================================
