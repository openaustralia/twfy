<?php

/**
 * @file
 * Name: import-suppressions.php
 * Description: One-off import that suppresses the addresses Postal's history
 * says have already hard-failed, so alert emails to them stop before the
 * Postal webhook is switched on.
 *
 * Usage: php import-suppressions.php export.csv
 *
 * Run from the scripts directory. The file has one row per address: the
 * address, the date of its most recent hard fail, and the receiving server's
 * reply exactly as Postal stored it. It comes from the infrastructure export,
 * holds real personal data, and must never be committed or pasted into a
 * ticket, chat or AI conversation. Delete it once imported.
 *
 * Refusals that blame our server (5.7.x), rows with no reply, and addresses
 * whose suppression was lifted after the failure are skipped. It is safe to
 * run twice, and prints counts only, never addresses.
 */

use OpenAustralia\TWFY\SuppressionImport;

include '../www/includes/easyparliament/init.php';

if ($argc != 2) {
    fwrite(STDERR, "Usage: php import-suppressions.php export.csv\n");
    exit(2);
}

try {
    $counts = SuppressionImport::fromFile($argv[1]);
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

foreach ($counts as $label => $count) {
    print str_replace('_', ' ', $label) . ": $count\n";
}
