<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin']);
$db = Database::getInstance();

$ngoId = $_GET['id'] ?? null;

if (!$ngoId) {
    echo json_encode(['error' => 'NGO ID required']);
    exit;
}

try {
    $sql = "SELECT n.*, u.full_name as owner_name, u.email as owner_email
            FROM ngos n
            LEFT JOIN users u ON n.user_id = u.id
            WHERE n.id = ?";
    
    $stmt = $db->query($sql, [$ngoId]);
    $ngo = $stmt->fetch();
    
    if (!$ngo) {
        echo json_encode(['error' => 'NGO not found']);
        exit;
    }
    
    echo json_encode($ngo);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load NGO details: ' . $e->getMessage()]);
}
