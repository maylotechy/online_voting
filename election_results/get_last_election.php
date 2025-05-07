<?php
global $pdo;
require_once  '../config/db.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
        header('Location: login.php');
        exit();
    }

    // Get the most recently ended election
    $sql = "SELECT 
                e.id, 
                e.title, 
                e.end_time,
                (SELECT COUNT(*) FROM students WHERE is_enrolled = 1) AS total_voters,
                (SELECT COUNT(DISTINCT student_id) FROM votes WHERE election_id = e.id) AS total_votes_cast
            FROM elections e
            WHERE e.end_time < NOW()
            ORDER BY e.end_time DESC
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        echo json_encode([
            'success' => false,
            'message' => 'No completed elections found'
        ]);
        exit;
    }

    // Format the response data
    $responseData = [
        'id' => (int)$election['id'],
        'title' => $election['title'],
        'end_time' => $election['end_time'],
        'total_voters' => (int)$election['total_voters'],
        'total_votes_cast' => (int)$election['total_votes_cast']
    ];

    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}