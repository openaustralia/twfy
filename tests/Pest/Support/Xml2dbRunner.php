<?php

/**
 * @file
 */

use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Gidredirect;
use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\Models\ImportError;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\Moffice;

/**
 * Runs scripts/xml2db.pl or scripts/importxml.php against the test database, with XML files from a temporary directory.
 *
 * The script commits on its own connection, so these specs cannot use the
 * transactional test case. They empty the tables the script writes before and
 * after each test instead, and refuse to run unless the database name contains "test".
 */
final class Xml2dbRunner {

    /**
     * Temporary directory holding scrapedxml/ and members/.
     */
    public string $dir;

    /**
     * Which loader run() starts: "perl" for scripts/xml2db.pl, "php" for scripts/importxml.php.
     */
    public string $implementation = 'perl';

    /**
     * Tables the script writes, emptied around each test.
     */
    private const TABLES = [Hansard::class, Epobject::class, Gidredirect::class, Member::class, Moffice::class, ImportError::class];

    /**
     * Creates the temporary directory tree.
     */
    public function __construct() {
        $this->dir = sys_get_temp_dir() . '/xml2db-test-' . bin2hex(random_bytes(6));
        foreach (['scrapedxml/representatives_debates', 'scrapedxml/senate_debates', 'members'] as $sub) {
            mkdir($this->dir . '/' . $sub, 0777, true);
        }
    }

    /**
     * Why these specs cannot run here, or NULL when they can.
     *
     * CI always has the Perl modules, so a missing one there fails the run instead of skipping it.
     */
    public static function unavailableReason(): ?string {
        $name = (string) getenv('DB_NAME');
        if (!str_contains($name, 'test')) {
            return "DB_NAME is '$name'; xml2db specs only run against a database whose name contains 'test'";
        }
        exec('perl -MDBI -MDBD::mysql -MXML::Twig -MHTML::Entities -e 1 2>&1', $output, $status);
        if ($status !== 0) {
            $reason = 'Perl modules DBI, DBD::mysql, XML::Twig or HTML::Entities are missing: ' . implode(' ', $output);
            if (getenv('CI')) {
                throw new RuntimeException($reason);
            }
            return $reason;
        }
        return null;
    }

    /**
     * Writes a day's XML for one house ("representatives" or "senate").
     */
    public function writeDay(string $house, string $date, string $xml, string $suffix = ''): void {
        file_put_contents("{$this->dir}/scrapedxml/{$house}_debates/{$date}{$suffix}.xml", $xml);
    }

    /**
     * Writes the five members files that --members reads, with empty ones for any not given.
     *
     * @param array<string, string> $files
     *   XML keyed by file name without extension: divisions, people, representatives, senators, ministers.
     */
    public function writeMembers(array $files): void {
        $empty = [
            'divisions' => '<divisions/>',
            'people' => '<people/>',
            'representatives' => '<members/>',
            'senators' => '<members/>',
            'ministers' => '<ministers/>',
        ];
        foreach ($files + $empty as $name => $xml) {
            file_put_contents("{$this->dir}/members/{$name}.xml", $xml);
        }
    }

    /**
     * Runs the script.
     *
     * @param list<string> $args
     *   Command-line arguments, for example ['--debates', '--date=2024-03-19'].
     *
     * @return array{status: int, output: string}
     *   Exit status, and standard output and error together.
     */
    public function run(array $args): array {
        $env = array_merge(getenv(), [
            'RAWDATA' => $this->dir . '/',
            'PWMEMBERS' => $this->dir . '/members/',
            'DB_HOST' => getenv('DB_HOST') ?: '127.0.0.1',
            'DB_PORT' => getenv('DB_PORT') ?: '3306',
            'DB_NAME' => (string) getenv('DB_NAME'),
            'DB_USER' => (string) getenv('DB_USER'),
            'DB_PASSWORD' => (string) getenv('DB_PASSWORD'),
        ]);
        $script = $this->implementation === 'php'
            ? [PHP_BINARY, ...$this->coverageOptions(), __DIR__ . '/../../../scripts/importxml.php']
            : ['perl', __DIR__ . '/../../../scripts/xml2db.pl'];
        $command = array_merge($script, $args);
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, null, $env);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        return ['status' => proc_close($process), 'output' => $output];
    }

    /**
     * PHP options that make the PHP loader write its coverage next to the parent's, or none outside a coverage run.
     *
     * @return list<string>
     */
    private function coverageOptions(): array {
        $directory = getenv('SUBPROCESS_COVERAGE_DIR');
        if ($directory === false || $directory === '') {
            return [];
        }
        return ['-d', 'auto_prepend_file=' . __DIR__ . '/subprocess-coverage.php'];
    }

    /**
     * Empties the tables the script writes.
     */
    public static function clearTables(): void {
        foreach (self::TABLES as $model) {
            $model::query()->delete();
        }
    }

    /**
     * Removes the temporary directory.
     */
    public function cleanup(): void {
        foreach (glob($this->dir . '/{scrapedxml/*,members}/*', GLOB_BRACE) as $file) {
            unlink($file);
        }
        foreach (glob($this->dir . '/{scrapedxml/*,scrapedxml,members}', GLOB_BRACE) as $directory) {
            rmdir($directory);
        }
        rmdir($this->dir);
    }

}
