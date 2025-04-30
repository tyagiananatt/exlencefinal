<?php
require_once 'database.php';

$messages = [];

// Create todos table
$todos_sql = "CREATE TABLE IF NOT EXISTS todos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    task VARCHAR(255) NOT NULL,
    due_date DATE NOT NULL,
    priority ENUM('high', 'medium', 'low') NOT NULL DEFAULT 'medium',
    completed BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    if ($conn->query($todos_sql)) {
        $messages[] = "Todos table created successfully!";
    }
} catch (Exception $e) {
    $messages[] = "Error creating todos table: " . $e->getMessage();
}

// Create timetable_events table
$events_sql = "CREATE TABLE IF NOT EXISTS timetable_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    if ($conn->query($events_sql)) {
        $messages[] = "Timetable events table created successfully!";
    }
} catch (Exception $e) {
    $messages[] = "Error creating timetable_events table: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Tables - ExLence</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f0f2f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #4A90E2;
            margin-bottom: 20px;
        }
        .message {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background: #e8f5e9;
            color: #2e7d32;
        }
        .error {
            background: #ffebee;
            color: #c62828;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4A90E2;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Setup</h1>
        <?php foreach ($messages as $msg): ?>
            <div class="message <?php echo strpos($msg, 'Error') !== false ? 'error' : ''; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endforeach; ?>
        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html> 