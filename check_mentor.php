<?php
require_once 'database.php';

// First, let's check if the role column exists
$check_column = "SHOW COLUMNS FROM users LIKE 'role'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // Add role column if it doesn't exist
    $add_column = "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'student'";
    if ($conn->query($add_column)) {
        echo "Added role column successfully.<br>";
    } else {
        echo "Error adding role column: " . $conn->error . "<br>";
    }
}

// Update mentor's role
$update_mentor = "UPDATE users SET role = 'mentor' WHERE username = 'mentor'";
if ($conn->query($update_mentor)) {
    echo "Updated mentor's role successfully.<br>";
} else {
    echo "Error updating mentor's role: " . $conn->error . "<br>";
}

// Verify mentor's role
$check_mentor = "SELECT username, role FROM users WHERE username = 'mentor'";
$result = $conn->query($check_mentor);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Current mentor settings:<br>";
    echo "Username: " . $row['username'] . "<br>";
    echo "Role: " . $row['role'] . "<br>";
} else {
    echo "Mentor account not found.<br>";
}

$conn->close();
?> 