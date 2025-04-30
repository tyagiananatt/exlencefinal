<?php
session_start();

// If user is already logged in, redirect to the return URL or index page
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $return_to = isset($_SESSION['return_to']) ? $_SESSION['return_to'] : 'index.php';
    unset($_SESSION['return_to']);
    header("Location: " . $return_to);
    exit();
}

require_once 'db_connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Set all required session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['user_id'] = $user['id']; // Add this for compatibility
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user'; // Default to 'user' if role is not set
                
                // Record login time
                $login_time = date('Y-m-d H:i:s');
                $login_sql = "INSERT INTO user_time_logs (user_id, login_time) VALUES (?, ?)";
                if ($login_stmt = $conn->prepare($login_sql)) {
                    $login_stmt->bind_param("is", $user['id'], $login_time);
                    $login_stmt->execute();
                    $_SESSION['login_record_id'] = $conn->insert_id;
                }
                
                // Redirect based on role
                if ($user['role'] === 'mentor') {
                    header("Location: queries.php");
                } else {
                    // Check for return URL in session or redirect to index page
                    $return_to = isset($_SESSION['return_to']) ? $_SESSION['return_to'] : 'index.php';
                    unset($_SESSION['return_to']);
                    header("Location: " . $return_to);
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(45deg, #4A90E2, #5C6BC0);
        }

        .container {
            display: flex;
            align-items: center;
            gap: 50px;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        }

        .login-animation {
            width: 400px;
            height: 400px;
            object-fit: contain;
        }

        .wrapper {
            position: relative;
            width: 380px;
            background: transparent;
            padding: 40px;
        }

        .form-box h2 {
            font-size: 2.2em;
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }

        .input-box {
            position: relative;
            width: 100%;
            height: 50px;
            margin: 25px 0;
        }

        .input-box input {
            width: 100%;
            height: 100%;
            background: transparent;
            border: none;
            outline: none;
            border-bottom: 2px solid #999;
            padding: 0 20px 0 5px;
            font-size: 1em;
            color: #333;
            transition: .5s;
        }

        .input-box label {
            position: absolute;
            top: 50%;
            left: 5px;
            transform: translateY(-50%);
            font-size: 1em;
            color: #999;
            pointer-events: none;
            transition: .5s;
        }

        .input-box input:focus ~ label,
        .input-box input:valid ~ label {
            top: -5px;
            color: #4A90E2;
        }

        .input-box input:focus,
        .input-box input:valid {
            border-bottom: 2px solid #4A90E2;
        }

        .btn {
            width: 100%;
            height: 45px;
            background: #4A90E2;
            border: none;
            outline: none;
            border-radius: 40px;
            cursor: pointer;
            font-size: 1em;
            color: #fff;
            font-weight: 500;
            transition: .3s;
        }

        .btn:hover {
            background: #5C6BC0;
            transform: translateY(-2px);
        }

        .register-link {
            font-size: .9em;
            color: #666;
            text-align: center;
            margin: 25px 0 10px;
        }

        .register-link p a {
            color: #4A90E2;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link p a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: #ff3333;
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.9em;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .error-shake {
            animation: shake 0.5s ease-in-out;
        }

        @media (max-width: 900px) {
            .container {
                flex-direction: column-reverse;
                padding: 20px;
            }

            .login-animation {
                width: 300px;
                height: 300px;
            }

            .wrapper {
                width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://cdnl.iconscout.com/lottie/premium/thumb/profile-login-6760440-5627366.gif" alt="Login Animation" class="login-animation">
        <div class="wrapper">
            <div class="form-box">
                <h2>Login</h2>
                <?php if (!empty($error)): ?>
                    <div class="error-message error-shake"><?php echo $error; ?></div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="input-box">
                        <input type="text" name="username" required>
                        <label>Username</label>
                    </div>
                    <div class="input-box">
                        <input type="password" name="password" required>
                        <label>Password</label>
                    </div>
                    <button type="submit" class="btn">Login</button>
                    <div class="register-link">
                        <p>Don't have an account? <a href="register.php">Register</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Check for return URL in sessionStorage
        document.addEventListener('DOMContentLoaded', function() {
            const returnTo = sessionStorage.getItem('returnTo');
            if (returnTo) {
                // Send the return URL to the server
                fetch('set_return_url.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'return_to=' + encodeURIComponent(returnTo)
                }).then(() => {
                    sessionStorage.removeItem('returnTo');
                });
            }
        });
    </script>
</body>
</html>