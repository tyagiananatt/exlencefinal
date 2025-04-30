<?php
require_once 'database.php';

// Create mentor_queries table
$mentor_queries_sql = "CREATE TABLE IF NOT EXISTS mentor_queries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    question TEXT NOT NULL,
    status ENUM('pending', 'answered') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

// Create mentor_responses table
$mentor_responses_sql = "CREATE TABLE IF NOT EXISTS mentor_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    query_id INT NOT NULL,
    response TEXT NOT NULL,
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (query_id) REFERENCES mentor_queries(id) ON DELETE CASCADE
)";

try {
    // Execute the queries
    if ($conn->query($mentor_queries_sql) === TRUE) {
        echo "mentor_queries table created successfully<br>";
    } else {
        echo "Error creating mentor_queries table: " . $conn->error . "<br>";
    }

    if ($conn->query($mentor_responses_sql) === TRUE) {
        echo "mentor_responses table created successfully<br>";
    } else {
        echo "Error creating mentor_responses table: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 