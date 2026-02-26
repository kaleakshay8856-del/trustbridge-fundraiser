<?php
header('Content-Type: application/json');

// Debug database connection
$debug = [
    'DB_HOST' => getenv('DB_HOST') ?: 'NOT SET',
    'DB_PORT' => getenv('DB_PORT') ?: 'NOT SET',
    'DB_NAME' => getenv('DB_NAME') ?: 'NOT SET',
    'DB_USER' => getenv('DB_USER') ?: 'NOT SET',
    'DB_PASS' => getenv('DB_PASS') ? 'SET (hidden)' : 'NOT SET',
];

try {
    $dsn = "pgsql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME') . ";sslmode=require";
    $conn = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connected successfully!',
        'env_vars' => $debug
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'env_vars' => $debug
    ]);
}
