<?php
/**
 * DTSS - secure resume download for the admin dashboard.
 * Files live in data/leads/resumes/ which is blocked from direct web access.
 */
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/leads-store.php';

dtss_admin_require_login();

$id = isset($_GET['id']) ? (string) $_GET['id'] : '';

foreach (dtss_read_leads('resume') as $r) {
    if (!isset($r['id']) || $r['id'] !== $id || empty($r['resume'])) {
        continue;
    }
    $file = basename($r['resume']);
    $path = DTSS_LEADS_DIR . DIRECTORY_SEPARATOR . 'resumes' . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path)) {
        break;
    }
    $ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $types = array(
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    );
    $who  = isset($r['name']) ? $r['name'] : 'candidate';
    $what = isset($r['position']) ? $r['position'] : '';
    $nice = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $who . ' ' . $what . ' Resume'), '_') . '.' . $ext;

    header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . $nice . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

http_response_code(404);
echo 'Resume not found.';
