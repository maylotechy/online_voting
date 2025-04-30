<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in at all
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] != 1) {
    header("Location: user-homepage.php=?invalid_role");
    exit;
}


