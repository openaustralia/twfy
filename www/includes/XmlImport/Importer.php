<?php

namespace OpenAustralia\TWFY\XmlImport;

use OpenAustralia\TWFY\ImportErrors;
use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;

/**
 * Loads the files the parser wrote into the database: the PHP version of xml2db.pl.
 *
 * Debates come first, the House and then the Senate, a day at a time from the oldest. Members come last.
 *
 * A day that fails is recorded in import_errors, and the load carries on with the next day.
 */
class Importer {

    /**
     * How many days failed to load.
     */
    private int $failedDays = 0;

    /**
     * Creates an importer.
     *
     * @param \OpenAustralia\TWFY\XmlImport\Options $options
     *   The command-line options.
     * @param \OpenAustralia\TWFY\XmlImport\Messages $messages
     *   Where progress is printed.
     * @param string $dataDirectory
     *   The parser's output directory with a trailing slash. It holds scrapedxml/.
     * @param string $membersDirectory
     *   The directory of the members files, with a trailing slash.
     */
    public function __construct(
      private readonly Options $options,
      private readonly Messages $messages,
      private readonly string $dataDirectory,
      private readonly string $membersDirectory,
    ) {
    }

    /**
     * Runs the load the options ask for.
     *
     * @return int
     *   How many days failed to load. They are in import_errors.
     */
    public function run(): int {
        self::deleteLonelyEpobjects($this->messages);

        if ($this->options->debates) {
            $this->loadHouse('representatives_debates', 1);
        }
        if ($this->options->senate) {
            $this->loadHouse('senate_debates', 101);
        }
        if ($this->options->members) {
            (new MembersLoader($this->messages, $this->membersDirectory))->load();
        }
        return $this->failedDays;
    }

    /**
     * Loads each selected day of one house.
     */
    private function loadHouse(string $directory, int $major): void {
        $loader = new DebatesLoader($this->options, $this->messages, $this->dataDirectory . 'scrapedxml/' . $directory . '/', $major);
        foreach ($loader->datesToLoad() as $date) {
            $this->messages->info("db loading  $date\n");
            try {
                $loader->loadDay($date);
                $this->recordSuccess($date, $major);
            } catch (\Throwable $error) {
                $this->recordFailure($date, $major, $error->getMessage());
            }
        }
    }

    /**
     * Records that a day failed, replacing the loader's earlier error for it, and carries on.
     *
     * Not being able to record it never stops the run, as the table may not exist yet on a server that has the new
     * code before its migration.
     */
    private function recordFailure(string $date, int $major, string $message): void {
        $this->failedDays++;
        $message = rtrim($message);
        fwrite(STDERR, "ERROR loading $date (major $major): $message\n");
        try {
            ImportErrors::clear($date, $major, 'loader');
            ImportErrors::record($date, $major, 'loader', $message);
        } catch (\Throwable $error) {
            fwrite(STDERR, 'Could not record the error in import_errors: ' . $error->getMessage() . "\n");
        }
    }

    /**
     * Clears the loader's earlier errors for a day that loaded.
     */
    private function recordSuccess(string $date, int $major): void {
        try {
            ImportErrors::clear($date, $major, 'loader');
        } catch (\Throwable $error) {
            fwrite(STDERR, 'Could not clear import_errors: ' . $error->getMessage() . "\n");
        }
    }

    /**
     * Deletes epobject rows that no hansard row uses, which a failed or forced load can leave behind.
     */
    public static function deleteLonelyEpobjects(Messages $messages): void {
        if (Epobject::query()->toBase()->where('type', '<>', 1)->count() > 0) {
            throw new \RuntimeException('Unknown type not 1 entries in epobject table, lots of code needs fixing');
        }
        $epobjects = Epobject::query()->toBase()->count();
        $hansard = Hansard::query()->toBase()->count();
        if ($epobjects === $hansard) {
            return;
        }
        $messages->info("Fixing up lonely epobjects. Counts: $epobjects $hansard\n");
        $lonely = Epobject::query()->toBase()
          ->whereNotIn('epobject_id', Hansard::query()->toBase()->select('epobject_id'))
          ->pluck('epobject_id')->all();
        $messages->info('Lonely epobject count: ' . count($lonely) . "\n");
        foreach (array_chunk($lonely, 1000) as $chunk) {
            Epobject::query()->toBase()->whereIn('epobject_id', $chunk)->delete();
        }
    }

}
