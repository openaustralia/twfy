<?php

/**
 * @file
 * The busiest API keys and data.openaustralia.org.au downloads, for staff.
 */

use OpenAustralia\TWFY\UsageStats\ApiUsageReport;
use OpenAustralia\TWFY\UsageStats\DownloadReport;
use OpenAustralia\TWFY\UsageStats\ReasonText;
use OpenAustralia\TWFY\UsageStats\Settings;

include_once __DIR__ . "/../../includes/easyparliament/init.php";

$this_page = "admin_usage";

$periods = [30, 7, 1];
$period = in_array((int) get_http_var('period'), $periods, true) ? (int) get_http_var('period') : $periods[0];
// In UTC throughout, as PHP's own zone (Sydney) shifts relative days across daylight saving.
$today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
$to = $today->format('Y-m-d');
$from = $today->modify('-' . ($period - 1) . ' days')->format('Y-m-d');

/**
 * Escapes text for HTML.
 */
function usage_page_text(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * The key holder's name, linked to the web address they gave if it's http(s).
 */
function usage_page_name(?object $user): string {
    if ($user === null) {
        return '<em>No account</em>';
    }
    $name = usage_page_text(trim("$user->firstname $user->lastname"));
    return preg_match('#^https?://#i', $user->url) ? '<a href="' . usage_page_text($user->url) . '">' . $name . '</a>' : $name;
}

$PAGE->page_start();
$PAGE->stripe_start();

$links = [];
foreach ($periods as $days) {
    $label = $days === 1 ? 'Today' : "Last $days days";
    if ($days === $period) {
        $links[] = "<strong>$label</strong>";
    } else {
        $URL = new URL($this_page);
        $URL->insert(['period' => $days]);
        $links[] = '<a href="' . $URL->generate() . '">' . $label . '</a>';
    }
}
?>

<p><?php echo implode(' | ', $links); ?></p>
<p>Days are UTC, from <?php echo $from; ?> to <?php echo $to; ?>. API calls are counted as they happen. Downloads are
imported from data.openaustralia.org.au's access log each night, so they run to yesterday.</p>

<h3>Busiest API keys</h3>

<?php
$rows = [];
foreach (ApiUsageReport::topKeys($from, $to) as $row) {
    $key = $row['key'];
    $reason = trim(preg_replace('/\s+/u', ' ', $key->reason));
    $rows[] = [
        usage_page_name($key->user),
        usage_page_text($key->user->email ?? ''),
        '<span title="' . usage_page_text($reason) . '">' . usage_page_text(ReasonText::shorten($reason)) . '</span>',
        $key->commercial ? 'Yes' : 'No',
        usage_page_text((string) $key->disabled),
        number_format($row['total']),
        number_format($row['mean'], 1),
        number_format($row['min']),
        number_format($row['max']),
    ];
}
if ($rows) {
    $PAGE->display_table([
        'header' => ['Name', 'Email', 'Reason', 'Commercial', 'Disabled', 'Calls', 'Mean per day', 'Fewest', 'Most'],
        'rows' => $rows,
    ]);
} else {
    echo "<p>No API calls with a known key in this period.</p>\n";
}

$downloads = new DownloadReport($from, $to, Settings::internalAddresses());
?>

<h3>Data site downloads per day</h3>

<?php
$rows = array_map(fn (array $row) => [
    $row['day'],
    number_format($row['downloads']),
    number_format($row['internal']),
], $downloads->dailyTotals());
if ($rows) {
    $PAGE->display_table(['header' => ['Day', 'Downloads', 'From OAF servers'], 'rows' => $rows]);
} else {
    echo "<p>No downloads imported for this period.</p>\n";
}
?>

<h3>Most downloaded files</h3>

<?php
$rows = array_map(fn (array $row) => [
    usage_page_text($row['path']),
    number_format($row['downloads']),
], $downloads->topPaths());
if ($rows) {
    $PAGE->display_table(['header' => ['Path', 'Downloads'], 'rows' => $rows]);
} else {
    echo "<p>No downloads imported for this period.</p>\n";
}
?>

<h3>Busiest visitors</h3>

<?php
$rows = array_map(fn (array $row) => [
    usage_page_text($row['ip_address']),
    usage_page_text($row['user_agent']),
    number_format($row['downloads']),
    $row['internal'] ? 'Yes' : 'No',
], $downloads->topVisitors());
if ($rows) {
    $PAGE->display_table(['header' => ['IP address', 'User agent', 'Downloads', 'OAF server'], 'rows' => $rows]);
} else {
    echo "<p>No downloads imported for this period.</p>\n";
}

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type' => 'html',
        'content' => $menu,
    ],
]);

$PAGE->page_end();
