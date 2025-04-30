<?php
global $pdo;
session_start();
require '../config/db.php';
header('Content-Type: application/json');

// 1. Verify Cloudflare Turnstile
if (empty($_POST['cf-turnstile-response'])) {
    die(json_encode(['success' => false, 'message' => 'Security verification failed']));
}

$secret = '0x4AAAAAABXBqW68Lz7ALXiUfNDHiVf5_mw'; // Get from Cloudflare dashboard
$token = $_POST['cf-turnstile-response'];
$ip = $_SERVER['REMOTE_ADDR'];

$url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
$data = ['secret' => $secret, 'response' => $token, 'remoteip' => $ip];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response = json_decode($result);

if (!$response->success) {
    die(json_encode(['success' => false, 'message' => 'Security check failed']));
}

// 2. Validate student credentials
$studentId = trim($_POST['student_id'] ?? '');
$accessCode = $_POST['access_code'] ?? '';

if (empty($studentId) || strlen($accessCode) !== 4) {
    die(json_encode(['success' => false, 'message' => 'Invalid credentials']));
}

try {
    // Check if student exists and code matches
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND access_code = ? AND can_vote = 1");
    $stmt->execute([$studentId, $accessCode]);

    if ($stmt->rowCount() === 1) {
        $student = $stmt->fetch();

        // Check if already voted
        if ($student['has_voted']) {
            die(json_encode(['success' => false, 'message' => 'You have already voted']));
        }

        // Start voting session
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['voting_allowed'] = true;
        session_regenerate_id(true);

        echo json_encode(['success' => true, 'redirect' => 'voting_booth.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID or access code']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error']);
}