<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database-supabase.php';
require_once '../config/jwt.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $action = $input['action'] ?? '';
    
    if ($action === 'register') {
        register($input);
    } elseif ($action === 'login') {
        login($input);
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
}

function register($data) {
    $db = Database::getInstance();
    
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    $full_name = htmlspecialchars($data['full_name']);
    $role = $data['role'] ?? 'donor';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email']);
        return;
    }
    
    if (strlen($password) < 8) {
        echo json_encode(['error' => 'Password must be at least 8 characters']);
        return;
    }
    
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    try {
        $sql = "INSERT INTO users (email, password_hash, full_name, role, ip_address) 
                VALUES (?, ?, ?, ?, ?)";
        $db->query($sql, [$email, $password_hash, $full_name, $role, $_SERVER['REMOTE_ADDR']]);
        
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Email already exists']);
    }
}

function login($data) {
    $db = Database::getInstance();
    
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    
    try {
        $sql = "SELECT id, email, password_hash, full_name, role, status FROM users WHERE email = ?";
        $stmt = $db->query($sql, [$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }
        
        if ($user['status'] !== 'active') {
            echo json_encode(['error' => 'Account suspended']);
            return;
        }
        
        // Update last login
        $db->query("UPDATE users SET last_login = CURRENT_TIMESTAMP, ip_address = ? WHERE id = ?", 
                   [$_SERVER['REMOTE_ADDR'], $user['id']]);
        
        $token = JWT::encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['full_name'],
                'role' => $user['role']
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Login failed']);
    }
}
