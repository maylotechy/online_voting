<?php
global $pdo;
session_start();
require '../config/db.php';
header('Content-Type: application/json');
ob_clean(); // Clear any accidental output

// Function to log login attempts
function logLoginAttempt($pdo, $user_id, $email, $status, $role_id = null) {
    $stmt = $pdo->prepare("
        INSERT INTO login_logs 
        (user_id, email_attempted, status, ip_address, user_agent, role_id)
        VALUES (:user_id, :email, :status, :ip, :ua, :role_id)
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':email' => $email,
        ':status' => $status,
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        ':role_id' => $role_id
    ]);
}

// Verify Turnstile CAPTCHA first
if (empty($_POST['cf-turnstile-response'])) {
    die(json_encode([
        'success' => false,
        'response' => 'error',
        'message' => 'Security check failed'
    ]));
}

$secret = '0x4AAAAAABXBqW68Lz7ALXiUfNDHiVf5_mw';
$token = $_POST['cf-turnstile-response'];
$ip = $_SERVER['REMOTE_ADDR'];

$url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
$data = ['secret' => $secret, 'response' => $token, 'remoteip' => $ip];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response = json_decode($result);

if (!$response->success) {
    error_log("Turnstile failed: " . print_r($response, true));
    die(json_encode([
        'success' => false,
        'response' => 'error',
        'message' => 'Security verification failed'
    ]));
}

// Main login processing
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 400);
    }

    if (empty($_POST["email"]) || empty($_POST["password"])) {
        throw new Exception('Email and password are required', 400);
    }

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Modified query to check for either admin role (1 = super admin, 2 = college admin)
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email AND role_id IN (1, 2)");
    $stmt->bindParam(":email", $email);

    if (!$stmt->execute()) {
        throw new Exception('Database error', 500);
    }

    if ($stmt->rowCount() === 0) {
        logLoginAttempt($pdo, null, $email, 'failed', null);
        throw new Exception('Invalid username/email or password', 401);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($password, $row['password_hash'])) {
        logLoginAttempt($pdo, $row['id'], $email, 'failed', $row['role_id']);
        throw new Exception('Invalid username/email or password', 401);
    }

    // Successful login - store all relevant session data
    $_SESSION['admin_id'] = $row['id'];
    $_SESSION['email'] = $row['email'];
    $_SESSION['role_id'] = $row['role_id']; // Store the role_id
    $_SESSION['college_id'] = $row['college_id'] ?? null; // Store college_id if exists

    // Regenerate session ID for security
    session_regenerate_id(true);

    // Log successful attempt
    logLoginAttempt($pdo, $row['id'], $email, 'success', $row['role_id']);

    // Determine redirect based on role
    $redirect = match((int)$row['role_id']) {
        1 => 'super_admin/dashboard.php', // Super admin
        2 => 'college_admin/dashboard.php', // College admin
        default => 'login.php' // Shouldn't happen due to query filter
    };

    echo json_encode([
        'success' => true,
        'response' => 'success',
        'message' => 'Successfully logged in',
        'redirect' => $redirect,
        'role_id' => $row['role_id'] // Optional: send role info to frontend
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'response' => 'error',
        'message' => $e->getMessage()
    ]);
}