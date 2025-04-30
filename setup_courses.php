<?php
require_once 'database.php';

try {
    // Create sample courses
    $sample_courses = [
        [
            'title' => 'Introduction to Web Development',
            'description' => 'Learn the basics of HTML, CSS, and JavaScript to build modern websites.',
            'image_url' => 'images/courses/web-dev.jpg'
        ],
        [
            'title' => 'Python Programming',
            'description' => 'Master Python programming from basics to advanced concepts.',
            'image_url' => 'images/courses/python.jpg'
        ],
        [
            'title' => 'Data Science Fundamentals',
            'description' => 'Explore data analysis, visualization, and machine learning basics.',
            'image_url' => 'images/courses/data-science.jpg'
        ],
        [
            'title' => 'Mobile App Development',
            'description' => 'Create mobile applications for iOS and Android platforms.',
            'image_url' => 'images/courses/mobile-dev.jpg'
        ]
    ];

    // Insert courses
    $sql = "INSERT INTO courses (title, description, image_url) VALUES (?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        foreach ($sample_courses as $course) {
            $stmt->bind_param("sss", $course['title'], $course['description'], $course['image_url']);
            $stmt->execute();
        }
        echo "Sample courses added successfully!";
    }

} catch (Exception $e) {
    echo "Error setting up courses: " . $e->getMessage();
}
?> 