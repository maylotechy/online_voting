<?php
global $pdo;
require "../config/db.php";
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $required = ['student_id', 'first_name', 'last_name', 'email', 'course_id', 'year_level'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("All fields are required");
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if student ID already exists
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $stmt->execute([$_POST['student_id']]);
    if ($stmt->fetch()) {
        throw new Exception("Student ID already exists");
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT email FROM students WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    if ($stmt->fetch()) {
        throw new Exception("Email already exists");
    }

    // Check if COR number already exists (if provided)
    if (!empty($_POST['cor_number'])) {
        $stmt = $pdo->prepare("SELECT cor_number FROM students WHERE cor_number = ?");
        $stmt->execute([$_POST['cor_number']]);
        if ($stmt->fetch()) {
            throw new Exception("COR Number already exists");
        }
    }

    // Insert new student
    $stmt = $pdo->prepare("
        INSERT INTO students 
        (student_id, first_name, last_name, email, college_id, course_id, year_level, cor_number, has_voted, is_enrolled)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 1)
    ");

    // Get college_id from the course
    $stmtCourse = $pdo->prepare("SELECT college_id FROM courses WHERE id = ?");
    $stmtCourse->execute([$_POST['course_id']]);
    $course = $stmtCourse->fetch();
    
    if (!$course) {
        throw new Exception("Invalid course selected");
    }
    
    $college_id = $course['college_id'];

    $success = $stmt->execute([
        $_POST['student_id'],
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $college_id, // Added college_id from course lookup
        $_POST['course_id'],
        $_POST['year_level'],
        $_POST['cor_number'] ?? null
    ]);

    if ($success) {
        $response = [
            'success' => true, 
            'message' => 'Student added successfully',
            'student_id' => $_POST['student_id']
        ];
    } else {
        throw new Exception("Failed to add student");
    }
} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
    error_log("Database error in add_student.php: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);