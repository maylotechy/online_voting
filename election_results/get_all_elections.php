<?php
// get_all_elections.php

global $pdo;
header('Content-Type: application/json');
header('Content-Type: text/html; charset=UTF-8');

require '../config/db.php';
require_once '../middleware/auth_admin.php';
try {


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
