<?php
/**
 * DTSS - CSV export of website enquiries (respects the dashboard filters).
 */

require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/leads-store.php';

dtss_admin_require_login();

$forms      = dtss_lead_forms();
$activeForm = isset($_GET['form']) && isset($forms[$_GET['form']]) ? $_GET['form'] : 'all';
$search     = isset($_GET['q']) ? trim($_GET['q']) : '';
$dateFrom   = isset($_GET['from']) ? trim($_GET['from']) : '';
$dateTo     = isset($_GET['to']) ? trim($_GET['to']) : '';

function dtss_csv_time($row)
{
    if (empty($row['date'])) {
        return 0;
    }
    $dt = DateTime::createFromFormat('d-m-Y H:i:s', $row['date']);
    return $dt ? $dt->getTimestamp() : strtotime($row['date']);
}

$fromTs = ($dateFrom !== '') ? strtotime($dateFrom . ' 00:00:00') : 0;
$toTs   = ($dateTo   !== '') ? strtotime($dateTo   . ' 23:59:59') : 0;

$keys = ($activeForm === 'all')
    ? array('name' => 'Name', 'email' => 'Email', 'mobile_no' => 'Contact Number', 'organisation_name' => 'Organisation', 'subject' => 'Subject', 'message' => 'Message')
    : $forms[$activeForm]['columns'];

$rowsView = array();
foreach ($forms as $key => $cfg) {
    if ($activeForm !== 'all' && $key !== $activeForm) {
        continue;
    }
    foreach (dtss_read_leads($key) as $row) {
        $row['_form'] = $key;
        $row['_ts']   = dtss_csv_time($row);

        if ($fromTs && $row['_ts'] < $fromTs) { continue; }
        if ($toTs   && $row['_ts'] > $toTs)   { continue; }

        if ($search !== '') {
            $hay = '';
            foreach ($row as $k => $v) {
                if ($k[0] !== '_' && is_string($v)) { $hay .= ' ' . $v; }
            }
            if (stripos($hay, $search) === false) { continue; }
        }
        $rowsView[] = $row;
    }
}

usort($rowsView, function ($a, $b) {
    if ($a['_ts'] === $b['_ts']) { return 0; }
    return ($a['_ts'] < $b['_ts']) ? 1 : -1;
});

$filename = 'dtss-enquiries-' . ($activeForm === 'all' ? 'all' : $activeForm) . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 correctly

$header = array('Received', 'Form');
foreach ($keys as $label) {
    $header[] = $label;
}
$header[] = 'IP';
$header[] = 'Source Page';
fputcsv($out, $header);

foreach ($rowsView as $row) {
    $line = array(
        isset($row['date']) ? $row['date'] : '',
        $forms[$row['_form']]['label'],
    );
    foreach ($keys as $k => $label) {
        $line[] = isset($row[$k]) ? $row[$k] : '';
    }
    $line[] = isset($row['ip']) ? $row['ip'] : '';
    $line[] = isset($row['page']) ? $row['page'] : '';
    fputcsv($out, $line);
}

fclose($out);
exit;
