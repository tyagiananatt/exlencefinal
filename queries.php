<?php
session_start();

// Check if user is logged in and has mentor role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "mentor") {
    header("location: login.php");
    exit;
}

require_once 'database.php';

$success_message = '';
$error_message = '';

// Handle response submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_response'])) {
    $query_id = $_POST['query_id'];
    $response = trim($_POST['response']);
    
    if (!empty($response)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert response
            $response_sql = "INSERT INTO mentor_responses (query_id, response, responded_at) VALUES (?, ?, NOW())";
            $response_stmt = $conn->prepare($response_sql);
            $response_stmt->bind_param("is", $query_id, $response);
            $response_stmt->execute();
            
            // Update query status
            $update_sql = "UPDATE mentor_queries SET status = 'answered' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $query_id);
            $update_stmt->execute();
            
            $conn->commit();
            $success_message = "Response submitted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error submitting response: " . $e->getMessage();
        }
    } else {
        $error_message = "Please enter a response.";
    }
}

// Fetch all queries with user information
$sql = "SELECT mq.*, u.username, COALESCE(mr.response, '') as mentor_response, mr.responded_at 
        FROM mentor_queries mq 
        INNER JOIN users u ON mq.user_id = u.id 
        LEFT JOIN mentor_responses mr ON mq.id = mr.query_id 
        ORDER BY mq.status ASC, mq.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Queries - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #e74c3c; /* Red color for logout */
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
            background: #c0392b; /* Darker red on hover */
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

        .queries-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .query-card {
            background: var(--card-background);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s ease;
        }

        .query-card:hover {
            transform: translateY(-5px);
        }

        .query-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .query-subject {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.2em;
        }

        .query-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .query-text {
            color: var(--text-color);
            margin-bottom: 20px;
            line-height: 1.6;
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

        .response-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 20px;
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
            resize: vertical;
            min-height: 100px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
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
            width: 100%;
            justify-content: center;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
        }

        .filter-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--card-background);
            color: var(--text-color);
            box-shadow: var(--shadow-sm);
        }

        .filter-btn.active {
            background: var(--gradient-primary);
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .queries-grid {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 2em;
            }

            .filters {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="logout.php" class="back-button">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>

        <div class="page-header">
            <h1>Student Queries</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="notification success" id="notification"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="notification error" id="notification"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="filters">
            <button class="filter-btn active" data-filter="all">All Queries</button>
            <button class="filter-btn" data-filter="pending">Pending</button>
            <button class="filter-btn" data-filter="answered">Answered</button>
        </div>

        <div class="queries-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="query-card" data-status="<?php echo $row['mentor_response'] ? 'answered' : 'pending'; ?>">
                    <div class="query-header">
                        <div class="query-subject"><?php echo htmlspecialchars($row['subject']); ?></div>
                        <span class="status-badge <?php echo $row['mentor_response'] ? 'status-answered' : 'status-pending'; ?>">
                            <?php echo $row['mentor_response'] ? 'Answered' : 'Pending'; ?>
                        </span>
                    </div>

                    <div class="query-meta">
                        <span>
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($row['username']); ?>
                        </span>
                        <span>
                            <i class="fas fa-clock"></i>
                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                        </span>
                    </div>

                    <div class="query-text"><?php echo htmlspecialchars($row['question']); ?></div>

                    <?php if ($row['mentor_response']): ?>
                        <div class="mentor-response">
                            <div class="mentor-response-header">
                                <i class="fas fa-comment-dots"></i> Your Response
                            </div>
                            <div class="response-text">
                                <?php echo htmlspecialchars($row['mentor_response']); ?>
                            </div>
                            <div class="response-date">
                                Answered on <?php echo date('M d, Y', strtotime($row['responded_at'])); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <form class="response-form" method="POST" action="">
                            <input type="hidden" name="query_id" value="<?php echo $row['id']; ?>">
                            <div class="form-group">
                                <textarea name="response" class="form-control" placeholder="Type your response here..." required></textarea>
                            </div>
                            <button type="submit" name="submit_response" class="btn-submit">
                                <i class="fas fa-paper-plane"></i>
                                Submit Response
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
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

            // Filter functionality
            const filterButtons = document.querySelectorAll('.filter-btn');
            const queryCards = document.querySelectorAll('.query-card');

            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const filter = button.dataset.filter;
                    
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    
                    // Filter cards
                    queryCards.forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html> 