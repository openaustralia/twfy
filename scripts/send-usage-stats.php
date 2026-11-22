<?php

/**
 * @file
 * Name: send-usage-stats.php
 * Description: The nightly usage stats job. Imports yesterday's
 * data.openaustralia.org.au access log into data_downloads, then sends new
 * api_stats and data_downloads rows to Elasticsearch for Kibana.
 *
 * Usage: php send-usage-stats.php [--batch-size=N] [--concurrency=N] [--no-import]
 *        php send-usage-stats.php --import=FILE
 *
 * With no options it does the whole nightly run. --import imports one log
 * file (plain or gzipped) and sends nothing, for loading older logs: import
 * them oldest first. --no-import only sends, as the backfill does. Run it
 * under run-with-lockfile so two runs can't overlap.
 *
 * Settings come from the environment or conf/general: ELASTICSEARCH_URL,
 * ELASTICSEARCH_API_KEY, DATA_ACCESS_LOG and DATA_DOWNLOADS_INTERNAL_IPS. Any
 * left empty skips its step. Exits non-zero if any step failed.
 */

use GuzzleHttp\Client;
use OpenAustralia\TWFY\UsageStats\ApiCallSource;
use OpenAustralia\TWFY\UsageStats\DownloadImport;
use OpenAustralia\TWFY\UsageStats\DownloadSource;
use OpenAustralia\TWFY\UsageStats\ElasticsearchClient;
use OpenAustralia\TWFY\UsageStats\Exporter;
use OpenAustralia\TWFY\UsageStats\Settings;

include_once __DIR__ . '/../www/includes/easyparliament/init.php';
include_once __DIR__ . '/../www/docs/api/api_functions.php';

// Parsed by hand, as getopt() silently skips options it doesn't know.
$options = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--(import|batch-size|concurrency)=(.+)$/', $arg, $m) || preg_match('/^--(no-import)$/', $arg, $m)) {
        $options[$m[1]] = $m[2] ?? true;
    } else {
        $options = null;
        break;
    }
}
if ($options === null) {
    fwrite(STDERR, "Usage: php send-usage-stats.php [--batch-size=N] [--concurrency=N] [--no-import]\n"
        . "       php send-usage-stats.php --import=FILE\n");
    exit(2);
}

/**
 * Runs one step, reporting a failure without stopping the steps after it.
 *
 * A failed query's message holds the values it wrote, which include IP
 * addresses, so only the kind of failure is printed. Sentry gets the detail.
 */
function usage_stats_step(string $label, callable $step): bool {
    try {
        $step();
        return true;
    } catch (Throwable $e) {
        if (function_exists('Sentry\captureException')) {
            \Sentry\captureException($e);
        }
        fwrite(STDERR, "$label failed: " . get_class($e) . "\n");
        return false;
    }
}

/**
 * Imports one log file and prints its counts.
 */
function usage_stats_import(string $path): void {
    foreach (DownloadImport::fromFile($path) as $label => $count) {
        print "Downloads " . str_replace('_', ' ', $label) . ": $count\n";
    }
}

if (isset($options['import'])) {
    exit(usage_stats_step('Import', fn () => usage_stats_import($options['import'])) ? 0 : 1);
}

$ok = true;
$log = Settings::get('DATA_ACCESS_LOG');
if ($log !== '' && !isset($options['no-import'])) {
    $ok = usage_stats_step('Import', fn () => usage_stats_import($log)) && $ok;
}

$url = Settings::get('ELASTICSEARCH_URL');
if ($url === '') {
    print "ELASTICSEARCH_URL is empty, so nothing was sent.\n";
    exit($ok ? 0 : 1);
}

$es = new ElasticsearchClient(new Client([
    'base_uri' => rtrim($url, '/') . '/',
    'headers' => ['Authorization' => 'ApiKey ' . Settings::get('ELASTICSEARCH_API_KEY')],
    'connect_timeout' => 10,
    'timeout' => 120,
]));
$exporter = new Exporter($es, (int) ($options['batch-size'] ?? 5000), (int) ($options['concurrency'] ?? 1));
// Elasticsearch index names must be lowercase.
$environment = preg_replace('/[^a-z0-9_-]/', '', strtolower(Settings::get('SENTRY_ENVIRONMENT'))) ?: 'development';
$methods = array_map(fn (array $method) => $method['parameters'], api_get_methods());

$sources = [
    'API calls' => new ApiCallSource($methods, $environment),
    'Downloads' => new DownloadSource($environment, Settings::internalAddresses()),
];
foreach ($sources as $label => $source) {
    $ok = usage_stats_step("Sending $label", function () use ($exporter, $source, $label) {
        $result = $exporter->export($source);
        print "$label sent: {$result['sent']}, last id {$result['last_id']}\n";
        if ($result['rejected']) {
            // Only document ids and error types, never field values.
            fwrite(STDERR, "$label: " . count($result['rejected']) . " rejected, such as:\n  "
                . implode("\n  ", array_slice($result['rejected'], 0, 3)) . "\n");
            throw new RuntimeException("$label rejected by Elasticsearch");
        }
    }) && $ok;
}

exit($ok ? 0 : 1);
