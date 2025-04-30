<?php
session_start();
require '../config/db.php';
header('Content-Type: application/json');

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

global $pdo;

try {
    // Check if the required POST data exists
    if (isset($_POST["email"]) && isset($_POST["password"])) {
        $email = trim($_POST["email"]);
        $password = $_POST["password"];

        // Prepare and execute the query (fixed SQL syntax: added '=')
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role_id = 1");
        $stmt->bindParam(":email", $email);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $hashed_password = $row['password_hash'];

                // Verify the password using password_verify()
                if (password_verify($password, $hashed_password)) {
                    // Set the session variable and regenerate the session ID for security
                    $_SESSION['admin_id'] = $row['id'];
                    session_regenerate_id(true); // Prevent session fixation attacks
                    echo json_encode(array( "response" => "success",
                            "message" => "Successfully logged in")
                    );
                } else {
                    echo json_encode(array( "response" => "error",
                            "message" => "Invalid username/email or password")
                    );
                }
            } else {
                echo json_encode(array( "response" => "error",
                    "message" => "Invalid username/email or password")
                );
            }
        } else {
            echo json_encode(array( "response" => "error",
                    "message" => "Database error")
            );
        }
    } else {
        echo json_encode(array( "response" => "error",
                "message" => "Username and password are required")
        );
    }
} catch (PDOException $e) {
    // Log the error internally (e.g., to a file)
    error_log("Database error: " . $e->getMessage());

    // Output a generic error message
    echo json_encode([
        'response' => 'error',
        'message' => 'A database error occurred. Please try again later.'
    ]);
}
