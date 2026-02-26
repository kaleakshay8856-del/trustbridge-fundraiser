<?php
header('Content-Type: application/json');
require_once '../config/database-supabase.php';
require_once '../config/jwt.php';
require_once '../utils/auth-middleware.php';

$user = authenticate(['ngo']);
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Get NGO ID
$ngo = $db->query("SELECT id FROM ngos WHERE user_id = ?", [$user['user_id']])->fetch();
if (!$ngo) {
    echo json_encode(['error' => 'NGO profile not found. Please complete your profile first.']);
    exit;
}

if ($method === 'GET') {
    try {
        $sql = "SELECT id, document_type, file_path, file_name, verified, uploaded_at as created_at 
                FROM ngo_documents WHERE ngo_id = ? ORDER BY uploaded_at DESC";
        $stmt = $db->query($sql, [$ngo['id']]);
        $documents = $stmt->fetchAll();
        
        // Add document_url for frontend compatibility
        foreach ($documents as &$doc) {
            $doc['document_url'] = $doc['file_path'];
        }
        
        echo json_encode(['documents' => $documents]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load documents: ' . $e->getMessage()]);
    }
    
} elseif ($method === 'POST') {
    $uploadMethod = $_POST['upload_method'] ?? 'url';
    $documentType = $_POST['document_type'] ?? '';
    $filePath = '';
    $fileName = '';
    
    if (empty($documentType)) {
        echo json_encode(['error' => 'Document type is required']);
        exit;
    }
    
    try {
        if ($uploadMethod === 'file' && isset($_FILES['document_file'])) {
            $file = $_FILES['document_file'];
            
            // Validate file
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['error' => 'File upload failed']);
                exit;
            }
            
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['error' => 'File size must be less than 5MB']);
                exit;
            }
            
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode(['error' => 'Invalid file type. Only PDF, JPG, PNG allowed']);
                exit;
            }
            
            // Create uploads directory if it doesn't exist
            $uploadDir = '../uploads/documents/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uniqueName = $ngo['id'] . '_' . $documentType . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $uniqueName;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $filePath = 'uploads/documents/' . $uniqueName;
                $fileName = $file['name'];
            } else {
                echo json_encode(['error' => 'Failed to save file']);
                exit;
            }
        } else {
            // URL method
            $url = $_POST['document_url'] ?? '';
            if (empty($url)) {
                echo json_encode(['error' => 'Document URL is required']);
                exit;
            }
            $filePath = $url;
            $fileName = basename(parse_url($url, PHP_URL_PATH));
        }
        
        $sql = "INSERT INTO ngo_documents (ngo_id, document_type, file_path, file_name)
                VALUES (?, ?, ?, ?)";
        
        $db->query($sql, [
            $ngo['id'],
            $documentType,
            $filePath,
            $fileName
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Document uploaded successfully']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to upload document: ' . $e->getMessage()]);
    }
}


