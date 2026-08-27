-- KofarArziki Data VTU Database Schema
-- MySQL 5.7+

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    referral_code VARCHAR(20) UNIQUE NOT NULL,
    referrer_id INT,
    profile_image VARCHAR(255),
    account_status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    kyc_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_referral_code (referral_code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wallets table (Secure - stores actual balances)
CREATE TABLE IF NOT EXISTS wallets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    balance DECIMAL(12, 2) DEFAULT 0.00,
    locked_balance DECIMAL(12, 2) DEFAULT 0.00,
    total_deposits DECIMAL(12, 2) DEFAULT 0.00,
    total_withdrawals DECIMAL(12, 2) DEFAULT 0.00,
    last_transaction_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'data_purchase', 'airtime_purchase', 'cable_purchase', 'electricity_purchase', 'referral_bonus', 'refund') NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    reference VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    payment_method VARCHAR(50),
    provider_response TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_reference (reference),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Plans table
CREATE TABLE IF NOT EXISTS data_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    network VARCHAR(50) NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    data_amount VARCHAR(50) NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    validity_days INT DEFAULT 30,
    api_plan_id VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_network (network),
    INDEX idx_is_active (is_active),
    UNIQUE KEY unique_plan (network, data_amount, price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Airtime Plans table
CREATE TABLE IF NOT EXISTS airtime_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    network VARCHAR(50) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    api_plan_id VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_network (network),
    INDEX idx_is_active (is_active),
    UNIQUE KEY unique_airtime (network, amount, price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cable Plans table
CREATE TABLE IF NOT EXISTS cable_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider VARCHAR(50) NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    package_type VARCHAR(50),
    price DECIMAL(12, 2) NOT NULL,
    duration VARCHAR(50),
    api_plan_id VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider (provider),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Electricity Plans table
CREATE TABLE IF NOT EXISTS electricity_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider VARCHAR(50) NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    min_amount DECIMAL(12, 2) NOT NULL,
    max_amount DECIMAL(12, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    api_provider_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider (provider),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Cards table (for printing)
CREATE TABLE IF NOT EXISTS data_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_id INT NOT NULL,
    network VARCHAR(50) NOT NULL,
    data_amount VARCHAR(50) NOT NULL,
    pin VARCHAR(20) NOT NULL,
    serial_number VARCHAR(50) NOT NULL,
    reference VARCHAR(50) NOT NULL,
    print_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_reference (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deposits table
CREATE TABLE IF NOT EXISTS deposits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    reference VARCHAR(50) UNIQUE NOT NULL,
    payment_gateway_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_reference (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Withdrawals table
CREATE TABLE IF NOT EXISTS withdrawals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    bank_name VARCHAR(100),
    account_number VARCHAR(20),
    account_name VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    reference VARCHAR(50) UNIQUE NOT NULL,
    bank_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_reference (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Referral Bonuses table
CREATE TABLE IF NOT EXISTS referral_bonuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    transaction_id INT,
    bonus_amount DECIMAL(12, 2) NOT NULL,
    bonus_type ENUM('registration', 'transaction', 'deposit') DEFAULT 'registration',
    status ENUM('pending', 'completed', 'claimed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_referrer_id (referrer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Credentials table (Encrypted in real implementation)
CREATE TABLE IF NOT EXISTS api_credentials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider VARCHAR(50) UNIQUE NOT NULL,
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    api_url VARCHAR(255),
    webhook_url VARCHAR(255),
    webhook_secret VARCHAR(255),
    is_active BOOLEAN DEFAULT FALSE,
    test_mode BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Log table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role ENUM('admin', 'superadmin', 'moderator') DEFAULT 'admin',
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin (user_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES 
    ('app_name', 'KofarArziki Data', 'Application name'),
    ('app_timezone', 'Africa/Lagos', 'Application timezone'),
    ('min_deposit', '1000', 'Minimum deposit amount'),
    ('min_withdrawal', '5000', 'Minimum withdrawal amount'),
    ('max_withdrawal', '500000', 'Maximum withdrawal amount'),
    ('withdrawal_fee_percent', '1', 'Withdrawal fee percentage'),
    ('referral_bonus_registration', '500', 'Registration referral bonus'),
    ('referral_bonus_transaction_percent', '2', 'Transaction referral bonus percentage'),
    ('maintenance_mode', '0', 'Application maintenance mode'),
    ('smtp_host', '', 'SMTP server host'),
    ('smtp_port', '587', 'SMTP server port'),
    ('smtp_username', '', 'SMTP username'),
    ('smtp_password', '', 'SMTP password'),
    ('smtp_from_email', 'noreply@kofararziki.com', 'SMTP from email'),
    ('sms_gateway_provider', '', 'SMS gateway provider'),
    ('sms_api_key', '', 'SMS API key'),
    ('support_email', 'support@kofararziki.com', 'Support email'),
    ('support_phone', '', 'Support phone number');
