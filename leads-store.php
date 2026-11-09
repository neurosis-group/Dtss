<?php
/**
 * DTSS - Shared lead storage helper
 * -----------------------------------------------------------------------------
 * Every website enquiry form writes through this file. Data is stored as plain
 * JSON, one file per form, inside data/leads/ (protected from direct web access
 * by an .htaccess deny rule that this file creates automatically).
 *
 * Used by: store_facility_audit.php, mail.php, dashboard.php
 */

define('DTSS_LEADS_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'leads');

/**
 * Master definition of every form the dashboard knows about.
 * key => label, storage file, and the fields shown as table columns.
 */
function dtss_lead_forms()
{
    return array(
        'facility_audit_popup' => array(
            'label'   => 'Facility Audit (Popup)',
            'short'   => 'Popup',
            'file'    => 'facility_audit_popup.json',
            'columns' => array(
                'name'              => 'Name',
                'email'             => 'Work Email',
                'mobile_no'         => 'Contact Number',
                'organisation_name' => 'Organisation',
            ),
        ),
        'facility_audit_home' => array(
            'label'   => 'Facility Audit (Home Banner)',
            'short'   => 'Home Banner',
            'file'    => 'facility_audit_home.json',
            'columns' => array(
                'name'              => 'Name',
                'email'             => 'Work Email',
                'mobile_no'         => 'Contact Number',
                'organisation_name' => 'Organisation',
            ),
        ),
        'contact' => array(
            'label'   => 'Contact Us',
            'short'   => 'Contact Us',
            'file'    => 'contact.json',
            'columns' => array(
                'name'    => 'Name',
                'email'   => 'Email',
                'subject' => 'Subject',
                'message' => 'Message',
            ),
        ),
        'resume' => array(
            'label'   => 'Resume Upload (Careers)',
            'short'   => 'Resume',
            'file'    => 'resume.json',
            'columns' => array(
                'name'     => 'Name',
                'phone'    => 'Phone',
                'email'    => 'Email',
                'position' => 'Position',
                'message'  => 'Message',
                'resume'   => 'Resume',
            ),
        ),
    );
}

/** Return the config array for a form key, or null. */
function dtss_form_config($form)
{
    $forms = dtss_lead_forms();
    return isset($forms[$form]) ? $forms[$form] : null;
}

/** Make sure data/leads/ exists and is not readable over HTTP. */
function dtss_leads_bootstrap()
{
    if (!is_dir(DTSS_LEADS_DIR)) {
        @mkdir(DTSS_LEADS_DIR, 0755, true);
    }

    $htaccess = DTSS_LEADS_DIR . DIRECTORY_SEPARATOR . '.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents(
            $htaccess,
            "# Block all direct web access to stored enquiry data.\n" .
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
        );
    }

    $index = DTSS_LEADS_DIR . DIRECTORY_SEPARATOR . 'index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }
}

/** Absolute path of the JSON file backing a form. */
function dtss_leads_path($form)
{
    $config = dtss_form_config($form);
    if (!$config) {
        return null;
    }
    return DTSS_LEADS_DIR . DIRECTORY_SEPARATOR . $config['file'];
}

/**
 * Read every stored entry for a form.
 * Returns a plain array (newest last, as written).
 */
function dtss_read_leads($form)
{
    dtss_leads_bootstrap();

    $path = dtss_leads_path($form);
    if (!$path || !file_exists($path)) {
        return array();
    }

    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return array();
    }

    $rows = json_decode($raw, true);
    return is_array($rows) ? $rows : array();
}

/** Overwrite a form's JSON file with the given rows (atomic-ish, with a lock). */
function dtss_write_leads($form, $rows)
{
    dtss_leads_bootstrap();

    $path = dtss_leads_path($form);
    if (!$path) {
        return false;
    }

    $json = json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return file_put_contents($path, $json, LOCK_EX) !== false;
}

