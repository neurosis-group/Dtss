<?php
/**
 * DTSS - Website enquiry dashboard
 * -----------------------------------------------------------------------------
 * Shows every submission from the three website forms, stored as JSON in
 * data/leads/ . Login protected via admin-auth.php.
 */

require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/leads-store.php';
require_once __DIR__ . '/mail-config.php';

dtss_admin_require_login();
dtss_migrate_legacy();

$forms  = dtss_lead_forms();
$notice = '';

/* ---------------------------------------------------------------- actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!dtss_csrf_valid(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $notice = 'Security token expired. Please try again.';
    } elseif ($_POST['action'] === 'testmail') {
        $recipients = dtss_notify_recipients();
        $html = '<html><body style="font-family:Arial,Helvetica,sans-serif;padding:24px;">'
              . '<div style="max-width:560px;margin:0 auto;background:#fff;border-top:5px solid #fcaf17;padding:24px;">'
              . '<h2 style="margin:0 0 10px;font-size:19px;color:#222;">Test email from the DTSS website</h2>'
              . '<p style="color:#555;line-height:1.6;font-size:14px;margin:0;">If you are reading this, enquiry '
              . 'notifications are set up correctly. Every new form submission will arrive at this address.</p>'
              . '<p style="color:#999;font-size:12px;margin:18px 0 0;">Sent ' . date('d-m-Y H:i:s') . '</p>'
              . '</div></body></html>';

        if (dtss_send_mail($recipients, 'Test email - DTSS website enquiry notifications', $html, '', 'manual test from dashboard')) {
            $notice = 'Test email sent to ' . implode(', ', $recipients) . '. Check the inbox (and the spam folder).';
        } else {
            $notice = 'Test email could not be sent. See the mail log below for the reason.';
        }
    } elseif ($_POST['action'] === 'delete') {
        $f  = isset($_POST['form']) ? $_POST['form'] : '';
        $id = isset($_POST['id']) ? $_POST['id'] : '';
        if (isset($forms[$f]) && dtss_delete_lead($f, $id)) {
            $notice = 'Entry deleted.';
        } else {
            $notice = 'Entry could not be found.';
        }
    }
}

/* ---------------------------------------------------------------- filters */
$activeForm = isset($_GET['form']) && isset($forms[$_GET['form']]) ? $_GET['form'] : 'all';
$search     = isset($_GET['q']) ? trim($_GET['q']) : '';
$dateFrom   = isset($_GET['from']) ? trim($_GET['from']) : '';
$dateTo     = isset($_GET['to']) ? trim($_GET['to']) : '';

/** dd-mm-YYYY H:i:s -> unix timestamp */
function dtss_row_time($row)
{
    if (empty($row['date'])) {
        return 0;
    }
    $dt = DateTime::createFromFormat('d-m-Y H:i:s', $row['date']);
    return $dt ? $dt->getTimestamp() : strtotime($row['date']);
}

/* ------------------------------------------------------ collect + filter */
$all    = array();
$counts = array();

foreach ($forms as $key => $cfg) {
    $rows          = dtss_read_leads($key);
    $counts[$key]  = count($rows);
    foreach ($rows as $row) {
        $row['_form'] = $key;
        $row['_ts']   = dtss_row_time($row);
        $all[]        = $row;
    }
}

$todayStart = strtotime('today');
$weekStart  = strtotime('-7 days');
$totalAll   = count($all);
$totalToday = 0;
$totalWeek  = 0;
foreach ($all as $row) {
    if ($row['_ts'] >= $todayStart) { $totalToday++; }
    if ($row['_ts'] >= $weekStart)  { $totalWeek++; }
}

$fromTs = ($dateFrom !== '') ? strtotime($dateFrom . ' 00:00:00') : 0;
$toTs   = ($dateTo   !== '') ? strtotime($dateTo   . ' 23:59:59') : 0;

$rowsView = array();
foreach ($all as $row) {
    if ($activeForm !== 'all' && $row['_form'] !== $activeForm) { continue; }
    if ($fromTs && $row['_ts'] < $fromTs) { continue; }
    if ($toTs   && $row['_ts'] > $toTs)   { continue; }

    if ($search !== '') {
        $haystack = '';
        foreach ($row as $k => $v) {
            if ($k[0] !== '_' && is_string($v)) { $haystack .= ' ' . $v; }
        }
        if (stripos($haystack, $search) === false) { continue; }
    }
    $rowsView[] = $row;
}

