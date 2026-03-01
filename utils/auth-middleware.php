<?php
require_once __DIR__ . '/../config/jwt.php';

function getBearerToken() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    return $token ?: null;
}

function authenticate($allowed_roles = []) {
    $token = getBearerToken();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit;
    }
    
    $payload = JWT::decode($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }
    
    if (!empty($allowed_roles) && !in_array($payload['role'], $allowed_roles)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    return $payload;
}
