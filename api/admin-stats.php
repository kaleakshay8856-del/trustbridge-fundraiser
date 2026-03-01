<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin']);
$db = Database::getInstance();
$action = $_GET['action'] ?? 'stats';

try {
    if ($action === 'audit_logs') {
        $limit = intval($_GET['limit'] ?? 50);
        
        $sql = "SELECT al.*, u.full_name as admin_name 
                FROM admin_logs al
                LEFT JOIN users u ON al.admin_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ?";
        
        $stmt = $db->query($sql, [$limit]);
        $logs = $stmt->fetchAll();
        
        // Rename 'action' to 'action_type' for frontend compatibility
        foreach ($logs as &$log) {
            $log['action_type'] = $log['action'];
            $log['entity_type'] = $log['target_type'];
            $log['entity_id'] = $log['target_id'];
        }
        
        echo json_encode(['logs' => $logs]);
    } else {
        // Total NGOs
        $total_ngos = $db->query("SELECT COUNT(*) as count FROM ngos")->fetch()['count'];
        
        // Pending approvals
        $pending_approvals = $db->query(
            "SELECT COUNT(*) as count FROM ngos WHERE status IN ('pending', 'under_review')"
        )->fetch()['count'];
        
        // Total donations amount
        $total_donations = $db->query(
            "SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE verification_status = 'approved'"
        )->fetch()['total'];
        
        // Active fraud flags (PostgreSQL uses FALSE not 0)
        $fraud_flags = $db->query(
            "SELECT COUNT(*) as count FROM fraud_flags WHERE resolved = FALSE"
        )->fetch()['count'];
        
        // Recent activity (PostgreSQL syntax)
        $recent_donations = $db->query(
            "SELECT COUNT(*) as count FROM donations WHERE created_at > NOW() - INTERVAL '7 days'"
        )->fetch()['count'];
        
        $recent_ngos = $db->query(
            "SELECT COUNT(*) as count FROM ngos WHERE created_at > NOW() - INTERVAL '7 days'"
        )->fetch()['count'];
        
        echo json_encode([
            'total_ngos' => intval($total_ngos),
            'pending_approvals' => intval($pending_approvals),
            'total_donations' => floatval($total_donations),
            'fraud_flags' => intval($fraud_flags),
            'recent_donations' => intval($recent_donations),
            'recent_ngos' => intval($recent_ngos)
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load data: ' . $e->getMessage()]);
}

