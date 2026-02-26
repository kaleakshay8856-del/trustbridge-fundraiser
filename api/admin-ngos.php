<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin']);
$db = Database::getInstance();

$status = $_GET['status'] ?? 'pending';
$limit = intval($_GET['limit'] ?? 50);

try {
    // Handle multiple statuses separated by comma
    $statuses = explode(',', $status);
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    
    $sql = "SELECT n.*, u.full_name as owner_name, u.email as owner_email
            FROM ngos n
            LEFT JOIN users u ON n.user_id = u.id
            WHERE n.status IN ($placeholders)
            ORDER BY n.created_at DESC
            LIMIT ?";
    
    $params = array_merge($statuses, [$limit]);
    $stmt = $db->query($sql, $params);
    $ngos = $stmt->fetchAll();
    
    echo json_encode(['ngos' => $ngos]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load NGOs: ' . $e->getMessage()]);
}
