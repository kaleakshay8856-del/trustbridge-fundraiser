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

-- Donations table indexes
CREATE INDEX IF NOT EXISTS idx_donations_donor_id ON donations(donor_id);
CREATE INDEX IF NOT EXISTS idx_donations_ngo_id ON donations(ngo_id);
CREATE INDEX IF NOT EXISTS idx_donations_verification_status ON donations(verification_status);
CREATE INDEX IF NOT EXISTS idx_donations_created_at ON donations(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_donations_status_created ON donations(verification_status, created_at DESC);

-- Campaigns table indexes
CREATE INDEX IF NOT EXISTS idx_campaigns_ngo_id ON campaigns(ngo_id);
CREATE INDEX IF NOT EXISTS idx_campaigns_status ON campaigns(status);

-- NGO Documents table indexes
CREATE INDEX IF NOT EXISTS idx_ngo_documents_ngo_id ON ngo_documents(ngo_id);
CREATE INDEX IF NOT EXISTS idx_ngo_documents_status ON ngo_documents(verification_status);

-- Fraud Flags table indexes
CREATE INDEX IF NOT EXISTS idx_fraud_flags_donation_id ON fraud_flags(donation_id);
CREATE INDEX IF NOT EXISTS idx_fraud_flags_ngo_id ON fraud_flags(ngo_id);
CREATE INDEX IF NOT EXISTS idx_fraud_flags_status ON fraud_flags(status);

-- Audit Logs table indexes
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs(created_at DESC);

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_donations_ngo_status ON donations(ngo_id, verification_status);
CREATE INDEX IF NOT EXISTS idx_ngos_status_score ON ngos(status, trust_score DESC);

-- Analyze tables for query optimization
ANALYZE users;
ANALYZE ngos;
ANALYZE donations;
ANALYZE campaigns;
ANALYZE ngo_documents;
ANALYZE fraud_flags;
ANALYZE audit_logs;
