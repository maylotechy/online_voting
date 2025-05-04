<?php
require '../config/db.php';
require '../vendor/autoload.php';
session_start();

header('Content-Type: application/json');

// Verify admin access
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$electionId = $input['election_id'] ?? null;
$newStatus = $input['status'] ?? null;

try {
    $pdo->beginTransaction();

    // Get current election data
    $stmt = $pdo->prepare("
        SELECT status, start_time, end_time 
        FROM elections 
        WHERE id = ? AND created_by = ?
    ");
    $stmt->execute([$electionId, $_SESSION['admin_id']]);
    $election = $stmt->fetch();

    if (!$election) {
        throw new Exception("Election not found or access denied");
    }

    // Define allowed transitions
    $allowedTransitions = [
        'draft' => ['ongoing'],
        'ongoing' => ['paused', 'completed'],
        'paused' => ['ongoing']
    ];

    // Validate transition
    if (!isset($allowedTransitions[$election['status']]) ||
        !in_array($newStatus, $allowedTransitions[$election['status']])) {
        throw new Exception("Invalid status transition from {$election['status']} to {$newStatus}");
    }

    // Special validation for completion
    if ($newStatus === 'completed') {
        $now = new DateTime();
        $endDate = new DateTime($election['end_time']);

        if ($now < $endDate) {
            throw new Exception("Cannot complete election before end date");
        }
    }

    // Update status
    $updateStmt = $pdo->prepare("
        UPDATE elections 
        SET status = ?, status_changed_at = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$newStatus, $electionId]);

    // If starting election, send verification codes
    if ($newStatus === 'ongoing' && $election['status'] === 'draft') {
        // Get all eligible students
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
            $mail->isSMTP();
            // [Your SMTP configuration here...]

            $successCount = 0;

            foreach ($students as $student) {
                try {
                    $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $code_stmt->execute([$student['student_id'], $electionId, $code]);

                    $mail->clearAddresses();
                    $mail->addAddress($student['email'], $student['first_name'].' '.$student['last_name']);
                    $mail->Subject = 'Your Election Verification Code';
                    $mail->Body = "
                        <h2>Election Verification Code</h2>
                        <p>Hello {$student['first_name']},</p>
                        <p>Your verification code for the election <strong>{$election['title']}</strong> is:</p>
                        <h3 style='color: #007bff;'>{$code}</h3>
                        <p>This code will expire in 24 hours.</p>
                        <p>Thank you,<br>Election Committee</p>
                    ";
                    $mail->send();
                    $successCount++;
                    usleep(200000); // 0.2 second delay
                } catch (Exception $e) {
                    error_log("Failed to send to {$student['email']}: " . $e->getMessage());
                    continue;
                }
            }
        }
    }

    // Log the status change
    $logStmt = $pdo->prepare("
        INSERT INTO election_status_logs 
        (election_id, admin_id, old_status, new_status) 
        VALUES (?, ?, ?, ?)
    ");
    $logStmt->execute([
        $electionId,
        $_SESSION['admin_id'],
        $election['status'],
        $newStatus
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Election status updated to {$newStatus}",
        'new_status' => $newStatus
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}