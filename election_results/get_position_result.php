<?php
global $pdo;
require_once '../config/db.php';


header('Content-Type: application/json');

try {
    // Authenticate the request


    // Validate input
    $electionId = filter_input(INPUT_GET, 'election_id', FILTER_VALIDATE_INT);
    $positionId = filter_input(INPUT_GET, 'position_id', FILTER_VALIDATE_INT);

    if (!$electionId || !$positionId) {
        throw new Exception('Invalid parameters', 400);
    }



    // Get position name
    $positionStmt = $pdo->prepare("SELECT name FROM positions WHERE id = :id");
    $positionStmt->execute([':id' => $positionId]);
    $position = $positionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$position) {
        throw new Exception('Position not found', 404);
    }

    $positionName = $position['name'];

    // Get candidates and their vote counts
    $sql = "SELECT 
                c.id AS candidate_id,
                s.id AS student_id,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                c.party_list,
                COUNT(v.id) AS votes
            FROM candidates c
            JOIN students s ON c.student_id = s.id
            LEFT JOIN votes v ON v.candidate_id = c.id AND v.election_id = :election_id
            WHERE c.election_id = :election_id 
              AND c.position_id = :position_id
            GROUP BY c.id, s.id, c.party_list
            ORDER BY votes DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':election_id' => $electionId,
        ':position_id' => $positionId
    ]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalVotes = array_sum(array_column($candidates, 'votes'));

    $responseData = [
        'position_name' => $positionName,
        'total_votes' => (int)$totalVotes,
        'candidates' => array_map(function ($candidate) {
            return [
                'id' => (int)$candidate['candidate_id'],
                'student_id' => (int)$candidate['student_id'],
                'student_name' => $candidate['student_name'],
                'party_list' => $candidate['party_list'],
                'votes' => (int)$candidate['votes']
            ];
        }, $candidates)
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
