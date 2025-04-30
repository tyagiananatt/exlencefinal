<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'database.php';

$user_id = $_SESSION["id"];

// Handle course removal
if (isset($_POST['remove_course'])) {
    $course_id = $_POST['course_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete from user_courses
        $delete_enrollment = $conn->prepare("DELETE FROM user_courses WHERE user_id = ? AND course_id = ?");
        $delete_enrollment->bind_param("ii", $user_id, $course_id);
        $delete_enrollment->execute();
        
        // Delete from course_progress
        $delete_progress = $conn->prepare("DELETE FROM course_progress WHERE user_id = ? AND course_id = ?");
        $delete_progress->bind_param("ii", $user_id, $course_id);
        $delete_progress->execute();
        
        $conn->commit();
        $removal_success = true;
    } catch (Exception $e) {
        $conn->rollback();
        $removal_error = true;
    }
}

// Get user's enrolled courses with progress
try {
    $sql = "SELECT c.*, cp.progress_percentage, cp.time_spent, cp.last_accessed,
            (SELECT SUM(time_spent) FROM course_progress WHERE user_id = ? AND DATE(last_accessed) = CURDATE()) as today_time,
            (SELECT SUM(time_spent) FROM course_progress WHERE user_id = ? AND last_accessed >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as weekly_time,
            (SELECT SUM(time_spent) FROM course_progress WHERE user_id = ? AND YEAR(last_accessed) = YEAR(CURDATE())) as yearly_time
            FROM courses c
            INNER JOIN user_courses uc ON c.id = uc.course_id
            LEFT JOIN course_progress cp ON c.id = cp.course_id AND cp.user_id = ?
            WHERE uc.user_id = ?
            ORDER BY cp.last_accessed DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Get the first row for time statistics
    $first_row = $result->fetch_assoc();
    $today_time = $first_row['today_time'] ?? 0;
    $weekly_time = $first_row['weekly_time'] ?? 0;
    $yearly_time = $first_row['yearly_time'] ?? 0;
    
    // Get daily time spent for the last 7 days
    $daily_sql = "SELECT DATE(last_accessed) as date, SUM(time_spent) as total_time
                  FROM course_progress 
                  WHERE user_id = ? 
                  AND last_accessed >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                  GROUP BY DATE(last_accessed)
                  ORDER BY date";
    $daily_stmt = $conn->prepare($daily_sql);
    $daily_stmt->bind_param("i", $user_id);
    $daily_stmt->execute();
    $daily_result = $daily_stmt->get_result();
    
    $daily_data = [];
    while ($row = $daily_result->fetch_assoc()) {
        $daily_data[$row['date']] = $row['total_time'];
    }
    
    // Get weekly data
    $weekly_sql = "SELECT 
                    YEARWEEK(last_accessed) as week,
                    SUM(time_spent) as total_time
                   FROM course_progress 
                   WHERE user_id = ? 
                   AND last_accessed >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
                   GROUP BY YEARWEEK(last_accessed)
                   ORDER BY week";
    $weekly_stmt = $conn->prepare($weekly_sql);
    $weekly_stmt->bind_param("i", $user_id);
    $weekly_stmt->execute();
    $weekly_result = $weekly_stmt->get_result();
    
    $weekly_data = [];
    while ($row = $weekly_result->fetch_assoc()) {
        $weekly_data[$row['week']] = $row['total_time'];
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4A90E2;
            --secondary-color: #5C6BC0;
            --success-color: #2ecc71;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --text-color: #333333;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--background-color);
            color: var(--text-color);
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
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--card-background);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 8px 0;
            color: var(--primary-color);
            font-size: 1.1em;
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .chart-container {
            background: var(--card-background);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }

        .course-card {
            background: var(--card-background);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .course-content {
            padding: 20px;
        }

        .course-title {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .progress-bar {
            height: 8px;
            background: #eee;
            border-radius: 4px;
            margin: 15px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--success-color);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .course-stats {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.9em;
        }

        .section-title {
            margin: 40px 0 20px;
            color: var(--primary-color);
            text-align: center;
        }

        .course-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .btn-remove {
            background: none;
            border: none;
            color: #e74c3c;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-remove:hover {
            background: rgba(231, 76, 60, 0.1);
            transform: translateY(-1px);
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            animation: slideIn 0.3s ease-out;
            z-index: 1000;
        }

        .message.success {
            background: var(--success-color);
        }

        .message.error {
            background: #e74c3c;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($removal_success)): ?>
            <div class="message success">Course removed successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($removal_error)): ?>
            <div class="message error">Error removing course. Please try again.</div>
        <?php endif; ?>

        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Today's Learning</h3>
                <div class="stat-value">
                    <?php 
                    $today_hours = floor($today_time / 3600);
                    $today_minutes = floor(($today_time % 3600) / 60);
                    echo "{$today_hours}h {$today_minutes}m";
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>This Week</h3>
                <div class="stat-value">
                    <?php 
                    $weekly_hours = floor($weekly_time / 3600);
                    $weekly_minutes = floor(($weekly_time % 3600) / 60);
                    echo "{$weekly_hours}h {$weekly_minutes}m";
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>This Year</h3>
                <div class="stat-value">
                    <?php 
                    $yearly_hours = floor($yearly_time / 3600);
                    $yearly_minutes = floor(($yearly_time % 3600) / 60);
                    echo "{$yearly_hours}h {$yearly_minutes}m";
                    ?>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-container">
                <canvas id="dailyChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>

        <h2 class="section-title">My Courses Progress</h2>
        <div class="courses-grid">
            <?php 
            $result->data_seek(0); // Reset result pointer
            while ($course = $result->fetch_assoc()): 
                $hours = floor($course['time_spent'] / 3600);
                $minutes = floor(($course['time_spent'] % 3600) / 60);
            ?>
            <div class="course-card">
                <div class="course-content">
                    <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                    </div>
                    <div class="course-stats">
                        <span>Progress: <?php echo $course['progress_percentage']; ?>%</span>
                        <span>Time: <?php echo "{$hours}h {$minutes}m"; ?></span>
                    </div>
                    <div class="course-actions">
                        <span>Last accessed: <?php echo date('M d, Y', strtotime($course['last_accessed'])); ?></span>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this course? This action cannot be undone.');">
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                            <button type="submit" name="remove_course" class="btn-remove">
                                <i class="fas fa-trash"></i>
                                Remove
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        // Daily Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyData = <?php 
            $labels = [];
            $data = [];
            for($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('D', strtotime($date));
                $data[] = isset($daily_data[$date]) ? round($daily_data[$date] / 3600, 1) : 0;
            }
            echo json_encode([
                'labels' => $labels,
                'data' => $data
            ]);
        ?>;

        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: dailyData.labels,
                datasets: [{
                    label: 'Hours Spent',
                    data: dailyData.data,
                    backgroundColor: '#4A90E2',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Learning Time'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hours'
                        }
                    }
                }
            }
        });

        // Weekly Chart
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyData = <?php 
            $labels = [];
            $data = [];
            foreach($weekly_data as $week => $time) {
                $labels[] = "Week " . substr($week, -2);
                $data[] = round($time / 3600, 1);
            }
            echo json_encode([
                'labels' => $labels,
                'data' => $data
            ]);
        ?>;

        new Chart(weeklyCtx, {
            type: 'line',
            data: {
                labels: weeklyData.labels,
                datasets: [{
                    label: 'Hours Spent',
                    data: weeklyData.data,
                    borderColor: '#5C6BC0',
                    backgroundColor: 'rgba(92, 107, 192, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Weekly Learning Progress'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hours'
                        }
                    }
                }
            }
        });

        // Auto-hide messages after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => message.remove(), 300);
                }, 3000);
            });
        });
    </script>
</body>
</html> 