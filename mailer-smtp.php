<?php
/**
 * DTSS - Minimal dependency-free SMTP sender.
 * -----------------------------------------------------------------------------
 * Speaks SMTP directly over a socket, so no Composer package or PHPMailer
 * install is needed. Supports implicit SSL (port 465) and STARTTLS (port 587)
 * with AUTH LOGIN / AUTH PLAIN.
 *
 * Settings live in mail-config.php.
 */

require_once __DIR__ . '/mail-config.php';

/** Read one full SMTP reply (handles multi-line 250- responses). */
function dtss_smtp_read($socket, &$log)
{
    $data = '';
    while (($line = fgets($socket, 1024)) !== false) {
        $data .= $line;
        $log[] = 'S: ' . rtrim($line);
        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }
    return $data;
}

/** Send one command and return the server's reply. */
function dtss_smtp_cmd($socket, $command, &$log, $hide = false)
{
    $log[] = 'C: ' . ($hide ? '[hidden]' : rtrim($command));
    fwrite($socket, $command . "\r\n");
    return dtss_smtp_read($socket, $log);
}

/** True when an SMTP reply starts with one of the expected codes. */
function dtss_smtp_ok($reply, $codes)
{
    $code = substr(trim($reply), 0, 3);
    return in_array($code, (array) $codes, true);
}

/**
 * Send an HTML mail through SMTP.
 *
 * @param string|array $to        one or more recipient addresses
 * @param string       $subject
 * @param string       $htmlBody
 * @param string       $replyTo   optional reply-to address
 * @param array        $log       filled with the SMTP transcript
 * @return bool
 */
function dtss_smtp_send($to, $subject, $htmlBody, $replyTo = '', &$log = array())
{
    $log = array();

    if (!DTSS_SMTP_ENABLED) {
        $log[] = 'SMTP is disabled in mail-config.php';
        return false;
    }
    if (DTSS_SMTP_USER === '' || DTSS_SMTP_PASS === '') {
        $log[] = 'SMTP username or password is not set in mail-config.php';
        return false;
    }
    if (!function_exists('stream_socket_client')) {
        $log[] = 'PHP is built without stream_socket_client()';
        return false;
    }

    $recipients = array_filter(array_map('trim', (array) $to));
    if (empty($recipients)) {
        $log[] = 'No recipients';
        return false;
    }

    $from     = (DTSS_MAIL_FROM !== '') ? DTSS_MAIL_FROM : DTSS_SMTP_USER;
    $fromName = DTSS_MAIL_FROM_NAME;
    $secure   = strtolower(DTSS_SMTP_SECURE);
    $host     = ($secure === 'ssl') ? 'ssl://' . DTSS_SMTP_HOST : DTSS_SMTP_HOST;

    $context = stream_context_create(array(
        'ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true),
    ));

    $errNo  = 0;
    $errStr = '';
    $socket = @stream_socket_client(
        $host . ':' . DTSS_SMTP_PORT,
        $errNo,
        $errStr,
        DTSS_SMTP_TIMEOUT,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        $log[] = 'Could not connect to ' . DTSS_SMTP_HOST . ':' . DTSS_SMTP_PORT . ' - ' . $errStr . ' (' . $errNo . ')';
        return false;
    }

    stream_set_timeout($socket, DTSS_SMTP_TIMEOUT);

    $reply = dtss_smtp_read($socket, $log);
    if (!dtss_smtp_ok($reply, '220')) {
        fclose($socket);
        return false;
    }

    $helo = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-z0-9\.\-]/i', '', $_SERVER['HTTP_HOST']) : 'localhost';
    if ($helo === '') {
        $helo = 'localhost';
    }

    $reply = dtss_smtp_cmd($socket, 'EHLO ' . $helo, $log);
    if (!dtss_smtp_ok($reply, '250')) {
        fclose($socket);
        return false;
    }

    if ($secure === 'tls') {
        $reply = dtss_smtp_cmd($socket, 'STARTTLS', $log);
        if (!dtss_smtp_ok($reply, '220')) {
            fclose($socket);
            return false;
        }
        $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
        if (!@stream_socket_enable_crypto($socket, true, $crypto)) {
            $log[] = 'STARTTLS negotiation failed';
            fclose($socket);
            return false;
        }
        $log[] = '--- TLS established ---';
        $reply = dtss_smtp_cmd($socket, 'EHLO ' . $helo, $log);
        if (!dtss_smtp_ok($reply, '250')) {
            fclose($socket);
            return false;
        }
    }

    // Authenticate.
    $reply = dtss_smtp_cmd($socket, 'AUTH LOGIN', $log);
    if (dtss_smtp_ok($reply, '334')) {
        $reply = dtss_smtp_cmd($socket, base64_encode(DTSS_SMTP_USER), $log, true);
        if (!dtss_smtp_ok($reply, '334')) {
            fclose($socket);
            return false;
        }
        $reply = dtss_smtp_cmd($socket, base64_encode(DTSS_SMTP_PASS), $log, true);
    } else {
        $reply = dtss_smtp_cmd(
            $socket,
            'AUTH PLAIN ' . base64_encode("\0" . DTSS_SMTP_USER . "\0" . DTSS_SMTP_PASS),
            $log,
            true
        );
    }

    if (!dtss_smtp_ok($reply, '235')) {
        $log[] = 'Authentication failed - check the username and password in mail-config.php';
        fclose($socket);
        return false;
    }

    $reply = dtss_smtp_cmd($socket, 'MAIL FROM:<' . $from . '>', $log);
    if (!dtss_smtp_ok($reply, '250')) {
        fclose($socket);
        return false;
    }

    $accepted = 0;
    foreach ($recipients as $rcpt) {
        $reply = dtss_smtp_cmd($socket, 'RCPT TO:<' . $rcpt . '>', $log);
        if (dtss_smtp_ok($reply, array('250', '251'))) {
            $accepted++;
        }
    }
    if ($accepted === 0) {
        $log[] = 'Every recipient was rejected by the server';
        fclose($socket);
        return false;
    }

    $reply = dtss_smtp_cmd($socket, 'DATA', $log);
    if (!dtss_smtp_ok($reply, '354')) {
        fclose($socket);
        return false;
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedName    = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers = array(
        'Date: ' . date('r'),
        'From: ' . $encodedName . ' <' . $from . '>',
        'To: ' . implode(', ', $recipients),
        'Subject: ' . $encodedSubject,
        'Message-ID: <' . (function_exists('random_bytes') ? bin2hex(random_bytes(8)) : uniqid('', true)) . '@' . DTSS_SMTP_HOST . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    );
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    $payload = implode("\r\n", $headers) . "\r\n\r\n"
             . chunk_split(base64_encode($htmlBody), 76, "\r\n");

    fwrite($socket, $payload . "\r\n.\r\n");
    $log[] = 'C: [message body]';
    $reply = dtss_smtp_read($socket, $log);

    $sent = dtss_smtp_ok($reply, '250');

    dtss_smtp_cmd($socket, 'QUIT', $log);
    fclose($socket);

    return $sent;
}
