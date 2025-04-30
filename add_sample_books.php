<?php
// Increase memory and execution time limits for handling large files
ini_set('memory_limit', '512M');
set_time_limit(300); // 5 minutes

require_once 'db_connection.php';

// Increase MySQL packet size and timeout
$conn->query("SET GLOBAL max_allowed_packet=67108864"); // 64MB
$conn->query("SET SESSION wait_timeout=300");

// Common book icon SVG for all books
$book_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="600" viewBox="0 0 400 600">
    <rect width="400" height="600" fill="#f5f5f5"/>
    <g transform="translate(100, 150)">
        <!-- Book base -->
        <rect x="0" y="0" width="200" height="250" fill="#306998" rx="5"/>
        <!-- Book pages -->
        <rect x="10" y="10" width="180" height="230" fill="white" rx="3"/>
        <!-- Book spine -->
        <rect x="0" y="0" width="40" height="250" fill="#FFD43B" rx="5"/>
        <!-- Python Logo (simplified) -->
        <g transform="translate(60, 40) scale(0.4)">
            <path d="M20,0 C9,0 10,10 10,10L10,20L30,20L30,25L0,25L0,50C0,61 9,60 9,60L30,60L30,70C30,81 40,80 40,80L70,80C81,80 80,70 80,70L80,50L90,50L90,40L40,40L40,35L70,35C81,35 80,25 80,25L80,10C80,-1 70,0 70,0L20,0z" fill="#306998"/>
            <path d="M85,100 C96,100 95,90 95,90L95,80L75,80L75,75L105,75L105,50C105,39 96,40 96,40L75,40L75,30C75,19 65,20 65,20L35,20C24,20 25,30 25,30L25,50L15,50L15,60L65,60L65,65L35,65C24,65 25,75 25,75L25,90C25,101 35,100 35,100L85,100z" fill="#FFD43B"/>
        </g>
        <!-- Book title -->
        <text x="100" y="140" text-anchor="middle" fill="white" font-family="Arial" font-size="16" font-weight="bold">Introduction to Python</text>
        <text x="100" y="160" text-anchor="middle" fill="white" font-family="Arial" font-size="16" font-weight="bold">and Large Language</text>
        <text x="100" y="180" text-anchor="middle" fill="white" font-family="Arial" font-size="16" font-weight="bold">Models</text>
        <!-- Author -->
        <text x="100" y="210" text-anchor="middle" fill="#FFD43B" font-family="Arial" font-size="12">by Coders Guild</text>
    </g>
</svg>';

try {
    // First, clear existing books
    $conn->query("DELETE FROM books");
    
    // Sample books data
    $books = [
        [
            'title' => 'Introduction to Python and Large Language Models',
            'author' => 'Coders Guild',
            'category' => 'programming',
            'description' => 'A comprehensive guide covering Python programming fundamentals and its application in Large Language Models (LLMs). Learn about Python basics, AI concepts, and how to work with modern language models.',
            'cover_url' => 'icons/courses.jpeg',
            'file_type' => 'pdf',
            'content' => 'https://codersguild.net/download/1301_Introduction_to_Python_and_Large_Language_Models.pdf'
        ],
        [
            'title' => 'Advanced Web Development with React',
            'author' => 'Tech Master',
            'category' => 'programming',
            'description' => 'Master modern web development using React. Learn about components, hooks, state management, and building responsive web applications.',
            'cover_url' => 'icons/courses.jpeg',
            'file_type' => 'pdf',
            'content' => 'Sample content for React development'
        ],
        [
            'title' => 'Data Science Fundamentals',
            'author' => 'Data Expert',
            'category' => 'programming',
            'description' => 'Learn the basics of data science, including data analysis, visualization, and machine learning concepts using Python and popular libraries.',
            'cover_url' => 'icons/courses.jpeg',
            'file_type' => 'pdf',
            'content' => 'Sample content for Data Science'
        ],
        [
            'title' => 'Mobile App Development with Flutter',
            'author' => 'App Guru',
            'category' => 'programming',
            'description' => 'Create beautiful cross-platform mobile applications using Flutter. Learn Dart programming and build responsive UIs.',
            'cover_url' => 'icons/courses.jpeg',
            'file_type' => 'pdf',
            'content' => 'Sample content for Flutter development'
        ],
        [
            'title' => 'Cybersecurity Essentials',
            'author' => 'Security Pro',
            'category' => 'programming',
            'description' => 'Understanding fundamental concepts of cybersecurity, including network security, encryption, and ethical hacking.',
            'cover_url' => 'icons/courses.jpeg',
            'file_type' => 'pdf',
            'content' => 'Sample content for Cybersecurity'
        ],
        [
            'title' => 'Cloud Computing with AWS',
            'author' => 'Cloud Expert',
            'category' => 'programming',
            'description' => 'Learn cloud computing concepts and practical implementation using Amazon Web Services (AWS).',
            'cover_url' => 'icons/courses.jpeg',
            'file_type' => 'pdf',
            'content' => 'Sample content for AWS'
        ],
        [
            'title' => 'Game Development with Unity',
            'author' => 'Game Dev Pro',
            'category' => 'programming',
            'description' => 'Create engaging 2D and 3D games using Unity engine. Learn C# programming and game design principles.',
            'cover_url' => 'icons/courses.jpeg',
            'file_type' => 'pdf',
            'content' => 'Sample content for Unity development'
        ],
        [
            'title' => 'DevOps and CI/CD Pipeline',
            'author' => 'DevOps Master',
            'category' => 'programming',
            'description' => 'Master DevOps practices and learn to build efficient CI/CD pipelines using modern tools and technologies.',
            'cover_url' => 'icons/courses.jpeg',
            'file_type' => 'pdf',
            'content' => 'Sample content for DevOps'
        ]
    ];

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO books (title, author, category, description, cover_url, content, file_type) VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Insert each book
    foreach ($books as $book) {
        $stmt->bind_param("sssssss", 
            $book['title'],
            $book['author'],
            $book['category'],
            $book['description'],
            $book['cover_url'],
            $book['content'],
            $book['file_type']
        );
        
        if ($stmt->execute()) {
            echo "<p>Successfully added: " . htmlspecialchars($book['title']) . "</p>";
        } else {
            echo "<p>Error adding book: " . htmlspecialchars($stmt->error) . "</p>";
        }
    }

    $stmt->close();
    $conn->close();

    echo "<p>All books have been added to the library!</p>";

} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 