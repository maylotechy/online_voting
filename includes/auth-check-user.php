<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit;

}
if ($_SESSION['role'] != 2) {
    // If not an admin, redirect to the user page or another appropriate page
    header("Location: user-homepage.php");
    exit;
}

