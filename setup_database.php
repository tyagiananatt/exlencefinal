<?php
// Connect to MySQL without selecting a database
$conn = new mysqli('localhost', 'root', '');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS cipherthon";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db('cipherthon');

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Drop existing community_posts table if it exists
$sql = "DROP TABLE IF EXISTS community_replies";
$conn->query($sql);
$sql = "DROP TABLE IF EXISTS community_posts";
$conn->query($sql);

// Create community_posts table with votes column
$sql = "CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Community posts table created successfully<br>";
} else {
    echo "Error creating community posts table: " . $conn->error . "<br>";
}

// Create community_replies table
$sql = "CREATE TABLE IF NOT EXISTS community_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Community replies table created successfully<br>";
} else {
    echo "Error creating community replies table: " . $conn->error . "<br>";
}

// Add some sample data if the tables are empty
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Add a sample user
    $password_hash = password_hash('test123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password, email) VALUES ('testuser', '$password_hash', 'test@example.com')";
    if ($conn->query($sql) === TRUE) {
        echo "Sample user created successfully<br>";
        
        // Add a sample question
        $user_id = $conn->insert_id;
        $sql = "INSERT INTO community_posts (user_id, title, content, votes) VALUES 
            ($user_id, 'Welcome to our Community!', 'This is a sample question to get our community started. Feel free to ask your own questions!', 0)";
        if ($conn->query($sql) === TRUE) {
            echo "Sample question created successfully<br>";
        }
    }
}

$conn->close();

echo "<br>Setup completed! You can now use the community features.";
echo "<br><a href='community.php'>Go to Community Page</a>";
?> 