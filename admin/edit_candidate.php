<?php
global $pdo;
require "../config/db.php";
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $required = ['student_id', 'first_name', 'last_name', 'email', 'course_id', 'year_level', 'original_student_id'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("All fields are required");
        }
    }

    $originalId = $_POST['original_student_id']; // ID before editing
    $newId = $_POST['student_id'];
    $email = $_POST['email'];
    $cor_number = $_POST['cor_number'] ?? null;
    $college_id = $_SESSION['college_id']; // Get college_id from session

    // Get original data
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND college_id = ?");
    $stmt->execute([$originalId, $college_id]);
    $currentData = $stmt->fetch();

    if (!$currentData) {
        throw new Exception("Original student record not found");
    }

    // Check if new student_id already exists (and not the current one)
    if ($newId !== $originalId) {
        $stmt = $pdo->prepare("SELECT 1 FROM students WHERE student_id = ? AND college_id = ?");
        $stmt->execute([$newId, $college_id]);
        if ($stmt->fetch()) {
            throw new Exception("Student ID already exists");
        }
    }

    // Check if new email is taken by another student
    if ($email !== $currentData['email']) {
        $stmt = $pdo->prepare("SELECT 1 FROM students WHERE email = ? AND student_id != ? AND college_id = ?");
        $stmt->execute([$email, $originalId, $college_id]);
        if ($stmt->fetch()) {
            throw new Exception("Email already exists");
        }
    }

    // Check if COR number is being changed and taken by another student
    if (!empty($cor_number) && $cor_number !== $currentData['cor_number']) {
        $stmt = $pdo->prepare("SELECT 1 FROM students WHERE cor_number = ? AND student_id != ? AND college_id = ?");
        $stmt->execute([$cor_number, $originalId, $college_id]);
        if ($stmt->fetch()) {
            throw new Exception("COR Number already exists");
        }
    }

    // Proceed with update
    $stmt = $pdo->prepare("
        UPDATE students SET
            student_id = ?,
            first_name = ?,
            last_name = ?,
            email = ?,
            course_id = ?,
            year_level = ?,
            cor_number = ?
        WHERE student_id = ? AND college_id = ?
    ");

    $success = $stmt->execute([
        $newId,
        $_POST['first_name'],
        $_POST['last_name'],
        $email,
        $_POST['course_id'],
        $_POST['year_level'],
        $cor_number,
        $originalId,
        $college_id
    ]);

    if ($success) {
        $response = ['success' => true, 'message' => 'Student updated successfully'];
    } else {
        throw new Exception("No changes made or student not found");
    }

} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);