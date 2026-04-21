-- ============================================
-- Organizational Structure Tables
-- Banking & Transaction System
-- ============================================

USE banking_system;

-- ============================================
-- Table: Departments
-- Stores department information for employees
-- ============================================
CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    INDEX idx_department_name (department_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: Designations
-- Stores job designations/positions
-- ============================================
CREATE TABLE IF NOT EXISTS designations (
    designation_id INT AUTO_INCREMENT PRIMARY KEY,
    designation_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    INDEX idx_designation_name (designation_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: Roles (System Roles)
-- Stores system role definitions
-- ============================================
CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    permissions JSON DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    INDEX idx_role_name (role_name),
    INDEX idx_role_slug (role_slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ALTER TABLE: Add department_id to users
-- ============================================
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = 'banking_system' 
               AND table_name = 'users' 
               AND column_name = 'department_id');
               
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE users ADD COLUMN department_id INT DEFAULT NULL AFTER status, 
     ADD CONSTRAINT fk_user_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "department_id column already exists" as message');
     
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- ALTER TABLE: Add designation_id to users
-- ============================================
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = 'banking_system' 
               AND table_name = 'users' 
               AND column_name = 'designation_id');
               
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE users ADD COLUMN designation_id INT DEFAULT NULL AFTER department_id, 
     ADD CONSTRAINT fk_user_designation FOREIGN KEY (designation_id) REFERENCES designations(designation_id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "designation_id column already exists" as message');
     
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- ALTER TABLE: Add INDEX for department_id and designation_id
-- ============================================
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = 'banking_system' 
               AND table_name = 'users' 
               AND index_name = 'idx_department_id');
               
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_department_id ON users(department_id)',
    'SELECT "idx_department_id index already exists" as message');
     
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = 'banking_system' 
               AND table_name = 'users' 
               AND index_name = 'idx_designation_id');
               
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_designation_id ON users(designation_id)',
    'SELECT "idx_designation_id index already exists" as message');
     
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- INSERT Default Departments
-- ============================================
INSERT INTO departments (department_name, description, status) VALUES
('Human Resources', 'HR Department - Employee management, recruitment, and welfare', 'active'),
('Information Technology', 'IT Department - Technical support and development', 'active'),
('Finance', 'Finance Department - Accounting, budgeting, and financial operations', 'active'),
('Customer Service', 'Customer Service Department - Customer support and relations', 'active'),
('Operations', 'Operations Department - Daily banking operations', 'active'),
('Marketing', 'Marketing Department - Marketing and business development', 'active'),
('Risk Management', 'Risk Management Department - Risk assessment and compliance', 'active'),
('Security', 'Security Department - Physical and digital security', 'active')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ============================================
-- INSERT Default Designations
-- ============================================
INSERT INTO designations (designation_name, description, status) VALUES
('Manager', 'Department Manager - Oversees department operations', 'active'),
('Assistant Manager', 'Assistant Manager - Supports department manager', 'active'),
('Senior Officer', 'Senior Officer - Experienced staff member', 'active'),
('Officer', 'Officer - Regular staff member', 'active'),
('Junior Officer', 'Junior Officer - Entry-level staff member', 'active'),
('Teller', 'Bank Teller - Handles cash transactions', 'active'),
('Customer Representative', 'Customer Representative - Handles customer inquiries', 'active'),
('IT Specialist', 'IT Specialist - Technical support specialist', 'active'),
('Financial Analyst', 'Financial Analyst - Analyzes financial data', 'active'),
('HR Executive', 'HR Executive - Human resources operations', 'active'),
('Security Officer', 'Security Officer - Security and safety operations', 'active')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ============================================
-- INSERT System Roles (if not exists)
-- ============================================
INSERT INTO roles (role_name, role_slug, description, status) VALUES
('Administrator', 'admin', 'System Administrator - Full access to all features', 'active'),
('Employee', 'employee', 'Bank Employee - Access to customer and transaction management', 'active'),
('Customer', 'customer', 'Bank Customer - Access to personal banking features', 'active')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ============================================
-- Useful Queries
-- ============================================

-- View all users with department and designation
-- SELECT u.*, d.department_name, des.designation_name 
-- FROM users u 
-- LEFT JOIN departments d ON u.department_id = d.department_id
-- LEFT JOIN designations des ON u.designation_id = des.designation_id;

-- View employees by department
-- SELECT d.department_name, COUNT(u.user_id) as employee_count
-- FROM departments d
-- LEFT JOIN users u ON d.department_id = u.department_id AND u.role = 'employee'
-- GROUP BY d.department_id;

-- ============================================
-- End of Organizational Structure
-- ============================================
