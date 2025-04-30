<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Initialize time variables
$hours = 0;
$minutes = 0;

// Get total time spent - wrapped in try-catch to handle potential database errors
try {
    require_once 'db_connection.php';
    $total_time = 0;
    $user_id = $_SESSION["id"];

    $sql = "SELECT SUM(time_spent) as total_time FROM user_time_logs WHERE user_id = ? AND logout_time IS NOT NULL";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $total_time = $row['total_time'] ?? 0;
        }
        $stmt->close();
        
        // Convert seconds to hours and minutes
        $hours = floor($total_time / 3600);
        $minutes = floor(($total_time % 3600) / 60);
    }
} catch (Exception $e) {
    // Silently handle database errors - time will show as 0h 0m
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>ExLence - Your Learning Journey</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4A90E2;
            --secondary: #5C6BC0;
            --background: #f0f2f5;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: var(--background);
            overflow-x: hidden;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--primary);
        }

        .header-navigation {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .nav-button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
        }

        .nav-button.active {
            background: #2ecc71;
        }

        .nav-link {
            position: relative;
            text-decoration: none;
            color: #333;
            padding: 10px 15px;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-link i {
            font-size: 1.1em;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .nav-link:hover::after {
            transform: scaleX(1);
        }

        /* Profile section styles */
        .profile-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .time-display {
            background: rgba(74, 144, 226, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            color: var(--primary);
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .profile-btn:hover {
            background: rgba(74, 144, 226, 0.1);
        }

        .profile-icon {
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 500;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 8px 0;
            z-index: 1000;
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .dropdown-content a:hover {
            background: rgba(74, 144, 226, 0.1);
            color: var(--primary);
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px;
            background: linear-gradient(135deg, #4A90E2 0%, #5C6BC0 100%);
            position: relative;
            overflow: hidden;
        }

        .study-scene {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
            perspective: 1000px;
        }

        .book {
            position: absolute;
            width: 60px;
            height: 80px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform-style: preserve-3d;
            animation: float 6s ease-in-out infinite;
        }

        .book::before {
            content: '';
            position: absolute;
            width: 10px;
            height: 100%;
            background: rgba(0,0,0,0.1);
            right: 0;
            transform: rotateY(30deg);
            transform-origin: right;
        }

        .graduation-cap {
            position: absolute;
            width: 100px;
            height: 100px;
            background: #2c3e50;
            clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
            animation: rotate 10s linear infinite;
        }

        .pencil {
            position: absolute;
            width: 8px;
            height: 120px;
            background: linear-gradient(#f1c40f, #e67e22);
            transform: rotate(45deg);
            animation: write 4s ease-in-out infinite;
        }

        .floating-formula {
            position: absolute;
            font-family: 'Times New Roman', serif;
            color: rgba(255, 255, 255, 0.8);
            font-size: 24px;
            animation: floatFormula 8s linear infinite;
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
        }

        .atom {
            position: absolute;
            width: 60px;
            height: 60px;
            border: 2px solid rgba(255,255,255,0.8);
            border-radius: 50%;
            animation: atomSpin 10s linear infinite;
        }

        .atom::before,
        .atom::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid rgba(255,255,255,0.8);
            border-radius: 50%;
            top: -2px;
            left: -2px;
        }

        .atom::before {
            transform: rotate(60deg);
        }

        .atom::after {
            transform: rotate(-60deg);
        }

        .electron {
            position: absolute;
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            top: 50%;
            left: -4px;
            transform-origin: 32px 0;
            animation: electronOrbit 3s linear infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        @keyframes rotate {
            from { transform: rotate(0deg) translateY(-30px); }
            to { transform: rotate(360deg) translateY(-30px); }
        }

        @keyframes write {
            0%, 100% { transform: rotate(45deg) translateX(0); }
            50% { transform: rotate(45deg) translateX(50px); }
        }

        @keyframes atomSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes electronOrbit {
            from { transform: rotate(0deg) translateX(30px); }
            to { transform: rotate(360deg) translateX(30px); }
        }

        @keyframes floatFormula {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-text {
            color: white;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            animation: slideIn 1s ease-out;
        }

        .hero-text h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            transform: translateZ(50px);
            transition: transform 0.3s ease;
            position: relative;
            text-shadow: 2px 2px 0 rgba(0,0,0,0.2);
        }

        .hero-text h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 3px;
            background: white;
            transform: scaleX(0);
            transform-origin: left;
            animation: underlineSlide 1s ease-out forwards 0.5s;
        }

        @keyframes underlineSlide {
            to { transform: scaleX(1); }
        }

        .feature-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transform-style: preserve-3d;
            transition: transform 0.5s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-10px) rotateX(10deg);
        }

        .card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 12px;
            margin-right: 15px;
        }

        .card-icon i {
            font-size: 24px;
            color: white;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .card-header > div {
            display: flex;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .dark-mode .card-header h3 {
            color: #fff;
        }

        .toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle span {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle span:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        .toggle input:checked + span {
            background-color: var(--primary);
        }

        .toggle input:checked + span:before {
            transform: translateX(20px);
        }

        .card-footer a {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .card-footer a:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes floatAnimation {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .floating-element {
            animation: floatAnimation 3s ease-in-out infinite;
        }

        .read-mode {
            filter: sepia(20%) brightness(95%) contrast(90%);
            background: #f4f1ea;
        }

        .dark-mode {
            background: #1a1a1a;
            color: #f0f0f0;
        }

        .dark-mode .card {
            background: #2d2d2d;
            color: #f0f0f0;
        }

        .proctored-warning {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
        }

        .warning-content {
            background: #dc3545;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            animation: shake 0.5s ease-in-out;
        }

        .warning-content button {
            margin-top: 20px;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background: white;
            color: #dc3545;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.3s ease;
        }

        .warning-content button:hover {
            transform: scale(1.05);
        }

        .app-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: rgba(46, 204, 113, 0.9);
            color: white;
            border-radius: 8px;
            transform: translateX(200%);
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 1000;
        }

        .app-notification.show {
            transform: translateX(0);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* Update the card styles */
        .card-image {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .card-image img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .card-header > div {
            display: flex;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .dark-mode .card-header h3 {
            color: #fff;
        }

        .card-footer a {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .card-footer a:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        /* Study Tools Section */
        .study-tools {
            padding: 2rem;
            margin: 2rem 0;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .tool-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .tool-icon {
            background: var(--primary);
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .tool-content {
            flex: 1;
        }

        .tool-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            margin-top: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .tool-btn:hover {
            background: var(--secondary);
        }

        /* Subject Progress Section */
        .subject-progress {
            padding: 2rem;
            margin: 2rem 0;
        }

        .progress-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .subject-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .chapters-list {
            margin-top: 1rem;
        }

        .chapter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .completed {
            color: #2ecc71;
        }

        .in-progress {
            color: #f1c40f;
            animation: spin 2s linear infinite;
        }

        .pending {
            color: #95a5a6;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        /* Study Timer Section */
        .study-timer {
            padding: 2rem;
            margin: 2rem 0;
        }

        .timer-container {
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 0 auto;
        }

        .timer-display {
            font-size: 4rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-family: monospace;
        }

        .timer-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .timer-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timer-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        .timer-btn:not(:disabled):hover {
            background: var(--secondary);
        }

        .session-count {
            margin-top: 1rem;
            color: #666;
        }

        /* Dark Mode Styles */
        .dark-mode .tool-card,
        .dark-mode .subject-card,
        .dark-mode .timer-container {
            background: #2d2d2d;
            color: #f0f0f0;
        }

        .dark-mode .chapter {
            border-bottom-color: #444;
        }

        .dark-mode .session-count {
            color: #aaa;
        }

        .main-header {
            padding: 2rem;
            margin: 2rem;
        }

        .main-header h1 {
            font-size: 2.5rem;
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .username-highlight {
            color: #4A90E2;
            font-weight: 600;
        }

        .search-container {
            position: relative;
            margin: 2rem 0;
        }

        .search {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 0.8rem;
            display: flex;
            align-items: center;
            max-width: 600px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .search::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 22px;
            background: linear-gradient(135deg, #4A90E2, #5C6BC0);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .search:focus-within::before {
            opacity: 1;
        }

        .search input {
            background: transparent;
            border: none;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            color: white;
            flex: 1;
            outline: none;
        }

        .search input::placeholder {
            color: rgba(255, 255, 255, 0.5);
            font-weight: 300;
        }

        .search button {
            background: linear-gradient(135deg, #4A90E2, #5C6BC0);
            border: none;
            border-radius: 15px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            font-size: 1.2rem;
        }

        .search button:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(74, 144, 226, 0.4);
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(74, 144, 226, 0.1), rgba(92, 107, 192, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #4A90E2;
            background: rgba(74, 144, 226, 0.1);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            margin: 0 auto 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #4A90E2, #5C6BC0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="header-logo floating-element">
                <a href="#">
                    <i class="fas fa-graduation-cap fa-2x"></i>
                </a>
            </div>
            <nav class="header-navigation">
                <a href="" class="nav-link"><i class="fas fa-home"></i> Home</a>
                <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <!-- <a href="chatbot.html" target="_blank" class="nav-link"><i class="fas fa-robot"></i> ChatBot</a> -->
                <a href="contact.php" target="" class="nav-link"><i class="fas fa-envelope"></i> Contact Us</a>
                <a href="login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a>
            </nav>
            <div class="profile-section">
                <div class="time-display">
                    <i class="fas fa-clock"></i>
                    <span>Time Spent: <?php echo $hours; ?>h <?php echo $minutes; ?>m</span>
                </div>
                <nav>
                    <div class="nav-buttons">
                        <button id="proctoredMode" class="nav-button">
                            <i class="fas fa-desktop"></i>
                            Proctored Mode
                        </button>
                        <button id="darkMode" class="nav-button">
                            <i class="fas fa-moon"></i>
                            Dark Mode
                        </button>
                        <button id="readMode" class="nav-button">
                            <i class="fas fa-book-reader"></i>
                            Read Mode
                        </button>
                    </div>
                </nav>
                <div class="profile-dropdown">
                    <button class="profile-btn">
                        <div class="profile-icon"><?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="study-scene">
                <div class="book"></div>
                <div class="book"></div>
                <div class="book"></div>
                <div class="graduation-cap"></div>
                <div class="pencil"></div>
                <div class="atom">
                    <div class="electron"></div>
                </div>
                <div class="floating-formula" style="left: 15%; animation-delay: -2s;">E = mc²</div>
                <div class="floating-formula" style="left: 45%; animation-delay: -5s;">∫ f(x)dx</div>
                <div class="floating-formula" style="left: 75%; animation-delay: -8s;">πr²</div>
            </div>
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Transform Your Learning Journey</h1>
                    <p>Experience interactive learning with cutting-edge technology and personalized guidance.</p>
                </div>
                <div class="feature-cards">
                    <div class="card">
                        <div class="card-icon">📚</div>
                        <h3>Interactive Courses</h3>
                        <p>Engage with dynamic content and real-time feedback</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">🎯</div>
                        <h3>Progress Tracking</h3>
                        <p>Monitor your growth with detailed analytics</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">🤖</div>
                        <h3>AI Assistant</h3>
                        <p>Get help anytime with our smart chatbot</p>
                    </div>
                </div>
            </div>
        </section>
        <div class="responsive-wrapper">
            <div class="main-header">
                <h1>Welcome back, <span class="username-highlight"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>!</h1>
            </div>
            <div class="search-container">
                <div class="search">
                    <input type="text" placeholder="Search for courses, resources, or tools..." />
                   
                </div>
            </div>
          
            <div class="horizontal-tabs">
                <a href="#courses" data-card="My Courses"><i class="fas fa-book"></i> My Courses</a>
                <a href="#timetable" data-card="Time Table"><i class="fas fa-calendar"></i> Time Table</a>
                <a href="#progress" data-card="Progress"><i class="fas fa-chart-line"></i> Progress</a>
                <a href="#todo" data-card="TO-Do list"><i class="fas fa-tasks"></i> TO-Do List</a>
                <a href="#mentor" data-card="Ask Mentor"><i class="fas fa-user-tie"></i> Mentor</a>
                <a href="#focus" data-card="Focus Mode"><i class="fas fa-focus"></i> Focus Mode</a>
                <a href="#doubtchat" data-card="Doubt Chat"><i class="fas fa-question-circle"></i> Doubt Chat</a>
                <a href="#community" data-card="Open Community"><i class="fas fa-video"></i> Doubt Session</a>
                <a href="#notes" data-card="Notes Submission" class="active"><i class="fas fa-sticky-note"></i> Notes Submission</a>
            </div>
            <div class="content-header">
                <div class="content-header-intro">
                    <h2>Integrations and Connected Features</h2>
                    <p>Supercharge your workflow and connect the tools you use every day.</p>
                </div>
                <div class="content-header-actions">
                    <a href="#" class="button">
                        <i class="fas fa-sliders"></i>
                        <span>Filters</span>
                    </a>
                    <a href="#" class="button">
                        <i class="fas fa-plus"></i>
                        <span>ADD</span>
                    </a>
                </div>
            </div>
            <div class="content-main">
                <div class="card-grid">
                    <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/courses.jpeg" alt="Courses">
                                </span>
                                <h3>My Courses</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Unlock Your Potential: Explore Diverse Courses Designed Just for You!</p>
                        </div>
                        <div class="card-footer">
                            <a href="courses.php">View</a>
                        </div>
                    </article>
                    <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/timetable.jpeg" alt="Timetable">
                                </span>
                                <h3>Time Table</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Master Your Schedule: Organize Your Time, Empower Your Learning!</p>
                        </div>
                        <div class="card-footer">
                            <a href="timetable.php" target="">View</a>
                        </div>
                    </article>
                    <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/progress.jpeg" alt="Progress">
                                </span>
                                <h3>Progress</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Track Your Success: Visualize Your Progress, Achieve Your Goals!</p>
                        </div>
                        <div class="card-footer">
                            <a href="progress.php">View</a>
                        </div>
                    </article>
                    <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/todo.jpeg" alt="Todo">
                                </span>
                                <h3>TO-Do list</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Stay on Track: Plan, Prioritize, and Conquer Your Tasks!</p>
                        </div>
                        <div class="card-footer">
                            <a href="todo.php" target="">View</a>
                        </div>
                    </article>
                    <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/mentor.jpeg" alt="Mentor">
                                </span>
                                <h3>Ask Mentor</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Guidance at Every Step: Connect with Experienced Mentors, Unlock Your Potential</p>
                        </div>
                        <div class="card-footer">
                            <a href="ask_mentor.php" target="">View</a>
                        </div>
                    </article>
                    <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/focus.png" alt="Focus Mode">
                                </span>
                                <h3>Focus Mode</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Zone In, Power Up: Activate Focus Mode for Uninterrupted Learning Excellence!</p>
                        </div>
                        <div class="card-footer">
                            <a href="focus-mode.html" target="">View</a>
                        </div>
                    </article>
                    <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/doubtchat.jpeg" alt="Doubt Chat">
                                </span>
                                <h3>Doubt Chat</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Clearing Paths, Answering Questions: Engage in Enlightening Doubt Chats!</p>
                        </div>
                        <div class="card-footer">
                            <a href="chatbot.html" target="">View</a>
                        </div>
                    </article>
                    <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/doubtsession.jpeg" alt="Doubt Session">
                                </span>
                                <h3>Open Community</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Unlock Insights, Resolve Queries: Join Engaging Community for Clarity!</p>
                        </div>
                        <div class="card-footer">
                            <a href="community.php" target="">View</a>
                        </div>
                    </article>
                    <!-- <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/reminder.jpeg" alt="Daily Reminders">
                                </span>
                                <h3>Daily Reminders</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Stay On Track: Daily Reminders to Keep You Motivated and Productive</p>
                        </div>
                        <div class="card-footer">
                            <a href="reminder.html" target="_blank">View</a>
                        </div>
                    </article> -->
                    <article class="card">
                        <div class="card-header">
                            <div>
                                <span class="card-image">
                                    <img src="icons/notessumission.jpeg" alt="Notes Submission">
                                </span>
                                <h3>Notes Submission</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Share Your Insights: Submit Your Notes and Contribute to the Learning Community!</p>
                        </div>
                        <div class="card-footer">
                            <a href="notes.php" target="">View</a>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Study Tools Section -->
            <section class="study-tools">
                <h2>Study Tools & Resources</h2>
                <div class="tools-grid">
                    <div class="tool-card">
                        <div class="tool-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="tool-content">
                            <h3>Scientific Calculator</h3>
                            <p>Advanced calculations for mathematics and physics</p>
                            <button class="tool-btn" onclick="window.location.href='calculator.php'">
                                Open Calculator
                            </button>
                        </div>
                    </div>
                    <div class="tool-card">
                        <div class="tool-icon">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <div class="tool-content">
                            <h3>Digital Library</h3>
                            <p>Access to textbooks and reference materials</p>
                            <button class="tool-btn" onclick="window.location.href='library.php'">
                                Browse Library
                            </button>
                        </div>
                    </div>
                    <div class="tool-card">
                        <div class="tool-icon">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <div class="tool-content">
                            <h3>Virtual Whiteboard</h3>
                            <p>Interactive space for problem-solving</p>
                            <button class="tool-btn" onclick="window.location.href='whiteboard.php'">
                                Start Drawing
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Subject Progress -->
            

            <!-- Study Timer -->
            <section class="study-timer">
                <!-- <h2>Focus Timer</h2>
                <div class="timer-container">
                    <div class="timer-display">
                        <span id="minutes">25</span>:<span id="seconds">00</span>
                    </div>
                    <div class="timer-controls">
                        <button id="startTimer" class="timer-btn">
                            <i class="fas fa-play"></i> Start
                        </button>
                        <button id="pauseTimer" class="timer-btn" disabled>
                            <i class="fas fa-pause"></i> Pause
                        </button>
                        <button id="resetTimer" class="timer-btn">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                    <div class="session-count">
                        Study Sessions Today: <span id="sessionCount">0</span>
                    </div>
                </div>
            </section>
        </div> -->
    </main>

    <div id="warningModal" class="modal">
        <div class="modal-content">
            <h2>Warning!</h2>
            <p id="warningMessage"></p>
            <button id="acknowledgeWarning">OK</button>
        </div>
    </div>

    <!-- Time spent display -->
    <div class="time-spent">
        <?php
        echo "Time Spent: {$hours}h {$minutes}m";
        ?>
    </div>

    <script src="app.js"></script>
    <script>
        // 3D card effect
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
            });
        });

        // Smooth scroll for horizontal tabs
        document.querySelectorAll('.horizontal-tabs a').forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                const targetCardTitle = this.getAttribute('data-card');
                const cards = document.querySelectorAll('.card');
                
                for (let card of cards) {
                    const cardTitle = card.querySelector('h3').textContent;
                    if (cardTitle === targetCardTitle) {
                        card.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        
                        // Add highlight animation
                        card.style.animation = 'highlight 1s ease';
                        setTimeout(() => {
                            card.style.animation = '';
                        }, 1000);
                        break;
                    }
                }
                
                // Update active tab
                document.querySelectorAll('.horizontal-tabs a').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Add highlight animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes highlight {
                0% { transform: scale(1); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
                50% { transform: scale(1.05); box-shadow: 0 15px 30px rgba(74, 144, 226, 0.3); }
                100% { transform: scale(1); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
            }
        `;
        document.head.appendChild(style);

        // Timer functionality
        let timeLeft = 25 * 60; // 25 minutes in seconds
        let timerId = null;
        let sessions = 0;

        const startBtn = document.getElementById('startTimer');
        const pauseBtn = document.getElementById('pauseTimer');
        const resetBtn = document.getElementById('resetTimer');
        const minutesDisplay = document.getElementById('minutes');
        const secondsDisplay = document.getElementById('seconds');
        const sessionDisplay = document.getElementById('sessionCount');

        function updateDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            minutesDisplay.textContent = minutes.toString().padStart(2, '0');
            secondsDisplay.textContent = seconds.toString().padStart(2, '0');
        }

        startBtn.addEventListener('click', () => {
            if (!timerId) {
                timerId = setInterval(() => {
                    timeLeft--;
                    updateDisplay();
                    if (timeLeft === 0) {
                        clearInterval(timerId);
                        timerId = null;
                        sessions++;
                        sessionDisplay.textContent = sessions;
                        timeLeft = 25 * 60;
                        updateDisplay();
                        new Audio('timer-end.mp3').play().catch(() => {});
                    }
                }, 1000);
                startBtn.disabled = true;
                pauseBtn.disabled = false;
            }
        });

        pauseBtn.addEventListener('click', () => {
            clearInterval(timerId);
            timerId = null;
            startBtn.disabled = false;
            pauseBtn.disabled = true;
        });

        resetBtn.addEventListener('click', () => {
            clearInterval(timerId);
            timerId = null;
            timeLeft = 25 * 60;
            updateDisplay();
            startBtn.disabled = false;
            pauseBtn.disabled = true;
        });
    </script>
</body>
</html>