usort($rowsView, function ($a, $b) {
    if ($a['_ts'] === $b['_ts']) { return 0; }
    return ($a['_ts'] < $b['_ts']) ? 1 : -1;
});

/* which columns to render */
if ($activeForm === 'all') {
    $columns = array('name' => 'Name', 'email' => 'Email', 'mobile_no' => 'Contact', 'organisation_name' => 'Organisation', 'subject' => 'Subject', 'message' => 'Message');
} else {
    $columns = $forms[$activeForm]['columns'];
}

$csrf     = dtss_csrf_token();
$queryStr = http_build_query(array('form' => $activeForm, 'q' => $search, 'from' => $dateFrom, 'to' => $dateTo));

function dtss_e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Enquiry Dashboard | DTSS</title>
<link rel="shortcut icon" href="img/fav.png">
<style>
  *{box-sizing:border-box}
  body{margin:0;background:#f2f3f5;color:#2b2b2b;font-family:Arial,Helvetica,sans-serif;font-size:14px}
  a{color:#c98d0a}

  header.top{background:#222;color:#fff;padding:0 22px;display:flex;align-items:center;
             justify-content:space-between;height:62px;position:sticky;top:0;z-index:20}
  header.top .brand{display:flex;align-items:center;gap:12px}
  header.top .brand img{max-height:30px}
  header.top .brand span{font-size:14px;letter-spacing:1.2px;text-transform:uppercase;
                         border-left:1px solid #4a4a4a;padding-left:12px;color:#d8d8d8}
  header.top .right{display:flex;align-items:center;gap:14px;font-size:12.5px;color:#a9a9a9}
  .btn{display:inline-block;background:#fcaf17;color:#fff;text-decoration:none;padding:9px 16px;
       border:0;border-radius:3px;font-size:12.5px;font-weight:bold;letter-spacing:.6px;cursor:pointer}
  .btn:hover{background:#e59d0d;color:#fff}
  .btn.ghost{background:transparent;border:1px solid #565656;color:#ddd}
  .btn.ghost:hover{background:#3a3a3a;color:#fff}
  .btn.sm{padding:6px 11px;font-size:11.5px}
  .btn.danger{background:#e2e2e2;color:#8a8a8a}
  .btn.danger:hover{background:#d9534f;color:#fff}

  .wrap{max-width:1400px;margin:0 auto;padding:24px 22px 60px}

  .notice{background:#fff8e6;border-left:4px solid #fcaf17;padding:11px 14px;margin-bottom:18px;border-radius:3px}

  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px}
  .stat{background:#fff;border-radius:5px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,.07);
        border-left:4px solid #fcaf17}
  .stat b{display:block;font-size:28px;line-height:1.1;margin-bottom:4px;color:#222}
  .stat span{font-size:11.5px;letter-spacing:.9px;text-transform:uppercase;color:#8b8b8b}
  .stat.grey{border-left-color:#c9c9c9}

  .tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
  .tabs a{background:#fff;border:1px solid #e2e2e2;border-radius:3px;padding:9px 15px;
          text-decoration:none;color:#5c5c5c;font-size:13px}
  .tabs a.active{background:#222;border-color:#222;color:#fff}
  .tabs a b{color:inherit;opacity:.65;margin-left:6px;font-weight:normal}

  .toolbar{background:#fff;border-radius:5px;padding:14px 16px;margin-bottom:16px;
           box-shadow:0 1px 3px rgba(0,0,0,.07);display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
  .field{display:flex;flex-direction:column;gap:5px}
  .field label{font-size:10.5px;letter-spacing:.8px;text-transform:uppercase;color:#909090;font-weight:bold}
  .field input{padding:9px 11px;border:1px solid #dedede;border-radius:3px;font-size:13.5px;min-width:150px}
  .field input:focus{outline:none;border-color:#fcaf17}
  .field.grow{flex:1;min-width:220px}
  .field.grow input{width:100%}
  .toolbar .spacer{flex:1}

  .card{background:#fff;border-radius:5px;box-shadow:0 1px 3px rgba(0,0,0,.07);overflow:hidden}
  .card .head{padding:14px 18px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;
              align-items:center;flex-wrap:wrap;gap:10px}
  .card .head h2{margin:0;font-size:14px;letter-spacing:.8px;text-transform:uppercase;color:#444}
  .card .head .cnt{font-size:12.5px;color:#9a9a9a}

  .tablewrap{overflow-x:auto}
  table{width:100%;border-collapse:collapse;font-size:13.5px;min-width:900px}
  th{background:#fafafa;text-align:left;padding:11px 14px;font-size:10.5px;letter-spacing:.9px;
     text-transform:uppercase;color:#8b8b8b;border-bottom:1px solid #ececec;white-space:nowrap}
  td{padding:12px 14px;border-bottom:1px solid #f2f2f2;vertical-align:top}
  tbody tr:hover{background:#fffdf6}
  td.msg{max-width:340px}
  .clip{display:block;max-height:42px;overflow:hidden;color:#555;line-height:1.5}
  .more{font-size:11.5px;color:#c98d0a;cursor:pointer;text-decoration:underline}
  .tag{display:inline-block;padding:3px 9px;border-radius:20px;font-size:10.5px;font-weight:bold;
       letter-spacing:.5px;background:#f0f0f0;color:#6b6b6b;white-space:nowrap}
  .tag.f-facility_audit_popup{background:#fff2d6;color:#a97806}
  .tag.f-facility_audit_home{background:#e4f1fb;color:#2b6f9e}
  .tag.f-contact{background:#e9f5ea;color:#3f7d45}
  .when{white-space:nowrap;color:#777;font-size:12.5px}
  .meta{color:#aaa;font-size:11px;margin-top:3px}
  td a.mail{color:#2b2b2b;text-decoration:none;border-bottom:1px dotted #c9c9c9}
  td a.mail:hover{color:#c98d0a}
  .empty{padding:60px 20px;text-align:center;color:#9a9a9a}
  .empty b{display:block;font-size:16px;color:#666;margin-bottom:6px}

  #modal{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;
         justify-content:center;padding:24px;z-index:50}
  #modal.on{display:flex}
  #modal .inner{background:#fff;border-radius:5px;max-width:560px;width:100%;max-height:80vh;overflow:auto;
                border-top:4px solid #fcaf17}
  #modal .inner .h{padding:16px 20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;
                   align-items:center}
  #modal .inner .h h3{margin:0;font-size:14px;text-transform:uppercase;letter-spacing:.8px}
  #modal .inner .b{padding:20px;white-space:pre-wrap;line-height:1.65;color:#444}
  #modal .x{cursor:pointer;font-size:22px;color:#aaa;line-height:1;border:0;background:none}

  @media(max-width:700px){
    header.top .brand span{display:none}
    .wrap{padding:16px 12px 50px}
  }
</style>
</head>
<body>

<header class="top">
  <div class="brand">
    <img src="img/logo.png" alt="DTSS" onerror="this.style.display='none'">
    <span>Website Enquiries</span>
  </div>
  <div class="right">
    <span>Signed in as <strong style="color:#fff"><?php echo dtss_e(DTSS_ADMIN_USER); ?></strong></span>
    <a class="btn ghost" href="index.php" target="_blank" rel="noopener">View site</a>
    <a class="btn" href="logout.php">Logout</a>
  </div>
</header>

<div class="wrap">

  <?php if ($notice !== '') { ?>
    <div class="notice"><?php echo dtss_e($notice); ?></div>
  <?php } ?>

  <div class="stats">
    <div class="stat"><b><?php echo $totalAll; ?></b><span>Total enquiries</span></div>
    <div class="stat"><b><?php echo $totalToday; ?></b><span>Today</span></div>
    <div class="stat"><b><?php echo $totalWeek; ?></b><span>Last 7 days</span></div>
    <?php foreach ($forms as $key => $cfg) { ?>
      <div class="stat grey"><b><?php echo $counts[$key]; ?></b><span><?php echo dtss_e($cfg['short']); ?></span></div>
    <?php } ?>
  </div>

  <div class="tabs">
    <a href="?<?php echo dtss_e(http_build_query(array('form' => 'all', 'q' => $search, 'from' => $dateFrom, 'to' => $dateTo))); ?>"
       class="<?php echo $activeForm === 'all' ? 'active' : ''; ?>">All forms <b><?php echo $totalAll; ?></b></a>
    <?php foreach ($forms as $key => $cfg) { ?>
      <a href="?<?php echo dtss_e(http_build_query(array('form' => $key, 'q' => $search, 'from' => $dateFrom, 'to' => $dateTo))); ?>"
         class="<?php echo $activeForm === $key ? 'active' : ''; ?>"><?php echo dtss_e($cfg['label']); ?> <b><?php echo $counts[$key]; ?></b></a>
    <?php } ?>
  </div>

  <form class="toolbar" method="get">
    <input type="hidden" name="form" value="<?php echo dtss_e($activeForm); ?>">
    <div class="field grow">
      <label for="q">Search</label>
      <input type="text" id="q" name="q" value="<?php echo dtss_e($search); ?>" placeholder="Name, email, organisation, message&hellip;">
    </div>
    <div class="field">
      <label for="from">From date</label>
      <input type="date" id="from" name="from" value="<?php echo dtss_e($dateFrom); ?>">
    </div>
    <div class="field">
      <label for="to">To date</label>
      <input type="date" id="to" name="to" value="<?php echo dtss_e($dateTo); ?>">
    </div>
    <div class="field">
      <label>&nbsp;</label>
      <button class="btn" type="submit">Apply</button>
    </div>
    <div class="field">
      <label>&nbsp;</label>
      <a class="btn ghost" style="border-color:#dcdcdc;color:#777" href="dashboard.php">Reset</a>
    </div>
    <div class="spacer"></div>
    <div class="field">
      <label>&nbsp;</label>
      <a class="btn" href="export-csv.php?<?php echo dtss_e($queryStr); ?>">Download CSV</a>
    </div>
  </form>

  <?php
    $mailLogPath = DTSS_LEADS_DIR . DIRECTORY_SEPARATOR . 'mail-errors.log';
    $mailLog     = file_exists($mailLogPath) ? trim(file_get_contents($mailLogPath)) : '';
    if ($mailLog !== '') {
        $mailLogLines = array_slice(explode("\n", $mailLog), -12);
        $mailLog      = implode("\n", $mailLogLines);
    }
    $smtpOn = defined('DTSS_SMTP_ENABLED') && DTSS_SMTP_ENABLED;
  ?>
  <div class="card" style="margin-bottom:18px">
    <div class="head">
      <h2>Email notifications</h2>
      <form method="post" style="margin:0">
        <input type="hidden" name="csrf" value="<?php echo dtss_e($csrf); ?>">
        <input type="hidden" name="action" value="testmail">
        <button class="btn sm" type="submit">Send test email</button>
      </form>
    </div>
    <div style="padding:16px 18px;font-size:13px;color:#555;line-height:1.7">
      Every new enquiry is emailed to
      <strong><?php echo dtss_e(implode(', ', dtss_notify_recipients())); ?></strong>.
      Delivery method:
      <?php if ($smtpOn) { ?>
        <span class="tag" style="background:#e9f5ea;color:#3f7d45">SMTP via <?php echo dtss_e(DTSS_SMTP_HOST); ?></span>
      <?php } else { ?>
        <span class="tag" style="background:#fdecea;color:#a33">PHP mail() &mdash; not reliable</span>
        <span style="color:#999">Fill in <code>mail-config.php</code> and set <code>DTSS_SMTP_ENABLED</code> to
        <code>true</code> so notifications actually arrive.</span>
      <?php } ?>

      <?php if ($mailLog !== '') { ?>
        <details style="margin-top:12px">
          <summary style="cursor:pointer;color:#c98d0a">Recent mail errors</summary>
          <pre style="background:#fafafa;border:1px solid #eee;border-radius:3px;padding:12px;margin:10px 0 0;
                      overflow:auto;font-size:11.5px;color:#777;white-space:pre-wrap"><?php echo dtss_e($mailLog); ?></pre>
        </details>
      <?php } ?>
    </div>
  </div>

  <div class="card">
    <div class="head">
      <h2><?php echo $activeForm === 'all' ? 'All enquiries' : dtss_e($forms[$activeForm]['label']); ?></h2>
      <span class="cnt"><?php echo count($rowsView); ?> record<?php echo count($rowsView) === 1 ? '' : 's'; ?> shown<?php
        echo ($search !== '' || $dateFrom !== '' || $dateTo !== '') ? ' (filtered)' : ''; ?></span>
    </div>

    <?php if (empty($rowsView)) { ?>
      <div class="empty">
        <b>No enquiries found</b>
        <?php echo ($search !== '' || $dateFrom !== '' || $dateTo !== '')
            ? 'Try clearing the search or date filters.'
            : 'New submissions from the website will appear here automatically.'; ?>
      </div>
    <?php } else { ?>
    <div class="tablewrap">
      <table>
        <thead>
          <tr>
            <th style="width:150px">Received</th>
            <?php if ($activeForm === 'all') { ?><th style="width:130px">Form</th><?php } ?>
            <?php foreach ($columns as $key => $label) { ?>
              <th><?php echo dtss_e($label); ?></th>
            <?php } ?>
            <th style="width:70px"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rowsView as $row) { ?>
          <tr>
            <td>
              <div class="when"><?php echo dtss_e(isset($row['date']) ? $row['date'] : '-'); ?></div>
              <?php if (!empty($row['ip'])) { ?><div class="meta">IP <?php echo dtss_e($row['ip']); ?></div><?php } ?>
            </td>

            <?php if ($activeForm === 'all') { ?>
              <td><span class="tag f-<?php echo dtss_e($row['_form']); ?>"><?php echo dtss_e($forms[$row['_form']]['short']); ?></span></td>
            <?php } ?>

            <?php foreach ($columns as $key => $label) {
                $val = isset($row[$key]) ? (string) $row[$key] : '';
                if ($key === 'message' || strlen($val) > 90) { ?>
                  <td class="msg">
                    <?php if ($val === '') { echo '<span style="color:#c9c9c9">&mdash;</span>'; } else { ?>
                      <span class="clip"><?php echo nl2br(dtss_e($val)); ?></span>
                      <?php if (strlen($val) > 90) { ?>
                        <span class="more" data-title="<?php echo dtss_e($label); ?>" data-body="<?php echo dtss_e($val); ?>">Read full</span>
                      <?php } ?>
                    <?php } ?>
                  </td>
                <?php } elseif ($key === 'email') { ?>
                  <td><?php echo $val === '' ? '<span style="color:#c9c9c9">&mdash;</span>'
                        : '<a class="mail" href="mailto:' . dtss_e($val) . '">' . dtss_e($val) . '</a>'; ?></td>
                <?php } elseif ($key === 'resume') { ?>
                  <td><?php if ($val !== '' && isset($row['id'])) { ?><a href="resume-download.php?id=<?php echo rawurlencode($row['id']); ?>" style="font-weight:600">Download</a><?php } else { echo '-'; } ?></td>
                <?php } elseif ($key === 'mobile_no') { ?>
                  <td><?php echo $val === '' ? '<span style="color:#c9c9c9">&mdash;</span>'
                        : '<a class="mail" href="tel:' . dtss_e($val) . '">' . dtss_e($val) . '</a>'; ?></td>
                <?php } else { ?>
                  <td><?php echo $val === '' ? '<span style="color:#c9c9c9">&mdash;</span>' : dtss_e($val); ?></td>
                <?php }
            } ?>

            <td>
              <form method="post" onsubmit="return confirm('Delete this enquiry permanently?');" style="margin:0">
                <input type="hidden" name="csrf" value="<?php echo dtss_e($csrf); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="form" value="<?php echo dtss_e($row['_form']); ?>">
                <input type="hidden" name="id" value="<?php echo dtss_e(isset($row['id']) ? $row['id'] : ''); ?>">
                <button class="btn sm danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php } ?>
  </div>

</div>

<div id="modal">
  <div class="inner">
    <div class="h"><h3 id="modal-title">Message</h3><button class="x" type="button" onclick="dtssCloseModal()">&times;</button></div>
    <div class="b" id="modal-body"></div>
  </div>
</div>

<script>
  var modal = document.getElementById('modal');
  document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('more')) {
      document.getElementById('modal-title').textContent = e.target.getAttribute('data-title') || 'Details';
      document.getElementById('modal-body').textContent = e.target.getAttribute('data-body') || '';
      modal.classList.add('on');
    } else if (e.target === modal) {
      dtssCloseModal();
    }
  });
  function dtssCloseModal() { modal.classList.remove('on'); }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { dtssCloseModal(); } });
</script>

</body>
</html>
