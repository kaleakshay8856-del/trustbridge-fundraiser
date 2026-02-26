<?php
header('Content-Type: application/json');
require_once 'config/database.php';

$db = Database::getInstance();

try {
    // Test simple query
    $result = $db->query("SELECT COUNT(*) as count FROM donations")->fetch();
    echo json_encode(['test' => 'success', 'donation_count' => $result['count']]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
