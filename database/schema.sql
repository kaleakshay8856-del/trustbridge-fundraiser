-- TrustBridge PostgreSQL Schema

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Users table
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(50) NOT NULL CHECK (role IN ('donor', 'ngo', 'admin', 'verification_admin', 'finance_admin')),
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'banned')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    ip_address INET
);

-- NGOs table
CREATE TABLE ngos (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
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
    founded_year INTEGER,
    has_80g BOOLEAN DEFAULT FALSE,
    trust_score INTEGER DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'under_review', 'approved', 'rejected', 'suspended')),
    approval_count INTEGER DEFAULT 0,
    complaint_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- NGO Documents table
CREATE TABLE ngo_documents (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    ngo_id UUID REFERENCES ngos(id) ON DELETE CASCADE,
    document_type VARCHAR(100) NOT NULL CHECK (document_type IN ('registration_certificate', '80g_certificate', 'pan_card', 'address_proof', 'bank_statement')),
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    verified_by UUID REFERENCES users(id),
    verified_at TIMESTAMP,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Campaigns table
CREATE TABLE campaigns (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    ngo_id UUID REFERENCES ngos(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    goal_amount DECIMAL(12, 2) NOT NULL,
    raised_amount DECIMAL(12, 2) DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('draft', 'active', 'completed', 'cancelled')),
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Donations table
CREATE TABLE donations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    donor_id UUID REFERENCES users(id) ON DELETE SET NULL,
    ngo_id UUID REFERENCES ngos(id) ON DELETE CASCADE,
    campaign_id UUID REFERENCES campaigns(id) ON DELETE SET NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,
    upi_id VARCHAR(100) NOT NULL,
    verification_status VARCHAR(50) DEFAULT 'pending_verification' CHECK (verification_status IN ('pending_verification', 'verified', 'approved', 'rejected')),
    verified_by UUID REFERENCES users(id),
    verified_at TIMESTAMP,
    rejection_reason TEXT,
    donor_name VARCHAR(255),
    donor_email VARCHAR(255),
    donor_phone VARCHAR(20),
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- NGO Approvals table (Multi-admin approval)
CREATE TABLE ngo_approvals (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    ngo_id UUID REFERENCES ngos(id) ON DELETE CASCADE,
    admin_id UUID REFERENCES users(id) ON DELETE CASCADE,
    action VARCHAR(50) NOT NULL CHECK (action IN ('approve', 'reject')),
    comments TEXT,
    ip_address INET NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(ngo_id, admin_id)
);

-- Admin Logs table (Immutable)
CREATE TABLE admin_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    admin_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id UUID NOT NULL,
    details JSONB,
    ip_address INET NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fraud Flags table
CREATE TABLE fraud_flags (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    entity_type VARCHAR(50) NOT NULL CHECK (entity_type IN ('ngo', 'user', 'donation')),
    entity_id UUID NOT NULL,
    flag_type VARCHAR(100) NOT NULL,
    severity VARCHAR(50) CHECK (severity IN ('low', 'medium', 'high', 'critical')),
    description TEXT,
    auto_detected BOOLEAN DEFAULT TRUE,
    resolved BOOLEAN DEFAULT FALSE,
    resolved_by UUID REFERENCES users(id),
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Complaints table
CREATE TABLE complaints (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    ngo_id UUID REFERENCES ngos(id) ON DELETE CASCADE,
    complainant_id UUID REFERENCES users(id) ON DELETE SET NULL,
    complaint_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'investigating', 'resolved', 'dismissed')),
    resolved_by UUID REFERENCES users(id),
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP
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

-- Trigger to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_ngos_updated_at BEFORE UPDATE ON ngos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_campaigns_updated_at BEFORE UPDATE ON campaigns FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Prevent admin_logs deletion (Immutable)
CREATE OR REPLACE FUNCTION prevent_admin_logs_deletion()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Admin logs cannot be deleted or modified';
END;
$$ language 'plpgsql';

CREATE TRIGGER prevent_delete_admin_logs BEFORE DELETE ON admin_logs FOR EACH ROW EXECUTE FUNCTION prevent_admin_logs_deletion();
CREATE TRIGGER prevent_update_admin_logs BEFORE UPDATE ON admin_logs FOR EACH ROW EXECUTE FUNCTION prevent_admin_logs_deletion();
