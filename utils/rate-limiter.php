<?php
// Rate Limiter - Prevent abuse

class RateLimiter {
    private $db;
    private $limits = [
        'login' => ['requests' => 5, 'window' => 300],      // 5 attempts per 5 minutes
        'register' => ['requests' => 3, 'window' => 3600],  // 3 per hour
        'donation' => ['requests' => 10, 'window' => 3600], // 10 per hour
        'api' => ['requests' => 100, 'window' => 60]        // 100 per minute
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check if request is allowed
     * @param string $action - Action type (login, register, etc.)
     * @param string $identifier - IP address or user ID
     * @return bool
     */
    public function isAllowed($action, $identifier) {
        if (!isset($this->limits[$action])) {
            return true;
        }
        
        $limit = $this->limits[$action];
        $key = $this->generateKey($action, $identifier);
        
        // Get request count in time window
        $count = $this->getRequestCount($key, $limit['window']);
        
        if ($count >= $limit['requests']) {
            $this->logRateLimitExceeded($action, $identifier);
            return false;
        }
        
        // Record this request
        $this->recordRequest($key);
        return true;
    }
    
    /**
     * Generate cache key
     */
    private function generateKey($action, $identifier) {
        return "rate_limit:{$action}:{$identifier}";
    }
    
    /**
     * Get request count in time window
     */
    private function getRequestCount($key, $window) {
        $cutoff = time() - $window;
        
        // Using simple file-based cache (replace with Redis in production)
        $cacheFile = sys_get_temp_dir() . '/' . md5($key) . '.cache';
        
        if (!file_exists($cacheFile)) {
            return 0;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        // Filter requests within time window
        $data = array_filter($data, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
        
        // Save filtered data
        file_put_contents($cacheFile, json_encode($data));
        
        return count($data);
    }
    
    /**
     * Record request
     */
    private function recordRequest($key) {
        $cacheFile = sys_get_temp_dir() . '/' . md5($key) . '.cache';
        
        $data = [];
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
        }
        
        $data[] = time();
        file_put_contents($cacheFile, json_encode($data));
    }
    
    /**
     * Log rate limit exceeded
     */
    private function logRateLimitExceeded($action, $identifier) {
        error_log("Rate limit exceeded: {$action} by {$identifier}");
        
        // Create fraud flag for excessive requests
        try {
            $this->db->query(
                "INSERT INTO fraud_flags (entity_type, entity_id, flag_type, severity, description) 
                 VALUES (?, ?, ?, ?, ?)",
                ['user', $identifier, 'rate_limit_exceeded', 'medium', "Excessive {$action} requests"]
            );
        } catch (Exception $e) {
            // Ignore if fraud_flags insert fails
        }
    }
    
    /**
     * Get remaining requests
     */
    public function getRemainingRequests($action, $identifier) {
        if (!isset($this->limits[$action])) {
            return null;
        }
        
        $limit = $this->limits[$action];
        $key = $this->generateKey($action, $identifier);
        $count = $this->getRequestCount($key, $limit['window']);
        
        return max(0, $limit['requests'] - $count);
    }
}

// Middleware function
function checkRateLimit($action) {
    $limiter = new RateLimiter();
    $identifier = $_SERVER['REMOTE_ADDR'];
    
    if (!$limiter->isAllowed($action, $identifier)) {
        http_response_code(429);
        echo json_encode([
            'error' => 'Too many requests. Please try again later.',
            'retry_after' => 60
        ]);
        exit;
    }
}
