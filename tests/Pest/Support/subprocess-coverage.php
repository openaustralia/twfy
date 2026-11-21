<?php

/**
 * @file
 * Collects code coverage inside a subprocess, as PHP's auto_prepend_file.
 *
 * Xml2dbRunner starts scripts/importxml.php as a child process, so the parent's Xdebug never sees the code it runs.
 * This file starts coverage in the child and writes a .cov file that "phpcov merge" combines with the parent's.
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\PHP;
use SebastianBergmann\FileIterator\Facade;

(static function (): void {
    $root = dirname(__DIR__, 3);
    $directory = getenv('SUBPROCESS_COVERAGE_DIR');
    if ($directory === false || $directory === '') {
        return;
    }
    require_once $root . '/vendor/autoload.php';

    $filter = new Filter();
    $filter->includeFiles((new Facade())->getFilesAsArray($root . '/www/includes', '.php'));
    $filter->includeFile($root . '/scripts/importxml.php');

    $coverage = new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);
    $coverage->start('importxml-' . getmypid());

    // Shutdown functions still run after exit(), which importxml.php uses for errors.
    register_shutdown_function(static function () use ($coverage, $directory): void {
        $coverage->stop();
        (new PHP())->process($coverage, $directory . '/subprocess-' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.cov');
    });
})();
