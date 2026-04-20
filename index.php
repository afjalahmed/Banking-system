<?php
/**
 * Homepage
 * Banking & Transaction System
 */

require_once 'includes/header.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1><i class="fas fa-university"></i> Banking & Transaction System</h1>
            <p>Secure, Fast, and Reliable Banking Services</p>
            <div class="hero-buttons">
                <?php if (isLoggedIn()): ?>
                    <?php if (getUserRole() === 'admin'): ?>
                        <a href="/admin/dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Go to Admin Dashboard
                        </a>
                    <?php elseif (getUserRole() === 'employee'): ?>
                        <a href="/employee/dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Go to Employee Dashboard
                        </a>
                    <?php elseif (getUserRole() === 'customer'): ?>
                        <a href="/customer/dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/customer/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="/customer/register.php" class="btn btn-secondary">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="features-section">
    <div class="container">
        <h2 class="text-center mb-4">Our Services</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-university"></i>
                </div>
                <h3>Account Management</h3>
                <p>Open and manage multiple bank accounts with ease. Track your balances and transactions in real-time.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h3>Easy Transfers</h3>
                <p>Transfer funds between accounts instantly. Send money to friends and family securely.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3>Transaction History</h3>
                <p>View complete transaction history with detailed records. Download statements anytime.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Banking</h3>
                <p>Bank-grade security with encryption and fraud protection. Your money is safe with us.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>24/7 Access</h3>
                <p>Access your account anytime, anywhere. Manage your finances on the go.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>Customer Support</h3>
                <p>Dedicated support team available to help you with any questions or issues.</p>
            </div>
        </div>
    </div>
</div>

<div class="about-section">
    <div class="container">
        <div class="about-content">
            <div class="about-text">
                <h2>About Our Banking System</h2>
                <p>Welcome to the Banking & Transaction System, your trusted partner for secure and efficient banking services. Our platform provides a comprehensive solution for managing your financial needs with ease and security.</p>
                <p>With our role-based access control, we ensure that your data is protected and only authorized personnel can access sensitive information. Our system supports multiple account types including savings, checking, and fixed deposit accounts.</p>
                <ul class="about-list">
                    <li><i class="fas fa-check-circle"></i> Role-based access control (Admin, Employee, Customer)</li>
                    <li><i class="fas fa-check-circle"></i> Secure authentication with password hashing</li>
                    <li><i class="fas fa-check-circle"></i> Real-time transaction processing</li>
                    <li><i class="fas fa-check-circle"></i> Comprehensive audit logging</li>
                    <li><i class="fas fa-check-circle"></i> Responsive and user-friendly interface</li>
                </ul>
            </div>
            <div class="about-image">
                <i class="fas fa-piggy-bank"></i>
            </div>
        </div>
    </div>
</div>

<style>
.hero-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: var(--white);
    padding: 6rem 0;
    text-align: center;
}

.hero-content h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.hero-content p {
    font-size: 1.5rem;
    margin-bottom: 2rem;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.features-section {
    padding: 4rem 0;
    background-color: var(--light-color);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.feature-card {
    background-color: var(--white);
    padding: 2rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    text-align: center;
    transition: var(--transition);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.feature-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: rgba(37, 99, 235, 0.1);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 1.5rem;
}

.feature-card h3 {
    margin-bottom: 1rem;
    color: var(--dark-color);
}

.feature-card p {
    color: var(--gray-color);
    line-height: 1.6;
}

.about-section {
    padding: 4rem 0;
    background-color: var(--white);
}

.about-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

.about-text h2 {
    margin-bottom: 1.5rem;
    color: var(--dark-color);
}

.about-text p {
    color: var(--gray-color);
    line-height: 1.8;
    margin-bottom: 1.5rem;
}

.about-list {
    list-style: none;
}

.about-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    color: var(--gray-color);
}

.about-list i {
    color: var(--success-color);
}

.about-image {
    text-align: center;
    font-size: 10rem;
    color: var(--primary-color);
}

@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-content p {
        font-size: 1.25rem;
    }
    
    .hero-buttons {
        flex-direction: column;
    }
    
    .about-content {
        grid-template-columns: 1fr;
    }
    
    .about-image {
        font-size: 6rem;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
