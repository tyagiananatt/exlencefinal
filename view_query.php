<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'database.php';

// Get query ID from URL
$query_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($query_id === 0) {
    header("location: ask_mentor.php");
    exit;
}

// Fetch query details
$sql = "SELECT mq.*, COALESCE(mr.response, '') as mentor_response, mr.responded_at 
        FROM mentor_queries mq 
        LEFT JOIN mentor_responses mr ON mq.id = mr.query_id 
        WHERE mq.id = ? AND mq.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $query_id, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

// If query not found or doesn't belong to user, redirect
if ($result->num_rows === 0) {
    header("location: ask_mentor.php");
    exit;
}

$query = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Query - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4A90E2;
            --primary-dark: #357ABD;
            --secondary-color: #5C6BC0;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --text-color: #333333;
            --gradient-primary: linear-gradient(135deg, #4A90E2 0%, #5C6BC0 100%);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f6f8fd 0%, #f1f4f9 100%);
            color: var(--text-color);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--gradient-primary);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .query-details {
            background: var(--card-background);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }

        .query-subject {
            font-size: 1.5em;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .query-meta {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .query-content {
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-pending {
            background: rgba(241, 196, 15, 0.1);
            color: var(--warning-color);
        }

        .status-answered {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .mentor-response {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
        }

        .mentor-response-header {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .response-content {
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .response-date {
            color: #666;
            font-size: 0.9em;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .query-details {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="ask_mentor.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Queries
        </a>

        <div class="query-details">
            <div class="query-subject"><?php echo htmlspecialchars($query['subject']); ?></div>
            
            <div class="query-meta">
                <span>
                    <i class="fas fa-calendar"></i>
                    Asked on <?php echo date('M d, Y', strtotime($query['created_at'])); ?>
                </span>
                <span class="status-badge <?php echo $query['mentor_response'] ? 'status-answered' : 'status-pending'; ?>">
                    <i class="fas <?php echo $query['mentor_response'] ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                    <?php echo $query['mentor_response'] ? 'Answered' : 'Pending'; ?>
                </span>
            </div>

            <div class="query-content">
                <?php echo nl2br(htmlspecialchars($query['question'])); ?>
            </div>

            <?php if ($query['mentor_response']): ?>
                <div class="mentor-response">
                    <div class="mentor-response-header">
                        <i class="fas fa-comment-dots"></i>
                        Mentor's Response
                    </div>
                    <div class="response-content">
                        <?php echo nl2br(htmlspecialchars($query['mentor_response'])); ?>
                    </div>
                    <div class="response-date">
                        Answered on <?php echo date('M d, Y', strtotime($query['responded_at'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 