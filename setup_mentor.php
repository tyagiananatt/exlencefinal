<?php
require_once 'database.php';

// First, ensure the role column exists
$check_column = "SHOW COLUMNS FROM users LIKE 'role'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // Add role column if it doesn't exist
    $add_column = "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'";
    if (!$conn->query($add_column)) {
        die("Error adding role column: " . $conn->error);
    }
}

// Check if mentor account exists
$check_sql = "SELECT * FROM users WHERE username = 'mentor'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    // Create mentor account if it doesn't exist
    $username = "mentor";
    $password = password_hash("mentor123", PASSWORD_DEFAULT); // Default password: mentor123
    $email = "mentor@exlence.com";
    $role = "mentor";

    $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $password, $email, $role);

    if ($stmt->execute()) {
        echo "Mentor account created successfully!<br>";
        echo "Username: mentor<br>";
        echo "Password: mentor123<br>";
    } else {
        echo "Error creating mentor account: " . $conn->error;
    }
} else {
    // Update existing mentor account to ensure role is set correctly
    $update_sql = "UPDATE users SET role = 'mentor' WHERE username = 'mentor'";
    if ($conn->query($update_sql)) {
        echo "Mentor role updated successfully.<br>";
    } else {
        echo "Error updating mentor role: " . $conn->error;
    }
}

// Create mentor tables if they don't exist
$mentor_queries_sql = "CREATE TABLE IF NOT EXISTS mentor_queries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    question TEXT NOT NULL,
    status ENUM('pending', 'answered') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

$mentor_responses_sql = "CREATE TABLE IF NOT EXISTS mentor_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    query_id INT NOT NULL,
    response TEXT NOT NULL,
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (query_id) REFERENCES mentor_queries(id) ON DELETE CASCADE
)";

if (!$conn->query($mentor_queries_sql)) {
    echo "Error creating mentor_queries table: " . $conn->error . "<br>";
}

if (!$conn->query($mentor_responses_sql)) {
    echo "Error creating mentor_responses table: " . $conn->error . "<br>";
}

$conn->close();
?> 