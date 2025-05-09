<?php
require "../config/db.php";
header('Content-Type: application/json');
global $pdo;
if (isset($_GET['student_id'])) {
    $studentId = $_GET['student_id'];
    $stmt = $pdo->prepare("
        SELECT student_id, first_name, last_name, email, 
               college_id, course_id, year_level, cor_number 
        FROM students 
        WHERE student_id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        echo json_encode(['success' => true, 'data' => $student]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Student ID not provided']);
}