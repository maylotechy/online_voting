<?php
global $pdo;
require "../config/db.php";
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if (empty($_POST['student_id'])) {
        throw new Exception("Student ID not provided");
    }

    $student_id = $_POST['student_id'];

    // Check if student exists
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Student not found");
    }

    // Check if student is a candidate
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE student_id = ?");
    $stmt->execute([$student_id]);
    if ($stmt->fetch()) {
        throw new Exception("Cannot delete student: This student is listed as a candidate.");
    }

    // Delete student
    $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
    $success = $stmt->execute([$student_id]);

    if ($success) {
        $response = ['success' => true, 'message' => 'Student deleted successfully'];
    } else {
        throw new Exception("Failed to delete student");
    }

} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