/** Trim, strip tags and cap the length of a submitted value. */
function dtss_clean($value, $maxLength = 500)
{
    $value = is_string($value) ? $value : '';
    $value = str_replace(array("\0", "\r"), '', $value);
    $value = strip_tags($value);
    $value = trim($value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }
    return substr($value, 0, $maxLength);
}

/** Best-effort visitor IP. */
function dtss_client_ip()
{
    foreach (array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $key) {
        if (!empty($_SERVER[$key])) {
            $parts = explode(',', $_SERVER[$key]);
            return trim($parts[0]);
        }
    }
    return '';
}

/**
 * Append one submission to a form's JSON file.
 * Adds id / date / ip / page automatically. Returns the stored row.
 */
function dtss_save_lead($form, $fields)
{
    $rows = dtss_read_leads($form);

    $row = array(
        'id'   => date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 6),
        'date' => date('d-m-Y H:i:s'),
    );

    foreach ($fields as $key => $value) {
        $row[$key] = $value;
    }

    $row['ip']   = dtss_client_ip();
    $row['page'] = isset($_SERVER['HTTP_REFERER']) ? dtss_clean($_SERVER['HTTP_REFERER'], 300) : '';

    $rows[] = $row;
    dtss_write_leads($form, $rows);

    return $row;
}

/** Delete a single entry by id. Returns true when something was removed. */
function dtss_delete_lead($form, $id)
{
    $rows    = dtss_read_leads($form);
    $kept    = array();
    $removed = false;

    foreach ($rows as $row) {
        if (!$removed && isset($row['id']) && $row['id'] === $id) {
            $removed = true;
            continue;
        }
        $kept[] = $row;
    }

    if ($removed) {
        dtss_write_leads($form, $kept);
    }

    return $removed;
}

/**
 * Simple honeypot + rate check shared by the collectors.
 * Returns an error string, or '' when the submission looks legitimate.
 */
function dtss_spam_check()
{
    // Hidden field that humans never see and never fill in.
    if (!empty($_POST['website_url'])) {
        return 'Spam detected.';
    }

    // Same visitor submitting twice within 10 seconds.
    if (isset($_SESSION['dtss_last_submit']) && (time() - $_SESSION['dtss_last_submit']) < 10) {
        return 'Please wait a few seconds before submitting again.';
    }

    return '';
}

/**
 * One-time migration of the legacy root-level contact.json into data/leads/.
 * The old file sat in the web root and was readable by anyone, so it is merged
 * into the protected store and renamed away.
 */
function dtss_migrate_legacy()
{
    dtss_leads_bootstrap();

    $legacy = __DIR__ . DIRECTORY_SEPARATOR . 'contact.json';
    if (!file_exists($legacy)) {
        return;
    }

    $rows = json_decode(file_get_contents($legacy), true);
    if (is_array($rows) && !empty($rows)) {
        $existing = dtss_read_leads('contact');

        $seen = array();
        foreach ($existing as $row) {
            $seen[dtss_lead_fingerprint($row)] = true;
        }

        $merged = array();
        foreach ($rows as $i => $row) {
            $print = dtss_lead_fingerprint($row);
            if (isset($seen[$print])) {
                continue;
            }
            if (!isset($row['id'])) {
                $row['id'] = 'legacy-' . $i . '-' . substr(md5($print), 0, 6);
            }
            $merged[]     = $row;
            $seen[$print] = true;
        }

        if (!empty($merged)) {
            dtss_write_leads('contact', array_merge($merged, $existing));
        }
    }

    @rename($legacy, __DIR__ . DIRECTORY_SEPARATOR . 'contact.json.migrated');
}

/** Stable signature of an entry, used to avoid importing duplicates. */
function dtss_lead_fingerprint($row)
{
    $parts = array();
    foreach (array('date', 'name', 'email', 'subject', 'message') as $k) {
        $parts[] = isset($row[$k]) ? (string) $row[$k] : '';
    }
    return md5(implode('|', $parts));
}

