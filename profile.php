<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once 'database.php';

// Initialize variables
$username = $_SESSION["username"];
$email = "";
$join_date = "";
$total_time = 0;
$hours = 0;
$minutes = 0;

// Get user details from database
try {
    // Get user email and join date
    $sql = "SELECT email, created_at FROM users WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $email = $row['email'] ?? 'Not provided';
            $join_date = date('F j, Y', strtotime($row['created_at']));
        }
        $stmt->close();
    }

    // Get total time spent
    $sql = "SELECT SUM(time_spent) as total_time FROM user_time_logs WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $_SESSION["id"]);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $total_time = $row['total_time'] ?? 0;
            $hours = floor($total_time / 3600);
            $minutes = floor(($total_time % 3600) / 60);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $error = "Error retrieving user details.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ExLence</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --secondary: #5C6BC0;
            --background: #f0f2f5;
            --text: #333;
            --success: #2ecc71;
            --warning: #f1c40f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 60px 20px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            margin: 0 auto 20px;
            border: 5px solid rgba(255, 255, 255, 0.3);
        }

        .profile-name {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .profile-email {
            font-size: 18px;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .profile-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 20px;
            color: var(--text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .detail-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: var(--background);
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
        }

        .detail-info h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
        }

        .detail-info p {
            font-size: 18px;
            color: var(--text);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .back-button:hover {
            transform: translateX(-5px);
            background: var(--secondary);
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }

            .profile-header {
                padding: 40px 20px;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }

            .profile-name {
                font-size: 24px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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

        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($username); ?></h1>
            <p class="profile-email"><?php echo htmlspecialchars($email); ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $hours; ?>h <?php echo $minutes; ?>m</div>
                <div class="stat-label">Total Time Spent Learning</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value"><?php echo $join_date; ?></div>
                <div class="stat-label">Member Since</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value">Active</div>
                <div class="stat-label">Account Status</div>
            </div>
        </div>

        <div class="profile-section">
            <h2 class="section-title">
                <i class="fas fa-user"></i>
                Personal Information
            </h2>
            <div class="detail-item">
                <div class="detail-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="detail-info">
                    <h3>Username</h3>
                    <p><?php echo htmlspecialchars($username); ?></p>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="detail-info">
                    <h3>Email Address</h3>
                    <p><?php echo htmlspecialchars($email); ?></p>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="detail-info">
                    <h3>Join Date</h3>
                    <p><?php echo $join_date; ?></p>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="detail-info">
                    <h3>Time Spent Learning</h3>
                    <p><?php echo $hours; ?> hours <?php echo $minutes; ?> minutes</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
