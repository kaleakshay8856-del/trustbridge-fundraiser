<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['ngo']);
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = "SELECT * FROM ngos WHERE user_id = ?";
        $stmt = $db->query($sql, [$user['user_id']]);
        $ngo = $stmt->fetch();
        
        echo json_encode(['ngo' => $ngo]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load profile']);
    }
    
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle submit for verification
    if (isset($input['action']) && $input['action'] === 'submit_verification') {
        try {
            $existing = $db->query("SELECT id, status FROM ngos WHERE user_id = ?", [$user['user_id']])->fetch();
            
            if (!$existing) {
                echo json_encode(['error' => 'Please complete your profile first']);
                exit;
            }
            
            if ($existing['status'] !== 'incomplete' && $existing['status'] !== 'rejected') {
                echo json_encode(['error' => 'NGO already submitted or approved']);
                exit;
            }
            
            // Update status to pending
            $db->query("UPDATE ngos SET status = 'pending' WHERE user_id = ?", [$user['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Submitted for verification']);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to submit: ' . $e->getMessage()]);
        }
        exit;
    }
    
    try {
        // Check if NGO profile exists
        $existing = $db->query("SELECT id FROM ngos WHERE user_id = ?", [$user['user_id']])->fetch();
        
        if ($existing) {
            // Update existing profile
            $sql = "UPDATE ngos SET 
                    ngo_name = ?, registration_number = ?, pan_number = ?, upi_id = ?,
                    description = ?, address = ?, city = ?, state = ?, pincode = ?,
                    website = ?, founded_year = ?, has_80g = ?
                    WHERE user_id = ?";
            
            $db->query($sql, [
                $input['ngo_name'], $input['registration_number'], $input['pan_number'], $input['upi_id'],
                $input['description'], $input['address'], $input['city'], $input['state'], $input['pincode'],
                $input['website'], $input['founded_year'], $input['has_80g'],
                $user['user_id']
            ]);
        } else {
            // Create new profile with incomplete status
            $sql = "INSERT INTO ngos (user_id, ngo_name, registration_number, pan_number, upi_id,
                    description, address, city, state, pincode, website, founded_year, has_80g, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'incomplete')";
            
            $db->query($sql, [
                $user['user_id'], $input['ngo_name'], $input['registration_number'], $input['pan_number'], $input['upi_id'],
                $input['description'], $input['address'] ?? '', $input['city'] ?? '', $input['state'] ?? '', $input['pincode'] ?? '',
                $input['website'] ?? '', $input['founded_year'] ?? null, $input['has_80g']
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Profile saved successfully']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to save profile: ' . $e->getMessage()]);
    }
}
