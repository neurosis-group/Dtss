<?php
/**
 * DTSS - Resume upload handler (resume-upload.php)
 * Saves the application to data/leads/resume.json, stores the CV in
 * data/leads/resumes/ (blocked from public web access) and emails the team.
 */
if (session_id() === '') {
    @session_start();
}
require_once __DIR__ . '/leads-store.php';

$back      = 'resume-upload';
$backLabel = 'Back to resume form';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $back);
    exit;
}

/* --- honeypot / flood guard --- */
$spam = dtss_spam_check();
if ($spam !== '') {
    dtss_form_response(false, 'Submission blocked', $spam, $back, $backLabel);
}

/* --- collect --- */
$name     = dtss_clean(isset($_POST['name']) ? $_POST['name'] : '', 120);
$phone    = dtss_clean(isset($_POST['phone']) ? $_POST['phone'] : '', 20);
$email    = dtss_clean(isset($_POST['email']) ? $_POST['email'] : '', 150);
$position = dtss_clean(isset($_POST['position']) ? $_POST['position'] : '', 100);
$message  = dtss_clean(isset($_POST['message']) ? $_POST['message'] : '', 2000);

/* --- validate --- */
$errors = array();
if ($name === '') {
    $errors[] = 'Please enter your name.';
}
$digits = preg_replace('/\D+/', '', $phone);
if (strlen($digits) < 10 || strlen($digits) > 13) {
    $errors[] = 'Please enter a valid phone number.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if ($position === '') {
    $errors[] = 'Please select the position you are applying for.';
}

/* --- resume file --- */
$maxBytes = 5 * 1024 * 1024;
$allowed  = array('pdf', 'doc', 'docx');
$file     = isset($_FILES['resume']) ? $_FILES['resume'] : null;
$ext      = '';

if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = 'Please attach your resume.';
} elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
    $errors[] = 'Your resume is too large. Please upload a file under 5 MB.';
} elseif ($file['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'The file could not be uploaded. Please try again.';
} else {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        $errors[] = 'Only PDF, DOC or DOCX files are accepted.';
    } elseif ($file['size'] > $maxBytes) {
        $errors[] = 'Your resume is too large. Please upload a file under 5 MB.';
    } elseif (!is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'The file could not be uploaded. Please try again.';
    } elseif (function_exists('finfo_open')) {
        $fi   = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $fi ? finfo_file($fi, $file['tmp_name']) : '';
        if ($fi) {
            finfo_close($fi);
        }
        $okMimes = array(
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream',
            'application/x-ole-storage',
            'application/CDFV2',
        );
        if ($mime !== '' && !in_array($mime, $okMimes, true)) {
            $errors[] = 'The uploaded file does not look like a PDF or Word document.';
        }
    }
}

if (!empty($errors)) {
    dtss_form_response(false, 'Please check your details', implode(' ', $errors), $back, $backLabel);
}

/* --- store the file outside public reach --- */
dtss_leads_bootstrap();
$dir = DTSS_LEADS_DIR . DIRECTORY_SEPARATOR . 'resumes';
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}
if (!file_exists($dir . DIRECTORY_SEPARATOR . 'index.html')) {
    @file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.html', '');
}

$stored = date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
if (!@move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $stored)) {
    dtss_form_response(false, 'Upload failed', 'Sorry, we could not save your resume. Please try again or email it to contactus@dtss.in.', $back, $backLabel);
}

$_SESSION['dtss_last_submit'] = time();

$row = dtss_save_lead('resume', array(
    'name'            => $name,
    'phone'           => $phone,
    'email'           => $email,
    'position'        => $position,
    'message'         => $message,
    'resume'          => $stored,
    'resume_original' => dtss_clean($file['name'], 200),
));

/* --- email notification (never blocks the submission) --- */
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'dtss.in';
$baseUrl = $scheme . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$dlLink  = $baseUrl . '/resume-download.php?id=' . rawurlencode($row['id']);

$h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$cells = array(
    'Name'          => $h($name),
    'Phone'         => $h($phone),
    'Email'         => '<a href="mailto:' . $h($email) . '">' . $h($email) . '</a>',
    'Position'      => $h($position),
    'Message'       => nl2br($h($message)),
    'Resume'        => '<a href="' . $h($dlLink) . '">Download ' . $h($file['name']) . '</a><br><small style="color:#777">Opens after admin login</small>',
    'Submitted on'  => $h(isset($row['date']) ? $row['date'] : date('d-m-Y H:i:s')),
);
$rowsHtml = '';
foreach ($cells as $label => $value) {
    if ($value === '') {
        continue;
    }
    $rowsHtml .= '<tr><td style="padding:8px 12px;border:1px solid #e5e5e5;background:#f7f7f7;font-weight:bold;width:140px;vertical-align:top">'
        . $label . '</td><td style="padding:8px 12px;border:1px solid #e5e5e5">' . $value . '</td></tr>';
}
$html = '<html><body style="font-family:Arial,sans-serif;font-size:14px;color:#222">'
    . '<div style="max-width:640px">'
    . '<h2 style="color:#1d2b4f;margin:0 0 12px">New resume received - ' . $h($position) . '</h2>'
    . '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%">' . $rowsHtml . '</table>'
    . '<p style="color:#777;font-size:12px;margin-top:14px">All applications are also listed in the DTSS website dashboard.</p>'
    . '</div></body></html>';

dtss_send_mail(
    dtss_notify_recipients('resume'),
    'New resume: ' . $position . ' - ' . $name,
    $html,
    $email,
    'form resume / id ' . $row['id']
);

dtss_form_response(true, 'Thank you for applying!', 'We have received your resume for the ' . $position . ' position. Our HR team will get in touch with you if your profile matches our requirements.', $back, $backLabel);
