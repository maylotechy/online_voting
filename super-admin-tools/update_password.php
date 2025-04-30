<?php
global $pdo;
require '../config/db.php'; // make sure $pdo is available

// --- You can use this via CLI ---
if (php_sapi_name() !== 'cli') {
    exit("Run this script from the command line.\n");
}

echo "Enter admin email: ";
$email = trim(fgets(STDIN));

echo "Enter new password: ";
$newPassword = trim(fgets(STDIN));

if (empty($email) || empty($newPassword)) {
    exit("Email and password must not be empty.\n");
}

// Hash the new password securely
$hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID); // or PASSWORD_DEFAULT

try {
    // Ensure the admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND role_id = 1");
    $stmt->execute(['email' => $email]);

    if ($stmt->rowCount() === 0) {
        exit("No super admin found with that email.\n");
    }

    // Update password
    $update = $pdo->prepare("UPDATE users SET password_hash = :password WHERE email = :email AND role_id = 1");
    $update->execute([
        'password' => $hashedPassword,
        'email' => $email
    ]);

    echo "✅ Password updated successfully.\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
