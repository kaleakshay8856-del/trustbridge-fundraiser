<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $ngo_id = $input['ngo_id'];
    $campaign_id = $input['campaign_id'] ?? null;
    $amount = floatval($input['amount']);
    $transaction_id = htmlspecialchars($input['transaction_id']);
    $is_anonymous = $input['is_anonymous'] ?? true;
    
    // Try to get authenticated user, but allow anonymous donations
    $donor_id = null;
    try {
        $token = getBearerToken();
        if ($token) {
            $decoded = JWT::decode($token);
            $donor_id = $decoded['user_id'];
        }
    } catch (Exception $e) {
        // Anonymous donation - no user logged in
        $donor_id = null;
    }
    
    // Get NGO UPI ID
    $ngo = $db->query("SELECT upi_id, status FROM ngos WHERE id = ?", [$ngo_id])->fetch();
    
    if (!$ngo || $ngo['status'] !== 'approved') {
        echo json_encode(['error' => 'NGO not available']);
        exit;
    }
    
    try {
        $sql = "INSERT INTO donations (donor_id, ngo_id, campaign_id, amount, transaction_id, upi_id, is_anonymous) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db->query($sql, [
            $donor_id, 
            $ngo_id, 
            $campaign_id, 
            $amount, 
            $transaction_id, 
            $ngo['upi_id'],
            $is_anonymous
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Donation submitted for verification']);
    } catch (Exception $e) {
        error_log("Donation error: " . $e->getMessage());
        echo json_encode(['error' => 'Donation submission failed: ' . $e->getMessage()]);
    }
    
} elseif ($method === 'GET') {
    $user = authenticate(['donor', 'ngo', 'admin', 'finance_admin']);
    
    if ($user['role'] === 'donor') {
        $donations = $db->query(
            "SELECT d.*, n.ngo_name, c.title as campaign_title 
             FROM donations d 
             LEFT JOIN ngos n ON d.ngo_id = n.id 
             LEFT JOIN campaigns c ON d.campaign_id = c.id 
             WHERE d.donor_id = ? 
             ORDER BY d.created_at DESC",
            [$user['user_id']]
        )->fetchAll();
        
        echo json_encode(['donations' => $donations]);
    }
}
