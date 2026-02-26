<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin']);
$db = Database::getInstance();

$ngoId = $_GET['ngo_id'] ?? null;

if (!$ngoId) {
    echo json_encode(['error' => 'NGO ID required']);
    exit;
}

try {
    $sql = "SELECT id, document_type, file_path, file_name, verified, uploaded_at 
            FROM ngo_documents 
            WHERE ngo_id = ? 
            ORDER BY uploaded_at DESC";
    
    $stmt = $db->query($sql, [$ngoId]);
    $documents = $stmt->fetchAll();
    
    echo json_encode(['documents' => $documents]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load documents: ' . $e->getMessage()]);
}
