<?php

session_start();
require_once "../middleware/auth_student.php";

// If already agreed to terms, redirect to elections
if (isset($_SESSION['terms_accepted'])) {
    header('Location: voting_page.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agree'])) {
        $_SESSION['terms_accepted'] = true;
        header('Location: voting_page.php');
        exit();
    } else {
        // Log out if they don't agree
        session_destroy();
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms and Conditions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .terms-container {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .terms-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            text-align: center;
        }

        .terms-title {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .terms-content {
            padding: 30px;
            max-height: 400px;
            overflow-y: auto;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .terms-footer {
            padding: 20px 30px;
            background-color: var(--light);
            text-align: center;
        }

        .btn-agree {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            padding: 10px 30px;
            font-weight: 600;
            color: white;
            border-radius: 8px;
            margin-right: 10px;
        }

        .btn-disagree {
            background: white;
            border: 1px solid var(--primary);
            padding: 10px 30px;
            font-weight: 600;
            color: var(--primary);
            border-radius: 8px;
        }

        /* Custom scrollbar */
        .terms-content::-webkit-scrollbar {
            width: 8px;
        }

        .terms-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .terms-content::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="terms-container">
    <div class="terms-header">
        <h1 class="terms-title"><i class="fas fa-file-contract me-2"></i> Election Terms and Conditions</h1>
        <p>Please read and agree to the terms before proceeding to vote</p>
    </div>

    <div class="terms-content">
        <h4>1. Voting Rules</h4>
        <p>By participating in this election, you agree to abide by the following rules:</p>
        <ul>
            <li>Each student is allowed to vote only once per election.</li>
            <li>Votes cannot be changed once submitted.</li>
            <li>Attempting to vote multiple times will result in disqualification.</li>
            <li>Voting is confidential - your selections will not be tied to your identity in the results.</li>
        </ul>

        <h4 class="mt-4">2. Code of Conduct</h4>
        <p>All voters must adhere to the university's code of conduct:</p>
        <ul>
            <li>No campaigning or influencing other voters within the voting area.</li>
            <li>No sharing of login credentials or attempting to vote on behalf of others.</li>
            <li>No use of offensive language in candidate platform reviews.</li>
        </ul>

        <h4 class="mt-4">3. Data Privacy</h4>
        <p>Your personal information will be handled according to our privacy policy:</p>
        <ul>
            <li>We collect only necessary information to verify your eligibility to vote.</li>
            <li>Your vote selections are stored separately from your personal information.</li>
            <li>Results will be published in aggregate form only.</li>
        </ul>

        <h4 class="mt-4">4. Consequences of Violations</h4>
        <p>Violation of these terms may result in:</p>
        <ul>
            <li>Disqualification of your vote</li>
            <li>Temporary or permanent suspension of voting privileges</li>
            <li>Disciplinary action according to university policies</li>
        </ul>

        <div class="alert alert-primary mt-4">
            <i class="fas fa-info-circle me-2"></i>
            You can download the complete election rules document after proceeding to the voting page.
        </div>
    </div>

    <div class="terms-footer">
        <form method="POST">
            <button type="submit" name="agree" value="1" class="btn btn-agree">
                <i class="fas fa-check-circle me-2"></i> I Agree
            </button>
            <button type="submit" name="disagree" value="1" class="btn btn-disagree">
                <i class="fas fa-times-circle me-2"></i> I Disagree
            </button>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>