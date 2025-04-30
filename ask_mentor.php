<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'database.php';

$user_id = $_SESSION["id"];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_question'])) {
    $question = trim($_POST['question']);
    $subject = trim($_POST['subject']);
    
    if (!empty($question) && !empty($subject)) {
        $sql = "INSERT INTO mentor_queries (user_id, subject, question, created_at, status) VALUES (?, ?, ?, NOW(), 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $subject, $question);
        
        if ($stmt->execute()) {
            $success_message = "Your question has been submitted successfully!";
        } else {
            $error_message = "Error submitting your question. Please try again.";
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}

// Fetch user's previous questions
$sql = "SELECT mq.*, COALESCE(mr.response, '') as mentor_response, mr.responded_at 
        FROM mentor_queries mq 
        LEFT JOIN mentor_responses mr ON mq.id = mr.query_id 
        WHERE mq.user_id = ? 
        ORDER BY mq.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask a Mentor - ExLence</title>
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
            max-width: 1000px;
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

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .page-header h1 {
            font-size: 2.5em;
            margin: 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .page-header h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 3px;
        }

        .question-form {
            background: var(--card-background);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 40px;
            transition: transform 0.3s ease;
        }

        .question-form:hover {
            transform: translateY(-5px);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1em;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .previous-questions {
            margin-top: 40px;
        }

        .question-card {
            background: var(--card-background);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.3s ease;
        }

        .question-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .question-subject {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .question-text {
            color: var(--text-color);
            margin-bottom: 15px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .question-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            font-size: 0.9em;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
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
            padding: 15px;
            border-radius: 12px;
            margin-top: 15px;
        }

        .mentor-response-header {
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 8px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 12px;
            color: white;
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: var(--success-color);
        }

        .notification.error {
            background: var(--danger-color);
        }

        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--gradient-primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .card-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .page-header h1 {
                font-size: 2em;
            }

            .question-form {
                padding: 20px;
            }

            .btn-submit {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="page-header">
            <h1>Ask a Mentor</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="notification success" id="notification"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="notification error" id="notification"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="question-form">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="Enter the subject of your question" required>
                </div>
                <div class="form-group">
                    <label for="question">Your Question</label>
                    <textarea id="question" name="question" class="form-control" placeholder="Type your question here..." required></textarea>
                </div>
                <button type="submit" name="submit_question" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Submit Question
                </button>
            </form>
        </div>

        <div class="previous-questions">
            <h2>Your Previous Questions</h2>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="question-card">
                        <div class="question-subject"><?php echo htmlspecialchars($row['subject']); ?></div>
                        <div class="question-text"><?php echo htmlspecialchars($row['question']); ?></div>
                        
                        <div class="question-meta">
                            <span>Asked on <?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                            <span class="status-badge <?php echo $row['mentor_response'] ? 'status-answered' : 'status-pending'; ?>">
                                <?php echo $row['mentor_response'] ? 'Answered' : 'Pending'; ?>
                            </span>
                        </div>

                        <div class="card-actions">
                            <a href="view_query.php?id=<?php echo $row['id']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="question-card">
                    <p>You haven't asked any questions yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide notifications after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            if (notification) {
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        });
    </script>
</body>
</html> 