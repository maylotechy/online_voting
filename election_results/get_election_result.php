<?php
global $pdo;
header('Content-Type: application/json');
require_once '../config/db.php';

$response = ['success' => false, 'data' => []];

if (!isset($_GET['election_id'])) {
    $response['message'] = 'Election ID is required';
    echo json_encode($response);
    exit;
}

$election_id = $_GET['election_id'];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First get election details
    $query = "SELECT e.scope, e.college_id, c.college_name 
              FROM elections e
              LEFT JOIN colleges c ON e.college_id = c.id
              WHERE e.id = :election_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':election_id', $election_id, PDO::PARAM_INT);
    $stmt->execute();
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        $response['message'] = 'Election not found';
        echo json_encode($response);
        exit;
    }

    // Get total voters
    if ($election['scope'] === 'university-wide') {
        $query = "SELECT COUNT(*) as total_voters FROM students WHERE is_enrolled = 1";
        $stmt = $pdo->prepare($query);
    } else {
        $query = "SELECT COUNT(*) as total_voters FROM students 
                  WHERE college_id = :college_id AND is_enrolled = 1";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':college_id', $election['college_id'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $total_voters = $stmt->fetchColumn();

    // Get total votes cast
    $query = "SELECT COUNT(DISTINCT student_id) as total_votes_cast FROM votes 
              WHERE election_id = :election_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':election_id', $election_id, PDO::PARAM_INT);
    $stmt->execute();
    $total_votes_cast = $stmt->fetchColumn();

    // Get positions and candidates
    $query = "SELECT p.id as position_id, p.name as position_name 
              FROM positions p
              JOIN candidates c ON p.id = c.position_id
              WHERE c.election_id = :election_id
              GROUP BY p.id, p.name
              ORDER BY p.`order`";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':election_id', $election_id, PDO::PARAM_INT);
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($positions as &$position) {
        $query = "SELECT c.id, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
          c.party_list, COUNT(v.id) as votes
          FROM candidates c
          JOIN students s ON c.student_id = s.student_id
          LEFT JOIN votes v ON v.candidate_id = c.id AND v.position_id = :join_position_id
          WHERE c.position_id = :where_position_id AND c.election_id = :election_id
          GROUP BY c.id, s.first_name, s.last_name, c.party_list
          ORDER BY votes DESC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':join_position_id', $position['position_id'], PDO::PARAM_INT);
        $stmt->bindParam(':where_position_id', $position['position_id'], PDO::PARAM_INT);
        $stmt->bindParam(':election_id', $election_id, PDO::PARAM_INT);
        $stmt->execute();
        $position['candidates'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $response['success'] = true;
    $response['data'] = [
        'total_voters' => $total_voters,
        'total_votes_cast' => $total_votes_cast,
        'positions' => $positions,
        'college_name' => $election['college_name'] ?? null
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>