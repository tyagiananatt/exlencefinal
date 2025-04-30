<?php
session_start();
require_once 'database.php';

if (isset($_SESSION["login_record_id"]) && isset($_SESSION["id"])) {
    try {
        $logout_time = date('Y-m-d H:i:s');
        $login_record_id = $_SESSION["login_record_id"];
        
        // Update the time log with logout time and calculate time spent
        $sql = "UPDATE user_time_logs SET 
                logout_time = ?, 
                time_spent = TIMESTAMPDIFF(SECOND, login_time, ?) 
                WHERE id = ?";
                
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $logout_time, $logout_time, $login_record_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->close();
    } catch (Exception $e) {
        // Log error if needed
    }
}

// Destroy the session
session_destroy();

// Redirect to login page
header("location: login.php");
exit;
