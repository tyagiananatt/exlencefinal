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

// Handle course enrollment via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll']) && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
    header('Content-Type: application/json');
    
    try {
        // Check if already enrolled
        $check_sql = "SELECT id FROM user_courses WHERE user_id = ? AND course_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $course_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Not enrolled yet, proceed with enrollment
            $enroll_sql = "INSERT INTO user_courses (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())";
            $enroll_stmt = $conn->prepare($enroll_sql);
            $enroll_stmt->bind_param("ii", $user_id, $course_id);
            
            if ($enroll_stmt->execute()) {
                // Initialize course progress
                $init_progress_sql = "INSERT INTO course_progress (user_id, course_id, progress_percentage, time_spent, last_accessed) VALUES (?, ?, 0, 0, NOW())";
                $init_progress_stmt = $conn->prepare($init_progress_sql);
                $init_progress_stmt->bind_param("ii", $user_id, $course_id);
                $init_progress_stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Successfully enrolled in the course!']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error enrolling in the course.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course.']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Get all courses and user's enrolled courses
$enrolled_courses = [];
try {
    // Get enrolled course IDs
    $enrolled_sql = "SELECT course_id FROM user_courses WHERE user_id = ?";
    $enrolled_stmt = $conn->prepare($enrolled_sql);
    $enrolled_stmt->bind_param("i", $user_id);
    $enrolled_stmt->execute();
    $enrolled_result = $enrolled_stmt->get_result();
    while ($row = $enrolled_result->fetch_assoc()) {
        $enrolled_courses[] = $row['course_id'];
    }

    // Get all courses
    $courses_sql = "SELECT * FROM courses ORDER BY id";
    $courses_result = $conn->query($courses_sql);
} catch (Exception $e) {
    $error_message = "Error fetching courses: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Courses - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4A90E2;
            --primary-dark: #357ABD;
            --secondary-color: #5C6BC0;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --text-color: #333333;
            --gradient-primary: linear-gradient(135deg, #4A90E2 0%, #5C6BC0 100%);
            --gradient-success: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --shadow-hover: 0 15px 30px rgba(0,0,0,0.15);
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f6f8fd 0%, #f1f4f9 100%);
            color: var(--text-color);
            padding: 20px;
            min-height: 100vh;
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
            background: var(--gradient-primary);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
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

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .back-button:hover::before {
            left: 100%;
        }

        .courses-header {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }

        .courses-header h1 {
            font-size: 2.5em;
            margin: 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .courses-header h1::after {
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

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            perspective: 1000px;
        }

        .course-card {
            background: var(--card-background);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            transform-style: preserve-3d;
            position: relative;
        }

        .course-card:hover {
            transform: translateY(-10px) rotateX(2deg);
            box-shadow: var(--shadow-hover);
        }

        .course-content {
            padding: 25px;
        }

        .course-title {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
            transition: color 0.3s ease;
        }

        .course-card:hover .course-title {
            color: var(--primary-dark);
        }

        .course-description {
            color: #666;
            margin-bottom: 20px;
            font-size: 0.95em;
            line-height: 1.6;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.3s ease;
        }

        .course-card:hover .video-container {
            transform: translateZ(20px);
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 12px;
        }

        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.9) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            backdrop-filter: blur(4px);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .video-overlay:hover {
            background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.8) 100%);
        }

        .video-overlay i {
            font-size: 2em;
            margin-bottom: 12px;
            color: var(--primary-color);
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .video-overlay p {
            font-size: 0.9em;
            font-weight: 500;
            margin: 0;
            opacity: 0.9;
            max-width: 200px;
            line-height: 1.4;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            flex: 1;
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
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(8px);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: var(--gradient-success);
        }

        .notification.error {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s ease infinite;
        }

        .loading span {
            opacity: 0;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .courses-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .courses-header h1 {
                font-size: 2em;
            }

            .course-title {
                font-size: 1.2em;
            }

            .btn {
                padding: 10px 20px;
            }

            .video-overlay {
                padding: 15px;
            }

            .video-overlay i {
                font-size: 1.75em;
                margin-bottom: 8px;
            }

            .video-overlay p {
                font-size: 0.85em;
                max-width: 160px;
            }
        }

        @media (max-width: 360px) {
            .video-overlay i {
                font-size: 1.5em;
                margin-bottom: 6px;
            }

            .video-overlay p {
                font-size: 0.8em;
                max-width: 140px;
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

        <div class="courses-header">
            <h1>Available Courses</h1>
        </div>

        <div id="notification" class="notification" role="alert"></div>

        <div class="courses-grid">
            <?php while ($course = $courses_result->fetch_assoc()): 
                $is_enrolled = in_array($course['id'], $enrolled_courses);
            ?>
            <div class="course-card" id="course-<?php echo $course['id']; ?>">
                <div class="course-content">
                    <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                    <div class="course-description"><?php echo htmlspecialchars($course['description']); ?></div>
                    
                    <div class="video-container">
                        <iframe 
                            src="<?php echo $course['video_url']; ?>"
                            title="<?php echo htmlspecialchars($course['title']); ?>"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            style="<?php echo $is_enrolled ? '' : 'display: none;'; ?>"
                        ></iframe>
                        <?php if (!$is_enrolled): ?>
                        <div class="video-overlay">
                            <i class="fas fa-lock"></i>
                            <p>Enroll in this course to watch the video</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="btn-group">
                        <?php if ($is_enrolled): ?>
                        <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-play"></i>
                            <span>View Course</span>
                        </a>
                        <?php else: ?>
                        <button type="button" class="btn btn-primary enroll-btn" data-course-id="<?php echo $course['id']; ?>">
                            <i class="fas fa-plus"></i>
                            <span>Enroll Now</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');

            function showNotification(message, type) {
                notification.textContent = message;
                notification.className = `notification ${type} show`;
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
            }

            document.querySelectorAll('.enroll-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const courseId = this.dataset.courseId;
                    const courseCard = document.getElementById(`course-${courseId}`);
                    const button = this;
                    
                    // Add loading state
                    button.classList.add('loading');
                    
                    // Create form data
                    const formData = new FormData();
                    formData.append('enroll', '1');
                    formData.append('course_id', courseId);

                    // Send AJAX request
                    fetch('courses.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            showNotification(data.message, 'success');
                            
                            // Update the button to View Course
                            const btnGroup = button.parentElement;
                            btnGroup.innerHTML = `
                                <a href="view_course.php?id=${courseId}" class="btn btn-primary">
                                    <i class="fas fa-play"></i>
                                    <span>View Course</span>
                                </a>
                            `;

                            // Show the video player and remove overlay
                            const videoContainer = courseCard.querySelector('.video-container');
                            const iframe = videoContainer.querySelector('iframe');
                            const overlay = videoContainer.querySelector('.video-overlay');
                            
                            if (overlay) {
                                overlay.remove();
                            }
                            if (iframe) {
                                iframe.style.display = 'block';
                            }
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        button.classList.remove('loading');
                    });
                });
            });
        });
    </script>
</body>
</html> 