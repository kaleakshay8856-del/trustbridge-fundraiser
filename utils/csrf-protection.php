<?php
// CSRF Protection

class CSRFProtection {
    
    /**
     * Generate CSRF token
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get token from request
     */
    public static function getTokenFromRequest() {
        // Check header first
        $headers = getallheaders();
        if (isset($headers['X-CSRF-Token'])) {
            return $headers['X-CSRF-Token'];
        }
        
        // Check POST data
        if (isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }
        
        // Check JSON body
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['csrf_token'])) {
            return $input['csrf_token'];
        }
        
        return null;
    }
    
    /**
     * Middleware to check CSRF token
     */
    public static function verify() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Only check for state-changing methods
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }
        
        $token = self::getTokenFromRequest();
        
        if (!$token || !self::validateToken($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
        
        return true;
    }
    
    /**
     * Generate HTML input field
     */
    public static function inputField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Generate meta tag for AJAX requests
     */
    public static function metaTag() {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}

// Usage in forms:
// echo CSRFProtection::inputField();

// Usage in API endpoints:
// CSRFProtection::verify();
