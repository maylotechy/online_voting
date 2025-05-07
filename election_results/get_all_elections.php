<?php
// get_all_elections.php
session_start();
global $pdo;
header('Content-Type: application/json');
require '../config/db.php';
try {

    if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
        header('Location: login.php');
        exit();
    }

    // Query to get all elections
    $stmt = $pdo->query("
        SELECT id, title, start_time, end_time, status FROM elections ORDER BY created_at DESC
       
    ");

    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return success response with election data
    echo json_encode([
        'success' => true,
        'data' => $elections,
        'count' => count($elections)
    ]);
    exit;

} catch (PDOException $e) {
    // Return error response if database operation fails
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch elections: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
    exit;
}
