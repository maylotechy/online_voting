<?php
global $pdo;
session_start();
require '../config/db.php';

// Check if user is logged in and has college_id (for college admins)
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['college_id'])) {
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
    $college_id = $_SESSION['college_id'];

    if (!$candidate_id) {
        throw new Exception('Invalid candidate ID');
    }

    // Check if candidate exists AND belongs to admin's college
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM candidates c
        JOIN students s ON c.student_id = s.student_id
        WHERE c.id = ? AND s.college_id = ?
    ");
    $stmt->execute([$candidate_id, $college_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Candidate not found in your college');
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
    error_log("Candidate deletion error: " . $e->getMessage());
    $response['message'] = 'Database error occurred';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
exit();