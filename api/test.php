<?php
header('Content-Type: application/json');
require_once '../utils/cors.php';

echo json_encode([
    'status' => 'success',
    'message' => 'TrustBridge API is running!',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'environment' => getenv('RAILWAY_ENVIRONMENT') ? 'production' : 'development'
]);
