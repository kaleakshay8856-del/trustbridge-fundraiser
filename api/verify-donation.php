<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin', 'finance_admin']);
$db = Database::getInstance();
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donation_id = $input['donation_id'];
    $action = $input['action']; // 'approve' or 'reject'
    $rejection_reason = $input['rejection_reason'] ?? '';
    
    try {
        $db->getConnection()->beginTransaction();
        
        if ($action === 'approve') {
            $db->query(
                "UPDATE donations SET verification_status = 'approved', verified_by = ?, verified_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$user['user_id'], $donation_id]
            );
            
            // Update campaign raised amount
            $donation = $db->query("SELECT campaign_id, amount FROM donations WHERE id = ?", [$donation_id])->fetch();
            if ($donation['campaign_id']) {
                $db->query(
                    "UPDATE campaigns SET raised_amount = raised_amount + ? WHERE id = ?",
                    [$donation['amount'], $donation['campaign_id']]
                );
            }
        } else {
            $db->query(
                "UPDATE donations SET verification_status = 'rejected', verified_by = ?, verified_at = CURRENT_TIMESTAMP, rejection_reason = ? WHERE id = ?",
                [$user['user_id'], $rejection_reason, $donation_id]
            );
        }
        
        // Log action
        $db->query(
            "INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $user['user_id'], 
                'donation_' . $action, 
                'donation', 
                $donation_id, 
                json_encode(['reason' => $rejection_reason]),
                $_SERVER['REMOTE_ADDR']
            ]
        );
        
        $db->getConnection()->commit();
        echo json_encode(['success' => true, 'message' => 'Donation ' . $action . 'd']);
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        echo json_encode(['error' => 'Verification failed']);
    }
}
