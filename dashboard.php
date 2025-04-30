<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'database.php';

// Get user's enrolled courses with progress
$user_id = $_SESSION["id"];
$enrolled_courses = [];

try {
    $sql = "SELECT 
                c.id,
                c.title,
                c.description,
                COALESCE(cp.progress_percentage, 0) as progress,
                COALESCE(cp.time_spent, 0) as time_spent,
                uc.enrolled_at,
                COALESCE(cp.last_accessed, uc.enrolled_at) as last_accessed
            FROM user_courses uc
            INNER JOIN courses c ON uc.course_id = c.id
            LEFT JOIN course_progress cp ON c.id = cp.course_id AND cp.user_id = ?
            WHERE uc.user_id = ?
            ORDER BY cp.last_accessed DESC, uc.enrolled_at DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $enrolled_courses[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $error = "Error fetching courses: " . $e->getMessage();
}

// Calculate total stats
$total_courses = count($enrolled_courses);
$total_progress = 0;
$total_time = 0;

foreach ($enrolled_courses as $course) {
    $total_progress += $course['progress'];
    $total_time += $course['time_spent'];
}

$average_progress = $total_courses > 0 ? round($total_progress / $total_courses) : 0;
$total_hours = floor($total_time / 3600);
$total_minutes = floor(($total_time % 3600) / 60);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4A90E2;
            --secondary-color: #5C6BC0;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
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

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .welcome-section h1 {
            margin: 0;
            font-size: 2em;
            color: var(--primary-color);
        }

        .welcome-section p {
            margin: 5px 0 0;
            color: #666;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-background);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 2em;
            color: var(--primary-color);
        }

        .stat-card p {
            margin: 5px 0 0;
            color: #666;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .course-card {
            background: var(--card-background);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-5px);
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

        .course-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.9em;
            line-height: 1.5;
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

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 0.9em;
            color: #666;
        }

        .no-courses {
            text-align: center;
            padding: 40px;
            background: var(--card-background);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .no-courses i {
            font-size: 3em;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .action-buttons {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
                <p>Track your learning progress and continue your courses</p>
            </div>
            <div class="action-buttons">
                <a href="courses.php" class="btn btn-primary">
                    <i class="fas fa-book"></i>
                    Browse Courses
                </a>
                <a href="progress.php" class="btn btn-primary">
                    <i class="fas fa-chart-line"></i>
                    View Progress
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_courses; ?></h3>
                <p>Enrolled Courses</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $average_progress; ?>%</h3>
                <p>Average Progress</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_hours; ?>h <?php echo $total_minutes; ?>m</h3>
                <p>Total Learning Time</p>
            </div>
        </div>

        <?php if (empty($enrolled_courses)): ?>
            <div class="no-courses">
                <i class="fas fa-graduation-cap"></i>
                <h2>No Courses Enrolled Yet</h2>
                <p>Start your learning journey by enrolling in our exciting courses!</p>
                <a href="courses.php" class="btn btn-primary">
                    <i class="fas fa-book"></i>
                    Browse Courses
                </a>
            </div>
        <?php else: ?>
            <h2>Your Enrolled Courses</h2>
            <div class="courses-grid">
                <?php foreach ($enrolled_courses as $course): ?>
                    <div class="course-card">
                        <div class="course-content">
                            <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                            <div class="course-description"><?php echo htmlspecialchars($course['description']); ?></div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $course['progress']; ?>%"></div>
                            </div>
                            <div class="course-meta">
                                <span>
                                    <i class="fas fa-clock"></i>
                                    <?php 
                                    $hours = floor($course['time_spent'] / 3600);
                                    $minutes = floor(($course['time_spent'] % 3600) / 60);
                                    echo "{$hours}h {$minutes}m";
                                    ?>
                                </span>
                                <span>
                                    <i class="fas fa-chart-line"></i>
                                    <?php echo $course['progress']; ?>% Complete
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 