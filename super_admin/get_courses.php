<?php
require "../config/db.php";
header('Content-Type: application/json');
global $pdo;
if (isset($_GET['college_id'])) {
    $collegeId = $_GET['college_id'];
    $stmt = $pdo->prepare("SELECT id, name FROM courses WHERE college_id = ?");
    $stmt->execute([$collegeId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($courses);
} else {
    echo json_encode([]);
}