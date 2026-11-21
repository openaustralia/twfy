<?php

/**
 * @file
 * Name: record-import-error.php
 * Description: Record an error from importing a day of Hansard, so the admin report counts it and Sentry hears of it.
 *
 * Usage: php record-import-error.php --date=2026-02-03 --house=senate --source=parser --message="..."
 *
 * House is representatives or senate. Source is parser or loader. Run on its own for a day with several errors by
 * running it once for each.
 */

include_once __DIR__ . '/../www/includes/easyparliament/init.php';

[$status, $line] = OpenAustralia\TWFY\ImportErrors::execute('record', getopt('', ['date:', 'house:', 'source:', 'message:']));
fwrite($status === 0 ? STDOUT : STDERR, $line === '' ? '' : "$line\n");
exit($status);
