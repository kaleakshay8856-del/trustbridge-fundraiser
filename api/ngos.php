<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? 'approved';
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);
    
    try {
        $sql = "SELECT n.*, 
                       COALESCE(SUM(d.amount), 0) as total_raised,
                       COUNT(DISTINCT d.id) as donation_count
                FROM ngos n
                LEFT JOIN donations d ON n.id = d.ngo_id AND d.verification_status = 'approved'
                WHERE n.status = ?
                GROUP BY n.id
                ORDER BY n.trust_score DESC, n.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $db->query($sql, [$status, $limit, $offset]);
        $ngos = $stmt->fetchAll();
        
        echo json_encode(['ngos' => $ngos]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load NGOs']);
    }
}
