-- ============================================
-- Banking & Transaction System Database Schema
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS banking_system;
USE banking_system;

-- ============================================
-- Table: Users
-- Stores user information for all roles (admin, employee, customer)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    role ENUM('admin', 'employee', 'customer') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: Accounts
-- Stores bank account information for customers
-- ============================================
CREATE TABLE IF NOT EXISTS accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    account_type ENUM('savings', 'checking', 'fixed_deposit') NOT NULL DEFAULT 'savings',
    account_name VARCHAR(100) NOT NULL,
    balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    status ENUM('active', 'inactive', 'frozen', 'closed') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_account_number (account_number),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    CONSTRAINT chk_balance CHECK (balance >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: Transactions
-- Stores all transaction records
-- ============================================
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_reference VARCHAR(50) NOT NULL UNIQUE,
    from_account_id INT DEFAULT NULL,
    to_account_id INT DEFAULT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'transfer', 'fee', 'interest') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME DEFAULT NULL,
    processed_by INT DEFAULT NULL,
    FOREIGN KEY (from_account_id) REFERENCES accounts(account_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (to_account_id) REFERENCES accounts(account_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_transaction_reference (transaction_reference),
    INDEX idx_from_account (from_account_id),
    INDEX idx_to_account (to_account_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: Audit_Logs
-- Stores system audit logs for security and tracking
-- ============================================
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) DEFAULT NULL,
    record_id INT DEFAULT NULL,
    old_values TEXT DEFAULT NULL,
    new_values TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ALTER TABLE Examples
-- ============================================

-- ADD COLUMN Examples
-- Add a new column to users table
ALTER TABLE users 
ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER phone;

-- Add a new column to accounts table
ALTER TABLE accounts 
ADD COLUMN overdraft_limit DECIMAL(15, 2) DEFAULT 0.00 AFTER balance;

-- Add a new column to transactions table
ALTER TABLE transactions 
ADD COLUMN notes TEXT DEFAULT NULL AFTER description;

-- DROP COLUMN Examples
-- Remove a column from users table
ALTER TABLE users 
DROP COLUMN profile_picture;

-- Remove a column from accounts table
ALTER TABLE accounts 
DROP COLUMN overdraft_limit;

-- MODIFY COLUMN Examples
-- Change the data type of a column
ALTER TABLE users 
MODIFY COLUMN phone VARCHAR(30) NOT NULL;

-- Change the default value of a column
ALTER TABLE accounts 
MODIFY COLUMN balance DECIMAL(15, 2) NOT NULL DEFAULT 1000.00;

-- Add NOT NULL constraint
ALTER TABLE users 
MODIFY COLUMN address TEXT NOT NULL;

-- ADD INDEX Examples
-- Add a composite index
ALTER TABLE transactions 
ADD INDEX idx_account_date (from_account_id, created_at);

-- Add a full-text index
ALTER TABLE users 
ADD FULLTEXT INDEX ft_full_name (full_name);

-- DROP INDEX Examples
-- Drop an index
ALTER TABLE transactions 
DROP INDEX idx_account_date;

-- ADD FOREIGN KEY Example
-- Add a foreign key constraint (if not already present)
ALTER TABLE transactions 
ADD CONSTRAINT fk_processed_by_user 
FOREIGN KEY (processed_by) REFERENCES users(user_id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- DROP FOREIGN KEY Example
-- Drop a foreign key constraint
ALTER TABLE transactions 
DROP FOREIGN KEY fk_processed_by_user;

-- ============================================
-- INSERT Sample Data
-- ============================================

-- Insert Admin Users
INSERT INTO users (username, email, password, full_name, phone, address, role, status) VALUES
('admin1', 'admin@banking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Administrator', '+1234567890', '123 Admin Street, City', 'admin', 'active'),
('admin2', 'manager@banking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Manager', '+1234567891', '456 Manager Ave, City', 'admin', 'active');

-- Insert Employee Users
INSERT INTO users (username, email, password, full_name, phone, address, role, status) VALUES
('emp1', 'employee1@banking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Employee', '+1234567892', '789 Employee Road, City', 'employee', 'active'),
('emp2', 'employee2@banking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa Staff', '+1234567893', '321 Staff Lane, City', 'employee', 'active'),
('emp3', 'teller@banking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Teller', '+1234567894', '654 Teller Blvd, City', 'employee', 'active');

-- Insert Customer Users
INSERT INTO users (username, email, password, full_name, phone, address, role, status) VALUES
('cust1', 'customer1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert Customer', '+1234567895', '111 Customer St, City', 'customer', 'active'),
('cust2', 'customer2@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily Client', '+1234567896', '222 Client Ave, City', 'customer', 'active'),
('cust3', 'customer3@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James User', '+1234567897', '333 User Road, City', 'customer', 'active'),
('cust4', 'customer4@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Patron', '+1234567898', '444 Patron Lane, City', 'customer', 'active'),
('cust5', 'customer5@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'William Member', '+1234567899', '555 Member Blvd, City', 'customer', 'inactive');

-- Insert Accounts
INSERT INTO accounts (account_number, user_id, account_type, account_name, balance, currency, status) VALUES
('ACC0010001', 6, 'savings', 'Robert Savings Account', 5000.00, 'USD', 'active'),
('ACC0010002', 6, 'checking', 'Robert Checking Account', 2500.00, 'USD', 'active'),
('ACC0010003', 7, 'savings', 'Emily Savings Account', 10000.00, 'USD', 'active'),
('ACC0010004', 8, 'checking', 'James Checking Account', 7500.00, 'USD', 'active'),
('ACC0010005', 8, 'savings', 'James Savings Account', 3000.00, 'USD', 'active'),
('ACC0010006', 9, 'savings', 'Maria Savings Account', 15000.00, 'USD', 'active'),
('ACC0010007', 10, 'checking', 'William Checking Account', 2000.00, 'USD', 'inactive');

-- Insert Transactions
INSERT INTO transactions (transaction_reference, from_account_id, to_account_id, transaction_type, amount, description, status, processed_at, processed_by) VALUES
('TXN2024010001', NULL, 1, 'deposit', 5000.00, 'Initial deposit', 'completed', '2024-01-15 10:00:00', 3),
('TXN2024010002', NULL, 2, 'deposit', 2500.00, 'Initial deposit', 'completed', '2024-01-15 10:30:00', 3),
('TXN2024010003', NULL, 3, 'deposit', 10000.00, 'Initial deposit', 'completed', '2024-01-16 09:00:00', 4),
('TXN2024010004', NULL, 4, 'deposit', 7500.00, 'Initial deposit', 'completed', '2024-01-16 09:30:00', 4),
('TXN2024010005', NULL, 5, 'deposit', 3000.00, 'Initial deposit', 'completed', '2024-01-16 10:00:00', 5),
('TXN2024010006', NULL, 6, 'deposit', 15000.00, 'Initial deposit', 'completed', '2024-01-17 08:00:00', 3),
('TXN2024010007', NULL, 7, 'deposit', 2000.00, 'Initial deposit', 'completed', '2024-01-17 08:30:00', 3),
('TXN2024010008', 2, 4, 'transfer', 1000.00, 'Transfer from Robert to James', 'completed', '2024-01-18 11:00:00', 3),
('TXN2024010009', 4, 6, 'transfer', 500.00, 'Transfer from James to Maria', 'completed', '2024-01-18 11:30:00', 4),
('TXN2024010010', 3, NULL, 'withdrawal', 2000.00, 'Cash withdrawal', 'completed', '2024-01-19 14:00:00', 5),
('TXN2024010011', 1, 3, 'transfer', 1500.00, 'Transfer from Robert to Emily', 'pending', NULL, NULL),
('TXN2024010012', 6, NULL, 'withdrawal', 3000.00, 'Cash withdrawal', 'failed', '2024-01-20 10:00:00', 3);

-- Insert Audit Logs
INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES
(1, 'CREATE', 'users', 1, NULL, '{"username":"admin1","role":"admin"}', '192.168.1.100', 'Mozilla/5.0'),
(1, 'CREATE', 'users', 2, NULL, '{"username":"admin2","role":"admin"}', '192.168.1.100', 'Mozilla/5.0'),
(3, 'CREATE', 'users', 3, NULL, '{"username":"emp1","role":"employee"}', '192.168.1.101', 'Mozilla/5.0'),
(3, 'CREATE', 'accounts', 1, NULL, '{"account_number":"ACC0010001","balance":5000.00}', '192.168.1.101', 'Mozilla/5.0'),
(3, 'UPDATE', 'accounts', 1, '{"balance":5000.00}', '{"balance":4000.00}', '192.168.1.101', 'Mozilla/5.0'),
(4, 'CREATE', 'transactions', 1, NULL, '{"transaction_reference":"TXN2024010001","amount":5000.00}', '192.168.1.102', 'Mozilla/5.0'),
(4, 'UPDATE', 'transactions', 8, '{"status":"pending"}', '{"status":"completed"}', '192.168.1.102', 'Mozilla/5.0'),
(5, 'DELETE', 'users', 11, '{"username":"deleted_user"}', NULL, '192.168.1.103', 'Mozilla/5.0'),
(6, 'LOGIN', NULL, NULL, NULL, NULL, '192.168.1.150', 'Mozilla/5.0'),
(6, 'LOGOUT', NULL, NULL, NULL, NULL, '192.168.1.150', 'Mozilla/5.0');

-- ============================================
-- Useful Queries
-- ============================================

-- View all users with their account count
-- SELECT u.*, COUNT(a.account_id) as account_count 
-- FROM users u 
-- LEFT JOIN accounts a ON u.user_id = a.user_id 
-- GROUP BY u.user_id;

-- View transaction history for a specific account
-- SELECT t.*, 
--        fa.account_number as from_account,
--        ta.account_number as to_account
-- FROM transactions t
-- LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
-- LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
-- WHERE t.from_account_id = 1 OR t.to_account_id = 1
-- ORDER BY t.created_at DESC;

-- View audit logs for a specific user
-- SELECT al.*, u.username 
-- FROM audit_logs al
-- LEFT JOIN users u ON al.user_id = u.user_id
-- WHERE al.user_id = 1
-- ORDER BY al.created_at DESC;

-- ============================================
-- End of Schema
-- ============================================
