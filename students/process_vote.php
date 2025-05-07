<?php
global $pdo;
require '../config/db.php';
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


session_start();

// Check authentication
if (!isset($_SESSION['student_id']) || $_SESSION['voting_allowed'] !== true || $_SESSION['otp_verified'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$student_id = $_SESSION['student_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['votes'])) {
    $votes = $_POST['votes'];
    $current_time = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();

        // Get student info and check if already voted
        $stmt = $pdo->prepare("SELECT email, CONCAT(first_name, ' ', last_name) as name, has_voted 
                              FROM students WHERE student_id = ? FOR UPDATE");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) throw new Exception("Student not found");
        if ($student['has_voted']) throw new Exception("You have already voted.");

        // Get all position rules for the elections being voted on
        $election_ids = array_keys($votes);
        $placeholders = implode(',', array_fill(0, count($election_ids), '?'));

        $stmt = $pdo->prepare("SELECT p.id, p.max_winners, p.name as position_name, c.election_id
                                FROM positions p
                                JOIN candidates c ON p.id = c.position_id
                                WHERE c.election_id IN ($placeholders)
                                GROUP BY p.id, p.max_winners, p.name, c.election_id");
        $stmt->execute($election_ids);
        $positionRules = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $positionRules[$row['id']] = $row;
        }

        $receipt_data = [
            'student_name' => $student['name'],
            'vote_date' => $current_time,
            'votes' => []
        ];

        // Process each vote
        foreach ($votes as $election_id => $positions) {
            foreach ($positions as $position_id => $candidate_data) {
                if (!isset($positionRules[$position_id])) {
                    throw new Exception("Invalid position selected");
                }

                $maxWinners = $positionRules[$position_id]['max_winners'];
                $position_name = $positionRules[$position_id]['position_name'];

                if (is_array($candidate_data)) {
                    // Multi-winner position
                    if (count($candidate_data) > $maxWinners) {
                        throw new Exception("Too many selections for $position_name (max: $maxWinners)");
                    }

                    foreach ($candidate_data as $candidate_id) {
                        insertVote($pdo, $student_id, $candidate_id, $election_id, $position_id, $current_time);

                        // Add to receipt
                        $candidate_name = getCandidateName($pdo, $candidate_id);
                        $receipt_data['votes'][] = [
                            'position' => $position_name,
                            'candidate' => $candidate_name
                        ];
                    }
                } else {
                    // Single-winner position
                    if ($maxWinners != 1) {
                        throw new Exception("Invalid selection for $position_name");
                    }

                    insertVote($pdo, $student_id, $candidate_data, $election_id, $position_id, $current_time);

                    // Add to receipt
                    $candidate_name = getCandidateName($pdo, $candidate_data);
                    $receipt_data['votes'][] = [
                        'position' => $position_name,
                        'candidate' => $candidate_name
                    ];
                }
            }
        }

        // Mark student as voted
        $stmt = $pdo->prepare("UPDATE students SET has_voted = 1, last_voted_at = ? WHERE student_id = ?");
        $stmt->execute([$current_time, $student_id]);



        $stmt = $pdo->prepare("UPDATE otps SET used = 1, used_at = ?  WHERE student_id = ?");
        $stmt->execute([$current_time, $student_id]);


        // Update session
        $_SESSION['has_voted'] = true;
        $_SESSION['last_voted_at'] = $current_time;

        // Send receipt email (in background if possible)
        if (!empty($student['email'])) {
            sendVoteReceipt($student['email'], $student['name'], $receipt_data);
        }

        $pdo->commit();

        $response = [
            'success' => true,
            'message' => 'Your vote has been successfully recorded!',
            'redirect' => 'thank_you.php'
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Voting error - Student: $student_id, Error: " . $e->getMessage());
        $response = [
            'success' => false,
            'message' => 'Failed to process your vote: ' . $e->getMessage()
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid request method or missing vote data'
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
exit();

function insertVote($pdo, $student_id, $candidate_id, $election_id, $position_id, $voted_at) {
    // Validate candidate exists and belongs to position/election
    $stmt = $pdo->prepare("SELECT 1 FROM candidates 
                          WHERE id = ? AND position_id = ? AND election_id = ?");
    $stmt->execute([$candidate_id, $position_id, $election_id]);
    if (!$stmt->fetchColumn()) {
        throw new Exception("Invalid candidate selection");
    }

    // Insert vote
    $stmt = $pdo->prepare("INSERT INTO votes 
                          (student_id, candidate_id, election_id, position_id, voted_at) 
                          VALUES (?, ?, ?, ?, ?)");
    if (!$stmt->execute([$student_id, $candidate_id, $election_id, $position_id, $voted_at])) {
        throw new Exception("Failed to record vote");
    }
}

function getCandidateName($pdo, $candidate_id) {
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name 
                          FROM candidates c
                          JOIN students s ON c.student_id = s.student_id
                          WHERE c.id = ?");
    $stmt->execute([$candidate_id]);
    return $stmt->fetchColumn();
}


/**
 * Send vote confirmation email
 */
function sendVoteReceipt($email, $name, $receipt_data) {
    $mail = new PHPMailer(true); // Fixed the instantiation here

    try {
        // Server settings
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

        $mail->addAddress($email, $name);

        // Content
        $mail->Subject = 'Your Voting Receipt';

        // Build email body
        $results_link = 'https://online_voting/results.php';

        $mail->Body = '
            <h2>Thank You for Voting!</h2>
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            <p>Here is a record of your votes:</p>
            <ul>';

        foreach ($receipt_data['votes'] as $vote) {
            $mail->Body .= '<li><strong>' . htmlspecialchars($vote['position']) . ':</strong> ' .
                htmlspecialchars($vote['candidate']) . '</li>';
        }

        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
  <style>
    body {
      font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background-color: #f4f6f9;
      color: #333;
      padding: 20px;
    }
    .container {
      max-width: 600px;
      margin: auto;
      background: #ffffff;
      border-radius: 8px;
      padding: 30px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .logo {
      text-align: center;
      margin-bottom: 20px;
    }
    .logo img {
      max-height: 80px;
    }
    h2 {
      color: #2d7dfc;
    }
    ul {
      padding-left: 20px;
    }
    li {
      margin-bottom: 10px;
    }
    .date {
      margin-top: 20px;
      font-size: 14px;
      color: #666;
    }
    .button {
      display: inline-block;
      margin-top: 25px;
      padding: 12px 20px;
      background-color: #2d7dfc;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }
    .footer {
      margin-top: 40px;
      font-size: 12px;
      color: #999;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <img src="https://www.usm.edu.ph/wp-content/uploads/2018/11/usm-seal.png" alt="USM Logo">
    </div>
    <h2>🗳️ Thank You for Voting!</h2>
    <p>Dear ' . htmlspecialchars($name) . ',</p>
    <p>Here is a summary of your votes:</p>
    <ul>';
        foreach ($receipt_data['votes'] as $vote) {
            $mail->Body .= '<li><strong>' . htmlspecialchars($vote['position']) . ':</strong> ' . htmlspecialchars($vote['candidate']) . '</li>';
        }
        $mail->Body .= '
    </ul>
    <p class="date"><strong>Voted on:</strong> ' . htmlspecialchars($receipt_data['vote_date']) . '</p>
    <a href="' . $results_link . '" class="button">View Election Results</a>
    <div class="footer">
      <p>This is an automated message from the <strong>USM Election System</strong>.<br>Please do not reply to this email.</p>
    </div>
  </div>
</body>
</html>';


        $mail->send();
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        // Don't fail the vote if email fails
    }
}