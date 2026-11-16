<?php

/**
 * @file
 * Merges JUnit XML reports into one, for tools that read a single report.
 *
 * Usage: php scripts/merge-junit.php <output.xml> <input.xml> [<input.xml> ...]
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php merge-junit.php <output.xml> <input.xml> [<input.xml> ...]\n");
    exit(1);
}

$merged = new DOMDocument('1.0', 'UTF-8');
$merged->formatOutput = true;
$root = $merged->appendChild($merged->createElement('testsuites'));

foreach (array_slice($argv, 2) as $path) {
    $report = new DOMDocument();
    if (!@$report->load($path)) {
        fwrite(STDERR, "Cannot read $path\n");
        exit(1);
    }

    $suites = (new DOMXPath($report))->query('/testsuites/testsuite | /testsuite');
    if ($suites->length === 0) {
        fwrite(STDERR, "No test suites in $path\n");
        exit(1);
    }
    foreach ($suites as $suite) {
        $root->appendChild($merged->importNode($suite, true));
    }
}

if ($merged->save($argv[1]) === false) {
    fwrite(STDERR, "Cannot write $argv[1]\n");
    exit(1);
}
