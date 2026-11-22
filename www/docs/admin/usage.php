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

// page_start() turns away anyone who is not an admin, so the report is only built after it.
$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'usage', 'title' => 'API and data site usage']);
print UsagePage::page($period, $from, $to, Settings::internalAddresses());
$PAGE->block_end();

$PAGE->stripe_end([
    [
        'type' => 'html',
        'content' => $PAGE->admin_menu(),
    ],
]);

$PAGE->page_end();