/* -----------------------------------------------------------------------------
 * Email notification
 * -------------------------------------------------------------------------- */

/** Who gets notified for every new enquiry. Add / remove addresses here. */
function dtss_notify_recipients($form = '')
{
    $list = array(
        'web@kcom.work',
        // 'notification@dtss.in',
    );
    // Resume applications also go to the DTSS inbox.
    if ($form === 'resume') {
        $list[] = 'contactus@dtss.in';
    }
    return $list;
}

/** From address used on notification mails (must be on your own domain). */
function dtss_notify_from()
{
    $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-z0-9\.\-]/i', '', $_SERVER['HTTP_HOST']) : 'dtss.in';
    $host = preg_replace('/^www\./i', '', $host);
    if ($host === '' || strpos($host, 'localhost') !== false) {
        $host = 'dtss.in';
    }
    return 'no-reply@' . $host;
}

/**
 * Send an HTML notification for a stored lead.
 * Never blocks or breaks the submission - failures are silent.
 */
function dtss_notify_lead($form, $row)
{
    if (!function_exists('mail')) {
        return false;
    }

    $config = dtss_form_config($form);
    if (!$config) {
        return false;
    }

    $recipients = dtss_notify_recipients();
    if (empty($recipients)) {
        return false;
    }

    $subject = 'New enquiry: ' . $config['label'] . ' - dtss.in';

    $rowsHtml = '';
    foreach ($config['columns'] as $key => $label) {
        $value = isset($row[$key]) ? $row[$key] : '';
        $rowsHtml .= '<tr>'
            . '<td style="padding:8px 12px;border:1px solid #e2e2e2;background:#faf8f2;font-weight:bold;width:180px;">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:8px 12px;border:1px solid #e2e2e2;">'
            . nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) . '</td>'
            . '</tr>';
    }

    $meta = array(
        'Submitted on' => isset($row['date']) ? $row['date'] : date('d-m-Y H:i:s'),
        'Source page'  => isset($row['page']) ? $row['page'] : '',
        'IP address'   => isset($row['ip']) ? $row['ip'] : '',
    );
    foreach ($meta as $label => $value) {
        if ($value === '') {
            continue;
        }
        $rowsHtml .= '<tr>'
            . '<td style="padding:8px 12px;border:1px solid #e2e2e2;background:#faf8f2;font-weight:bold;">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:8px 12px;border:1px solid #e2e2e2;color:#666;">'
            . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>'
            . '</tr>';
    }

    $message = '<html><body style="font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;padding:24px;">'
        . '<div style="max-width:640px;margin:0 auto;background:#fff;border-top:5px solid #fcaf17;padding:24px;">'
        . '<h2 style="margin:0 0 4px;color:#222;font-size:20px;">New ' . htmlspecialchars($config['label'], ENT_QUOTES, 'UTF-8') . ' enquiry</h2>'
        . '<p style="margin:0 0 18px;color:#777;font-size:13px;">Received from the DTSS website.</p>'
        . '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px;color:#333;">'
        . $rowsHtml
        . '</table>'
        . '<p style="margin:20px 0 0;font-size:12px;color:#999;">View all enquiries in the admin dashboard: dashboard.php</p>'
        . '</div></body></html>';

    $from    = dtss_notify_from();
    $replyTo = (isset($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) ? $row['email'] : $from;

    return dtss_send_mail($recipients, $subject, $message, $replyTo, 'form ' . $form . ' / id ' . (isset($row['id']) ? $row['id'] : '?'));
}

/**
 * Send an HTML mail. Uses SMTP when it is configured in mail-config.php,
 * otherwise falls back to PHP's mail(). Never throws; failures are logged to
 * data/leads/mail-errors.log so nothing is lost silently.
 *
 * @param array|string $recipients
 * @param string       $subject
 * @param string       $html
 * @param string       $replyTo
 * @param string       $context   short note used in the error log
 * @return bool
 */
