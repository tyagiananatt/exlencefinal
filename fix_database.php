<?php
require_once 'db_connection.php';

// Check if votes column exists
$check_column = "SHOW COLUMNS FROM community_posts LIKE 'votes'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // Add votes column if it doesn't exist
    $add_column = "ALTER TABLE community_posts ADD COLUMN votes INT DEFAULT 0";
    if ($conn->query($add_column) === TRUE) {
        echo "Added votes column successfully<br>";
    } else {
        echo "Error adding votes column: " . $conn->error . "<br>";
    }
} else {
    echo "Votes column already exists<br>";
}

// Show table structure
echo "<h3>Current community_posts table structure:</h3>";
$show_structure = "DESCRIBE community_posts";
$result = $conn->query($show_structure);
if ($result) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
    echo "</pre>";
}

// Show sample data
echo "<h3>Sample data in community_posts:</h3>";
$show_data = "SELECT * FROM community_posts LIMIT 5";
$result = $conn->query($show_data);
if ($result) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
    echo "</pre>";
}

$conn->close();
echo "<br><a href='community.php'>Go back to Community Page</a>";
?> 