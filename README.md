# Banking & Transaction System

A complete, secure, and professional web-based banking system built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### Core Functionality
- **Role-Based Access Control** - Three user roles: Admin, Employee, Customer
- **Secure Authentication** - Session-based login with password hashing
- **Account Management** - Create and manage multiple account types (savings, checking, fixed deposit)
- **Transaction System** - Deposit, withdrawal, and transfer operations
- **Audit Logging** - Comprehensive system audit trail for security
- **Responsive Design** - Mobile-friendly interface

### User Roles

#### Admin
- View all users and accounts
- Monitor system statistics
- Access audit logs
- Generate reports
- Full system oversight

#### Employee
- Add new customers
- Create bank accounts
- Process deposits and withdrawals
- Manage pending transactions
- View customer information

#### Customer
- View account balances
- Transfer funds between accounts
- Request deposits and withdrawals
- View transaction history
- Manage profile settings

## Technology Stack

- **Backend**: PHP (Procedural with modular structure)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Security**: Prepared statements, password hashing, session management

## Project Structure

```
project/
├── config/
│   └── db.php                 # Database connection
├── includes/
│   ├── header.php             # Reusable header with navigation
│   └── footer.php             # Reusable footer
├── admin/
│   ├── auth.php               # Admin session protection
│   └── dashboard.php          # Admin dashboard
├── employee/
│   ├── auth.php               # Employee session protection
│   ├── dashboard.php          # Employee dashboard
│   ├── deposit.php            # Employee deposit operations
│   └── withdraw.php           # Employee withdrawal operations
├── customer/
│   ├── auth.php               # Customer session protection
│   ├── dashboard.php          # Customer dashboard
│   ├── login.php              # Login page
│   ├── register.php           # Registration page
│   ├── deposit.php            # Customer deposit requests
│   ├── withdraw.php           # Customer withdrawal requests
│   └── transfer.php           # Fund transfers
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── main.js            # JavaScript functionality
├── database/
│   ├── schema.sql             # Database schema
│   ├── triggers.sql           # Database triggers
│   └── advanced_queries.sql   # Advanced SQL queries
├── index.php                  # Homepage
└── logout.php                 # Logout script
```

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, or PHP built-in server)

### Step 1: Database Setup

1. Create a new MySQL database named `banking_system`
2. Import the database schema:
   ```bash
   mysql -u root -p banking_system < database/schema.sql
   ```

3. (Optional) Import triggers:
   ```bash
   mysql -u root -p banking_system < database/triggers.sql
   ```

### Step 2: Configuration

Edit `config/db.php` to match your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'banking_system');
```

### Step 3: Web Server Setup

**Using PHP built-in server (for development):**
```bash
cd project
php -S localhost:8000
```

**Using Apache:**
1. Place the project in your web root (e.g., `/var/www/html/banking`)
2. Configure virtual host if needed
3. Restart Apache

**Using Nginx:**
1. Place the project in your web root
2. Configure Nginx to serve PHP files
3. Restart Nginx

### Step 4: Access the Application

Open your browser and navigate to:
- `http://localhost:8000` (if using PHP built-in server)
- `http://localhost/banking` (if using Apache with default configuration)

## Default Credentials

The database schema includes sample users for testing:

### Admin Users
- **Username**: admin1
- **Email**: admin@banking.com
- **Password**: password

- **Username**: admin2
- **Email**: manager@banking.com
- **Password**: password

### Employee Users
- **Username**: emp1
- **Email**: employee1@banking.com
- **Password**: password

- **Username**: emp2
- **Email**: employee2@banking.com
- **Password**: password

### Customer Users
- **Username**: cust1
- **Email**: customer1@email.com
- **Password**: password

- **Username**: cust2
- **Email**: customer2@email.com
- **Password**: password

**Note**: All sample passwords are hashed. The plain text password is "password" for all sample accounts.

## Security Features

### SQL Injection Prevention
- All database queries use prepared statements
- Input sanitization with `sanitize()` function
- Type casting for numeric inputs

### Authentication Security
- Password hashing using `password_hash()` and `PASSWORD_DEFAULT`
- Session-based authentication
- Session regeneration to prevent session fixation
- Role-based access control

### Audit Logging
- All login/logout events logged
- Account balance changes tracked
- User actions recorded with IP address and user agent

### Data Validation
- Server-side validation for all forms
- Client-side validation for better UX
- Balance checks before withdrawals
- Transaction amount limits

## Database Schema

### Tables

