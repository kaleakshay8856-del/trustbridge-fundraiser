<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['admin']);
$db = Database::getInstance();

try {
    $result = [];
    
    // Donations per month (last 12 months)
    try {
        $donationsPerMonth = $db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(amount) as total
            FROM donations
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            AND verification_status = 'approved'
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ")->fetchAll();
        $result['donations_per_month'] = $donationsPerMonth;
    } catch (Exception $e) {
        $result['donations_per_month'] = [];
        error_log('Donations query error: ' . $e->getMessage());
    }
    
    // NGO approvals status
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
    
    // Fraud flags per month (last 12 months)
    try {
        $fraudPerMonth = $db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count,
                severity
            FROM fraud_flags
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m'), severity
            ORDER BY month ASC
        ")->fetchAll();
        $result['fraud_per_month'] = $fraudPerMonth;
    } catch (Exception $e) {
        $result['fraud_per_month'] = [];
        error_log('Fraud flags query error: ' . $e->getMessage());
    }
    
    // Revenue by year
    try {
        $revenueByYear = $db->query("
            SELECT 
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                SUM(amount) as total
            FROM donations
            WHERE verification_status = 'approved'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY year ASC, month ASC
        ")->fetchAll();
        $result['revenue_by_year'] = $revenueByYear;
    } catch (Exception $e) {
        $result['revenue_by_year'] = [];
        error_log('Revenue query error: ' . $e->getMessage());
    }
    
    // Top NGOs by donations
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
            LIMIT 10
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

