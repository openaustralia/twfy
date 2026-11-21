<?php

namespace OpenAustralia\TWFY;

use OpenAustralia\TWFY\Models\ImportError;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\State\Scope;

/**
 * Errors found while importing a day of Hansard, recorded so the admin report can show which days had them.
 *
 * The importers call this through scripts/record-import-error.php and scripts/record-import-success.php. Each stage
 * (the parser and the loader) records and clears only its own errors for a day.
 */
class ImportErrors {

    public const SOURCES = ['parser', 'loader'];

    /**
     * The houses the importers name, by their hansard.major value.
     */
    public const HOUSES = ['representatives' => 1, 'senate' => 101];

    private const MAX_MESSAGE = 5000;

    /**
     * Records an error for a day and house, and reports it to Sentry when that is configured.
     */
    public static function record(string $date, int $major, string $source, string $message): void {
        $message = mb_substr($message, 0, self::MAX_MESSAGE);
        ImportError::create(['hdate' => $date, 'major' => $major, 'source' => $source, 'message' => $message]);
        self::report($date, $major, $source, $message);
    }

    /**
     * Clears a stage's errors for a day and house, because it handled that day without an error.
     *
     * @return int
     *   How many errors were cleared.
     */
    public static function clear(string $date, int $major, string $source): int {
        return ImportError::where('hdate', $date)->where('major', $major)->where('source', $source)->delete();
    }

    /**
     * The message with the names, times and numbers taken out, so Sentry groups the same kind of error together.
     */
    public static function pattern(string $message): string {
        return preg_replace(["/'[^']*'/", '/"[^"]*"/', '/\([^)]*\)/', '/\d+/'], ['?', '?', '(?)', '0'], $message);
    }

    /**
     * Runs one of the two commands from its command line options.
     *
     * @param string $command
     *   Either record or clear.
     * @param array<string, mixed>|false $options
     *   What getopt() returned.
     *
     * @return array{int, string}
     *   The exit status and a line to print, which is empty on success.
     */
    public static function execute(string $command, array|false $options): array {
        $options = is_array($options) ? array_map(fn ($value): string => (string) (is_array($value) ? end($value) : $value), $options) : [];
        $problem = self::problem($command, $options);
        if ($problem !== null) {
            return [2, $problem];
        }

        $major = self::HOUSES[$options['house']];
        if ($command === 'record') {
            self::record($options['date'], $major, $options['source'], $options['message']);
        } else {
            self::clear($options['date'], $major, $options['source']);
        }
        return [0, ''];
    }

    /**
     * What is wrong with the options for a command, or null when they are fine.
     *
     * @param string $command
     *   Either record or clear.
     * @param array<string, string> $options
     *   The command line options.
     */
    private static function problem(string $command, array $options): ?string {
        $needed = $command === 'record' ? ['date', 'house', 'source', 'message'] : ['date', 'house', 'source'];
        foreach ($needed as $name) {
            if (($options[$name] ?? '') === '') {
                return "Missing --$name";
            }
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $options['date'], $parts) || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            return 'The --date must be a real date as YYYY-MM-DD';
        }
        if (!isset(self::HOUSES[$options['house']])) {
            return '--house must be one of ' . implode(', ', array_keys(self::HOUSES));
        }
        if (!in_array($options['source'], self::SOURCES, true)) {
            return '--source must be one of ' . implode(', ', self::SOURCES);
        }
        return null;
    }

    /**
     * Sends the error to Sentry, as one issue for each kind of error whatever the names and dates in it.
     */
    private static function report(string $date, int $major, string $source, string $message): void {
        if (!class_exists(SentrySdk::class)) {
            return;
        }
        \Sentry\withScope(function (Scope $scope) use ($date, $major, $source, $message): void {
            $scope->setTag('import_source', $source);
            $scope->setTag('house', (string) $major);
            $scope->setTag('date', $date);
            $scope->setFingerprint(['import-error', $source, self::pattern($message)]);
            \Sentry\captureMessage($message, Severity::error());
        });
    }

}
