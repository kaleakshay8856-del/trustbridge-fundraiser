-- Performance Optimization: Add Database Indexes
-- Run this in Supabase SQL Editor to speed up queries

-- Users table indexes
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);

-- NGOs table indexes
CREATE INDEX IF NOT EXISTS idx_ngos_user_id ON ngos(user_id);
CREATE INDEX IF NOT EXISTS idx_ngos_status ON ngos(status);
CREATE INDEX IF NOT EXISTS idx_ngos_trust_score ON ngos(trust_score);
CREATE INDEX IF NOT EXISTS idx_ngos_status_score ON ngos(status, trust_score DESC);

-- Donations table indexes
CREATE INDEX IF NOT EXISTS idx_donations_donor_id ON donations(donor_id);
CREATE INDEX IF NOT EXISTS idx_donations_ngo_id ON donations(ngo_id);
CREATE INDEX IF NOT EXISTS idx_donations_verification_status ON donations(verification_status);
CREATE INDEX IF NOT EXISTS idx_donations_created_at ON donations(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_donations_status_created ON donations(verification_status, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_donations_ngo_status ON donations(ngo_id, verification_status);

-- Campaigns table indexes
CREATE INDEX IF NOT EXISTS idx_campaigns_ngo_id ON campaigns(ngo_id);
CREATE INDEX IF NOT EXISTS idx_campaigns_status ON campaigns(status);
CREATE INDEX IF NOT EXISTS idx_campaigns_dates ON campaigns(start_date, end_date);

-- NGO Documents table indexes
CREATE INDEX IF NOT EXISTS idx_ngo_documents_ngo_id ON ngo_documents(ngo_id);
CREATE INDEX IF NOT EXISTS idx_ngo_documents_verified ON ngo_documents(verified);
CREATE INDEX IF NOT EXISTS idx_ngo_documents_type ON ngo_documents(document_type);

-- NGO Approvals table indexes
CREATE INDEX IF NOT EXISTS idx_ngo_approvals_ngo_id ON ngo_approvals(ngo_id);
CREATE INDEX IF NOT EXISTS idx_ngo_approvals_admin_id ON ngo_approvals(admin_id);

-- Fraud Flags table indexes
CREATE INDEX IF NOT EXISTS idx_fraud_flags_entity ON fraud_flags(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_fraud_flags_resolved ON fraud_flags(resolved);
CREATE INDEX IF NOT EXISTS idx_fraud_flags_severity ON fraud_flags(severity);

-- Admin Logs table indexes
CREATE INDEX IF NOT EXISTS idx_admin_logs_admin_id ON admin_logs(admin_id);
CREATE INDEX IF NOT EXISTS idx_admin_logs_action ON admin_logs(action);
CREATE INDEX IF NOT EXISTS idx_admin_logs_created_at ON admin_logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_admin_logs_target ON admin_logs(target_type, target_id);

-- Analyze tables for query optimization
ANALYZE users;
ANALYZE ngos;
ANALYZE donations;
ANALYZE campaigns;
ANALYZE ngo_documents;
ANALYZE ngo_approvals;
ANALYZE fraud_flags;
ANALYZE admin_logs;
