<?php
require_once 'db_connection.php';

// Create books directory if it doesn't exist
if (!file_exists('books')) {
    mkdir('books', 0777, true);
}

// Sample books with their download URLs (these are actual free PDFs from reputable sources)
$books = [
    [
        'title' => 'Think Python',
        'author' => 'Allen B. Downey',
        'category' => 'programming',
        'description' => 'An introduction to Python programming for beginners. Learn Python concepts through practical examples and exercises.',
        'url' => 'https://greenteapress.com/thinkpython2/thinkpython2.pdf',
        'cover_url' => 'icons/python.jpg'
    ],
    [
        'title' => 'Dive into HTML5',
        'author' => 'Mark Pilgrim',
        'category' => 'programming',
        'description' => 'A comprehensive guide to HTML5 features and APIs. Learn modern web development techniques.',
        'url' => 'https://diveinto.html5doctor.com/dive-into-html5-pdf-latest.pdf',
        'cover_url' => 'icons/web_dev.jpg'
    ],
    [
        'title' => 'Pro Git',
        'author' => 'Scott Chacon and Ben Straub',
        'category' => 'programming',
        'description' => 'Everything you need to know about Git, from basics to advanced techniques.',
        'url' => 'https://github.com/progit/progit2/releases/download/2.1.360/progit.pdf',
        'cover_url' => 'icons/cs_book.jpg'
    ]
];

foreach ($books as $book) {
    $filename = 'books/' . sanitize_filename($book['title']) . '.pdf';
    
    // Download the PDF
    echo "Downloading {$book['title']}...<br>";
    $pdf_content = @file_get_contents($book['url']);
    
    if ($pdf_content !== false) {
        // Save the PDF
        file_put_contents($filename, $pdf_content);
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO books (title, author, category, description, cover_url, content) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", 
            $book['title'],
            $book['author'],
            $book['category'],
            $book['description'],
            $book['cover_url'],
            $filename
        );
        
        if ($stmt->execute()) {
            echo "Added book: {$book['title']}<br>";
        } else {
            echo "Error adding book: {$book['title']} - {$stmt->error}<br>";
        }
        
        $stmt->close();
    } else {
        echo "Error downloading {$book['title']}<br>";
    }
}

$conn->close();

function sanitize_filename($filename) {
    // Remove any character that isn't a letter, number, or underscore
    $filename = preg_replace("/[^a-zA-Z0-9_]/", "_", $filename);
    return strtolower($filename);
}

echo "Book download process completed!";
?> 