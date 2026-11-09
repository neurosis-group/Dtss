<?php
require_once __DIR__ . '/admin-auth.php';
dtss_admin_logout();
header('Location: login.php?bye=1');
exit;
