<?php

/**
 * @file
 * Name: lift-suppression.php
 * Description: Lift the email suppression on an address, so alert emails to
 * it resume. Use when someone writes in after their mailbox problem is fixed.
 * Their alerts were never deleted, so they continue from the next daily run.
 *
 * Usage: php lift-suppression.php person@example.org
 *
 * Run from the scripts directory. The suppression row is kept, with the time
 * it was lifted, so the history of why alerts stopped is still there.
 */

use OpenAustralia\TWFY\EmailSuppressions;

include '../www/includes/easyparliament/init.php';

if ($argc != 2) {
    fwrite(STDERR, "Usage: php lift-suppression.php person@example.org\n");
    exit(2);
}

$lifted = EmailSuppressions::lift($argv[1]);
print $lifted > 0 ? "Suppression lifted.\n" : "No active suppression for that address.\n";
