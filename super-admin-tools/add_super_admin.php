<?php
// add_user.php
global $pdo;
require '../config/db.php';

// Constants for roles
define('ROLE_SUPER_ADMIN', 1);  // Assuming super admin has role_id 1

echo "Enter email: ";
$email = trim(fgets(STDIN));

echo "Enter username: ";
$username = trim(fgets(STDIN));

echo "Enter password: ";
$plainPassword = trim(fgets(STDIN));

// Hash the password using Argon2id
$hashedPassword = password_hash($plainPassword, PASSWORD_ARGON2ID);

// Set the role of the new user (super admin in this case)
$roleId = ROLE_SUPER_ADMIN;

// Get the creator's details (the developer or system creating the super admin)
$creatorId = null;  // Since the developer is creating the super admin directly, this can be NULL
$creatorRole = 'system';  // Developer or system is creating the user
$creatorIp = $_SERVER['REMOTE_ADDR'];  // IP address of the developer (if applicable)

// Log file for system-level logging
$logFile = '/var/log/admin_creation.log';

try {
    // Insert new super admin user
    $stmt = $pdo->prepare("INSERT INTO users (email, username, password_hash, role_id) VALUES (:email, :username, :password, :role_id)");
    $stmt->execute([
        ':email' => $email,
        ':username' => $username,
        ':password' => $hashedPassword,
        ':role_id' => $roleId
    ]);
    $newSuperAdminId = $pdo->lastInsertId();  // Get the ID of the newly created super admin

    // Log the creation of the super admin user in the admin_creation_logs table
    $logStmt = $pdo->prepare("INSERT INTO admin_creation_logs 
    (event_time, creator_id, creator_role, creator_ip, new_admin_id, new_admin_email, new_admin_role, action, details)
    VALUES (NOW(), ?, ?, ?, ?, ?, ?, 'create', ?)");

    $logStmt->execute([
        $creatorId,          // Creator ID (can be null for developer)
        $creatorRole,        // Creator role ('system' or 'developer')
        $creatorIp,          // IP address of the creator
        $newSuperAdminId,    // ID of the newly created super admin
        $email,              // Email of the newly created super admin
        'super_admin',       // Role of the newly created user (super admin in this case)
        'Created by Developer via CLI'  // Details about the action
    ]);

    // Log this creation to a log file for record keeping
    $logMessage = "SUCCESS - Super Admin created: $email (ID: $newSuperAdminId) via CLI";
    logEvent($logMessage, $logFile, $creatorId, $creatorRole);

    echo "Super Admin user successfully added and logged.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Helper function to log events in a file and syslog
function logEvent($message, $logFile, $creatorId = null, $creatorRole = 'system') {
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = "$timestamp [$creatorRole] $message\n";

    // File logging
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    // Syslog with priority based on content
    $priority = strpos($message, 'SUCCESS') !== false ? LOG_INFO : LOG_WARNING;
    openlog('AdminCreation', LOG_PID | LOG_PERROR, LOG_AUTH);
    syslog($priority, "[$creatorRole] $message");
    closelog();
}
?>
