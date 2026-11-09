<?php
/**
 * DTSS - Admin authentication for the enquiry dashboard.
 * -----------------------------------------------------------------------------
 * Credentials live here only. To change the password, generate a new hash with
 *     php -r 'echo password_hash("YOUR NEW PASSWORD", PASSWORD_DEFAULT);'
 * and paste it into DTSS_ADMIN_HASH below.
 */

define('DTSS_ADMIN_USER', 'dtss');

// Hash of the current password: dtss@2026
define('DTSS_ADMIN_HASH', '$2y$12$m9Eb.MK0BpH/AuEy9sJbguay84d3MPwVrcHK6G7IAlPonCSFDAe4u');

// Auto logout after 45 minutes of inactivity.
define('DTSS_ADMIN_IDLE_TIMEOUT', 45 * 60);

/** Random hex string that works on every PHP build (no openssl requirement). */
function dtss_random_hex($bytes = 16)
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($bytes));
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($bytes));
    }
    $out = '';
    for ($i = 0; $i < $bytes * 2; $i++) {
        $out .= dechex(mt_rand(0, 15));
    }
    return $out;
}

if (function_exists('session_status')) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
} elseif (session_id() === '') {
    session_start();
}

/** Verify a username / password pair. */
function dtss_admin_check($user, $pass)
{
    if (!hash_equals(DTSS_ADMIN_USER, (string) $user)) {
        return false;
    }

    if (function_exists('password_verify')) {
        return password_verify((string) $pass, DTSS_ADMIN_HASH);
    }

    // Fallback for very old PHP builds without password_verify().
    return crypt((string) $pass, DTSS_ADMIN_HASH) === DTSS_ADMIN_HASH;
}

/** Mark the current session as logged in. */
function dtss_admin_login()
{
    session_regenerate_id(true);
    $_SESSION['login']          = true;
    $_SESSION['dtss_admin']     = DTSS_ADMIN_USER;
    $_SESSION['dtss_last_seen'] = time();
    $_SESSION['dtss_csrf']      = dtss_random_hex(16);
}

/** True when the visitor holds a valid, non-expired admin session. */
function dtss_admin_is_logged_in()
{
    if (empty($_SESSION['login'])) {
        return false;
    }

    if (isset($_SESSION['dtss_last_seen']) && (time() - $_SESSION['dtss_last_seen']) > DTSS_ADMIN_IDLE_TIMEOUT) {
        dtss_admin_logout();
        return false;
    }

    $_SESSION['dtss_last_seen'] = time();
    return true;
}

/** Redirect to the login screen unless already authenticated. */
function dtss_admin_require_login()
{
    if (!dtss_admin_is_logged_in()) {
        header('Location: login.php?expired=1');
        exit;
    }
}

/** Destroy the admin session. */
function dtss_admin_logout()
{
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** CSRF token for admin forms. */
function dtss_csrf_token()
{
    if (empty($_SESSION['dtss_csrf'])) {
        $_SESSION['dtss_csrf'] = dtss_random_hex(16);
    }
    return $_SESSION['dtss_csrf'];
}

/** Validate a submitted CSRF token. */
function dtss_csrf_valid($token)
{
    return !empty($_SESSION['dtss_csrf']) && hash_equals($_SESSION['dtss_csrf'], (string) $token);
}
