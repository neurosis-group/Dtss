<?php
/**
 * DTSS - Outgoing mail settings
 * -----------------------------------------------------------------------------
 * Enquiry notifications are sent through SMTP when DTSS_SMTP_ENABLED is true.
 * SMTP is far more reliable than PHP's mail(): it works on XAMPP/localhost and
 * it stops notifications landing in spam, because the mail is properly
 * authenticated by the sending mailbox.
 *
 * Fill in the four SMTP_* values below with the mailbox the notifications
 * should be SENT FROM, then set DTSS_SMTP_ENABLED to true.
 *
 * Common settings
 * ---------------
 * Google Workspace / Gmail : host smtp.gmail.com          port 465  secure ssl
 *                            (use a Google App Password, not the login password)
 * Microsoft 365 / Outlook  : host smtp.office365.com      port 587  secure tls
 * cPanel / shared hosting  : host mail.yourdomain.com     port 465  secure ssl
 * Zoho Mail                : host smtp.zoho.in            port 465  secure ssl
 */

/* Turn SMTP on once the credentials below are filled in. */
define('DTSS_SMTP_ENABLED', true);

define('DTSS_SMTP_HOST',   'smtp.gmail.com');   // Google Workspace
define('DTSS_SMTP_PORT',   465);                 // 465 for ssl, 587 for tls
define('DTSS_SMTP_SECURE', 'ssl');               // 'ssl' or 'tls'
define('DTSS_SMTP_USER',   'contctus@dtss.in');     // full mailbox address
define('DTSS_SMTP_PASS',   'CHANGE_ME'); // 16-char Google App Password, no spaces

/* Shown as the sender. Must be a mailbox you are allowed to send from,
   otherwise the mail is likely to be rejected or marked as spam. */
define('DTSS_MAIL_FROM',      'contctus@dtss.in');  // blank = use DTSS_SMTP_USER
define('DTSS_MAIL_FROM_NAME', 'DTSS Website');

/* Seconds to wait for the mail server before giving up. */
define('DTSS_SMTP_TIMEOUT', 20);
