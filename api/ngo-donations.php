<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['ngo']);
$db = Database::getInstance();

// Get NGO ID
$ngo = $db->query("SELECT id FROM ngos WHERE user_id = ?", [$user['user_id']])->fetch();
if (!$ngo) {
    echo json_encode(['error' => 'NGO profile not found']);
    exit;
}

try {
    $sql = "SELECT d.*, u.full_name as donor_name
            FROM donations d
            LEFT JOIN users u ON d.donor_id = u.id
            WHERE d.ngo_id = ?
            ORDER BY d.created_at DESC";
    
    $stmt = $db->query($sql, [$ngo['id']]);
    $donations = $stmt->fetchAll();
    
    echo json_encode(['donations' => $donations]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load donations']);
}
