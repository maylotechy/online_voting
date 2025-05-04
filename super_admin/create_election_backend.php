<?php
global $pdo;
ob_start();
session_start();
require '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Set JSON header
header('Content-Type: application/json');

$admin_id = $_SESSION['admin_id'];
$role_id = $_SESSION['role_id'];
$college_id = $_SESSION['college_id'] ?? null;

try {
    $pdo->beginTransaction();

    // Validate required fields (removed status from required fields)
    $required = ['title', 'description', 'start_date', 'end_date'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("All required fields must be filled.");
        }
    }
    // Check for existing ongoing election
    $check_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM elections 
    WHERE status = 'ongoing' 
    " . ($role_id != 1 ? "AND college_id = ?" : "") . "
");
    $check_stmt->execute($role_id != 1 ? [$college_id] : []);
    $ongoing_count = $check_stmt->fetchColumn();

    if ($ongoing_count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You cannot add a new election while another is still ongoing.'
        ]);
        exit();
    }


    // Validate date range
    $start_date = new DateTime($_POST['start_date']);
    $end_date = new DateTime($_POST['end_date']);

    if ($end_date <= $start_date) {
        throw new Exception("End date must be after the start date.");
    }

    // Handle PDF upload
    $rules_file_path = null;
    if (isset($_FILES['election_rules']) && $_FILES['election_rules']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/election_rules/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Validate PDF
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['election_rules']['tmp_name']);
        finfo_close($finfo);

        if ($mime_type !== 'application/pdf') {
            throw new Exception("Only PDF files are allowed.");
        }

        $filename = 'election_' . uniqid() . '.pdf';
        $target_path = $upload_dir . $filename;

        if (!move_uploaded_file($_FILES['election_rules']['tmp_name'], $target_path)) {
            throw new Exception("Failed to save election rules document.");
        }
        $rules_file_path = 'uploads/election_rules/' . $filename;
    }

    // Create election record - STATUS IS NOW AUTOMATICALLY SET TO 'ongoing'
    $scope = ($role_id == 1) ? 'university-wide' : 'college';

    $stmt = $pdo->prepare("
        INSERT INTO elections 
        (title, description, scope, college_id, created_by, status, start_time, end_time, rules_file, created_at) 
        VALUES (?, ?, ?, ?, ?, 'ongoing', ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['description'],
        $scope,
        $college_id,
        $admin_id,
        $start_date->format('Y-m-d H:i:s'),
        $end_date->format('Y-m-d H:i:s'),
        $rules_file_path
    ]);

    $election_id = $pdo->lastInsertId();

    // Update candidates with election_id
    if (isset($_POST['candidates']) && is_array($_POST['candidates'])) {
        $update_stmt = $pdo->prepare("UPDATE candidates SET election_id = ? WHERE id = ?");
        foreach ($_POST['candidates'] as $candidate_id => $data) {
            if (!empty($candidate_id)) {
                $update_stmt->execute([$election_id, $candidate_id]);
            }
        }
    }

    // Get all eligible students (since status is always ongoing now, we always send codes)
    $students_query = "SELECT student_id, email, first_name, last_name FROM students WHERE is_enrolled = 1";
    if ($scope === 'college' && $college_id) {
        $students_query .= " AND college_id = " . $pdo->quote($college_id);
    }

    $students = $pdo->query($students_query)->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($students)) {
        $code_stmt = $pdo->prepare("
            INSERT INTO otps 
            (student_id, election_id, code, expires_at) 
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))
        ");

        $mail = new PHPMailer(true);
        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'maylo.techy@gmail.com';
            $mail->Password = 'tfgdedurstaukzyi';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('maylo.techy@gmail.com', 'USM Election System');

            $successCount = 0;
            $failedCount = 0;
            $failedEmails = [];

            foreach ($students as $student) {
                try {
                    $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

                    // Store OTP in database
                    $code_stmt->execute([
                        $student['student_id'],
                        $election_id,
                        $code
                    ]);

                    // Prepare email
                    $mail->clearAddresses();
                    $mail->addAddress($student['email'], $student['first_name'] . ' ' . $student['last_name']);
                    $mail->Subject = 'Your Election Verification Code: ' . $_POST['title'];
                    $mail->Body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #2c3e50;'>Election Verification Code</h2>
        <p>Hello {$student['first_name']},</p>
        <p>Your verification code for the election <strong>{$_POST['title']}</strong> is:</p>
        
        <div style='background-color: #f8f9fa; border: 1px solid #dee2e6; 
                    border-radius: 4px; padding: 15px; margin: 20px 0; 
                    text-align: center; font-size: 24px; color: #007bff;'>
            {$code}
        </div>
        
        <div style='background-color: #f0f8ff; border-left: 4px solid #007bff; 
                    padding: 15px; margin-bottom: 20px;'>
            <h4 style='margin-top: 0; color: #007bff;'>Election Schedule</h4>
            <p><strong>Start:</strong> " . date('F j, Y g:i A', strtotime($_POST['start_date'])) . "</p>
            <p><strong>End:</strong> " . date('F j, Y g:i A', strtotime($_POST['end_date'])) . "</p>
        </div>
        
        <p>To participate in the election, please:</p>
        <ol>
            <li>Go to our voting portal: 
                <a href='http://localhost:8080/usm-online-voting/students/login.php' 
                   style='color: #007bff; text-decoration: none;'>
                   http://localhost:8080/usm-online-voting/students/login.php
                </a>
            </li>
            <li>Enter your student credentials</li>
            <li>When prompted, enter the verification code above</li>
        </ol>
        
        <p style='color: #dc3545;'><strong>Note:</strong> This code will expire in 24 hours.</p>
        
        <div style='margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee;'>
            <p>Thank you for participating in the election process!</p>
            <p><strong>Election Committee</strong><br>
            University Student Management</p>
        </div>
    </div>
";

                    if ($mail->send()) {
                        $successCount++;
                    } else {
                        throw new Exception("Failed to send email");
                    }

                    // Small delay to prevent rate limiting
                    usleep(200000); // 0.2 seconds

                } catch (Exception $e) {
                    $failedCount++;
                    $failedEmails[] = $student['email'];
                    error_log("Failed to send to {$student['email']}: " . $e->getMessage());
                    continue;
                }
            }

            // Log email sending results
            error_log("Sent verification codes: {$successCount} success, {$failedCount} failed");

        } catch (Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
            throw new Exception("Email system configuration error");
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Election created successfully! Verification codes sent to students.',
        'redirect' => 'create_elections.php'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();

    // Delete uploaded file if transaction failed
    if (isset($target_path) && file_exists($target_path)) {
        unlink($target_path);
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit();
}