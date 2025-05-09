<?php
// get_courses.php - Endpoint to fetch courses by college ID
require "../config/db.php";
require "../auth_session/auth_check_collegeadmin.php"; // Include your auth check

// Set headers for JSON response
header('Content-Type: application/json');

// Check if college_id parameter exists
if (!isset($_GET['college_id']) || empty($_GET['college_id'])) {
    echo json_encode(['success' => false, 'message' => 'College ID is required']);
    exit;
}

$college_id = $_GET['college_id'];

// Validate that college_id is numeric
if (!is_numeric($college_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid College ID']);
    exit;
}

try {
    $pdo = $GLOBALS['pdo'];
    
    // Prepare and execute query to get courses for the selected college
    $stmt = $pdo->prepare("SELECT id, name FROM courses WHERE college_id = :college_id ORDER BY name");
    $stmt->bindParam(':college_id', $college_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Fetch all courses
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the courses as JSON
    echo json_encode($courses);
    
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>