<?php

/**
 * @file
 * The busiest API keys and data.openaustralia.org.au downloads, for staff.
 */

use OpenAustralia\TWFY\UsageStats\Settings;
use OpenAustralia\TWFY\UsageStats\UsagePage;

include_once __DIR__ . "/../../includes/easyparliament/init.php";

$this_page = "admin_usage";

$period = UsagePage::period((string) get_http_var('period'));
[$from, $to] = UsagePage::range($period, new DateTimeImmutable('today', new DateTimeZone('UTC')));
$links = UsagePage::periodLinks($period, function (int $days) use ($this_page) {
    $URL = new URL($this_page);
    $URL->insert(['period' => $days]);
    return $URL->generate();
});

$PAGE->page_start();
$PAGE->stripe_start();
?>

<p><?php echo $links; ?></p>
<p>Days are UTC, from <?php echo $from; ?> to <?php echo $to; ?>. API calls are counted as they happen. Downloads are
imported from data.openaustralia.org.au's access log each night, so they run to yesterday.</p>

<?php
foreach (UsagePage::tables($from, $to, Settings::internalAddresses()) as $heading => [$header, $rows, $empty]) {
    echo "<h3>$heading</h3>\n";
    if ($rows) {
        $PAGE->display_table(['header' => $header, 'rows' => $rows]);
    } else {
        echo "<p>$empty</p>\n";
    }
}

$PAGE->stripe_end([
    [
        'type' => 'html',
        'content' => $PAGE->admin_menu(),
    ],
]);

$PAGE->page_end();
