-- ============================================
-- Update Transaction Status Enum
-- Banking & Transaction System
-- ============================================

USE banking_system;

-- ============================================
-- Step 1: Modify the status column to new ENUM values
-- ============================================
ALTER TABLE transactions 
MODIFY COLUMN status ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING';

-- ============================================
-- Step 2: Migrate existing data
-- ============================================

-- Convert 'pending' to 'PENDING'
UPDATE transactions SET status = 'PENDING' WHERE status = 'pending';

-- Convert 'completed' to 'APPROVED'
UPDATE transactions SET status = 'APPROVED' WHERE status = 'completed';

-- Convert 'failed' or 'cancelled' to 'REJECTED'
UPDATE transactions SET status = 'REJECTED' WHERE status IN ('failed', 'cancelled', 'rejected');

-- ============================================
-- Step 3: Verify the migration
-- ============================================
SELECT 
    status, 
    COUNT(*) as count 
FROM transactions 
GROUP BY status;

-- ============================================
-- Useful Queries for the new status system
-- ============================================

-- Get all pending transactions
SELECT * FROM transactions WHERE status = 'PENDING';

-- Get all approved transactions
SELECT * FROM transactions WHERE status = 'APPROVED';

-- Get all rejected transactions
SELECT * FROM transactions WHERE status = 'REJECTED';

-- Get transaction summary by status
SELECT 
    status,
    COUNT(*) as count,
    SUM(amount) as total_amount
FROM transactions
GROUP BY status;
