<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'database.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$course_id = $data['course_id'] ?? null;
$time_spent = $data['time_spent'] ?? 0; // Time in seconds

if (!$course_id || !is_numeric($time_spent)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $user_id = $_SESSION["id"];
    
    // First, check if the user is enrolled in the course
    $sql = "SELECT 1 FROM user_courses WHERE user_id = ? AND course_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'User not enrolled in this course']);
            exit;
        }
        $stmt->close();
    }
    
    // Update or insert course progress
    $sql = "INSERT INTO course_progress (user_id, course_id, time_spent, last_accessed) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            time_spent = time_spent + VALUES(time_spent),
            last_accessed = NOW()";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iii", $user_id, $course_id, $time_spent);
        $stmt->execute();
        $stmt->close();
        
        // Get updated total time for this course
        $sql = "SELECT time_spent FROM course_progress WHERE user_id = ? AND course_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $user_id, $course_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $total_time = $row['time_spent'];
            
            $hours = floor($total_time / 3600);
            $minutes = floor(($total_time % 3600) / 60);
            
            echo json_encode([
                'success' => true,
                'message' => 'Time tracked successfully',
                'total_time' => [
                    'hours' => $hours,
                    'minutes' => $minutes,
                    'seconds' => $total_time
                ]
            ]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?> 