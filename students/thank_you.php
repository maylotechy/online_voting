<?php
session_start();
if (!isset($_SESSION['has_voted'])) {
    header('Location: elections.php');
    exit();
}

// Clear voting session flags to prevent replay
unset($_SESSION['has_voted']);
unset($_SESSION['otp_verified']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Voting</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }
        .thank-you-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .icon {
            font-size: 60px;
            color: #27ae60;
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .receipt-box {
            background-color: #f8f9fa;
            border-left: 4px solid #27ae60;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="thank-you-container">
    <div class="icon">✓</div>
    <h1>Your Vote Has Been Recorded!</h1>
    <p>Thank you for participating in our election. Your voice matters!</p>

    <div class="receipt-box">
        <p><strong>Receipt:</strong></p>
        <p>A copy of your votes has been sent to your registered email address.</p>
    </div>

    <p>Election results will be available after voting closes.</p>
    <a href="../students/realtime_results.php" class="btn">View Election Results</a>
    <a href="logout.php" class="btn" style="background-color: #95a5a6;">Logout</a>
</div>
</body>
</html>