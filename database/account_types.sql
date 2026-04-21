-- ============================================
-- Account Types System
-- Banking & Transaction System
-- ============================================

USE banking_system;

-- ============================================
-- Table: account_types
-- Stores different types of bank accounts
-- ============================================
CREATE TABLE IF NOT EXISTS account_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Default Account Types
-- ============================================
INSERT INTO account_types (type_name, description, status) VALUES
('Savings', 'Standard savings account with interest', 'active'),
('Current', 'Current/checking account for daily transactions', 'active'),
('Fixed Deposit', 'Fixed deposit account with higher interest rates', 'active'),
('Salary', 'Salary account for employees', 'active'),
('Business', 'Business account for companies', 'active')
ON DUPLICATE KEY UPDATE type_name = type_name;

-- ============================================
-- Modify Accounts Table
-- Add account_type_id foreign key
-- ============================================

-- Step 1: Add the new account_type_id column
ALTER TABLE accounts 
ADD COLUMN account_type_id INT DEFAULT NULL AFTER user_id;

-- Step 2: Add foreign key constraint
ALTER TABLE accounts 
ADD FOREIGN KEY (account_type_id) REFERENCES account_types(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Step 3: Add index for account_type_id
ALTER TABLE accounts 
ADD INDEX idx_account_type_id (account_type_id);

-- Step 4: Migrate existing account types to new system
-- Map ENUM values to account_types table
UPDATE accounts a 
SET account_type_id = (
    SELECT id FROM account_types at 
    WHERE LOWER(at.type_name) = LOWER(REPLACE(a.account_type, '_', ' '))
    OR (a.account_type = 'savings' AND at.type_name = 'Savings')
    OR (a.account_type = 'checking' AND at.type_name = 'Current')
    OR (a.account_type = 'fixed_deposit' AND at.type_name = 'Fixed Deposit')
    LIMIT 1
);

-- Step 5: Optional - Remove old account_type ENUM column
-- Uncomment after verifying migration is successful
-- ALTER TABLE accounts DROP COLUMN account_type;

-- ============================================
-- Useful Queries for Account Types
-- ============================================

-- Get all account types with account count
SELECT 
    at.id,
    at.type_name,
    at.description,
    at.status,
    COUNT(a.account_id) as account_count
FROM account_types at
LEFT JOIN accounts a ON at.id = a.account_type_id
GROUP BY at.id, at.type_name, at.description, at.status
ORDER BY at.type_name;

-- Get accounts with their type names
SELECT 
    a.account_id,
    a.account_number,
    a.account_name,
    COALESCE(at.type_name, a.account_type) as account_type_display,
    a.balance,
    a.status
FROM accounts a
LEFT JOIN account_types at ON a.account_type_id = at.id;
