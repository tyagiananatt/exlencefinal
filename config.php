<?php
session_start();

// Configuration
define('SITE_NAME', 'Excellence');
define('USER_FILE', 'users.txt');

// Function to add a new user
function addUser($username, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $userData = "$username:$hashedPassword\n";
    file_put_contents(USER_FILE, $userData);  // Overwrite the file
    error_log("Added user: $username");  // Debug log
}

// Simple file-based user authentication
function authenticateUser($username, $password) {
    if (!file_exists(USER_FILE)) {
        error_log("User file does not exist");
        return false;
    }

    $users = file(USER_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if (empty($users)) {
        error_log("No users found in file");
        return false;
    }

    foreach ($users as $user) {
        list($storedUsername, $storedPassword) = explode(':', $user);
        error_log("Checking user: $storedUsername");
        
        if ($username === $storedUsername) {
            if (password_verify($password, $storedPassword)) {
                error_log("Password verified for user: $username");
                return true;
            } else {
                error_log("Invalid password for user: $username");
                return false;
            }
        }
    }
    
    error_log("User not found: $username");
    return false;
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
