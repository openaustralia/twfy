<?php

/**
 * @file
 * Name: rebuild-api-daily-usage.php
 * Description: One-off recount of api_daily_usage from api_stats, so the
 * admin usage page covers the years before daily counting began.
 *
 * Usage: php rebuild-api-daily-usage.php
 *
 * Replaces the count for every API key and UTC day before today, and leaves
 * today to the live counter. Safe to run again. Reads api_stats in ranges of
 * 100,000 ids, so it doesn't hold up the API while it runs.
 */

use OpenAustralia\TWFY\UsageStats\DailyUsageRebuild;

include_once __DIR__ . '/../www/includes/easyparliament/init.php';

if ($argc !== 1) {
    fwrite(STDERR, "Usage: php rebuild-api-daily-usage.php\n");
    exit(2);
}

$counts = DailyUsageRebuild::run(100000, function (int $lastId) {
    fwrite(STDERR, "Read api_stats up to id $lastId\n");
});
foreach ($counts as $label => $count) {
    print str_replace('_', ' ', ucfirst($label)) . ": $count\n";
}
