DTSS WEBSITE (www.dtss.in) - HOSTING HANDOVER PACKAGE
Prepared by K Communications, 2026-11-06

1. WHAT IS INCLUDED
- dtss.in/ : complete website source, exactly as live on the KCOM hosting server.
- Plain PHP website. No database is required.
- Form submissions (contact, facility audit, resume) are stored as JSON files inside the site folder (contact.json, facility_audit_home.json, facility_audit_popup.json, resume.json) and shown in the admin dashboard (login.php / dashboard.php).
- phpfiles/ : older Laravel build kept for reference only. It is not used by the live site and can be ignored.

2. SERVER REQUIREMENTS
- Apache (or LiteSpeed) with mod_rewrite and .htaccess enabled
- PHP 8.x (current live version), with mbstring, json, openssl, fileinfo
- Write permission for the web server user on the site root (for the JSON lead files) and on upload folders

3. INSTALLATION
a) Upload the contents of the dtss.in folder to the web root of the new server.
b) Edit mail-config.php and set the SMTP details for DTSS's own mailbox. The previous credentials have been removed and replaced with CHANGE_ME. Until this is set, form notification emails will not be sent (submissions are still saved).
c) Install an SSL certificate for dtss.in and www.dtss.in.
d) Test: home page, all menu pages, each form, admin login and dashboard.

4. DNS
Point dtss.in and www.dtss.in to the new server IP (or to the WAF in front of it, with the new server set as the WAF backend origin on HTTPS/443).

5. NOTES
- .htaccess contains clean-URL rules, redirects and a WAF IP allow block. Keep it.
- Credentials for the KCOM hosting account are not part of this package.
