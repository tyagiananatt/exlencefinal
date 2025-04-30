<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db_connection.php';
$user_id = $_SESSION["id"];
$success_message = $error_message = "";

// Create notes table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS user_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    color VARCHAR(20) DEFAULT '#ffffff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Handle note actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["action"])) {
        // Add new note
        if ($_POST["action"] == "add") {
            $title = trim($_POST["title"]);
            $content = trim($_POST["content"]);
            $color = $_POST["color"] ?? "#ffffff";
            
            if (!empty($title)) {
                $stmt = $conn->prepare("INSERT INTO user_notes (user_id, title, content, color) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user_id, $title, $content, $color);
                
                if ($stmt->execute()) {
                    $success_message = "Note added successfully!";
                } else {
                    $error_message = "Error adding note. Please try again.";
                }
                $stmt->close();
            } else {
                $error_message = "Title cannot be empty.";
            }
        }
        
        // Update note
        elseif ($_POST["action"] == "update") {
            $note_id = $_POST["note_id"];
            $title = trim($_POST["title"]);
            $content = trim($_POST["content"]);
            $color = $_POST["color"] ?? "#ffffff";
            
            if (!empty($title)) {
                $stmt = $conn->prepare("UPDATE user_notes SET title = ?, content = ?, color = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sssii", $title, $content, $color, $note_id, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Note updated successfully!";
                } else {
                    $error_message = "Error updating note. Please try again.";
                }
                $stmt->close();
            } else {
                $error_message = "Title cannot be empty.";
            }
        }
        
        // Delete note
        elseif ($_POST["action"] == "delete") {
            $note_id = $_POST["note_id"];
            
            $stmt = $conn->prepare("DELETE FROM user_notes WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $note_id, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Note deleted successfully!";
            } else {
                $error_message = "Error deleting note. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Fetch all notes for the user
$notes = [];
$stmt = $conn->prepare("SELECT * FROM user_notes WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4A90E2;
            --secondary: #5C6BC0;
            --accent: #3D5AFE;
            --light: #f5f7fb;
            --dark: #2c3e50;
            --success: #42b883;
            --error: #ff4444;
            --gray-light: #eee;
            --gray: #999;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--dark);
            overflow-x: hidden;
        }

        .wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        h1 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        h1 i {
            color: var(--accent);
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        button, .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 50px;
            background: var(--primary);
            color: white;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(74, 144, 226, 0.2);
        }

        button:hover, .btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(74, 144, 226, 0.3);
        }

        button.back {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        button.back:hover {
            background: var(--light);
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transform: translateX(200%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--error);
        }

        .toast.show {
            transform: translateX(0);
        }

        /* Notes Grid */
        .notes-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .note-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 200px;
        }

        .note-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .note-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            word-break: break-word;
        }

        .note-time {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 5px;
        }

        .note-content {
            font-size: 1rem;
            line-height: 1.5;
            color: #444;
            flex-grow: 1;
            overflow-wrap: break-word;
            margin-bottom: 20px;
            white-space: pre-line;
        }

        .note-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: auto;
        }

        .action-btn {
            background: none;
            border: none;
            padding: 8px;
            color: var(--gray);
            cursor: pointer;
            transition: var(--transition);
            border-radius: 50%;
            box-shadow: none;
        }

        .action-btn:hover {
            color: var(--primary);
            background: var(--light);
            transform: translateY(0);
            box-shadow: none;
        }

        .action-btn.edit:hover {
            color: var(--accent);
        }

        .action-btn.delete:hover {
            color: var(--error);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            padding: 30px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
            transition: color 0.3s ease;
            box-shadow: none;
            padding: 0;
        }

        .close-modal:hover {
            color: var(--error);
            background: none;
            transform: none;
            box-shadow: none;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .color-picker {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .color-option {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
        }

        .color-option:hover {
            transform: scale(1.1);
        }

        .color-option.selected::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            text-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 25px;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .toolbar button {
            padding: 6px 12px;
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #ced4da;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            box-shadow: none;
        }

        .toolbar button:hover {
            background: #e9ecef;
            transform: none;
            box-shadow: none;
        }

        /* Fancy animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .notes-container {
            animation: fadeIn 0.5s ease-out;
        }

        .note-card {
            animation: fadeIn 0.5s ease-out;
            animation-fill-mode: both;
        }

        @media (max-width: 768px) {
            .wrapper {
                padding: 20px 15px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .notes-container {
                grid-template-columns: 1fr;
            }

            .header-actions {
                flex-direction: column;
            }

            .modal {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <header>
            <h1><i class="fas fa-sticky-note"></i> My Notes</h1>
            <div class="header-actions">
                <button class="back" onclick="location.href='index.php'"><i class="fas fa-chevron-left"></i> Back to Dashboard</button>
                <button type="button" id="addNoteBtn" onclick="showAddNoteModal()"><i class="fas fa-plus"></i> New Note</button>
            </div>
        </header>

        <?php if (!empty($success_message)): ?>
            <div class="toast success" id="successToast"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="toast error" id="errorToast"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (empty($notes)): ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h3>No notes yet</h3>
                <p>Create your first note to get started!</p>
                <button type="button" id="emptyStateBtn" onclick="showAddNoteModal()"><i class="fas fa-plus"></i> Create Note</button>
            </div>
        <?php else: ?>
            <div class="notes-container">
                <?php foreach ($notes as $index => $note): ?>
                    <div class="note-card" style="background-color: <?php echo htmlspecialchars($note['color']); ?>">
                        <div class="note-header">
                            <div>
                                <h3 class="note-title"><?php echo htmlspecialchars($note['title']); ?></h3>
                                <div class="note-time">
                                    <?php 
                                    echo date('M j, Y g:i A', strtotime($note['updated_at']));
                                    if ($note['created_at'] != $note['updated_at']) {
                                        echo ' (edited)';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="note-content"><?php echo nl2br(htmlspecialchars($note['content'])); ?></div>
                        <div class="note-actions">
                            <button type="button" class="action-btn edit" onclick="showEditNoteModal(<?php echo $note['id']; ?>, '<?php echo addslashes(htmlspecialchars($note['title'])); ?>', '<?php echo addslashes(htmlspecialchars($note['content'])); ?>', '<?php echo htmlspecialchars($note['color']); ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="action-btn delete" onclick="showDeleteNoteModal(<?php echo $note['id']; ?>, '<?php echo addslashes(htmlspecialchars($note['title'])); ?>')">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Note Modal -->
    <div class="modal-overlay" id="noteModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add New Note</h3>
                <button type="button" class="close-modal" id="closeModal" onclick="closeNoteModal()">&times;</button>
            </div>
            <form id="noteForm" method="POST" action="notes.php">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="note_id" id="noteId" value="">
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Note Title" required>
                </div>
                
                <div class="form-group">
                    <label for="content">Content</label>
                    <div class="toolbar">
                        <button type="button" onclick="formatText('bold')"><i class="fas fa-bold"></i></button>
                        <button type="button" onclick="formatText('italic')"><i class="fas fa-italic"></i></button>
                        <button type="button" onclick="formatText('underline')"><i class="fas fa-underline"></i></button>
                        <button type="button" onclick="formatText('bullet')"><i class="fas fa-list-ul"></i></button>
                        <button type="button" onclick="formatText('number')"><i class="fas fa-list-ol"></i></button>
                    </div>
                    <textarea class="form-control" id="content" name="content" placeholder="Write your note here..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Note Color</label>
                    <div class="color-picker">
                        <div class="color-option selected" style="background-color: #ffffff;" data-color="#ffffff" onclick="selectColor('#ffffff', this)"></div>
                        <div class="color-option" style="background-color: #ffcdd2;" data-color="#ffcdd2" onclick="selectColor('#ffcdd2', this)"></div>
                        <div class="color-option" style="background-color: #ffe0b2;" data-color="#ffe0b2" onclick="selectColor('#ffe0b2', this)"></div>
                        <div class="color-option" style="background-color: #fff9c4;" data-color="#fff9c4" onclick="selectColor('#fff9c4', this)"></div>
                        <div class="color-option" style="background-color: #c8e6c9;" data-color="#c8e6c9" onclick="selectColor('#c8e6c9', this)"></div>
                        <div class="color-option" style="background-color: #b3e5fc;" data-color="#b3e5fc" onclick="selectColor('#b3e5fc', this)"></div>
                        <div class="color-option" style="background-color: #d1c4e9;" data-color="#d1c4e9" onclick="selectColor('#d1c4e9', this)"></div>
                    </div>
                    <input type="hidden" name="color" id="selectedColor" value="#ffffff">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn" id="cancelBtn" onclick="closeNoteModal()">Cancel</button>
                    <button type="submit" class="btn" id="saveBtn">Save Note</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button type="button" class="close-modal" id="closeDeleteModal" onclick="closeDeleteModal()">&times;</button>
            </div>
            <p>Are you sure you want to delete "<span id="deleteNoteTitle"></span>"? This action cannot be undone.</p>
            <div class="form-actions">
                <button type="button" class="btn" id="cancelDeleteBtn" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" action="notes.php" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="note_id" id="deleteNoteId">
                    <button type="submit" class="btn" style="background-color: var(--error);">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show/hide toast messages
        document.addEventListener('DOMContentLoaded', function() {
            const successToast = document.getElementById('successToast');
            const errorToast = document.getElementById('errorToast');
            
            if (successToast) {
                successToast.classList.add('show');
                setTimeout(() => {
                    successToast.classList.remove('show');
                }, 3000);
            }
            
            if (errorToast) {
                errorToast.classList.add('show');
                setTimeout(() => {
                    errorToast.classList.remove('show');
                }, 3000);
            }
        });

        // Direct functions without event listeners
        function showAddNoteModal() {
            document.getElementById('modalTitle').textContent = 'Add New Note';
            document.getElementById('formAction').value = 'add';
            document.getElementById('noteId').value = '';
            document.getElementById('title').value = '';
            document.getElementById('content').value = '';
            document.getElementById('selectedColor').value = '#ffffff';
            
            // Reset color selection
            const colorOptions = document.querySelectorAll('.color-option');
            colorOptions.forEach((opt, index) => {
                if (index === 0) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
            
            document.getElementById('noteModal').classList.add('active');
        }
        
        function showEditNoteModal(id, title, content, color) {
            document.getElementById('modalTitle').textContent = 'Edit Note';
            document.getElementById('formAction').value = 'update';
            document.getElementById('noteId').value = id;
            document.getElementById('title').value = title;
            document.getElementById('content').value = content;
            document.getElementById('selectedColor').value = color;
            
            // Set color selection
            const colorOptions = document.querySelectorAll('.color-option');
            colorOptions.forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.color === color) {
                    opt.classList.add('selected');
                }
            });
            
            document.getElementById('noteModal').classList.add('active');
        }
        
        function closeNoteModal() {
            document.getElementById('noteModal').classList.remove('active');
        }
        
        function showDeleteNoteModal(id, title) {
            document.getElementById('deleteNoteId').value = id;
            document.getElementById('deleteNoteTitle').textContent = title;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        function selectColor(color, element) {
            document.getElementById('selectedColor').value = color;
            
            document.querySelectorAll('.color-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            element.classList.add('selected');
        }
        
        // Text formatting functions
        function formatText(command) {
            const textarea = document.getElementById('content');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            let formattedText = '';
            
            switch(command) {
                case 'bold':
                    formattedText = `**${selectedText}**`;
                    break;
                case 'italic':
                    formattedText = `_${selectedText}_`;
                    break;
                case 'underline':
                    formattedText = `~${selectedText}~`;
                    break;
                case 'bullet':
                    formattedText = selectedText.split('\n').map(line => `• ${line}`).join('\n');
                    break;
                case 'number':
                    formattedText = selectedText.split('\n').map((line, i) => `${i+1}. ${line}`).join('\n');
                    break;
            }
            
            textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
            textarea.focus();
            textarea.selectionStart = start;
            textarea.selectionEnd = start + formattedText.length;
        }

        // Animation for note cards
        document.querySelectorAll('.note-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
</body>
</html> 