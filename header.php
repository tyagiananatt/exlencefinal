<?php
session_start();
// Initialize time variables
$hours = 0;
$minutes = 0;

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get total time spent if user is logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    try {
        require_once 'database.php';
        $total_time = 0;
        $user_id = $_SESSION["id"];

        // Get total time from course_progress
        $sql = "SELECT SUM(time_spent) as total_time FROM course_progress WHERE user_id = ?";
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

        // Get user role if not set
        if (!isset($_SESSION['role'])) {
            $role_sql = "SELECT role FROM users WHERE id = ?";
            if ($stmt = $conn->prepare($role_sql)) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $_SESSION['role'] = $row['role'];
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        // Log error
        error_log("Error in header.php: " . $e->getMessage());
    }
}
?>

<!-- Header Navigation -->
<header class="header">
    <div class="header-content">
        <!-- Logo Section -->
        <a href="index.php" class="header-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>ExLence</span>
        </a>

        <!-- Navigation Links -->
        <nav class="header-navigation">
            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                Home
            </a>
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                Dashboard
            </a>
            <a href="courses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                Courses
            </a>
            <a href="progress.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'progress.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                Progress
            </a>
            <a href="chatbot.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'chatbot.php' ? 'active' : ''; ?>">
                <i class="fas fa-robot"></i>
                ChatBot
            </a>
            <a href="contact.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i>
                Contact Us
            </a>
        </nav>

        <!-- Profile Section -->
        <div class="profile-section">
            <!-- Time Display -->
            <div class="time-display">
                <i class="fas fa-clock"></i>
                <span><?php echo $hours; ?>h <?php echo $minutes; ?>m</span>
            </div>

            <!-- Mode Buttons -->
            <div class="nav-buttons">
                <button class="nav-button" id="proctorMode">
                    <i class="fas fa-user-shield"></i>
                    Proctored Mode
                </button>
                <button class="nav-button" id="darkMode">
                    <i class="fas fa-moon"></i>
                    Dark Mode
                </button>
                <button class="nav-button" id="readMode">
                    <i class="fas fa-book-reader"></i>
                    Read Mode
                </button>
            </div>

            <!-- Profile Dropdown -->
            <div class="profile-dropdown">
                <button class="profile-btn" id="profileDropdownBtn">
                    <div class="profile-icon">
                        <?php echo isset($_SESSION["username"]) ? strtoupper(substr($_SESSION["username"], 0, 1)) : 'G'; ?>
                    </div>
                    <span><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Guest'; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu" id="profileDropdown">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                    <?php 
                    // Debug output
                    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                        echo "<!-- Debug: User Role = " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set') . " -->";
                    }
                    
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'mentor'): 
                    ?>
                        <a href="queries.php" class="dropdown-item">
                            <i class="fas fa-question-circle"></i> View Queries
                            <?php
                            // Get count of pending queries for mentor
                            $pending_count = 0;
                            if (isset($conn)) {
                                $sql = "SELECT COUNT(*) as count FROM mentor_queries WHERE status = 'pending'";
                                $result = $conn->query($sql);
                                if ($result && $row = $result->fetch_assoc()) {
                                    $pending_count = $row['count'];
                                }
                            }
                            if ($pending_count > 0): ?>
                                <span class="badge"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
/* Header Styles */
.header {
    background: var(--card-background, #ffffff);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--primary-color, #4A90E2);
    font-size: 1.5rem;
    font-weight: bold;
}

.header-navigation {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: var(--text-color, #333);
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.nav-link:hover, .nav-link.active {
    background: var(--primary-color, #4A90E2);
    color: white;
}

.nav-buttons {
    display: flex;
    gap: 10px;
}

.nav-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    background: rgba(74, 144, 226, 0.1);
    color: var(--text-color, #333);
    cursor: pointer;
    transition: all 0.3s ease;
}

.nav-button:hover, .nav-button.active {
    background: var(--primary-color, #4A90E2);
    color: white;
}

.profile-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

.time-display {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(74, 144, 226, 0.1);
    border-radius: 20px;
    color: var(--primary-color, #4A90E2);
}

.profile-dropdown {
    position: relative;
}

.profile-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    border: none;
    border-radius: 20px;
    background: rgba(74, 144, 226, 0.1);
    color: var(--text-color, #333);
    cursor: pointer;
    transition: all 0.3s ease;
}

.profile-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary-color, #4A90E2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--card-background, #ffffff);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 8px 0;
    min-width: 200px;
    z-index: 1000;
    display: none;
    transform: translateY(10px);
    transition: all 0.3s ease;
}

.dropdown-menu.show {
    display: block;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    color: var(--text-color);
    text-decoration: none;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: var(--background-color);
    color: var(--primary-color);
}

.dropdown-item i {
    font-size: 1.1em;
    width: 20px;
    text-align: center;
}

.dropdown-divider {
    height: 1px;
    background: var(--border-color);
    margin: 8px 0;
}

/* Add highlight for active menu item */
.dropdown-item.active {
    background: var(--primary-color);
    color: white;
}

/* Add notification badge for pending queries */
.dropdown-item .badge {
    background: var(--primary-color);
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-left: auto;
}

/* Dark mode styles */
body.dark-mode .header {
    background: var(--card-background, #2d2d2d);
}

body.dark-mode .nav-link {
    color: var(--text-color, #ffffff);
}

body.dark-mode .nav-button {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-color, #ffffff);
}

body.dark-mode .profile-btn {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-color, #ffffff);
}

body.dark-mode .dropdown-item {
    color: var(--text-color, #ffffff);
}

body.dark-mode .dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Responsive styles */
@media (max-width: 1200px) {
    .nav-buttons {
        display: none;
    }
}

@media (max-width: 992px) {
    .header-navigation {
        display: none;
    }
    
    .time-display {
        display: none;
    }
}
</style>

<script>
// Mode toggle functionality
document.getElementById('proctorMode').addEventListener('click', function() {
    this.classList.toggle('active');
});

document.getElementById('darkMode').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
    this.classList.toggle('active');
});

document.getElementById('readMode').addEventListener('click', function() {
    document.body.classList.toggle('read-mode');
    this.classList.toggle('active');
});

// Profile dropdown functionality
const profileBtn = document.getElementById('profileDropdownBtn');
const profileDropdown = document.getElementById('profileDropdown');

profileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    profileDropdown.classList.toggle('show');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!profileBtn.contains(e.target)) {
        profileDropdown.classList.remove('show');
    }
});

// Prevent dropdown from closing when clicking inside it
profileDropdown.addEventListener('click', function(e) {
    e.stopPropagation();
});
</script> 