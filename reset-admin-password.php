<?php
require_once 'config/database.php';

$email = 'admin@trustbridge.local';
$newPassword = 'admin123';
$passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

$db = Database::getInstance();

try {
    $sql = "UPDATE users SET password_hash = ? WHERE email = ?";
    $db->query($sql, [$passwordHash, $email]);
    
    echo "Admin password reset successfully!\n";
    echo "Email: $email\n";
    echo "Password: $newPassword\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
