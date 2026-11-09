<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate form input values
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
    $phone = filter_var(trim($_POST['phone']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email format');
    }

    // Define recipient email address
    $to = 'shaikhabuzaid046@gmail.com'; // Replace with your email address
    $subject = 'Reports Download Database';

    // Sanitize the email subject and message
    $subject = filter_var(trim($subject), FILTER_SANITIZE_STRING);
    $message = "Name: " . htmlspecialchars($name) . "\nPhone: " . htmlspecialchars($phone) . "\nEmail: " . htmlspecialchars($email);

    // Sanitize the 'From' header
    $from = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    $headers = "From: $from\r\nReply-To: $from\r\n";

    // Send the email
    if (mail($to, $subject, $message, $headers)) {
        // Redirect to a thank you page or display a success message
        header('Location: thank_you.html');
        exit();
    } else {
        echo 'Failed to send email. Please try again.';
    }
}
?>
