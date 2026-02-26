<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin']);
$db = Database::getInstance();
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ngo_id = $input['ngo_id'];
    $action = $input['action']; // 'approve' or 'reject'
    $comments = $input['comments'] ?? '';
    
    // Check if admin already approved this NGO
    $check = $db->query("SELECT id FROM ngo_approvals WHERE ngo_id = ? AND admin_id = ?", 
                        [$ngo_id, $user['user_id']])->fetch();
    
    if ($check) {
        echo json_encode(['error' => 'You already voted on this NGO']);
        exit;
    }
    
    // Check admin approval rate (fraud prevention)
    $today_approvals = $db->query(
        "SELECT COUNT(*) as count FROM ngo_approvals 
         WHERE admin_id = ? AND DATE(created_at) = CURRENT_DATE",
        [$user['user_id']]
    )->fetch();
    
    if ($today_approvals['count'] >= 5) {
        echo json_encode(['error' => 'Daily approval limit reached. Contact supervisor.']);
        exit;
    }
    
    try {
        $db->getConnection()->beginTransaction();
        
        // Record approval
        $db->query(
            "INSERT INTO ngo_approvals (ngo_id, admin_id, action, comments, ip_address) 
             VALUES (?, ?, ?, ?, ?)",
            [$ngo_id, $user['user_id'], $action, $comments, $_SERVER['REMOTE_ADDR']]
        );
        
        // Log action (immutable)
        $db->query(
            "INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $user['user_id'], 
                'ngo_' . $action, 
                'ngo', 
                $ngo_id, 
                json_encode(['comments' => $comments]),
                $_SERVER['REMOTE_ADDR']
            ]
        );
        
        // Count approvals
        $approval_count = $db->query(
            "SELECT COUNT(*) as count FROM ngo_approvals WHERE ngo_id = ? AND action = 'approve'",
            [$ngo_id]
        )->fetch();
        
        // Update NGO status if minimum 1 approval (changed from 2 for single admin testing)
        if ($approval_count['count'] >= 1) {
            // Calculate trust score
            $trust_score = calculateTrustScore($db, $ngo_id);
            
            // Lower threshold to 10 for testing (was 60)
            if ($trust_score >= 10) {
                $db->query(
                    "UPDATE ngos SET status = 'approved', trust_score = ?, approval_count = ? WHERE id = ?",
                    [$trust_score, $approval_count['count'], $ngo_id]
                );
            } else {
                $db->query(
                    "UPDATE ngos SET status = 'rejected', trust_score = ? WHERE id = ?",
                    [$trust_score, $ngo_id]
                );
            }
        } else {
            $db->query("UPDATE ngos SET status = 'under_review' WHERE id = ?", [$ngo_id]);
        }
        
        $db->getConnection()->commit();
        echo json_encode(['success' => true, 'message' => 'Vote recorded']);
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        echo json_encode(['error' => 'Approval failed']);
    }
}

function calculateTrustScore($db, $ngo_id) {
    $ngo = $db->query("SELECT * FROM ngos WHERE id = ?", [$ngo_id])->fetch();
    $score = 0;
    
    // Govt registration: +40 (don't require verified flag for now)
    $docs = $db->query(
        "SELECT document_type FROM ngo_documents WHERE ngo_id = ?",
        [$ngo_id]
    )->fetchAll();
    
    $doc_types = array_column($docs, 'document_type');
    if (in_array('registration_certificate', $doc_types)) $score += 40;
    if (in_array('80g_certificate', $doc_types)) $score += 20;
    if (in_array('address_proof', $doc_types)) $score += 10;
    if (in_array('pan_card', $doc_types)) $score += 15;
    
    // 3+ years old: +15
    if ($ngo['founded_year'] && (date('Y') - $ngo['founded_year']) >= 3) {
        $score += 15;
    }
    
    // Complaints: -30 each
    $score -= ($ngo['complaint_count'] * 30);
    
    return max(0, min(100, $score));
}
