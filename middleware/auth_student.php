<?php
require_once '../components/not_found.php'; // Adjust path as needed

$isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1');




// Timeout: 15 mins
$timeout = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    showNotFoundPage([
        'title' => 'Session Expired',
        'message' => 'Your session has expired due to inactivity. Please log in again.',
        'primary_action' => [
            'text' => 'Login Again',
            'url' => '../super_admin/login.php',
            'icon' => 'sign-in-alt'
        ],
    ]);
}

$_SESSION['last_activity'] = time();

// Only allow logged-in users (check for student_id in session)
if (!isset($_SESSION['student_id'])) {
    showNotFoundPage([
        'title' => 'Unauthorized Access',
        'message' => 'You are not logged in. Please log in to access this page.',
        'primary_action' => [
            'text' => 'Go to Login',
            'url' => '../students/login.php',
            'icon' => 'lock'
        ],
    ]);
}


// Optional: block API tools
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/postman|insomnia|curl|httpie/i', $userAgent)) {
    showNotFoundPage([
        'title' => 'Access Blocked',
        'message' => 'Access via API testing tools is not allowed.',
        'primary_action' => [
            'text' => 'Return',
            'url' => 'javascript:history.back()',
            'icon' => 'arrow-left'
        ]
    ]);
}
