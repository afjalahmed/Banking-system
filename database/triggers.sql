-- ============================================
-- Banking & Transaction System - Triggers
-- Compatible with phpMyAdmin
-- ============================================

USE banking_system;

-- ============================================
-- Drop existing triggers (if they exist)
-- ============================================
DROP TRIGGER IF EXISTS before_users_insert;
DROP TRIGGER IF EXISTS before_transactions_insert;
DROP TRIGGER IF EXISTS after_accounts_update;

-- ============================================
-- Trigger 1: BEFORE INSERT on Users
-- Convert full_name to UPPERCASE
-- ============================================
DELIMITER $$

CREATE TRIGGER before_users_insert
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    SET NEW.full_name = UPPER(NEW.full_name);
END$$

DELIMITER ;

-- ============================================
-- Trigger 2: BEFORE INSERT on Transactions
-- Validate amount must be greater than 0
-- Throw error if amount <= 0 using SIGNAL SQLSTATE
-- ============================================
DELIMITER $$

CREATE TRIGGER before_transactions_insert
BEFORE INSERT ON transactions
FOR EACH ROW
BEGIN
    IF NEW.amount <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Transaction amount must be greater than 0';
    END IF;
END$$

DELIMITER ;

-- ============================================
-- Trigger 3: AFTER UPDATE on Accounts
-- Log balance change into Audit_Logs
-- ============================================
DELIMITER $$

CREATE TRIGGER after_accounts_update
AFTER UPDATE ON accounts
FOR EACH ROW
BEGIN
    DECLARE old_balance_val DECIMAL(15, 2);
    DECLARE new_balance_val DECIMAL(15, 2);
    DECLARE balance_changed BOOLEAN;
    
    SET old_balance_val = OLD.balance;
    SET new_balance_val = NEW.balance;
    SET balance_changed = (old_balance_val != new_balance_val);
    
    -- Only log if balance has changed
    IF balance_changed THEN
        INSERT INTO audit_logs (
            user_id,
            action,
            table_name,
            record_id,
            old_values,
            new_values,
            ip_address,
            user_agent
        ) VALUES (
            NEW.user_id,
            'BALANCE_CHANGE',
            'accounts',
            NEW.account_id,
            CONCAT('{"balance":', old_balance_val, '}'),
            CONCAT('{"balance":', new_balance_val, '}'),
            NULL,
            NULL
        );
    END IF;
END$$

DELIMITER ;

-- ============================================
-- Test Queries to Verify Triggers
-- ============================================

-- Test 1: Verify BEFORE INSERT on Users trigger
-- This should insert the user with full_name converted to UPPERCASE
INSERT INTO users (username, email, password, full_name, phone, address, role, status)
VALUES ('testuser1', 'testuser1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john doe test', '+1234567800', 'Test Address', 'customer', 'active');

-- Verify the result
SELECT user_id, username, full_name FROM users WHERE username = 'testuser1';
-- Expected: full_name should be 'JOHN DOE TEST'

-- Test 2: Verify BEFORE INSERT on Transactions trigger
-- This should succeed (amount > 0)
INSERT INTO transactions (transaction_reference, from_account_id, to_account_id, transaction_type, amount, description, status)
VALUES ('TXN_TEST_001', 1, 2, 'transfer', 100.00, 'Test transfer', 'pending');

-- This should FAIL with error (amount <= 0)
-- Uncomment to test the error:
-- INSERT INTO transactions (transaction_reference, from_account_id, to_account_id, transaction_type, amount, description, status)
-- VALUES ('TXN_TEST_002', 1, 2, 'transfer', 0.00, 'Test invalid transfer', 'pending');
-- Expected error: Transaction amount must be greater than 0

-- This should also FAIL with error (negative amount)
-- Uncomment to test the error:
-- INSERT INTO transactions (transaction_reference, from_account_id, to_account_id, transaction_type, amount, description, status)
-- VALUES ('TXN_TEST_003', 1, 2, 'transfer', -50.00, 'Test negative transfer', 'pending');
-- Expected error: Transaction amount must be greater than 0

-- Test 3: Verify AFTER UPDATE on Accounts trigger
-- Update account balance to trigger the audit log
UPDATE accounts SET balance = balance + 500.00 WHERE account_id = 1;

-- Verify the audit log was created
SELECT al.*, u.username 
FROM audit_logs al
LEFT JOIN users u ON al.user_id = u.user_id
WHERE al.action = 'BALANCE_CHANGE' AND al.table_name = 'accounts'
ORDER BY al.created_at DESC
LIMIT 5;

-- Update account balance again to trigger another audit log
UPDATE accounts SET balance = balance - 200.00 WHERE account_id = 1;

-- Verify another audit log was created
SELECT al.*, u.username 
FROM audit_logs al
LEFT JOIN users u ON al.user_id = u.user_id
WHERE al.action = 'BALANCE_CHANGE' AND al.table_name = 'accounts'
ORDER BY al.created_at DESC
LIMIT 5;

-- Test 4: Verify that updating a non-balance field does NOT trigger the audit log
UPDATE accounts SET account_name = 'Updated Account Name' WHERE account_id = 1;

-- Verify no new audit log was created for this update
SELECT COUNT(*) as audit_log_count
FROM audit_logs
WHERE action = 'BALANCE_CHANGE' AND table_name = 'accounts' AND record_id = 1;

-- ============================================
-- Cleanup Test Data (Optional)
-- ============================================

-- Remove test user
-- DELETE FROM users WHERE username = 'testuser1';

-- Remove test transactions
-- DELETE FROM transactions WHERE transaction_reference LIKE 'TXN_TEST_%';

-- Remove test audit logs
-- DELETE FROM audit_logs WHERE action = 'BALANCE_CHANGE' AND record_id = 1;

-- ============================================
-- View All Triggers
-- ============================================
SHOW TRIGGERS;

-- ============================================
-- View Specific Trigger Details
-- ============================================
SHOW CREATE TRIGGER before_users_insert;
SHOW CREATE TRIGGER before_transactions_insert;
SHOW CREATE TRIGGER after_accounts_update;

-- ============================================
-- End of Triggers File
-- ============================================
