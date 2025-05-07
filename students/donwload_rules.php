<?php
global $pdo;
require '../config/db.php';
session_start();

// Authentication checks
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

// Get and validate election ID
$electionId = (int)($_GET['election_id'] ?? 0);
if ($electionId <= 0) {
    require_once '../components/not_found.php';
    showNotFoundPage([
        'title' => 'Document Not Found',
        'message' => 'The election rules PDF could not be located.'
    ]);
}

try {
    // 1. Get file path from database
    $stmt = $pdo->prepare("SELECT rules_file FROM elections WHERE id = ?");
    $stmt->execute([$electionId]);
    $rulesPathFromDB = $stmt->fetchColumn();

    if (!$rulesPathFromDB) {
        die("No rules file found for this election");
    }


    // 2. Secure file path handling
    $baseDir = realpath(__DIR__.'/../uploads/election_rules');
    $fullPath = realpath($baseDir.'/'.basename($rulesPathFromDB));

    // Security checks
    if ($fullPath === false || strpos($fullPath, $baseDir) !== 0) {
        die("Invalid file path");
    }

    if (!file_exists($fullPath)) {
        showNotFoundPage([
            'title' => 'Document Not Found',
            'message' => 'The election rules PDF could not be located.'
        ]);
    }

    // 3. Force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.basename($fullPath).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));

    readfile($fullPath);
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}