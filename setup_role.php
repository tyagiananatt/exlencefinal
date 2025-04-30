<?php
require_once 'database.php';

// Add role column to users table if it doesn't exist
$check_column_sql = "SHOW COLUMNS FROM users LIKE 'role'";
$result = $conn->query($check_column_sql);

if ($result->num_rows == 0) {
    $alter_sql = "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'student'";
    if ($conn->query($alter_sql)) {
        echo "Role column added successfully to users table.<br>";
    } else {
        echo "Error adding role column: " . $conn->error . "<br>";
    }
} else {
    echo "Role column already exists in users table.<br>";
}

$conn->close();
?> 