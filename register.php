<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// Include database connection
require_once "database.php";

// Define variables and initialize with empty values
$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";
$success_message = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } elseif (strlen(trim($_POST["username"])) < 3 || strlen(trim($_POST["username"])) > 50) {
        $username_err = "Username must be between 3 and 50 characters.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email address.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = trim($_POST["email"]);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $email_err = "This email is already registered.";
                } else {
                    $email = trim($_POST["email"]);
                }
            }
            $stmt->close();
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
         
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sss", $param_username, $param_email, $param_password);
            
            // Set parameters
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                $success_message = "Registration successful! You can now <a href='login.php'>login</a>.";
                // Clear form data
                $username = $email = $password = $confirm_password = "";
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --secondary: #5C6BC0;
            --success: #42b883;
            --error: #ff4444;
            --text: #333;
            --text-light: #666;
            --background: #f0f2f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            display: flex;
            align-items: center;
            gap: 50px;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            width: 100%;
        }

        .register-animation {
            width: 400px;
            height: 400px;
            object-fit: contain;
        }

        .wrapper {
            background: transparent;
            padding: 20px;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            transform-style: preserve-3d;
            perspective: 1000px;
            animation: fadeIn 0.5s ease-out;
            box-shadow: none;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            color: var(--text);
            text-align: center;
            margin-bottom: 30px;
            font-size: 2em;
            font-weight: 600;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
            transform-style: preserve-3d;
        }

        .form-group label {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: all 0.3s ease;
            pointer-events: none;
            font-size: 0.9em;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(74, 144, 226, 0.1);
            border-radius: 10px;
            font-size: 1em;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label {
            top: 0;
            transform: translateY(-50%) scale(0.9);
            background: white;
            padding: 0 5px;
            color: var(--primary);
        }

        .form-group .help-text {
            font-size: 0.8em;
            color: var(--text-light);
            margin-top: 5px;
            padding-left: 15px;
        }

        .error-text {
            color: var(--error);
            font-size: 0.85em;
            margin-top: 5px;
            padding-left: 15px;
            animation: shake 0.3s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 50px;
            background: var(--primary);
            color: white;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            background: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
        }

        .btn:hover::after {
            transform: translateX(100%);
            transition: transform 0.6s ease;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
            color: var(--text-light);
        }

        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .success-message {
            background: var(--success);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        .success-message a {
            color: white;
            font-weight: 600;
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .container {
                flex-direction: column-reverse;
                padding: 20px;
            }

            .register-animation {
                width: 300px;
                height: 300px;
            }

            .wrapper {
                max-width: 100%;
                padding: 20px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://cdn.dribbble.com/users/277921/screenshots/4223608/gw-dribbble.gif" alt="Register Animation" class="register-animation">
        <div class="wrapper">
            <h2>Create Account</h2>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <input type="text" name="username" id="username" placeholder=" " value="<?php echo $username; ?>">
                    <label for="username">Username</label>
                    <?php if (!empty($username_err)): ?>
                        <div class="error-text"><?php echo $username_err; ?></div>
                    <?php endif; ?>
                    <div class="help-text">Use only letters, numbers and underscores</div>
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" id="email" placeholder=" " value="<?php echo $email; ?>">
                    <label for="email">Email Address</label>
                    <?php if (!empty($email_err)): ?>
                        <div class="error-text"><?php echo $email_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" id="password" placeholder=" ">
                    <label for="password">Password</label>
                    <?php if (!empty($password_err)): ?>
                        <div class="error-text"><?php echo $password_err; ?></div>
                    <?php endif; ?>
                    <div class="help-text">Minimum 6 characters</div>
                </div>
                
                <div class="form-group">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder=" ">
                    <label for="confirm_password">Confirm Password</label>
                    <?php if (!empty($confirm_password_err)): ?>
                        <div class="error-text"><?php echo $confirm_password_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn">Register</button>
            </form>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html> 