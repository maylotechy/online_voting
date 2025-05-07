<?php
global $pdo;
session_start();
require '../config/db.php';

// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}

// Set JSON content type
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Validate required field
    if (!isset($_POST['candidate_id'])) {
        throw new Exception('Candidate ID is required');
    }

    // Sanitize input
    $candidate_id = filter_var($_POST['candidate_id'], FILTER_VALIDATE_INT);

    if (!$candidate_id) {
        throw new Exception('Invalid candidate ID');
    }

    // Check if candidate exists
    $stmt = $pdo->prepare("SELECT id FROM candidates WHERE id = ?");
    $stmt->execute([$candidate_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Candidate not found');
    }

    // Delete candidate from database
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
    $success = $stmt->execute([$candidate_id]);

    if ($success) {
        $response['success'] = true;
        $response['message'] = 'Candidate deleted successfully';
    } else {
        throw new Exception('Failed to delete candidate');
    }

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
exit();