<?php
global $pdo;
session_start();
require '../config/db.php';
header('Content-Type: application/json');

// 1. Verify Cloudflare Turnstile
if (empty($_POST['cf-turnstile-response'])) {
    die(json_encode(['success' => false, 'message' => 'Security verification failed']));
}

$secret = '0x4AAAAAABXBqW68Lz7ALXiUfNDHiVf5_mw';
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

// 2. Validate student credentials and OTP
$studentId = trim($_POST['student_id'] ?? '');
$otpCode = $_POST['access_code'] ?? '';

if (empty($studentId) || strlen($otpCode) !== 6 || !ctype_digit($otpCode)) {
    die(json_encode(['success' => false, 'message' => 'Invalid credentials']));
}

try {
    // Check if student exists and is enrolled
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM students s
        WHERE s.student_id = ? 
        AND s.is_enrolled = 1
        LIMIT 1
    ");
    $stmt->execute([$studentId]);

    if ($stmt->rowCount() !== 1) {
        die(json_encode(['success' => false, 'message' => 'Invalid student ID or not enrolled']));
    }

    $student = $stmt->fetch();

    // Get active election
    $electionStmt = $pdo->prepare("
        SELECT id, title FROM elections 
        WHERE status = 'ongoing' 
        AND start_time <= NOW() 
        AND end_time >= NOW()
        LIMIT 1
    ");
    $electionStmt->execute();

    if ($electionStmt->rowCount() !== 1) {
        die(json_encode(['success' => false, 'message' => 'No active election found']));
    }

    $election = $electionStmt->fetch();

    // Check if already voted in this election using has_voted field in students table
    if ($student['has_voted'] == 1) {
        die(json_encode(['success' => false, 'message' => 'You have already voted in this election']));
    }

    // Verify OTP code for this election
    $otpStmt = $pdo->prepare("
        SELECT otp_id FROM otps 
        WHERE student_id = ?
        AND election_id = ?
        AND code = ?
        AND expires_at > NOW()
        AND used = 0
        LIMIT 1
    ");
    $otpStmt->execute([$student['student_id'], $election['id'], $otpCode]);

    if ($otpStmt->rowCount() !== 1) {
        die(json_encode(['success' => false, 'message' => "Invalid or Expired OTP"]));
    }

    $otp = $otpStmt->fetch();

    // Mark OTP as used
    //$updateOtp = $pdo->prepare("UPDATE otps SET used = 1, used_at = NOW() WHERE id = ?");
    //$updateOtp->execute([$otp['otp_id']]);

    // Start voting session
    $_SESSION = [
        'student_id' => $student['student_id'],
        'student_db_id' => $student['id'],
        'college_id' => $student['college_id'],
        'election_id' => $election['id'],
        'election_title' => $election['title'],
        'voting_allowed' => true,
        'otp_verified' => true,
        'login_time' => time()
    ];
    session_regenerate_id(true);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => 'voting_page.php'
    ]);

} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'System error. Please try again later. Error: ' . $e->getMessage()
    ]);
}