<?php
session_start();
require '../config/db.php';

$pdo = $GLOBALS['pdo'];

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
    if (isAjaxRequest()) {
        http_response_code(403);
        echo json_encode(['message' => 'Unauthorized access']);
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        // Required fields validation
        $required = ['username', 'email', 'password', 'college_id'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All fields are required");
            }
        }

        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $college_id = (int)$_POST['college_id'];
        $role_id = 2;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email address already exists");
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO admins 
            (role_id, college_id, email, username, password_hash) 
            VALUES (?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$role_id, $college_id, $email, $username, $password_hash])) {
            echo json_encode(['success' => true, 'message' => 'Admin added successfully']);
        } else {
            throw new Exception("Failed to create admin account");
        }

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}
