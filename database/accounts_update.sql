-- ============================================
-- Accounts Table Update - Add branch_name
-- Banking & Transaction System
-- ============================================

USE banking_system;

-- ============================================
-- ALTER TABLE: Add branch_name to accounts
-- ============================================
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = 'banking_system' 
               AND table_name = 'accounts' 
               AND column_name = 'branch_name');
               
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE accounts ADD COLUMN branch_name VARCHAR(100) DEFAULT NULL AFTER account_name',
    'SELECT "branch_name column already exists" as message');
     
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- ALTER TABLE: Add INDEX for branch_name
-- ============================================
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = 'banking_system' 
               AND table_name = 'accounts' 
               AND index_name = 'idx_branch_name');
               
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_branch_name ON accounts(branch_name)',
    'SELECT "idx_branch_name index already exists" as message');
     
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- End of Accounts Update
-- ============================================
