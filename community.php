<?php
session_start();

// Initialize guest flag - check for loggedin status
$is_guest = !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true;

require_once 'db_connection.php';

// Handle post submission and replies
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_guest) {
        if (isset($_POST['title']) && isset($_POST['content'])) {
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $user_id = $_SESSION['id'];

            if (!empty($title) && !empty($content)) {
                $stmt = $conn->prepare("INSERT INTO community_posts (user_id, title, content, likes) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("iss", $user_id, $title, $content);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Your question has been posted successfully!";
                } else {
                    $_SESSION['error_message'] = "Error posting your question. Please try again.";
                }
                header("Location: community.php");
                exit();
            }
        }

        // Handle likes
        if (isset($_POST['like']) && isset($_POST['post_id'])) {
            $post_id = $_POST['post_id'];
            $user_id = $_SESSION['id'];
            
            // Check if user already liked the post
            $check_stmt = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
            $check_stmt->bind_param("ii", $post_id, $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Unlike
                $delete_stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
                $delete_stmt->bind_param("ii", $post_id, $user_id);
                $delete_stmt->execute();
                
                $update_stmt = $conn->prepare("UPDATE community_posts SET likes = likes - 1 WHERE id = ?");
                $update_stmt->bind_param("i", $post_id);
                $update_stmt->execute();
            } else {
                // Like
                $insert_stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
                $insert_stmt->bind_param("ii", $post_id, $user_id);
                $insert_stmt->execute();
                
                $update_stmt = $conn->prepare("UPDATE community_posts SET likes = likes + 1 WHERE id = ?");
                $update_stmt->bind_param("i", $post_id);
                $update_stmt->execute();
            }
            
            header("Location: community.php#post-" . $post_id);
            exit();
        }

        // Handle reply submission
        if (isset($_POST['reply_content']) && isset($_POST['post_id'])) {
            $reply_content = trim($_POST['reply_content']);
            $post_id = $_POST['post_id'];
            $user_id = $_SESSION['id'];

            if (!empty($reply_content)) {
                try {
                    // First verify that the post exists
                    $check_post = $conn->prepare("SELECT id FROM community_posts WHERE id = ?");
                    $check_post->bind_param("i", $post_id);
                    $check_post->execute();
                    $post_result = $check_post->get_result();

                    if ($post_result->num_rows === 0) {
                        throw new Exception("Post not found");
                    }

                    // Insert the reply
                    $stmt = $conn->prepare("INSERT INTO community_replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("iis", $post_id, $user_id, $reply_content);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Your reply has been posted successfully!";
                    } else {
                        throw new Exception("Error posting reply");
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error posting your reply. Please try again.";
                }
                
                header("Location: community.php#post-" . $post_id);
                exit();
            } else {
                $_SESSION['error_message'] = "Reply content cannot be empty.";
                header("Location: community.php#post-" . $post_id);
                exit();
            }
        }

        // Handle voting
        if (isset($_POST['vote']) && isset($_POST['post_id'])) {
            $post_id = $_POST['post_id'];
            $vote_type = $_POST['vote'];
            
            $vote_value = ($vote_type === 'up') ? 1 : -1;
            
            $stmt = $conn->prepare("UPDATE community_posts SET votes = COALESCE(votes, 0) + ? WHERE id = ?");
            $stmt->bind_param("ii", $vote_value, $post_id);
            $stmt->execute();
            
            header("Location: community.php#post-" . $post_id);
            exit();
        }

        // Add this in the POST handling section at the top of the file
        if (isset($_POST['submit_reply']) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            $post_id = $_POST['post_id'];
            $reply_content = trim($_POST['reply_content']);
            $user_id = $_SESSION['id'];

            if (!empty($reply_content)) {
                $reply_stmt = $conn->prepare("INSERT INTO community_replies (post_id, user_id, content) VALUES (?, ?, ?)");
                $reply_stmt->bind_param("iis", $post_id, $user_id, $reply_content);
                
                if ($reply_stmt->execute()) {
                    // Redirect to prevent form resubmission
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
            }
        }
    } else {
        // Store the current page as return URL and the specific post ID if replying
        $_SESSION['return_to'] = isset($_POST['post_id']) ? 'community.php#post-' . $_POST['post_id'] : 'community.php';
        header("Location: login.php");
        exit();
    }
}

// Fetch all posts with user information, replies, and likes
$query = "SELECT 
    p.id,
    p.title,
    p.content,
    p.created_at,
    IFNULL(p.votes, 0) as votes,
    IFNULL(p.likes, 0) as likes,
    u.username,
    (SELECT COUNT(*) FROM community_replies WHERE post_id = p.id) as reply_count,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
    (SELECT COUNT(*) > 0 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM community_posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$user_id = $is_guest ? 0 : $_SESSION['id'];
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch replies for all posts
$replies = array();
if ($result && $result->num_rows > 0) {
    $reply_query = "SELECT 
        r.*, 
        u.username as replier_name,
        DATE_FORMAT(r.created_at, '%M %d, %Y at %h:%i %p') as formatted_date
        FROM community_replies r
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at ASC";
    $reply_result = $conn->query($reply_query);
    
    while ($reply = $reply_result->fetch_assoc()) {
        if (!isset($replies[$reply['post_id']])) {
            $replies[$reply['post_id']] = array();
        }
        $replies[$reply['post_id']][] = $reply;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --secondary: #5C6BC0;
            --background: #f0f2f5;
            --text: #1a1a1a;
            --text-secondary: #65676b;
            --success: #42b883;
            --error: #ff4444;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--background) 0%, #e8f0fe 100%);
            color: var(--text);
        }

        .top-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-bottom: 20px;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .back-btn, .ask-question-button {
            padding: 10px 20px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .back-btn {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .back-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .ask-question-button {
            background: var(--primary);
            color: white;
            animation: float 3s ease-in-out infinite;
        }

        .ask-question-button:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .ask-question-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transform: scale(0.95);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .ask-question-section.active {
            transform: scale(1);
            opacity: 1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-weight: 500;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(74, 144, 226, 0.1);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            max-width: 100%;
            resize: vertical;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            transform: translateZ(10px);
        }

        .form-group textarea {
            min-height: 120px;
            max-height: 400px;
        }

        .post-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .post-card:hover {
            transform: translateY(-5px) rotateX(2deg);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .post-title {
            font-size: 1.5rem;
            color: var(--text);
            margin: 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: calc(100% - 100px); /* Account for vote buttons */
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .post-content {
            color: var(--text);
            line-height: 1.6;
            margin-bottom: 20px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .vote-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
            transform-style: preserve-3d;
        }

        .vote-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 5px;
            transition: all 0.3s ease;
            transform: translateZ(5px);
        }

        .vote-btn:hover {
            color: var(--primary);
            transform: translateZ(15px) scale(1.2);
        }

        .reply-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e4e6eb;
        }

        .reply-form {
            margin-bottom: 20px;
        }

        .reply-form textarea {
            width: 100%;
            box-sizing: border-box;
            max-width: 100%;
            min-height: 80px;
            max-height: 300px;
        }

        .reply-form textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .reply-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            transform: translateZ(10px);
            transition: all 0.3s ease;
        }

        .reply-card:hover {
            transform: translateZ(20px) scale(1.02);
        }

        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .reply-content {
            color: var(--text);
            line-height: 1.5;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease, float 3s ease-in-out infinite;
            transform: translateZ(20px);
        }

        .success {
            background: var(--success);
            color: white;
        }

        .error {
            background: var(--error);
            color: white;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .btn {
            padding: 10px 20px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
        }

        .btn:hover::after {
            transform: translateX(100%);
            transition: transform 0.6s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
            animation: float 4s ease-in-out infinite;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .like-button {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .like-button:hover {
            transform: scale(1.1) translateZ(10px);
        }

        .like-button.liked {
            color: #e74c3c;
            animation: pulse 0.3s ease;
        }

        .like-button i {
            transition: all 0.3s ease;
        }

        .like-button:hover i {
            transform: scale(1.2) translateZ(20px);
        }

        .like-count {
            font-weight: 500;
            color: var(--text-secondary);
        }

        .interaction-buttons {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .post-title {
                font-size: 1.2rem;
                max-width: calc(100% - 80px);
            }

            .post-card {
                padding: 15px;
            }

            .nav-buttons {
                flex-wrap: wrap;
            }

            .back-btn, .ask-question-button {
                width: 100%;
                justify-content: center;
            }
        }

        .post-replies {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .reply-form {
            margin-bottom: 20px;
        }

        .reply-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            margin-bottom: 10px;
        }

        .reply-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .reply-btn:hover {
            background-color: #0056b3;
        }

        .guest-message {
            color: #666;
            font-style: italic;
        }

        .guest-message a {
            color: #007bff;
            text-decoration: none;
        }

        .guest-message a:hover {
            text-decoration: underline;
        }

        .replies-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .replies-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .replies-header h4 {
            margin: 0;
            color: #2c3e50;
        }

        .replies-header span {
            color: #6c757d;
            font-size: 0.9em;
        }

        .reply-form {
            margin-bottom: 20px;
        }

        .reply-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            min-height: 80px;
            resize: vertical;
        }

        .reply-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .reply-btn:hover {
            background: #0056b3;
        }

        .reply-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .reply-card:hover {
            transform: translateY(-2px);
        }

        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .replier {
            color: #2c3e50;
            font-weight: 500;
        }

        .reply-date {
            color: #6c757d;
        }

        .reply-content {
            color: #2c3e50;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .fas {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="container">
            <div class="nav-buttons">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home
                </a>
                <?php if (!$is_guest): ?>
                <button onclick="showAskQuestionForm()" class="ask-question-button">
                    <i class="fas fa-plus"></i>
                    Ask Question
                </button>
                <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="ask-question-button">
                        <i class="fas fa-sign-in-alt"></i>
                        Log in to Ask Questions
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <div id="askQuestionSection" class="ask-question-section">
            <h2>Ask a Question</h2>
            <form method="POST" action="community.php">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" 
                           placeholder="What's your programming question? Be specific." 
                           required>
                </div>
                <div class="form-group">
                    <label for="content">Question Details</label>
                    <textarea id="content" name="content" 
                            placeholder="Include all the information someone would need to answer your question..."
                            required></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="hideAskQuestionForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post Your Question</button>
                </div>
            </form>
        </div>

        <div class="posts-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="post-card" id="post-<?php echo $row['id']; ?>">
                        <div class="post-header">
                            <h3 class="post-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="vote-buttons">
                                <form method="POST" action="community.php" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="vote" value="up">
                                    <button type="submit" class="vote-btn" <?php echo $is_guest ? 'disabled' : ''; ?>>
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                </form>
                                <span class="vote-count"><?php echo $row['votes']; ?></span>
                                <form method="POST" action="community.php" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="vote" value="down">
                                    <button type="submit" class="vote-btn" <?php echo $is_guest ? 'disabled' : ''; ?>>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="post-meta">
                            <span>Asked by <?php echo htmlspecialchars($row['username']); ?></span>
                            <span>on <?php echo date('M j, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                        </div>
                        
                        <div class="interaction-buttons">
                            <div class="vote-buttons">
                                <!-- ... existing vote buttons ... -->
                            </div>
                            
                            <form method="POST" action="community.php" style="display: inline;">
                                <input type="hidden" name="post_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="like" value="1">
                                <button type="submit" class="like-button <?php echo $row['user_liked'] ? 'liked' : ''; ?>" <?php echo $is_guest ? 'disabled' : ''; ?>>
                                    <i class="fas <?php echo $row['user_liked'] ? 'fa-heart' : 'fa-heart-o'; ?>"></i>
                                    <span class="like-count"><?php echo $row['like_count']; ?></span>
                                </button>
                            </form>
                        </div>

                        <div class="post-replies">
                            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                                <form class="reply-form" method="POST" action="community.php" onsubmit="return validateReplyForm(this)">
                                    <input type="hidden" name="post_id" value="<?php echo $row['id']; ?>">
                                    <div class="form-group">
                                        <textarea name="reply_content" class="form-control" placeholder="Write your reply here..." rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary reply-btn">Post Reply</button>
                                </form>
                            <?php else: ?>
                                <p class="guest-message">Please <a href="login.php?return=community.php">login</a> to reply to this post.</p>
                            <?php endif; ?>

                            <?php
                            // Fetch replies for this post
                            $reply_query = "SELECT r.*, u.username as replier_name, 
                                           DATE_FORMAT(r.created_at, '%M %d, %Y at %h:%i %p') as formatted_date 
                                           FROM community_replies r 
                                           JOIN users u ON r.user_id = u.id 
                                           WHERE r.post_id = ? 
                                           ORDER BY r.created_at DESC";
                            
                            if ($stmt = $conn->prepare($reply_query)) {
                                $stmt->bind_param("i", $row['id']);
                                $stmt->execute();
                                $replies = $stmt->get_result();
                                
                                if ($replies->num_rows > 0) {
                                    echo '<div class="replies-section">';
                                    echo '<div class="replies-header">';
                                    echo '<h4><i class="fas fa-comments"></i> Replies</h4>';
                                    echo '<span>' . $replies->num_rows . ' replies</span>';
                                    echo '</div>';
                                    echo '<div class="replies-list">';
                                    
                                    while ($reply = $replies->fetch_assoc()) {
                                        echo '<div class="reply-card">';
                                        echo '<div class="reply-header">';
                                        echo '<span class="replier"><i class="fas fa-user"></i> ' . htmlspecialchars($reply['replier_name']) . '</span>';
                                        echo '<span class="reply-date"><i class="far fa-clock"></i> ' . $reply['formatted_date'] . '</span>';
                                        echo '</div>';
                                        echo '<div class="reply-content">' . nl2br(htmlspecialchars($reply['content'])) . '</div>';
                                        echo '</div>';
                                    }
                                    
                                    echo '</div>';
                                    echo '</div>';
                                }
                                $stmt->close();
                            }
                            ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h2>No questions yet</h2>
                    <p>Be the first to ask a question!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showAskQuestionForm() {
            const section = document.getElementById('askQuestionSection');
            section.classList.add('active');
            document.getElementById('title').focus();
        }

        function hideAskQuestionForm() {
            const section = document.getElementById('askQuestionSection');
            section.classList.remove('active');
        }

        // Auto-hide messages after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const messages = document.querySelectorAll('.message');
                messages.forEach(function(message) {
                    message.style.display = 'none';
                });
            }, 3000);
        });

        // Add loading animation
        function addLoadingState() {
            const posts = document.querySelectorAll('.post-card');
            posts.forEach(post => post.classList.add('loading'));
            setTimeout(() => {
                posts.forEach(post => post.classList.remove('loading'));
            }, 1000);
        }

        // Initialize loading animation
        document.addEventListener('DOMContentLoaded', addLoadingState);

        function validateReplyForm(form) {
            const replyContent = form.querySelector('textarea[name="reply_content"]').value.trim();
            if (!replyContent) {
                alert('Please write something before posting your reply.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>