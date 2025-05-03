<?php
session_start();
require '../config/db.php';
global $pdo;
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
    // Validate required fields
    if (!isset($_POST['candidate_id']) || !isset($_POST['position_id']) ||
        !isset($_POST['party_list']) || !isset($_POST['platform']) ||
        !isset($_POST['status'])) {
        throw new Exception('All fields are required');
    }

    // Sanitize inputs
    $candidate_id = filter_var($_POST['candidate_id'], FILTER_VALIDATE_INT);
    $position_id = filter_var($_POST['position_id'], FILTER_VALIDATE_INT);
    $party_list = trim($_POST['party_list']);
    $platform = trim($_POST['platform']);
    $status = trim($_POST['status']);

    // Validate inputs
    if (!$candidate_id || !$position_id) {
        throw new Exception('Invalid candidate or position ID');
    }

    if (empty($party_list) || empty($platform) || empty($status)) {
        throw new Exception('All fields must be filled out');
    }

    if (!in_array($status, ['active', 'pending', 'archived'])) {
        throw new Exception('Invalid status value');
    }

    // Check if position exists
    $stmt = $pdo->prepare("SELECT id FROM positions WHERE id = ?");
    $stmt->execute([$position_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Selected position does not exist');
    }

    // Update candidate in database
    $stmt = $pdo->prepare("
        UPDATE candidates 
        SET position_id = ?, party_list = ?, platform = ?, status = ?
        WHERE id = ?
    ");

    $success = $stmt->execute([
        $position_id,
        $party_list,
        $platform,
        $status,
        $candidate_id
    ]);

    if ($success && $stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Candidate updated successfully';
    } else {
        throw new Exception('No changes made or candidate not found');
    }

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
exit();