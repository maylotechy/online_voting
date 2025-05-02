<?php
require_once '../config/db.php';
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');


require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exists' => false, 'error' => 'Invalid request method']);
    exit;
}

$student_id = $_POST['student_id'] ?? null;

try {
    $stmt = $pdo->prepare("
        SELECT s.*, c.college_name, cr.name AS course_name 
        FROM students s
        JOIN colleges c ON s.college_id = c.id
        JOIN courses cr ON s.course_id = cr.id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        echo json_encode([
            'exists' => true,
            'student' => $student
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
} catch (PDOException $e) {
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}
?>