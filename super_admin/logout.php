<?php
// logout.php
require '../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store message temporarily before destroying session
$toastr = [
    'type' => 'success',
    'message' => 'You have been logged out successfully'
];

// Unset all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a fresh session just to set the toastr message
session_start();
$_SESSION['toastr'] = $toastr;

// Redirect to login page
header('Location: ../super_admin/login.php');
exit();
