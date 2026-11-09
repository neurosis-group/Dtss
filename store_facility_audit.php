<?php
/**
 * DTSS - "Book a Free Facility Audit" collector
 * -----------------------------------------------------------------------------
 * Handles both audit forms:
 *   - the popup in header.php        (source = popup)
 *   - the home banner form in index.php (source = home)
 *
 * Stores the enquiry as JSON via leads-store.php and emails the team.
 */

if (session_id() === '') {
    @session_start();
}
require_once __DIR__ . '/leads-store.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/* --- which form posted ---------------------------------------------------- */
$source = isset($_POST['form_source']) ? $_POST['form_source'] : 'popup';
$form   = ($source === 'home') ? 'facility_audit_home' : 'facility_audit_popup';

/* --- honeypot / flood guard ----------------------------------------------- */
$spam = dtss_spam_check();
if ($spam !== '') {
    dtss_form_response(false, 'Submission blocked', $spam, 'index.php');
}

/* --- collect (accepts the field names used by either form) ---------------- */
$name         = dtss_clean(isset($_POST['name']) ? $_POST['name'] : '', 120);
$email        = dtss_clean(isset($_POST['email']) ? $_POST['email'] : '', 160);
$mobile       = isset($_POST['mobile_no']) ? $_POST['mobile_no'] : (isset($_POST['phone']) ? $_POST['phone'] : '');
$mobile       = preg_replace('/[^0-9\+]/', '', dtss_clean($mobile, 20));
$organisation = isset($_POST['organisation_name']) ? $_POST['organisation_name'] : (isset($_POST['organization_name']) ? $_POST['organization_name'] : '');
$organisation = dtss_clean($organisation, 160);

/* --- validate ------------------------------------------------------------- */
$errors = array();

if ($name === '') {
    $errors[] = 'Please enter your name.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid work email address.';
}
if (strlen(preg_replace('/[^0-9]/', '', $mobile)) < 10) {
    $errors[] = 'Please enter a valid 10 digit contact number.';
}
if ($organisation === '') {
    $errors[] = 'Please enter your organisation name.';
}

if (!empty($errors)) {
    dtss_form_response(
        false,
        'Please check your details',
        implode(' ', $errors),
        ($source === 'home') ? 'index.php' : 'index.php'
    );
}

/* --- store ---------------------------------------------------------------- */
$row = dtss_save_lead($form, array(
    'name'              => $name,
    'email'             => $email,
    'mobile_no'         => $mobile,
    'organisation_name' => $organisation,
));

$_SESSION['dtss_last_submit'] = time();

/* --- notify --------------------------------------------------------------- */
dtss_notify_lead($form, $row);

/* --- confirm -------------------------------------------------------------- */
dtss_form_response(
    true,
    'Thank you, ' . $name,
    'Your free facility audit request has been received. Our team will contact you on ' . $email . ' shortly.',
    'index.php',
    'Back to home'
);
