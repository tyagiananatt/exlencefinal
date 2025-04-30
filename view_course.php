<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'database.php';

$user_id = $_SESSION["id"];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$course_id) {
    header("location: courses.php");
    exit;
}

// Get course details
try {
    $sql = "SELECT c.*, COALESCE(cp.progress_percentage, 0) as progress, COALESCE(cp.time_spent, 0) as time_spent 
            FROM courses c 
            LEFT JOIN course_progress cp ON c.id = cp.course_id AND cp.user_id = ? 
            WHERE c.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course = $result->fetch_assoc();

    if (!$course) {
        header("location: courses.php");
        exit;
    }

    // Update last_accessed timestamp
    $update_sql = "UPDATE course_progress SET last_accessed = NOW() WHERE user_id = ? AND course_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $user_id, $course_id);
    $update_stmt->execute();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Get video URL based on course ID
$video_urls = [
    1 => "https://www.youtube.com/embed/kqtD5dpn9C8",  // Python
    2 => "https://www.youtube.com/embed/qz0aGYrrlhU",  // HTML
    3 => "https://www.youtube.com/embed/PkZNo7MFNFg",  // JavaScript
    4 => "https://www.youtube.com/embed/1Rs2ND1ryYc"   // CSS
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
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

        .course-header {
            margin-bottom: 30px;
        }

        .course-title {
            font-size: 2em;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .course-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
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

        #timeSpent {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="courses.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Courses
        </a>

        <div class="course-header">
            <h1 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h1>
            <div class="course-stats">
                <div class="stat">
                    <i class="fas fa-clock"></i>
                    <span>Time Spent: 
                        <?php 
                        $hours = floor($course['time_spent'] / 3600);
                        $minutes = floor(($course['time_spent'] % 3600) / 60);
                        echo "{$hours}h {$minutes}m";
                        ?>
                    </span>
                </div>
                <div class="stat">
                    <i class="fas fa-chart-line"></i>
                    <span>Progress: <?php echo $course['progress']; ?>%</span>
                </div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $course['progress']; ?>%"></div>
            </div>
        </div>

        <div class="video-container">
            <iframe src="<?php echo $video_urls[$course_id]; ?>" allowfullscreen></iframe>
        </div>

        <div id="timeSpent">
            Current Session: <span id="sessionTime">0:00</span>
        </div>
    </div>

    <script>
        let startTime = Date.now();
        let isActive = true;
        let sessionSeconds = 0;
        let timer;

        // Update time display
        function updateTimeDisplay() {
            if (isActive) {
                sessionSeconds++;
                const minutes = Math.floor(sessionSeconds / 60);
                const seconds = sessionSeconds % 60;
                document.getElementById('sessionTime').textContent = 
                    `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        }

        // Start timer
        timer = setInterval(updateTimeDisplay, 1000);

        // Track user activity/inactivity
        document.addEventListener('visibilitychange', () => {
            isActive = !document.hidden;
        });

        // Save time spent when leaving the page
        window.addEventListener('beforeunload', () => {
            const timeSpent = Math.floor((Date.now() - startTime) / 1000);
            
            // Only send if time spent is significant (more than 5 seconds)
            if (timeSpent > 5) {
                fetch('update_progress.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `course_id=<?php echo $course_id; ?>&time_spent=${timeSpent}`
                });
            }
        });
    </script>
</body>
</html> 