<?php
/**
 * DTSS - Admin login for the website enquiry dashboard.
 */
require_once __DIR__ . '/admin-auth.php';

if (dtss_admin_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Slow down brute force attempts.
    if (isset($_SESSION['dtss_login_fails']) && $_SESSION['dtss_login_fails'] >= 5
        && isset($_SESSION['dtss_login_block']) && time() < $_SESSION['dtss_login_block']) {
        $error = 'Too many failed attempts. Please try again in a minute.';
    } elseif (dtss_admin_check(isset($_POST['username']) ? $_POST['username'] : '', isset($_POST['password']) ? $_POST['password'] : '')) {
        unset($_SESSION['dtss_login_fails'], $_SESSION['dtss_login_block']);
        dtss_admin_login();
        header('Location: dashboard.php');
        exit;
    } else {
        $_SESSION['dtss_login_fails'] = isset($_SESSION['dtss_login_fails']) ? $_SESSION['dtss_login_fails'] + 1 : 1;
        if ($_SESSION['dtss_login_fails'] >= 5) {
            $_SESSION['dtss_login_block'] = time() + 60;
        }
        $error = 'Invalid username or password.';
    }
} elseif (isset($_GET['expired'])) {
    $error = 'Your session has expired. Please sign in again.';
} elseif (isset($_GET['bye'])) {
    $error = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Admin Login | DTSS Enquiries</title>
<link rel="shortcut icon" href="img/fav.png">
<style>
  *{box-sizing:border-box}
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
       font-family:Arial,Helvetica,sans-serif;color:#2b2b2b;
       background:#1d1d1d url('img/home-banner.jpg') center/cover no-repeat;}
  body:before{content:'';position:fixed;inset:0;background:rgba(20,20,20,.82);}
  .box{position:relative;width:100%;max-width:400px;background:#fff;border-radius:6px;
       box-shadow:0 20px 60px rgba(0,0,0,.45);border-top:5px solid #fcaf17;padding:38px 34px;}
  .logo{text-align:center;margin-bottom:22px}
  .logo img{max-height:52px;max-width:180px}
  h1{font-size:17px;text-align:center;margin:0 0 4px;letter-spacing:1px;text-transform:uppercase}
  .sub{text-align:center;color:#8b8b8b;font-size:12.5px;margin:0 0 26px}
  label{display:block;font-size:11.5px;font-weight:bold;letter-spacing:.8px;
        text-transform:uppercase;color:#6d6d6d;margin:0 0 6px}
  input{width:100%;padding:12px 14px;border:1px solid #dcdcdc;border-radius:3px;font-size:14.5px;
        margin-bottom:18px;background:#fbfbfb;transition:.15s}
  input:focus{outline:none;border-color:#fcaf17;background:#fff;box-shadow:0 0 0 3px rgba(252,175,23,.15)}
  button{width:100%;padding:13px;border:0;border-radius:3px;background:#fcaf17;color:#fff;
         font-size:14px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;cursor:pointer;transition:.15s}
  button:hover{background:#e59d0d}
  .err{background:#fdecea;border-left:3px solid #d9534f;color:#a33;padding:10px 12px;
       font-size:13px;border-radius:3px;margin-bottom:18px;line-height:1.5}
  .back{display:block;text-align:center;margin-top:20px;font-size:12.5px;color:#9a9a9a;text-decoration:none}
  .back:hover{color:#fcaf17}
</style>
</head>
<body>
  <div class="box">
    <div class="logo"><img src="img/logo.png" alt="DTSS" onerror="this.style.display='none'"></div>
    <h1>Enquiry Dashboard</h1>
    <p class="sub">Sign in to view website form submissions</p>

    <?php if ($error !== '') { ?>
      <div class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>

    <form method="post" autocomplete="off">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Sign in</button>
    </form>

    <a class="back" href="index.php">&larr; Back to website</a>
  </div>
</body>
</html>