1. **users** - User accounts and authentication
   - user_id (PRIMARY KEY, AUTO_INCREMENT)
   - username (UNIQUE)
   - email (UNIQUE)
   - password (hashed)
   - full_name
   - phone
   - address
   - role (ENUM: admin, employee, customer)
   - status (ENUM: active, inactive, suspended)
   - created_at, updated_at, last_login

2. **accounts** - Bank accounts
   - account_id (PRIMARY KEY, AUTO_INCREMENT)
   - account_number (UNIQUE)
   - user_id (FOREIGN KEY)
   - account_type (ENUM: savings, checking, fixed_deposit)
   - account_name
   - balance (DECIMAL)
   - currency
   - status (ENUM: active, inactive, frozen, closed)
   - created_at, updated_at

3. **transactions** - Transaction records
   - transaction_id (PRIMARY KEY, AUTO_INCREMENT)
   - transaction_reference (UNIQUE)
   - from_account_id (FOREIGN KEY)
   - to_account_id (FOREIGN KEY)
   - transaction_type (ENUM: deposit, withdrawal, transfer, fee, interest)
   - amount (DECIMAL)
   - description
   - status (ENUM: pending, completed, failed, cancelled)
   - created_at, processed_at, processed_by

4. **audit_logs** - System audit trail
   - log_id (PRIMARY KEY, AUTO_INCREMENT)
   - user_id (FOREIGN KEY)
   - action
   - table_name
   - record_id
   - old_values, new_values
   - ip_address, user_agent
   - created_at

## Database Triggers

1. **before_users_insert** - Converts full_name to UPPERCASE
2. **before_transactions_insert** - Validates amount > 0
3. **after_accounts_update** - Logs balance changes to audit_logs

## Usage Guide

### For Admins

1. **Login** with admin credentials
2. **Dashboard** - View system statistics and recent activity
3. **Manage Users** - View all user accounts
4. **View Accounts** - Monitor all bank accounts
5. **Audit Logs** - Review system activity
6. **Reports** - Generate system reports

### For Employees

1. **Login** with employee credentials
2. **Dashboard** - View customer statistics and pending transactions
3. **Add Customer** - Register new customers
4. **Create Account** - Open new bank accounts
5. **Deposit** - Process customer deposits
6. **Withdraw** - Process customer withdrawals
7. **Pending Transactions** - Review and approve pending requests

### For Customers

1. **Register** - Create a new account
2. **Login** with customer credentials
3. **Dashboard** - View account balances and recent transactions
4. **Transfer** - Send money to other accounts
5. **Deposit** - Request deposit (requires employee approval)
6. **Withdraw** - Request withdrawal (requires employee approval)
7. **Transactions** - View complete transaction history

## API/Helper Functions

### Database Functions (config/db.php)

- `sanitize($data)` - Sanitize input data
- `executeQuery($sql, $params)` - Execute prepared statement
- `fetchAll($stmt)` - Fetch all results as associative array
- `fetchOne($stmt)` - Fetch single result as associative array

### Authentication Functions (includes/header.php)

- `isLoggedIn()` - Check if user is logged in
- `getUserRole()` - Get current user role
- `requireLogin()` - Redirect if not logged in
- `requireAdmin()` - Redirect if not admin
- `requireEmployee()` - Redirect if not employee
- `getCurrentPage()` - Get current page filename

## Advanced SQL Queries

The `database/advanced_queries.sql` file contains examples of:

- INNER JOIN queries
- LEFT JOIN queries
- LIKE pattern matching
- BETWEEN range filtering
- ORDER BY sorting
- GROUP BY aggregation
- DISTINCT values
- UNION/UNION ALL operations
- Aggregate functions (SUM, COUNT, AVG, MAX, MIN)

## Troubleshooting

### Database Connection Issues
- Verify database credentials in `config/db.php`
- Ensure MySQL server is running
- Check database name matches

### Session Issues
- Ensure session.save_path is writable
- Check PHP session configuration
- Verify cookies are enabled in browser

### File Permission Issues
- Ensure web server has read access to all files
- Check write permissions for session storage

### Transaction Failures
- Check database constraints
- Verify sufficient balance for withdrawals
- Review audit logs for error details

## Development Notes

### Code Style
- Procedural PHP with modular structure
- Clean and readable code
- Proper commenting and documentation
- Consistent naming conventions

### Security Best Practices
- Never trust user input
- Always use prepared statements
- Hash passwords before storage
- Validate data on both client and server
- Log important system events

### Performance Optimization
- Use indexes on frequently queried columns
- Limit result sets when possible
- Use appropriate data types
- Optimize complex queries with EXPLAIN

## License

This project is for educational purposes.

## Support

For issues or questions, please refer to the documentation or contact the development team.

---

**Version**: 1.0.0  
**Last Updated**: 2026-04-20
