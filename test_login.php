<?php
session_start();
require_once 'database.php';

// Test login with mentor credentials
$username = "mentor";
$password = "mentor123";

// Prepare a select statement
$sql = "SELECT id, username, password, role FROM users WHERE username = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        if (password_verify($password, $row['password'])) {
            echo "Login successful!<br>";
            echo "User ID: " . $row['id'] . "<br>";
            echo "Username: " . $row['username'] . "<br>";
            echo "Role: " . $row['role'] . "<br>";
            
            // Set session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            echo "<br>Session variables:<br>";
            echo "loggedin: " . ($_SESSION['loggedin'] ? 'true' : 'false') . "<br>";
            echo "id: " . $_SESSION['id'] . "<br>";
            echo "username: " . $_SESSION['username'] . "<br>";
            echo "role: " . $_SESSION['role'] . "<br>";
        } else {
            echo "Invalid password<br>";
        }
    } else {
        echo "No user found with username: " . $username . "<br>";
    }
    
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error . "<br>";
}

$conn->close();
?> 