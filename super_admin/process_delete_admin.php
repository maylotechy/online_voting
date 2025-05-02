<?php
session_start();
require '../config/db.php';
$pdo = $GLOBALS['pdo'];
// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $admin_id = (int)$_POST['admin_id'];

        // Validate admin ID
        if ($admin_id <= 0) {
            throw new Exception("Invalid admin ID");
        }

        // Prevent deleting yourself
        if ($admin_id == $_SESSION['admin_id']) {
            throw new Exception("You cannot delete your own account");
        }

        // Check if admin exists and is a college admin (role_id = 2)
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ? AND role_id = 2");
        $stmt->execute([$admin_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Admin not found or cannot be deleted");
        }

        // Delete the admin
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);

        $_SESSION['toastr'] = [
            'type' => 'success',
            'message' => 'Admin deleted successfully!'
        ];

    } catch (Exception $e) {
        $_SESSION['toastr'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
    }

    header('Location: manage_admin.php');
    exit();
} else {
    header('Location: manage_admin.php');
    exit();
}