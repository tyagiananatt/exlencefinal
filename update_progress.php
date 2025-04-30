<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['course_id']) || !isset($_POST['time_spent'])) {
    http_response_code(400);
    exit;
}

require_once 'database.php';

$user_id = $_SESSION["id"];
$course_id = (int)$_POST['course_id'];
$time_spent = (int)$_POST['time_spent'];

try {
    // Update course progress
    $sql = "UPDATE course_progress 
            SET time_spent = time_spent + ?, 
                last_accessed = NOW(),
                progress_percentage = LEAST(progress_percentage + ?, 100)
            WHERE user_id = ? AND course_id = ?";
    
    $progress_increment = min(5, ceil($time_spent / 60)); // Increase progress by 5% max per session
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $time_spent, $progress_increment, $user_id, $course_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        // If no rows were updated, insert a new record
        $sql = "INSERT INTO course_progress (user_id, course_id, time_spent, progress_percentage, last_accessed) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $initial_progress = min(5, ceil($time_spent / 60));
        $stmt->bind_param("iiii", $user_id, $course_id, $time_spent, $initial_progress);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 