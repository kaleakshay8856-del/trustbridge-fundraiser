<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin']);
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? 'active';
    
    try {
        $sql = "SELECT ff.*, 
                       CASE 
                           WHEN ff.entity_type = 'ngo' THEN n.ngo_name
                           WHEN ff.entity_type = 'user' THEN u.full_name
                           ELSE NULL
                       END as entity_name
                FROM fraud_flags ff
                LEFT JOIN ngos n ON ff.entity_type = 'ngo' AND ff.entity_id = n.id
                LEFT JOIN users u ON ff.entity_type = 'user' AND ff.entity_id = u.id
                WHERE ff.resolved = ?
                ORDER BY ff.created_at DESC";
        
        $resolved = ($status === 'active') ? 0 : 1;
        $stmt = $db->query($sql, [$resolved]);
        $flags = $stmt->fetchAll();
        
        echo json_encode(['flags' => $flags]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load fraud flags: ' . $e->getMessage()]);
    }
    
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'resolve') {
        $flagId = $input['flag_id'];
        $resolutionNotes = $input['resolution_notes'] ?? '';
        
        try {
            $sql = "UPDATE fraud_flags 
                    SET resolved = 1, 
                        resolved_by = ?, 
                        resolution_notes = ?,
                        resolved_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $db->query($sql, [$user['user_id'], $resolutionNotes, $flagId]);
            
            // Log the action
            $db->query(
                "INSERT INTO admin_logs (admin_id, action_type, entity_type, entity_id, details, ip_address) 
                 VALUES (?, 'resolve_fraud_flag', 'fraud_flag', ?, ?, ?)",
                [$user['user_id'], $flagId, $resolutionNotes, $_SERVER['REMOTE_ADDR']]
            );
            
            echo json_encode(['success' => true, 'message' => 'Fraud flag resolved']);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to resolve flag: ' . $e->getMessage()]);
        }
    }
}
