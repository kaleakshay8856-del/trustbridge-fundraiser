-- TrustBridge MySQL Schema (for local development)

CREATE DATABASE IF NOT EXISTS trustbridge;
USE trustbridge;

-- Users table
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('donor', 'ngo', 'admin', 'verification_admin', 'finance_admin') NOT NULL,
    status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    ip_address VARCHAR(45)
);

-- NGOs table
CREATE TABLE ngos (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36),
    ngo_name VARCHAR(255) NOT NULL,
    registration_number VARCHAR(100) UNIQUE NOT NULL,
    pan_number VARCHAR(10) UNIQUE NOT NULL,
    upi_id VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(10),
    website VARCHAR(255),
    founded_year INT,
    has_80g BOOLEAN DEFAULT FALSE,
    trust_score INT DEFAULT 0,
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
    approval_count INT DEFAULT 0,
    complaint_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- NGO Documents table
CREATE TABLE ngo_documents (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    ngo_id VARCHAR(36),
    document_type ENUM('registration_certificate', '80g_certificate', 'pan_card', 'address_proof', 'bank_statement') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    verified_by VARCHAR(36),
    verified_at TIMESTAMP NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ngo_id) REFERENCES ngos(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

-- Campaigns table
CREATE TABLE campaigns (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    ngo_id VARCHAR(36),
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    goal_amount DECIMAL(12, 2) NOT NULL,
    raised_amount DECIMAL(12, 2) DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'active',
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ngo_id) REFERENCES ngos(id) ON DELETE CASCADE
);

-- Donations table
CREATE TABLE donations (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    donor_id VARCHAR(36),
    ngo_id VARCHAR(36),
    campaign_id VARCHAR(36),
    amount DECIMAL(10, 2) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,
    upi_id VARCHAR(100) NOT NULL,
    verification_status ENUM('pending_verification', 'verified', 'approved', 'rejected') DEFAULT 'pending_verification',
    verified_by VARCHAR(36),
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT,
    donor_name VARCHAR(255),
    donor_email VARCHAR(255),
    donor_phone VARCHAR(20),
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (ngo_id) REFERENCES ngos(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

-- NGO Approvals table
CREATE TABLE ngo_approvals (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    ngo_id VARCHAR(36),
    admin_id VARCHAR(36),
    action ENUM('approve', 'reject') NOT NULL,
    comments TEXT,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_approval (ngo_id, admin_id),
    FOREIGN KEY (ngo_id) REFERENCES ngos(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin Logs table (Immutable)
CREATE TABLE admin_logs (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    admin_id VARCHAR(36),
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id VARCHAR(36) NOT NULL,
    details JSON,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Fraud Flags table
CREATE TABLE fraud_flags (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    entity_type ENUM('ngo', 'user', 'donation') NOT NULL,
    entity_id VARCHAR(36) NOT NULL,
    flag_type VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical'),
    description TEXT,
    auto_detected BOOLEAN DEFAULT TRUE,
    resolved BOOLEAN DEFAULT FALSE,
    resolved_by VARCHAR(36),
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- Complaints table
CREATE TABLE complaints (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    ngo_id VARCHAR(36),
    complainant_id VARCHAR(36),
    complaint_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
    resolved_by VARCHAR(36),
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (ngo_id) REFERENCES ngos(id) ON DELETE CASCADE,
    FOREIGN KEY (complainant_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- Indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_ngos_status ON ngos(status);
CREATE INDEX idx_ngos_trust_score ON ngos(trust_score);
CREATE INDEX idx_ngos_pan ON ngos(pan_number);
CREATE INDEX idx_ngos_upi ON ngos(upi_id);
CREATE INDEX idx_campaigns_ngo ON campaigns(ngo_id);
CREATE INDEX idx_campaigns_status ON campaigns(status);
CREATE INDEX idx_donations_donor ON donations(donor_id);
CREATE INDEX idx_donations_ngo ON donations(ngo_id);
CREATE INDEX idx_donations_status ON donations(verification_status);
CREATE INDEX idx_donations_transaction ON donations(transaction_id);
CREATE INDEX idx_admin_logs_admin ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_created ON admin_logs(created_at);
CREATE INDEX idx_fraud_flags_entity ON fraud_flags(entity_type, entity_id);

-- Insert sample admin user (password: admin123)
INSERT INTO users (id, email, password_hash, full_name, role, status)
VALUES (
    UUID(),
    'admin@trustbridge.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin User',
    'admin',
    'active'
);

-- Insert sample donor user (password: donor123)
INSERT INTO users (id, email, password_hash, full_name, role, status)
VALUES (
    UUID(),
    'donor@trustbridge.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Test Donor',
    'donor',
    'active'
);

-- Insert sample NGO
INSERT INTO ngos (id, ngo_name, registration_number, pan_number, upi_id, description, founded_year, status, trust_score)
VALUES (
    UUID(),
    'Save Children Foundation',
    'REG123456',
    'ABCDE1234F',
    'savechildren@upi',
    'Dedicated to child education and welfare',
    2018,
    'approved',
    85
);

SELECT 'Database setup complete!' as message;
