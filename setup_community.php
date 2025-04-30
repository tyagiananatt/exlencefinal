<?php
require_once 'database.php';

// Create community_posts table
$sql_posts = "CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

// Create community_replies table
$community_replies_sql = "CREATE TABLE IF NOT EXISTS community_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

try {
    // Execute the queries
    if ($conn->query($sql_posts) === TRUE) {
        echo "community_posts table created successfully\n";
    } else {
        echo "Error creating community_posts table: " . $conn->error . "\n";
    }

    if ($conn->query($community_replies_sql)) {
        echo "community_replies table created successfully<br>";
    } else {
        echo "Error creating community_replies table: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 