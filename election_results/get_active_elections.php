<?php
global $pdo;
header('Content-Type: application/json');
require_once '../config/db.php';

$response = ['success' => false, 'data' => []];

try {

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get only ongoing elections with college names if applicable
    $query = "SELECT e.id, e.title, e.description, e.scope, e.college_id, 
                     c.college_name, e.start_time, e.end_time, e.status 
              FROM elections e
              LEFT JOIN colleges c ON e.college_id = c.id
              WHERE e.status = 'ongoing'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($elections) {
        $response['success'] = true;
        $response['data'] = $elections;
    }

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>