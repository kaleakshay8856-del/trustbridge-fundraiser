<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin']);
$db = Database::getInstance();

try {
    $result = [];
    
    // NGO approvals status (lightweight query)
    try {
        $approvalStatus = $db->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM ngos
            GROUP BY status
        ")->fetchAll();
        $result['approval_status'] = $approvalStatus;
    } catch (Exception $e) {
        $result['approval_status'] = [];
        error_log('Approval status query error: ' . $e->getMessage());
    }
    
    // Donations per month (last 6 months only - faster)
    try {
        $donationsPerMonth = $db->query("
            SELECT 
                TO_CHAR(created_at, 'YYYY-MM') as month,
                COUNT(*) as count,
                SUM(amount) as total
            FROM donations
            WHERE created_at >= NOW() - INTERVAL '6 months'
            AND verification_status = 'approved'
            GROUP BY TO_CHAR(created_at, 'YYYY-MM')
            ORDER BY month ASC
        ")->fetchAll();
        $result['donations_per_month'] = $donationsPerMonth;
    } catch (Exception $e) {
        $result['donations_per_month'] = [];
        error_log('Donations query error: ' . $e->getMessage());
    }
    
    // Top 5 NGOs only (faster)
    try {
        $topNGOs = $db->query("
            SELECT 
                n.ngo_name,
                COUNT(d.id) as donation_count,
                COALESCE(SUM(d.amount), 0) as total_raised
            FROM ngos n
            LEFT JOIN donations d ON n.id = d.ngo_id AND d.verification_status = 'approved'
            WHERE n.status = 'approved'
            GROUP BY n.id, n.ngo_name
            ORDER BY total_raised DESC
            LIMIT 5
        ")->fetchAll();
        $result['top_ngos'] = $topNGOs;
    } catch (Exception $e) {
        $result['top_ngos'] = [];
        error_log('Top NGOs query error: ' . $e->getMessage());
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load analytics: ' . $e->getMessage()]);
}

