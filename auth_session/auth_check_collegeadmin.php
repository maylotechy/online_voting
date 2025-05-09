<?php 
session_start();

// Verify college admin access
if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 2 || !isset($_SESSION['college_id'])) {
    header("Location: ../super_admin/login.php");
    exit();
}
