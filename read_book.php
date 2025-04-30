<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// Check if book ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: library.php");
    exit;
}

$book_id = intval($_GET['id']);

// Get current page number
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$words_per_page = 1000; // Adjust this value to control content per page

// Fetch book details
$stmt = $conn->prepare("SELECT title, author, content, file_type, file_url FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: library.php");
    exit;
}

$book = $result->fetch_assoc();

// Split content into pages
$words = str_word_count($book['content'], 1);
$total_words = count($words);
$total_pages = ceil($total_words / $words_per_page);

// Get current page content
$start_index = ($current_page - 1) * $words_per_page;
$page_words = array_slice($words, $start_index, $words_per_page);
$page_content = implode(' ', $page_words);

// Calculate progress percentage
$progress = ($current_page / $total_pages) * 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading: <?php echo htmlspecialchars($book['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            background-color: white;
            color: #1e3c72;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background-color: #f0f0f0;
            transform: translateY(-1px);
        }

        .btn i {
            font-size: 1.1rem;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.2);
            margin-top: 0.5rem;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background-color: white;
            width: <?php echo $progress; ?>%;
            transition: width 0.3s ease;
        }

        .content-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            min-height: calc(100vh - 200px);
        }

        .book-meta {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .book-meta h2 {
            margin: 0 0 0.5rem 0;
            color: #1e3c72;
            font-size: 1.8rem;
        }

        .book-meta p {
            margin: 0;
            color: #666;
        }

        .book-content {
            font-size: 18px;
            line-height: 1.8;
            color: #333;
            margin-bottom: 2rem;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .page-info {
            color: #666;
            font-size: 0.9rem;
        }

        /* Dark mode styles */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #f5f5f5;
        }

        body.dark-mode .content-container {
            background-color: #2d2d2d;
            color: #f5f5f5;
        }

        body.dark-mode .book-meta h2 {
            color: #7aa2f7;
        }

        body.dark-mode .book-meta p {
            color: #aaa;
        }

        body.dark-mode .book-content {
            color: #f5f5f5;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .controls {
                flex-wrap: wrap;
                justify-content: center;
            }

            .content-container {
                margin: 1rem;
                padding: 1rem;
            }

            .book-content {
                font-size: 16px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo htmlspecialchars($book['title']); ?></h1>
            <div class="controls">
                <button class="btn" onclick="adjustFontSize(-1)"><i class="fas fa-minus"></i> A</button>
                <button class="btn" onclick="adjustFontSize(1)">A <i class="fas fa-plus"></i></button>
                <button class="btn" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>
                <a href="library.php" class="btn"><i class="fas fa-book"></i> Library</a>
            </div>
        </div>
        <div class="progress-bar">
            <div class="progress"></div>
        </div>
    </div>

    <div class="content-container">
        <div class="book-meta">
            <h2><?php echo htmlspecialchars($book['title']); ?></h2>
            <p>By <?php echo htmlspecialchars($book['author']); ?></p>
        </div>

        <div class="book-content" id="bookContent">
            <?php echo nl2br(htmlspecialchars($page_content)); ?>
        </div>

        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?id=<?php echo $book_id; ?>&page=<?php echo $current_page - 1; ?>" class="btn">
                    <i class="fas fa-chevron-left"></i> Previous Page
                </a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <span class="page-info">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>

            <?php if ($current_page < $total_pages): ?>
                <a href="?id=<?php echo $book_id; ?>&page=<?php echo $current_page + 1; ?>" class="btn">
                    Next Page <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let currentFontSize = 18;
        const bookContent = document.getElementById('bookContent');
        
        function adjustFontSize(change) {
            currentFontSize = Math.max(14, Math.min(24, currentFontSize + change));
            if (bookContent) {
                bookContent.style.fontSize = currentFontSize + 'px';
                localStorage.setItem('preferred_font_size', currentFontSize);
            }
        }

        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('dark_mode', isDarkMode);
        }

        // Restore user preferences
        document.addEventListener('DOMContentLoaded', () => {
            // Restore font size
            const savedFontSize = localStorage.getItem('preferred_font_size');
            if (savedFontSize) {
                currentFontSize = parseInt(savedFontSize);
                bookContent.style.fontSize = currentFontSize + 'px';
            }

            // Restore dark mode
            const savedDarkMode = localStorage.getItem('dark_mode');
            if (savedDarkMode === 'true') {
                document.body.classList.add('dark-mode');
            }

            // Save reading progress
            localStorage.setItem('book_<?php echo $book_id; ?>_page', <?php echo $current_page; ?>);
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' && <?php echo $current_page; ?> > 1) {
                window.location.href = '?id=<?php echo $book_id; ?>&page=<?php echo $current_page - 1; ?>';
            } else if (e.key === 'ArrowRight' && <?php echo $current_page; ?> < <?php echo $total_pages; ?>) {
                window.location.href = '?id=<?php echo $book_id; ?>&page=<?php echo $current_page + 1; ?>';
            }
        });
    </script>
</body>
</html> 