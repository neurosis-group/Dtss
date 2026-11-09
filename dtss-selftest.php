<?php
/**
 * DTSS - Enquiry system self test
 * -----------------------------------------------------------------------------
 * Upload this file next to index.php and open:
 *     https://yourdomain.com/dtss-selftest.php?key=dtss2026
 *
 * It checks that every file is present, that the data folder is writable,
 * and that the mail server can actually be reached from this hosting account.
 *
 * DELETE THIS FILE once the setup is confirmed working.
 */

define('DTSS_SELFTEST_KEY', 'dtss2026');

if (!isset($_GET['key']) || $_GET['key'] !== DTSS_SELFTEST_KEY) {
    header('HTTP/1.1 404 Not Found');
    exit('Not found');
}

$checks = array();

function dtss_check(&$checks, $label, $state, $detail = '')
{
    $checks[] = array('label' => $label, 'state' => $state, 'detail' => $detail);
}

/* ---------------------------------------------------------------- PHP ---- */
$phpOk = version_compare(PHP_VERSION, '5.5.0', '>=');
dtss_check($checks, 'PHP version', $phpOk ? 'pass' : 'fail', PHP_VERSION . ($phpOk ? '' : ' - needs 5.5 or newer'));

dtss_check($checks, 'Sessions', function_exists('session_start') ? 'pass' : 'fail', '');
dtss_check($checks, 'password_verify()', function_exists('password_verify') ? 'pass' : 'warn',
    function_exists('password_verify') ? '' : 'Missing - admin login will use the crypt() fallback');
dtss_check($checks, 'openssl / TLS support', extension_loaded('openssl') ? 'pass' : 'fail',
    extension_loaded('openssl') ? '' : 'Required for SMTP on port 465/587. Ask your host to enable it.');
dtss_check($checks, 'stream_socket_client()', function_exists('stream_socket_client') ? 'pass' : 'fail',
    function_exists('stream_socket_client') ? '' : 'Required for SMTP. Ask your host to enable it.');
dtss_check($checks, 'mail() available', function_exists('mail') ? 'pass' : 'warn',
    function_exists('mail') ? '' : 'Disabled - SMTP is required');

/* -------------------------------------------------------------- files ---- */
$required = array(
    'leads-store.php'          => 'shared JSON storage',
    'admin-auth.php'           => 'admin login',
    'mail-config.php'          => 'SMTP settings',
    'mailer-smtp.php'          => 'SMTP sender',
    'store_facility_audit.php' => 'facility audit form handler',
    'mail.php'                 => 'contact form handler',
    'dashboard.php'            => 'admin dashboard',
    'login.php'                => 'admin login page',
    'export-csv.php'           => 'CSV export',
);
$missing = array();
foreach ($required as $file => $what) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $missing[] = $file . ' (' . $what . ')';
    }
}
dtss_check($checks, 'Required files uploaded', empty($missing) ? 'pass' : 'fail',
    empty($missing) ? 'All ' . count($required) . ' files present' : 'MISSING: ' . implode(', ', $missing));

/* ------------------------------------------------- forms wired up? ------- */
$formChecks = array(
    'header.php' => array(
        'need'    => 'store_facility_audit.php',
        'bad'     => 'txtCaptcha',
        'label'   => 'Popup audit form (header.php)',
    ),
    'index.php' => array(
        'need'    => 'store_facility_audit.php',
        'bad'     => '',
        'label'   => 'Home banner form (index.php)',
    ),
    'branch-locator.php' => array(
        'need'    => 'mail.php',
        'bad'     => '',
        'label'   => 'Contact form (branch-locator.php)',
    ),
    'footer-link.php' => array(
        'need'    => 'dtssSubmitForm',
        'bad'     => 'refreshCaptcha()\n        {',
        'label'   => 'Form JavaScript (footer-link.php)',
    ),
);
foreach ($formChecks as $file => $rule) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        dtss_check($checks, $rule['label'], 'fail', $file . ' is missing');
        continue;
    }
    $content = file_get_contents($path);
    if (strpos($content, $rule['need']) === false) {
        dtss_check($checks, $rule['label'], 'fail',
            'This is the OLD version of ' . $file . ' - re-upload it (expected to contain "' . $rule['need'] . '")');
    } elseif ($rule['bad'] !== '' && strpos($content, $rule['bad']) !== false) {
        dtss_check($checks, $rule['label'], 'warn', 'Old CAPTCHA code still present in ' . $file);
    } else {
        dtss_check($checks, $rule['label'], 'pass', 'Wired correctly');
    }
}

