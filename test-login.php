<?php
header('Content-Type: text/plain');
require_once 'config/database-supabase.php';

echo "=== Testing Admin Login ===\n\n";

$email = 'admin@trustbridge.com';
$password = 'admin123';

echo "Testing email: $email\n";
echo "Testing password: $password\n\n";

$db = Database::getInstance();

// Check if user exists
$sql = "SELECT id, email, password_hash, full_name, role, status FROM users WHERE email = ?";
$stmt = $db->query($sql, [$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "❌ ERROR: User not found in database!\n";
    echo "Please run this SQL in Supabase:\n\n";
    
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "INSERT INTO users (id, email, password_hash, full_name, role, status, created_at) \n";
    echo "VALUES (\n";
    echo "    uuid_generate_v4(),\n";
    echo "    '$email',\n";
    echo "    '$hash',\n";
    echo "    'Admin User',\n";
    echo "    'admin',\n";
    echo "    'active',\n";
    echo "    NOW()\n";
    echo ");\n";
    exit;
}

echo "✓ User found in database\n";
echo "  ID: {$user['id']}\n";
echo "  Email: {$user['email']}\n";
echo "  Name: {$user['full_name']}\n";
echo "  Role: {$user['role']}\n";
echo "  Status: {$user['status']}\n\n";

echo "Password hash in DB: {$user['password_hash']}\n\n";

// Test password verification
if (password_verify($password, $user['password_hash'])) {
    echo "✓ Password verification PASSED!\n";
    echo "✓ Login should work with:\n";
    echo "  Email: $email\n";
    echo "  Password: $password\n";
} else {
    echo "❌ Password verification FAILED!\n";
    echo "The password hash in the database doesn't match.\n\n";
    
    echo "To fix this, run this SQL in Supabase:\n\n";
    $new_hash = password_hash($password, PASSWORD_BCRYPT);
    echo "UPDATE users SET password_hash = '$new_hash' WHERE email = '$email';\n";
}
