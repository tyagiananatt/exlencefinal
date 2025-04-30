<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'database.php';

$user_id = $_SESSION["id"];
$message = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_todo'])) {
        $task = trim($_POST['task']);
        $due_date = $_POST['due_date'];
        $priority = $_POST['priority'];
        
        if (!empty($task)) {
            $sql = "INSERT INTO todos (user_id, task, due_date, priority, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $user_id, $task, $due_date, $priority);
            
            if ($stmt->execute()) {
                $message = "Task added successfully!";
            } else {
                $message = "Error adding task.";
            }
        }
    } elseif (isset($_POST['complete_todo'])) {
        $todo_id = $_POST['todo_id'];
        $sql = "UPDATE todos SET completed = 1, completed_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $todo_id, $user_id);
        $stmt->execute();
    } elseif (isset($_POST['delete_todo'])) {
        $todo_id = $_POST['todo_id'];
        $sql = "DELETE FROM todos WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $todo_id, $user_id);
        $stmt->execute();
    }
}

// Fetch todos
$sql = "SELECT * FROM todos WHERE user_id = ? ORDER BY completed ASC, due_date ASC, priority DESC";
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
    <title>Todo List - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4A90E2;
            --secondary-color: #5C6BC0;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --text-color: #333333;
            --gradient-1: linear-gradient(135deg, #4A90E2, #5C6BC0);
            --gradient-2: linear-gradient(135deg, #2ecc71, #26c281);
            --shadow-1: 0 10px 20px rgba(0,0,0,0.1);
            --shadow-2: 0 5px 15px rgba(0,0,0,0.05);
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: url('https://aryan0141.github.io/KaizenTodo/gif.gif') center/cover fixed;
            color: var(--text-color);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            z-index: 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            perspective: 1000px;
            position: relative;
            z-index: 1;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            animation: fadeInDown 0.8s ease-out;
        }

        .page-header h1 {
            font-size: 2.5em;
            margin: 0;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: var(--gradient-1);
            border-radius: 3px;
        }

        .mascot {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 150px;
            height: 150px;
            z-index: 1000;
            pointer-events: none;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--gradient-1);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-2);
            position: relative;
            overflow: hidden;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-1);
        }

        .back-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .back-button:hover::before {
            left: 100%;
        }

        .todo-form {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-1);
            margin-bottom: 40px;
            transform-style: preserve-3d;
            transform: translateZ(0);
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .todo-form:hover {
            transform: translateZ(10px);
        }

        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1em;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
            box-shadow: var(--shadow-2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-1);
        }

        .todo-list {
            perspective: 1000px;
        }

        .todo-item {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            margin-bottom: 15px;
            box-shadow: var(--shadow-2);
            transform-style: preserve-3d;
            transform: translateZ(0);
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease-out;
            animation-fill-mode: both;
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: relative;
            overflow: hidden;
        }

        .todo-item:hover {
            transform: translateY(-5px) translateZ(10px);
            box-shadow: var(--shadow-1);
        }

        .todo-content {
            position: relative;
        }

        .todo-task {
            font-weight: 500;
            font-size: 1.1em;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .todo-meta {
            font-size: 0.9em;
            color: #666;
            display: flex;
            gap: 15px;
        }

        .todo-actions {
            display: flex;
            gap: 10px;
        }

        .completed {
            background: rgba(248, 249, 250, 0.9);
        }

        .completed .todo-task {
            text-decoration: line-through;
            color: #666;
        }

        .priority-high {
            border-left: 4px solid var(--danger-color);
        }

        .priority-medium {
            border-left: 4px solid var(--warning-color);
        }

        .priority-low {
            border-left: 4px solid var(--success-color);
        }

        .priority-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .priority-high .priority-badge {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .priority-medium .priority-badge {
            background: rgba(241, 196, 15, 0.1);
            color: var(--warning-color);
        }

        .priority-low .priority-badge {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .message {
            background: var(--gradient-2);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            position: fixed;
            top: 20px;
            right: 20px;
            box-shadow: var(--shadow-1);
            animation: slideInRight 0.5s ease-out, fadeOut 0.5s ease-out 2.5s forwards;
            z-index: 1000;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
            animation: fadeIn 0.5s ease-out;
        }

        .empty-state img {
            width: 200px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .form-row {
                grid-template-columns: 1fr;

            }

            .todo-item {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .todo-actions {
                justify-content: flex-end;
            }

            .mascot {
                width: 100px;
                height: 100px;
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
            <h1>My Todo List</h1>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form class="todo-form" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="task">Task</label>
                    <input type="text" id="task" name="task" class="form-control" placeholder="What needs to be done?" required>
                </div>
                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-control" required>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_todo" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add Task
            </button>
        </form>

        <div class="todo-list">
            <?php 
            $delay = 0;
            $has_todos = false;
            while ($todo = $result->fetch_assoc()): 
                $has_todos = true;
                $delay += 0.1;
            ?>
                <div class="todo-item <?php echo $todo['completed'] ? 'completed' : ''; ?> priority-<?php echo $todo['priority']; ?>" 
                     style="animation-delay: <?php echo $delay; ?>s">
                    <div class="todo-content">
                        <div class="todo-task"><?php echo htmlspecialchars($todo['task']); ?></div>
                        <div class="todo-meta">
                            <div class="due-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('M d, Y', strtotime($todo['due_date'])); ?>
                            </div>
                            <div class="priority-badge">
                                <?php 
                                $icon = '';
                                switch($todo['priority']) {
                                    case 'high':
                                        $icon = 'exclamation-circle';
                                        break;
                                    case 'medium':
                                        $icon = 'dot-circle';
                                        break;
                                    case 'low':
                                        $icon = 'check-circle';
                                        break;
                                }
                                ?>
                                <i class="fas fa-<?php echo $icon; ?>"></i>
                                <?php echo ucfirst($todo['priority']); ?> Priority
                            </div>
                        </div>
                    </div>
                    <div class="todo-actions">
                        <?php if (!$todo['completed']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                                <button type="submit" name="complete_todo" class="btn btn-success">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                            <button type="submit" name="delete_todo" class="btn btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; 
            
            if (!$has_todos): ?>
                <div class="empty-state">
                    <img src="https://cdn-icons-png.flaticon.com/512/6195/6195678.png" alt="No tasks">
                    <h3>No tasks yet!</h3>
                    <p>Add your first task using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- <img src="https://cdn-icons-png.flaticon.com/512/1791/1791961.png" alt="Todo Mascot" class="mascot" style="animation: float 3s ease-in-out infinite;"> -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date for due date input to today
            const dueDateInput = document.getElementById('due_date');
            const today = new Date().toISOString().split('T')[0];
            dueDateInput.min = today;

            // Add loading animation to buttons when clicked
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function() {
                    if (this.form && !this.form.checkValidity()) return;
                    this.classList.add('loading');
                });
            });

            // Add hover effects
            document.querySelectorAll('.todo-item').forEach(item => {
                item.addEventListener('mouseover', function() {
                    this.style.transform = 'translateY(-5px) translateZ(20px)';
                });
                item.addEventListener('mouseout', function() {
                    this.style.transform = 'translateY(0) translateZ(0)';
                });
            });
        });
    </script>
</body>
</html> 