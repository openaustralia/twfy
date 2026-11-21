#!/usr/bin/env php
<?php

/**
 * @file
 * Loads Australian parliamentary data into the TWFY database. The PHP version of xml2db.pl, with the same options.
 *
 * Settings come from environment variables when they are set, as for xml2db.pl: DB_HOST, DB_PORT, DB_NAME,
 * DB_USER, DB_PASSWORD, RAWDATA (the parser's output directory) and PWMEMBERS (its members directory).
 * Otherwise they come from conf/general.
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use OpenAustralia\TWFY\XmlImport\Importer;
use OpenAustralia\TWFY\XmlImport\Messages;
use OpenAustralia\TWFY\XmlImport\Options;

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('memory_limit', '-1');

$options = Options::parse(
  array_slice($argv, 1),
  static function (string $problem): void {
      fwrite(STDERR, $problem . "\n");
  }
);
if ($options === null) {
    echo Options::usage();
    exit(0);
}

/**
 * A setting: the environment variable when it is set, otherwise the constant from conf/general.
 */
function importxml_setting(string $name, bool $emptyCounts = false): string {
    $value = getenv($name);
    if ($value !== false && ($emptyCounts || $value !== '')) {
        return $value;
    }
    $config = __DIR__ . '/../conf/general';
    if (!defined($name) && is_readable($config)) {
        include_once $config;
    }
    if (!defined($name)) {
        throw new RuntimeException("No value for '$name': set the environment variable or define it in conf/general");
    }
    return constant($name);
}

try {
    $capsule = new Capsule();
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => importxml_setting('DB_HOST'),
        'port' => getenv('DB_PORT') ?: 3306,
        'database' => importxml_setting('DB_NAME'),
        'username' => importxml_setting('DB_USER'),
        'password' => importxml_setting('DB_PASSWORD', true),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_0900_ai_ci',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $importer = new Importer(
      $options,
      new Messages($options->cronquiet),
      importxml_setting('RAWDATA'),
      $options->members ? importxml_setting('PWMEMBERS') : ''
    );
    // Some days failing is not a crash: they are in import_errors, and the rest loaded.
    exit($importer->run() > 0 ? 1 : 0);
}
catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(255);
}
