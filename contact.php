<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = 'anttyagi710@gmail.com';
    $name = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    
    $subject = "New Contact Form Submission from $name";
    
    $headers = [
        'From' => $email,
        'Reply-To' => $email,
        'X-Mailer' => 'PHP/' . phpversion(),
        'Content-Type' => 'text/html; charset=UTF-8'
    ];

    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f8f9fa; padding: 20px; border-radius: 5px; }
            .content { margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Form Submission</h2>
            </div>
            <div class='content'>
                <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
        </div>
    </body>
    </html>";

    $mailSent = mail($to, $subject, $emailBody, implode("\r\n", array_map(
        function ($v, $k) { return "$k: $v"; },
        $headers,
        array_keys($headers)
    )));

    if ($mailSent) {
        $response = ['success' => true, 'message' => 'Message sent successfully!'];
    } else {
        $response = ['success' => false, 'message' => 'Failed to send message. Please try again.'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Contact Us</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #4A90E2 0%, #5C6BC0 100%);
            min-height: 100vh;
            padding: 40px 0;
        }

        section {
            width: 100%;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            margin-top: 30px;
        }

        .contact-info {
            flex: 1;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .contact-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            background: rgba(74, 144, 226, 0.1);
            transition: all 0.3s ease;
        }

        .contact-info-item:hover {
            transform: translateX(10px);
            background: rgba(74, 144, 226, 0.2);
        }

        .contact-info-icon {
            width: 50px;
            height: 50px;
            background: #4A90E2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 20px;
        }

        .contact-info-content h4 {
            color: #4A90E2;
            margin-bottom: 5px;
        }

        .contact-form {
            flex: 1;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .contact-form h2 {
            color: #4A90E2;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
        }

        .input-box {
            position: relative;
            margin-bottom: 20px;
        }

        .input-box input,
        .input-box textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        .input-box input {
            height: 50px;
        }

        .input-box textarea {
            height: 150px;
            resize: none;
            padding-top: 15px;
        }

        .input-box span {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            pointer-events: none;
            transition: all 0.3s ease;
            background: white;
            padding: 0 5px;
        }

        .input-box textarea + span {
            top: 25px;
            transform: none;
        }

        .input-box input:focus,
        .input-box textarea:focus {
            border-color: #4A90E2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
        }

        .input-box input:focus + span,
        .input-box textarea:focus + span,
        .input-box input:not(:placeholder-shown) + span,
        .input-box textarea:not(:placeholder-shown) + span {
            top: -10px;
            font-size: 14px;
            color: #4A90E2;
            background: white;
            padding: 0 5px;
        }

        .input-box textarea:focus + span,
        .input-box textarea:not(:placeholder-shown) + span {
            top: -10px;
        }

        .input-box input[type="submit"] {
            background: linear-gradient(135deg, #4A90E2 0%, #5C6BC0 100%);
            color: white;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }

        .input-box input[type="submit"]:hover {
            background: linear-gradient(135deg, #5C6BC0 0%, #4A90E2 100%);
            transform: translateY(-2px);
        }

        .section-header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-size: 36px;
            margin-bottom: 15px;
        }

        .section-header p {
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            display: none;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .row {
                flex-direction: column;
            }

            .contact-info,
            .contact-form {
                width: 100%;
            }

            body {
                padding: 20px;
            }

            .section-header h2 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <section>
        <div class="container">
            <div class="section-header">
                <h2>Contact Us</h2>
                <p>Have a question or suggestion? We're here to listen and assist.</p>
            </div>
            
            <div class="row">
                <div class="contact-info">
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-info-content">
                            <h4>Address</h4>
                            <p>Lovely Professional University,Phagwara, INDIA, 140306</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-info-content">
                            <h4>Phone</h4>
                            <p>571-457-2321</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-info-content">
                            <h4>Email</h4>
                            <p>Excellence@gmail.com</p>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form">
                    <div id="successAlert" class="alert alert-success">Message sent successfully!</div>
                    <div id="errorAlert" class="alert alert-error">Error sending message. Please try again.</div>
                    
                    <form action="contact.php" method="POST" id="contact-form">
                        <h2>Send Message</h2>
                        <div class="input-box">
                            <input type="text" required name="fullname" id="fullname" placeholder=" ">
                            <span>Full Name</span>
                        </div>
                        
                        <div class="input-box">
                            <input type="email" required name="email" id="email" placeholder=" ">
                            <span>Email</span>
                        </div>
                        
                        <div class="input-box">
                            <textarea required name="message" id="message" placeholder=" "></textarea>
                            <span>Type your Message...</span>
                        </div>
                        
                        <div class="input-box">
                            <input type="submit" value="Send Message">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.getElementById('contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('input[type="submit"]');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            submitBtn.value = 'Sending...';
            submitBtn.disabled = true;
            
            successAlert.style.display = 'none';
            errorAlert.style.display = 'none';

            fetch('contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successAlert.style.display = 'block';
                    this.reset();
                } else {
                    errorAlert.style.display = 'block';
                    errorAlert.textContent = data.message || 'Error sending message. Please try again.';
                }
            })
            .catch(error => {
                errorAlert.style.display = 'block';
                errorAlert.textContent = 'Error sending message. Please try again.';
            })
            .finally(() => {
                submitBtn.value = 'Send Message';
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html> 