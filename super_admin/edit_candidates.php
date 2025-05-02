<?php
session_start();
include '../config/db.php';
global $pdo;
header('Content-Type: application/json');

if (
    isset($_POST['candidate_id'], $_POST['student_id'], $_POST['position_id'],
        $_POST['platform'], $_POST['party_list'], $_POST['status'])
) {
    $candidate_id = $_POST['candidate_id'];
    $student_id = $_POST['student_id'];
    $position_id = $_POST['position_id'];
    $platform = $_POST['platform'];
    $party_list = $_POST['party_list'];
    $status = $_POST['status'];
    $updated_at = date('Y-m-d H:i:s');

    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Admin not logged in.']);
        exit;
    }

    try {
        // 1. Check if the student exists in the students table
        $checkStudent = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
        $checkStudent->execute([$student_id]);
        if ($checkStudent->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Student ID does not exist.']);
            exit;
        }

        // 2. Check for duplicate student_id with status not 'used' in other records
        $checkDup = $pdo->prepare("SELECT * FROM candidates WHERE student_id = ? AND id != ? AND status != 'used'");
        $checkDup->execute([$student_id, $candidate_id]);

        if ($checkDup->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Another active candidate with this Student ID exists.']);
            exit;
        }

        // 3. Update the candidate record
        $update = $pdo->prepare("
            UPDATE candidates SET 
                student_id = ?, 
                position_id = ?, 
                platform = ?, 
                party_list = ?, 
                status = ?, 
                created_at = ? 
            WHERE id = ?
        ");
        $update->execute([$student_id, $position_id, $platform, $party_list, $status, $updated_at, $candidate_id]);

        echo json_encode(['status' => 'success', 'message' => 'Candidate updated successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Incomplete form data.']);
}
