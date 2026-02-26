<?php
require_once '../config/database-supabase.php';

class FraudDetection {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function checkDuplicatePAN($pan_number, $exclude_ngo_id = null) {
        $sql = "SELECT id, ngo_name FROM ngos WHERE pan_number = ?";
        $params = [$pan_number];
        
        if ($exclude_ngo_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_ngo_id;
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        
        if ($result) {
            $this->createFraudFlag('ngo', $result['id'], 'duplicate_pan', 'high', 
                                   'Duplicate PAN number detected');
            return true;
        }
        return false;
    }
    
    public function checkDuplicateUPI($upi_id, $exclude_ngo_id = null) {
        $sql = "SELECT id, ngo_name FROM ngos WHERE upi_id = ?";
        $params = [$upi_id];
        
        if ($exclude_ngo_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_ngo_id;
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        
        if ($result) {
            $this->createFraudFlag('ngo', $result['id'], 'duplicate_upi', 'critical', 
                                   'Duplicate UPI ID detected');
            return true;
        }
        return false;
    }
    
    public function checkSuspiciousIP($user_id) {
        $sql = "SELECT COUNT(DISTINCT id) as count FROM users WHERE ip_address = (SELECT ip_address FROM users WHERE id = ?)";
        $result = $this->db->query($sql, [$user_id])->fetch();
        
        if ($result['count'] > 5) {
            $this->createFraudFlag('user', $user_id, 'suspicious_ip', 'medium', 
                                   'Multiple accounts from same IP');
            return true;
        }
        return false;
    }
    
    public function checkComplaintThreshold($ngo_id) {
        $ngo = $this->db->query("SELECT complaint_count FROM ngos WHERE id = ?", [$ngo_id])->fetch();
        
        if ($ngo['complaint_count'] >= 10) {
            $this->db->query("UPDATE ngos SET status = 'suspended' WHERE id = ?", [$ngo_id]);
            $this->createFraudFlag('ngo', $ngo_id, 'excessive_complaints', 'critical', 
                                   'Auto-suspended due to 10+ complaints');
            return true;
        }
        return false;
    }
    
    private function createFraudFlag($entity_type, $entity_id, $flag_type, $severity, $description) {
        $this->db->query(
            "INSERT INTO fraud_flags (entity_type, entity_id, flag_type, severity, description) 
             VALUES (?, ?, ?, ?, ?)",
            [$entity_type, $entity_id, $flag_type, $severity, $description]
        );
    }
    
    public function getFraudFlags($entity_type = null, $resolved = false) {
        $sql = "SELECT * FROM fraud_flags WHERE resolved = ?";
        $params = [$resolved];
        
        if ($entity_type) {
            $sql .= " AND entity_type = ?";
            $params[] = $entity_type;
        }
        
        $sql .= " ORDER BY created_at DESC";
        return $this->db->query($sql, $params)->fetchAll();
    }
}
