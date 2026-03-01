<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin']);
$db = Database::getInstance();

$status = $_GET['status'] ?? 'pending_verification';
$limit = intval($_GET['limit'] ?? 50);

try {
    $sql = "SELECT d.*, 
                   u.full_name as donor_name,
                   u.email as donor_email,
                   n.ngo_name
            FROM donations d
            LEFT JOIN users u ON d.donor_id = u.id
            LEFT JOIN ngos n ON d.ngo_id = n.id
            WHERE d.verification_status = ?
            ORDER BY d.created_at DESC
            LIMIT ?";
    
    $stmt = $db->query($sql, [$status, $limit]);
    $donations = $stmt->fetchAll();
    
    echo json_encode([
        'donations' => $donations,
        'count' => count($donations),
        'status_filter' => $status
    ]);
    
} catch (Exception $e) {
    error_log("Admin donations error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to load donations: ' . $e->getMessage()]);
}
