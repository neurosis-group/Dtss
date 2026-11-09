<?php
/**
 * DTSS - "Contact Us" collector (branch-locator.php)
 * Stores the enquiry as JSON via leads-store.php and emails the team.
 */

if (session_id() === '') {
    @session_start();
}
require_once __DIR__ . '/leads-store.php';

dtss_migrate_legacy();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: branch-locator.php');
    exit;
}

$spam = dtss_spam_check();
if ($spam !== '') {
    dtss_form_response(false, 'Submission blocked', $spam, 'branch-locator.php');
}

$name    = dtss_clean(isset($_POST['name']) ? $_POST['name'] : '', 120);
$email   = dtss_clean(isset($_POST['email']) ? $_POST['email'] : '', 160);
$subject = dtss_clean(isset($_POST['subject']) ? $_POST['subject'] : '', 200);
$message = dtss_clean(isset($_POST['message']) ? $_POST['message'] : '', 3000);

$errors = array();
if ($name === '') {
    $errors[] = 'Please enter your name.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if ($message === '') {
    $errors[] = 'Please enter your message.';
}

if (!empty($errors)) {
    dtss_form_response(false, 'Please check your details', implode(' ', $errors), 'branch-locator.php', 'Back to the form');
}

$row = dtss_save_lead('contact', array(
    'name'    => $name,
    'email'   => $email,
    'subject' => $subject,
    'message' => $message,
));

$_SESSION['dtss_last_submit'] = time();

dtss_notify_lead('contact', $row);

dtss_form_response(
    true,
    'Thank you, ' . $name,
    'Your enquiry has been received. Our team will get back to you on ' . $email . ' shortly.',
    'branch-locator.php',
    'Back to Contact Us'
);
