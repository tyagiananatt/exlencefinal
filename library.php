<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Debug: Check if we can connect to database
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch books from database with error handling
try {
    $sql = "SELECT * FROM books ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Error executing query: " . $conn->error);
    }
    
    $books = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
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
    <title>Digital Library - ExLence</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f0f2f5;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .search-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .search-box {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-box:focus {
            outline: none;
            border-color: #6B73FF;
            box-shadow: 0 0 0 3px rgba(107, 115, 255, 0.2);
        }

        .categories {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .category-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .category-btn.active {
            background: #6B73FF;
            color: white;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .book-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.15);
        }

        .book-cover {
            width: 100%;
            height: 320px;
            object-fit: contain;
            border-bottom: 1px solid #eee;
            background: #f5f5f5;
            padding: 1rem;
        }

        .book-info {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .book-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
            line-height: 1.4;
        }

        .book-author {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .book-description {
            font-size: 0.9rem;
            color: #444;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.6;
            flex-grow: 1;
        }

        .read-btn {
            background: #6B73FF;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
            margin-top: auto;
        }

        .read-btn:hover {
            background: #000DFF;
            transform: translateY(-2px);
        }

        .no-books {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
            grid-column: 1 / -1;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .no-books i {
            font-size: 4rem;
            color: #6B73FF;
            margin-bottom: 1.5rem;
        }

        .no-books p {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .loading-placeholder {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 15px;
            height: 320px;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-left {
                flex-direction: column;
                gap: 1rem;
            }

            .search-container {
                padding: 0 1rem;
            }

            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                padding: 1rem;
                gap: 1rem;
            }

            .book-cover {
                height: 280px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <h1>Digital Library</h1>
        </div>
        <div class="categories">
            <button class="category-btn active" onclick="filterByCategory('all')">All</button>
            <button class="category-btn" onclick="filterByCategory('programming')">Programming</button>
            <button class="category-btn" onclick="filterByCategory('design')">Design</button>
            <button class="category-btn" onclick="filterByCategory('business')">Business</button>
            <button class="category-btn" onclick="filterByCategory('science')">Science</button>
        </div>
    </div>

    <div class="search-container">
        <input type="text" class="search-box" placeholder="Search books by title, author, or category..." oninput="filterBooks()">
    </div>

    <div class="books-grid">
        <?php if (empty($books)): ?>
            <div class="no-books">
                <i class="fas fa-books"></i>
                <p>No books available at the moment.</p>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="add_books.php" style="color: #6B73FF; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-plus"></i> Add Books
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
            <div class="book-card" data-category="<?php echo htmlspecialchars($book['category']); ?>">
                <div class="loading-placeholder" style="display: none;"></div>
                <img src="<?php echo !empty($book['cover_url']) ? htmlspecialchars($book['cover_url']) : 'icons/courses.jpeg'; ?>" 
                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                     class="book-cover"
                     onerror="this.src='icons/courses.jpeg'"
                     onload="this.previousElementSibling.style.display='none'">
                <div class="book-info">
                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p class="book-author">
                        <i class="fas fa-user"></i>
                        by <?php echo htmlspecialchars($book['author']); ?>
                    </p>
                    <p class="book-description"><?php echo htmlspecialchars($book['description']); ?></p>
                    <a href="read_book.php?id=<?php echo $book['id']; ?>" class="read-btn">
                        <i class="fas fa-book-reader"></i>
                        Read Now
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function filterBooks() {
            const searchTerm = document.querySelector('.search-box').value.toLowerCase();
            const bookCards = document.querySelectorAll('.book-card');

            bookCards.forEach(card => {
                const title = card.querySelector('.book-title').textContent.toLowerCase();
                const author = card.querySelector('.book-author').textContent.toLowerCase();
                const category = card.dataset.category.toLowerCase();

                if (title.includes(searchTerm) || author.includes(searchTerm) || category.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function filterByCategory(category) {
            const categoryBtns = document.querySelectorAll('.category-btn');
            categoryBtns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const bookCards = document.querySelectorAll('.book-card');
            bookCards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Add loading placeholders
        document.querySelectorAll('.book-cover').forEach(img => {
            if (!img.complete) {
                img.previousElementSibling.style.display = 'block';
            }
        });
    </script>
</body>
</html>