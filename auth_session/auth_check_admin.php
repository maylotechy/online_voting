<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}
if ($_SESSION['role_id'] != 1) {
    header("Location:dashboard.php=?invalid_role");
    exit;
}


