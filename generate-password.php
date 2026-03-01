<?php
// Generate password hash for admin user
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: $password\n";
echo "Hash: $hash\n\n";

echo "SQL Query:\n";
echo "INSERT INTO users (id, email, password_hash, full_name, role, status, created_at) \n";
echo "VALUES (\n";
echo "    uuid_generate_v4(),\n";
echo "    'admin@trustbridge.com',\n";
echo "    '$hash',\n";
echo "    'Admin User',\n";
echo "    'admin',\n";
echo "    'active',\n";
echo "    NOW()\n";
echo ");\n";
