<?php
header('Content-Type: application/json');

// Get form data
$name = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

// Validate inputs
if (empty($name) || empty($email) || empty($message)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all fields'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address'
    ]);
    exit;
}

// Email configuration
$to = 'anttyagi710@gmail.com';
$subject = 'New Contact Form Submission';
$headers = [
    'From' => $email,
    'Reply-To' => $email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'Content-Type' => 'text/html; charset=UTF-8'
];

// Email body
$emailBody = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 5px; }
        .content { margin-top: 20px; }
        .footer { margin-top: 20px; font-size: 12px; color: #666; }
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
        <div class='footer'>
            <p>This email was sent from your website's contact form.</p>
        </div>
    </div>
</body>
</html>
";

// Try to send the email
try {
    $mailSent = mail($to, $subject, $emailBody, implode("\r\n", array_map(
        function ($v, $k) { return "$k: $v"; },
        $headers,
        array_keys($headers)
    )));

    if ($mailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send message. Please try again later.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?> 