<?php
session_start();
require '../config/db.php';
$pdo = $GLOBALS['pdo'];

// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $admin_id = (int)$_POST['admin_id'];

        if ($admin_id <= 0) {
            throw new Exception("Invalid admin ID");
        }

        // Prevent deleting self
        if ($admin_id == $_SESSION['admin_id']) {
            throw new Exception("You cannot delete your own account");
        }

        // Verify admin exists and is deletable
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ? AND role_id = 2");
        $stmt->execute([$admin_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Admin not found or cannot be deleted");
        }

        // Delete admin
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);

        echo json_encode(['success' => true, 'message' => 'Admin deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
