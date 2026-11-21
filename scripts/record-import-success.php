<?php

/**
 * @file
 * Name: record-import-success.php
 * Description: Record that a day of Hansard imported without error, which clears that stage's earlier errors for it.
 *
 * Usage: php record-import-success.php --date=2026-02-03 --house=senate --source=parser
 *
 * House is representatives or senate. Source is parser or loader. Only that source's errors for that day and house
 * are cleared.
 */

include_once __DIR__ . '/../www/includes/easyparliament/init.php';

use OpenAustralia\TWFY\ImportErrors;

[$status, $line] = ImportErrors::execute('clear', getopt('', ['date:', 'house:', 'source:']));
fwrite($status === 0 ? STDOUT : STDERR, $line === '' ? '' : "$line\n");
exit($status);
