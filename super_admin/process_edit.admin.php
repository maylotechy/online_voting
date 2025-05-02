<?php
session_start();
require '../config/db.php';
$pdo = $GLOBALS['pdo'];

// 1. Authorization check
if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// 2. Sanitize inputs
$admin_id = filter_input(INPUT_POST, 'admin_id', FILTER_VALIDATE_INT);
$username = trim(htmlspecialchars($_POST['username'] ?? ''));
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$college_id = filter_input(INPUT_POST, 'college_id', FILTER_VALIDATE_INT);
$password = $_POST['password'] ?? null;

// 3. Validation
if (!$admin_id || !$username || !$email || !$college_id) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

try {
    // 4. Check for existing email (excluding current user)
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    $stmt->execute([$email, $admin_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'message' => 'Email address already exists'
        ]));
    }

    $pdo->beginTransaction();

    // 5. Update admin data
    $stmt = $pdo->prepare("
        UPDATE admins 
        SET college_id = ?, email = ?, username = ?
        WHERE id = ?
    ");
    $stmt->execute([$college_id, $email, $username, $admin_id]);

    // 6. Update password if provided
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")
            ->execute([$hashed_password, $admin_id]);
    }

    $pdo->commit();

    // 7. Return success response
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Admin updated successfully!',
        'data' => [
            'id' => $admin_id,
            'username' => $username
        ]
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Admin update failed: " . $e->getMessage()); // Optional: logs to PHP error log
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An internal server error occurred. Please try again.'
    ]);
}
