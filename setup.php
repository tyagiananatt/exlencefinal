<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - CIPHERTHON</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #4A90E2 0%, #5C6BC0 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .setup-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .setup-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .setup-status {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .status-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
        }

        .success {
            background: #28a745;
        }

        .error {
            background: #dc3545;
        }

        .status-text {
            flex-grow: 1;
        }

        .status-text h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }

        .status-text p {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .credentials-box {
            background: #e8f4ff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .credentials-box h3 {
            color: #2d6da3;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .credential-item {
            margin-bottom: 10px;
        }

        .credential-item span {
            font-weight: 500;
            color: #4A90E2;
        }

        .btn {
            display: inline-block;
            background: #4A90E2;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
            text-align: center;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #357ABD;
        }

        .center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <h1>CIPHERTHON Setup</h1>
            <p>Initializing your application</p>
        </div>

        <div class="setup-box">
            <?php
            $success = true;
            $error_message = '';

            try {
                require_once 'database.php';

                // Create a test user
                $username = "admin";
                $password = password_hash("password123", PASSWORD_DEFAULT);
                $email = "admin@example.com";

                // Check if test user exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_exists = $result->num_rows > 0;

                if (!$user_exists) {
                    $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $password, $email);
                    $stmt->execute();
                }

                $stmt->close();
                $conn->close();

            } catch (Exception $e) {
                $success = false;
                $error_message = $e->getMessage();
            }
            ?>

            <div class="setup-status">
                <div class="status-icon <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo $success ? '✓' : '×'; ?>
                </div>
                <div class="status-text">
                    <h3>Database Setup</h3>
                    <p><?php echo $success ? 'Successfully initialized database and tables' : 'Error: ' . $error_message; ?></p>
                </div>
            </div>

            <div class="setup-status">
                <div class="status-icon <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo $success ? '✓' : '×'; ?>
                </div>
                <div class="status-text">
                    <h3>Test User</h3>
                    <p><?php echo $success ? ($user_exists ? 'Test user already exists' : 'Test user created successfully') : 'Failed to create test user'; ?></p>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="credentials-box">
                <h3>Default Login Credentials</h3>
                <div class="credential-item">Username: <span>admin</span></div>
                <div class="credential-item">Password: <span>password123</span></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="center">
            <a href="login.php" class="btn">Go to Login Page</a>
        </div>
    </div>
</body>
</html> 