/* --------------------------------------------------------- data folder --- */
$dataDir  = __DIR__ . '/data';
$leadsDir = $dataDir . '/leads';

if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}
if (!is_dir($leadsDir)) {
    @mkdir($leadsDir, 0755, true);
}

if (!is_dir($leadsDir)) {
    dtss_check($checks, 'data/leads/ folder', 'fail', 'Could not be created. Create it manually and set permissions to 755.');
} else {
    $probe = $leadsDir . '/.writetest';
    $wrote = @file_put_contents($probe, 'ok');
    if ($wrote === false) {
        dtss_check($checks, 'data/leads/ writable', 'fail',
            'NOT writable - this is why submissions are not being saved. Set the folder permission to 755 (or 775) in cPanel File Manager.');
    } else {
        @unlink($probe);
        dtss_check($checks, 'data/leads/ writable', 'pass', $leadsDir);
    }

    $ht = $leadsDir . '/.htaccess';
    dtss_check($checks, 'data/leads/ protected', file_exists($ht) ? 'pass' : 'warn',
        file_exists($ht) ? '.htaccess deny rule present' : '.htaccess missing - lead data may be publicly readable');
}

/* ------------------------------------------------- stored submissions --- */
if (file_exists(__DIR__ . '/leads-store.php')) {
    require_once __DIR__ . '/leads-store.php';
    $totals = array();
    foreach (dtss_lead_forms() as $key => $cfg) {
        $totals[] = $cfg['short'] . ': ' . count(dtss_read_leads($key));
    }
    dtss_check($checks, 'Stored enquiries', 'info', implode('  |  ', $totals));
}

/* ------------------------------------------------------------- SMTP ----- */
if (file_exists(__DIR__ . '/mail-config.php')) {
    require_once __DIR__ . '/mail-config.php';

    if (!DTSS_SMTP_ENABLED) {
        dtss_check($checks, 'SMTP enabled', 'fail', 'DTSS_SMTP_ENABLED is false in mail-config.php');
    } elseif (DTSS_SMTP_PASS === '' || DTSS_SMTP_PASS === 'PASTE-APP-PASSWORD-HERE') {
        dtss_check($checks, 'SMTP password', 'fail', 'No app password set in mail-config.php');
    } else {
        dtss_check($checks, 'SMTP configured', 'pass',
            DTSS_SMTP_USER . ' via ' . DTSS_SMTP_HOST . ':' . DTSS_SMTP_PORT . ' (' . DTSS_SMTP_SECURE . ')');

        // Can this server even open a connection to the mail host?
        $host   = (strtolower(DTSS_SMTP_SECURE) === 'ssl') ? 'ssl://' . DTSS_SMTP_HOST : DTSS_SMTP_HOST;
        $errNo  = 0;
        $errStr = '';
        $ctx    = stream_context_create(array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false)));
        $sock   = @stream_socket_client($host . ':' . DTSS_SMTP_PORT, $errNo, $errStr, 10, STREAM_CLIENT_CONNECT, $ctx);

        if ($sock) {
            $greeting = fgets($sock, 512);
            fclose($sock);
            dtss_check($checks, 'Connection to mail server', 'pass', trim($greeting));
        } else {
            dtss_check($checks, 'Connection to mail server', 'fail',
                'Cannot reach ' . DTSS_SMTP_HOST . ':' . DTSS_SMTP_PORT . ' - ' . $errStr . ' (' . $errNo . '). '
                . 'Most shared hosts block outgoing SMTP by default. Ask your host to open port '
                . DTSS_SMTP_PORT . ', or use the host\'s own mail server instead of Gmail.');
        }
    }

    // Live send test
    if (isset($_GET['sendtest']) && file_exists(__DIR__ . '/leads-store.php')) {
        $html = '<html><body style="font-family:Arial,sans-serif"><h2>DTSS self test</h2>'
              . '<p>If you are reading this, enquiry notifications work on the live server.</p>'
              . '<p style="color:#999;font-size:12px">Sent ' . date('d-m-Y H:i:s') . '</p></body></html>';
        $sent = dtss_send_mail(dtss_notify_recipients(), 'DTSS self test email', $html, '', 'self test');
        dtss_check($checks, 'Test email', $sent ? 'pass' : 'fail',
            $sent ? 'Sent to ' . implode(', ', dtss_notify_recipients()) . ' - check the inbox and spam folder'
                  : 'Failed. See the SMTP log below.');
    }
}

