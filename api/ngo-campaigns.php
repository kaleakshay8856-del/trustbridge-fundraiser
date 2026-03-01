<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['ngo']);
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Get NGO ID
$ngo = $db->query("SELECT id, status FROM ngos WHERE user_id = ?", [$user['user_id']])->fetch();
if (!$ngo) {
    echo json_encode(['error' => 'NGO profile not found']);
    exit;
}

if ($method === 'GET') {
    try {
        $sql = "SELECT 
                    c.*,
                    COALESCE(SUM(CASE WHEN d.verification_status = 'approved' THEN d.amount ELSE 0 END), 0) as raised_amount
                FROM campaigns c
                LEFT JOIN donations d ON c.id = d.campaign_id
                WHERE c.ngo_id = ?
                GROUP BY c.id
                ORDER BY c.created_at DESC";
        $stmt = $db->query($sql, [$ngo['id']]);
        $campaigns = $stmt->fetchAll();
        
        echo json_encode(['campaigns' => $campaigns]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load campaigns: ' . $e->getMessage()]);
    }
    
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($ngo['status'] !== 'approved') {
        echo json_encode(['error' => 'Your NGO must be approved before creating campaigns']);
        exit;
    }
    
    try {
        $sql = "INSERT INTO campaigns (ngo_id, title, description, goal_amount, end_date, status)
                VALUES (?, ?, ?, ?, ?, 'active')";
        
        $db->query($sql, [
            $ngo['id'],
            $input['title'],
            $input['description'],
            $input['goal_amount'],
            $input['end_date'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Campaign created successfully']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to create campaign: ' . $e->getMessage()]);
    }
}