function dtss_send_mail($recipients, $subject, $html, $replyTo = '', $context = '')
{
    dtss_leads_bootstrap();

    $recipients = array_values(array_filter(array_map('trim', (array) $recipients)));
    if (empty($recipients)) {
        return false;
    }

    require_once __DIR__ . '/mail-config.php';

    /* --- preferred route: authenticated SMTP --------------------------- */
    if (defined('DTSS_SMTP_ENABLED') && DTSS_SMTP_ENABLED) {
        require_once __DIR__ . '/mailer-smtp.php';

        $log  = array();
        $sent = dtss_smtp_send($recipients, $subject, $html, $replyTo, $log);

        if ($sent) {
            return true;
        }

        dtss_mail_log("SMTP send failed | " . $context . "\n    " . implode("\n    ", $log));
        // fall through and still try mail() as a last resort
    }

    /* --- fallback: PHP mail() ----------------------------------------- */
    if (!function_exists('mail')) {
        dtss_mail_log('mail() is disabled on this server | ' . $context);
        return false;
    }

    $from = dtss_notify_from();

    $headers  = 'From: DTSS Website <' . $from . '>' . "\r\n";
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= 'Reply-To: ' . $replyTo . "\r\n";
    }
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

    $sent = @mail(implode(', ', $recipients), $subject, $html, $headers, '-f' . $from);

    if (!$sent) {
        dtss_mail_log('mail() returned false | ' . $context
            . ' | configure SMTP in mail-config.php so notifications are delivered reliably');
    }

    return $sent;
}

/** Append a line to data/leads/mail-errors.log. */
function dtss_mail_log($line)
{
    @file_put_contents(
        DTSS_LEADS_DIR . DIRECTORY_SEPARATOR . 'mail-errors.log',
        date('d-m-Y H:i:s') . ' | ' . $line . "\n",
        FILE_APPEND | LOCK_EX
    );
}

/* -----------------------------------------------------------------------------
 * Front-end response page
 * -------------------------------------------------------------------------- */

/**
 * Render a small self-contained confirmation / error screen and stop.
 * Deliberately does not include head.php / footer-link.php so it can never be
 * broken by a JS error on the main template.
 */
function dtss_form_response($ok, $heading, $message, $backUrl = 'index.php', $backLabel = 'Back to website')
{
    $accent = $ok ? '#fcaf17' : '#d9534f';
    $icon   = $ok ? '&#10003;' : '!';

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex,nofollow">'
        . '<title>' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . ' | DTSS</title>'
        . '<link rel="shortcut icon" href="img/fav.png">'
        . '<style>'
        . 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
        . 'font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;color:#333;padding:24px;}'
        . '.card{background:#fff;max-width:520px;width:100%;padding:44px 36px;text-align:center;'
        . 'border-radius:6px;box-shadow:0 10px 40px rgba(0,0,0,.08);border-top:5px solid ' . $accent . ';}'
        . '.badge{width:64px;height:64px;line-height:64px;border-radius:50%;background:' . $accent . ';'
        . 'color:#fff;font-size:30px;margin:0 auto 20px;}'
        . 'h1{font-size:22px;margin:0 0 12px;letter-spacing:.5px;text-transform:uppercase;}'
        . 'p{margin:0 0 26px;color:#666;line-height:1.6;font-size:15px;}'
        . 'a.btn{display:inline-block;background:' . $accent . ';color:#fff;text-decoration:none;'
        . 'padding:12px 30px;font-weight:bold;font-size:14px;letter-spacing:.5px;border-radius:3px;}'
        . 'a.btn:hover{opacity:.88;}'
        . '</style></head><body><div class="card">'
        . '<div class="badge">' . $icon . '</div>'
        . '<h1>' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h1>'
        . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<a class="btn" href="' . htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8') . '</a>'
        . '</div></body></html>';
    exit;
}
