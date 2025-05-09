<?php
session_start();
include '../config/db.php';
global $pdo;
header('Content-Type: application/json');

if (isset($_POST['student_id'], $_POST['position_id'], $_POST['platform'], $_POST['party_list'], $_POST['status'])) {
    $student_id = $_POST['student_id'];
    $position_id = $_POST['position_id'];
    $platform = $_POST['platform'];
    $party_list = $_POST['party_list'];
    $status = $_POST['status'];
    $created_at = date('Y-m-d H:i:s');

    if (isset($_SESSION['admin_id'])) {
        $uploaded_by = $_SESSION['admin_id'];
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Admin not logged in.']);
        exit;
    }

    try {
        // 1. Check if student exists
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Student ID does not exist.']);
            exit;
        }

        // 2. Check if student_id already exists in candidates
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the student is already an active candidate, prevent insertion
        if ($existing && $existing['status'] === 'active') {
            echo json_encode([
                'success' => false,
                'message' => 'This student is already registered as an active candidate.'
            ]);
            exit;
        }

// If status being submitted is NOT "pending", prevent duplicate insert
        if ($existing && $status !== 'pending') {
            echo json_encode([
                'success' => false,
                'message' => 'This student is already a candidate. You can only re-register them with a pending status.'
            ]);
            exit;
        }

        // 3. Insert candidate
        $insert = $pdo->prepare("INSERT INTO candidates (position_id, student_id, platform, party_list, uploaded_by, status, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$position_id, $student_id, $platform, $party_list, $uploaded_by, $status, $created_at]);

        echo json_encode(['success' => true, 'message' => 'Candidate added successfully.']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Incomplete form data.']);
}
?>