/* -------------------------------------------------------- mail log ------ */
$mailLog = '';
if (is_dir($leadsDir) && file_exists($leadsDir . '/mail-errors.log')) {
    $lines   = explode("\n", trim(file_get_contents($leadsDir . '/mail-errors.log')));
    $mailLog = implode("\n", array_slice($lines, -30));
}

$fails = 0;
$warns = 0;
foreach ($checks as $c) {
    if ($c['state'] === 'fail') { $fails++; }
    if ($c['state'] === 'warn') { $warns++; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>DTSS enquiry system - self test</title>
<style>
  *{box-sizing:border-box}
  body{margin:0;background:#f2f3f5;font-family:Arial,Helvetica,sans-serif;color:#2b2b2b;font-size:14px;padding:24px}
  .wrap{max-width:900px;margin:0 auto}
  h1{font-size:20px;margin:0 0 6px}
  .sub{color:#888;font-size:13px;margin:0 0 22px}
  .summary{padding:14px 18px;border-radius:5px;margin-bottom:20px;font-weight:bold}
  .summary.ok{background:#e9f5ea;color:#2f6b36;border-left:4px solid #4c9a55}
  .summary.bad{background:#fdecea;color:#a33;border-left:4px solid #d9534f}
  table{width:100%;border-collapse:collapse;background:#fff;border-radius:5px;overflow:hidden;
        box-shadow:0 1px 3px rgba(0,0,0,.08)}
  td{padding:12px 14px;border-bottom:1px solid #f2f2f2;vertical-align:top}
  td.s{width:74px}
  .pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:10.5px;font-weight:bold;letter-spacing:.5px}
  .pass{background:#e9f5ea;color:#3f7d45}
  .fail{background:#fdecea;color:#c0392b}
  .warn{background:#fff4de;color:#a97806}
  .info{background:#eef2f7;color:#5a6b80}
  td.l{width:250px;font-weight:bold;color:#444}
  td.d{color:#666;line-height:1.6}
  pre{background:#fff;border:1px solid #e6e6e6;border-radius:5px;padding:14px;overflow:auto;
      font-size:11.5px;color:#666;white-space:pre-wrap;margin:18px 0 0}
  .actions{margin:22px 0 0;display:flex;gap:10px;flex-wrap:wrap}
  a.btn{display:inline-block;background:#fcaf17;color:#fff;text-decoration:none;padding:11px 18px;
        border-radius:3px;font-weight:bold;font-size:13px}
  a.btn.grey{background:#5a5a5a}
  .note{margin-top:22px;padding:14px 16px;background:#fff8e6;border-left:4px solid #fcaf17;
        border-radius:3px;font-size:13px;line-height:1.7;color:#6b5a2a}
</style>
</head>
<body>
<div class="wrap">
  <h1>DTSS enquiry system &mdash; self test</h1>
  <p class="sub"><?php echo htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8'); ?>
     &middot; <?php echo date('d-m-Y H:i:s'); ?></p>

  <?php if ($fails === 0) { ?>
    <div class="summary ok">Everything looks good<?php echo $warns ? ' (' . $warns . ' warning' . ($warns > 1 ? 's' : '') . ')' : ''; ?>.</div>
  <?php } else { ?>
    <div class="summary bad"><?php echo $fails; ?> problem<?php echo $fails > 1 ? 's' : ''; ?> found &mdash; see the red rows below.</div>
  <?php } ?>

  <table>
    <?php foreach ($checks as $c) { ?>
      <tr>
        <td class="s"><span class="pill <?php echo $c['state']; ?>"><?php echo strtoupper($c['state']); ?></span></td>
        <td class="l"><?php echo htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="d"><?php echo htmlspecialchars($c['detail'], ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
    <?php } ?>
  </table>

  <div class="actions">
    <a class="btn" href="?key=<?php echo urlencode(DTSS_SELFTEST_KEY); ?>&amp;sendtest=1">Send a test email now</a>
    <a class="btn grey" href="?key=<?php echo urlencode(DTSS_SELFTEST_KEY); ?>">Re-run checks</a>
  </div>

  <?php if ($mailLog !== '') { ?>
    <pre><?php echo htmlspecialchars($mailLog, ENT_QUOTES, 'UTF-8'); ?></pre>
  <?php } ?>

  <div class="note">
    Delete <strong>dtss-selftest.php</strong> from the server once everything passes.
  </div>
</div>
</body>
</html>
