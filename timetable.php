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
    if (isset($_POST['add_event'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $event_date = $_POST['event_date'];
        
        // Handle empty time fields
        $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        
        if (!empty($title)) {
            // Validate that if one time is set, both must be set
            if (($start_time && !$end_time) || (!$start_time && $end_time)) {
                $message = "Please provide both start and end time, or leave both empty.";
            } else {
                if ($start_time === null && $end_time === null) {
                    // If both times are null, use a different SQL query
                    $sql = "INSERT INTO timetable_events (user_id, title, description, event_date, created_at) 
                            VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isss", $user_id, $title, $description, $event_date);
                } else {
                    // If both times are provided
                    $sql = "INSERT INTO timetable_events (user_id, title, description, event_date, start_time, end_time, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isssss", $user_id, $title, $description, $event_date, $start_time, $end_time);
                }
                
                if ($stmt->execute()) {
                    $message = "Event added successfully!";
                } else {
                    $message = "Error adding event: " . $stmt->error;
                }
            }
        }
    } elseif (isset($_POST['delete_event'])) {
        $event_id = $_POST['event_id'];
        $sql = "DELETE FROM timetable_events WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $event_id, $user_id);
        
        if ($stmt->execute()) {
            $message = "Event deleted successfully!";
        } else {
            $message = "Error deleting event.";
        }
    }
}

// Fetch events for the current week
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$end_of_week = date('Y-m-d', strtotime('sunday this week'));

$sql = "SELECT * FROM timetable_events 
        WHERE user_id = ? 
        AND event_date BETWEEN ? AND ? 
        ORDER BY event_date ASC, start_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $start_of_week, $end_of_week);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[date('w', strtotime($row['event_date']))][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable - ExLence</title>
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
            background: url('https://img.freepik.com/free-vector/abstract-background-with-squares_23-2148995948.jpg?w=1380&t=st=1709667144~exp=1709667744~hmac=d9c3725e6b69db8c22a5d0c8de7b4dca9b0c4c6e7f7d0a8e8e0d8e0d8e0d8e0') center/cover fixed;
            color: var(--text-color);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            perspective: 1000px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
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
            transform-style: preserve-3d;
            transform: translateZ(0);
        }

        .back-button:hover {
            transform: translateY(-2px) translateZ(10px);
            box-shadow: var(--shadow-1);
        }

        .timetable-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-1);
            margin-bottom: 40px;
            transform-style: preserve-3d;
            transform: translateZ(0);
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease-out;
            backdrop-filter: blur(10px);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            position: relative;
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
            background: white;
            transition: all 0.3s ease;
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

        .timetable {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .day-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-2);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .day-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: var(--gradient-1);
        }

        .day-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-1);
        }

        .day-header {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .day-header i {
            color: var(--primary-color);
        }

        .event {
            background: rgba(74, 144, 226, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .event:hover {
            transform: translateX(5px);
            background: rgba(74, 144, 226, 0.15);
        }

        .event-title {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        .event-time {
            font-size: 0.9em;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .event-description {
            margin-top: 8px;
            font-size: 0.9em;
            color: #666;
        }

        .event-actions {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 8px 16px;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 12px;
            background: var(--gradient-2);
            color: white;
            box-shadow: var(--shadow-1);
            animation: slideInRight 0.5s ease-out, fadeOut 0.5s ease-out 2.5s forwards;
            z-index: 1000;
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

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateY(-20px);
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

        .empty-day {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        .day-icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .day-card {
                margin-bottom: 20px;
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
            <h1>Weekly Schedule</h1>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form class="timetable-form" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Event Title</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="What's happening?" required>
                </div>
                <div class="form-group">
                    <label for="event_date">Date</label>
                    <input type="date" id="event_date" name="event_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="start_time">Start Time (Optional)</label>
                    <input type="time" id="start_time" name="start_time" class="form-control">
                </div>
                <div class="form-group">
                    <label for="end_time">End Time (Optional)</label>
                    <input type="time" id="end_time" name="end_time" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Add some details..."></textarea>
            </div>
            <button type="submit" name="add_event" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add Event
            </button>
        </form>

        <div class="timetable">
            <?php
            $days = [
                'Sunday' => 'sun',
                'Monday' => 'calendar-day',
                'Tuesday' => 'calendar-day',
                'Wednesday' => 'calendar-day',
                'Thursday' => 'calendar-day',
                'Friday' => 'calendar-day',
                'Saturday' => 'calendar-week'
            ];

            $currentDate = new DateTime();
            $currentWeekStart = clone $currentDate;
            $currentWeekStart->modify('last sunday');

            $i = 0;
            foreach ($days as $day => $icon):
                $date = clone $currentWeekStart;
                $date->modify("+$i days");
                $dateStr = $date->format('Y-m-d');
            ?>
                <div class="day-card">
                    <div class="day-header">
                        <i class="far fa-<?php echo $icon; ?>"></i>
                        <?php echo $day; ?>
                        <small style="margin-left: auto; color: #666;">
                            <?php echo $date->format('M d'); ?>
                        </small>
                    </div>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM timetable_events WHERE user_id = ? AND event_date = ? ORDER BY start_time");
                    $stmt->bind_param("is", $user_id, $dateStr);
                    $stmt->execute();
                    $events = $stmt->get_result();
                    $hasEvents = false;

                    while ($event = $events->fetch_assoc()):
                        $hasEvents = true;
                        $has_time = !empty($event['start_time']) && !empty($event['end_time']);
                        $start = $has_time ? date('g:i A', strtotime($event['start_time'])) : '';
                        $end = $has_time ? date('g:i A', strtotime($event['end_time'])) : '';
                    ?>
                        <div class="event">
                            <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                            <?php if ($has_time): ?>
                            <div class="event-time">
                                <i class="far fa-clock"></i>
                                <?php echo $start; ?> - <?php echo $end; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($event['description']): ?>
                                <div class="event-description"><?php echo htmlspecialchars($event['description']); ?></div>
                            <?php endif; ?>
                            <div class="event-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" name="delete_event" class="btn btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; 
                    
                    if (!$hasEvents): ?>
                        <div class="empty-day">
                            <i class="far fa-calendar-plus"></i>
                            <p>No events scheduled</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php 
                $i++;
            endforeach; 
            ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date for event date input to today
            const eventDateInput = document.getElementById('event_date');
            const today = new Date().toISOString().split('T')[0];
            eventDateInput.min = today;

            // Add validation for time inputs
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');

            function validateTime() {
                if (startTimeInput.value && !endTimeInput.value) {
                    endTimeInput.setCustomValidity('Please provide an end time');
                } else if (!startTimeInput.value && endTimeInput.value) {
                    startTimeInput.setCustomValidity('Please provide a start time');
                } else {
                    startTimeInput.setCustomValidity('');
                    endTimeInput.setCustomValidity('');
                }
            }

            startTimeInput.addEventListener('input', validateTime);
            endTimeInput.addEventListener('input', validateTime);

            // Add loading animation to buttons when clicked
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function() {
                    if (this.form && !this.form.checkValidity()) return;
                    this.classList.add('loading');
                });
            });

            // Add hover effects
            document.querySelectorAll('.day-card').forEach(card => {
                card.addEventListener('mouseover', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseout', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html> 