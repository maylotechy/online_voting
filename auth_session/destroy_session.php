<?php
session_start(); // Start the session at the beginning

if (session_destroy()) {
    // Session destroyed successfully
    echo json_encode(array("response" => "success", "message" => "Logged out successfully!"));
} else {
    // Potential error during session destruction (rare but possible)
    echo json_encode(array("response" => "error", "message" => "An error occurred during logout. Please try again."));
}
?>
