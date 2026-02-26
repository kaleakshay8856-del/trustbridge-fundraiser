<?php
/**
 * TrustBridge - Connection Test Script
 * Run this file to test if everything is working
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>TrustBridge - Connection Test</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .test { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .success { border-left: 4px solid #10B981; }
        .error { border-left: 4px solid #EF4444; }
        .info { border-left: 4px solid #3B82F6; }
        h1 { color: #1E3A8A; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🌉 TrustBridge - System Test</h1>";

// Test 1: PHP Version
echo "<div class='test " . (version_compare(PHP_VERSION, '8.0.0', '>=') ? 'success' : 'error') . "'>";
echo "<h3>✓ PHP Version</h3>";
echo "<p>Current: <code>" . PHP_VERSION . "</code></p>";
echo version_compare(PHP_VERSION, '8.0.0', '>=') 
    ? "<p>✅ PHP version is compatible</p>" 
    : "<p>❌ PHP 8.0+ required</p>";
echo "</div>";

// Test 2: Required Extensions
echo "<div class='test info'>";
echo "<h3>📦 PHP Extensions</h3>";
$required = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p>" . ($loaded ? "✅" : "❌") . " {$ext}: " . ($loaded ? "Loaded" : "Missing") . "</p>";
}
echo "</div>";

// Test 3: Database Connection
echo "<div class='test'>";
echo "<h3>🗄️ Database Connection</h3>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    echo "<p>✅ Database connection successful!</p>";
    echo "<p>Host: <code>" . DB_HOST . "</code></p>";
    echo "<p>Database: <code>" . DB_NAME . "</code></p>";
    
    // Test query
    try {
        $result = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
        echo "<p>✅ Found {$result['count']} users in database</p>";
    } catch (Exception $e) {
        echo "<p>⚠️ Tables not found. Please import schema:</p>";
        echo "<code>database/schema-mysql.sql</code>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed</p>";
    echo "<p>Error: <code>" . $e->getMessage() . "</code></p>";
    echo "<p>💡 Fix: Update credentials in <code>config/database.php</code></p>";
}
echo "</div>";

// Test 4: File Permissions
echo "<div class='test info'>";
echo "<h3>📁 File System</h3>";
$writable = is_writable(__DIR__);
echo "<p>" . ($writable ? "✅" : "❌") . " Directory writable: " . ($writable ? "Yes" : "No") . "</p>";
echo "<p>Current directory: <code>" . __DIR__ . "</code></p>";
echo "</div>";

// Test 5: JWT Configuration
echo "<div class='test info'>";
echo "<h3>🔐 JWT Configuration</h3>";
try {
    require_once 'config/jwt.php';
    echo "<p>✅ JWT configuration loaded</p>";
    echo "<p>Secret length: <code>" . strlen(JWT_SECRET) . " characters</code></p>";
    if (JWT_SECRET === 'your-super-secret-key-change-this-in-production') {
        echo "<p>⚠️ Warning: Using default JWT secret. Change it in production!</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ JWT configuration error</p>";
}
echo "</div>";

// Test 6: API Endpoints
echo "<div class='test info'>";
echo "<h3>🔌 API Endpoints</h3>";
$endpoints = [
    'api/auth.php' => 'Authentication',
    'api/ngos.php' => 'NGO Listing',
    'api/donations.php' => 'Donations'
];
foreach ($endpoints as $file => $name) {
    $exists = file_exists($file);
    echo "<p>" . ($exists ? "✅" : "❌") . " {$name}: <code>{$file}</code></p>";
}
echo "</div>";

// Summary
echo "<div class='test success'>";
echo "<h3>🎉 Next Steps</h3>";
echo "<ol>";
echo "<li>If all tests pass, open <a href='index.html'>Homepage</a></li>";
echo "<li>Login with: <code>admin@trustbridge.local</code> / <code>admin123</code></li>";
echo "<li>Go to <a href='admin/dashboard.html'>Admin Dashboard</a></li>";
echo "<li>Read <code>QUICK_START.md</code> for more info</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
