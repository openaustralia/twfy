<?php

namespace OpenAustralia\TWFY\XmlImport;

use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;

/**
 * Loads the files the parser wrote into the database: the PHP version of xml2db.pl.
 *
 * Debates come first, the House and then the Senate, a day at a time from the oldest. Members come last.
 */
class Importer {

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
     */
    public function run(): void {
        self::deleteLonelyEpobjects($this->messages, $this->options);

        if ($this->options->debates) {
            $this->loadHouse('representatives_debates', 1);
        }
        if ($this->options->senate) {
            $this->loadHouse('senate_debates', 101);
        }
        if ($this->options->members) {
            (new MembersLoader($this->messages, $this->membersDirectory))->load();
        }
    }

    /**
     * Loads each selected day of one house.
     */
    private function loadHouse(string $directory, int $major): void {
        $loader = new DebatesLoader($this->options, $this->messages, $this->dataDirectory . 'scrapedxml/' . $directory . '/', $major);
        foreach ($loader->datesToLoad() as $date) {
            $this->messages->info("db loading  $date\n");
            $loader->loadDay($date);
        }
    }

    /**
     * Deletes epobject rows that no hansard row uses, which a failed or forced load can leave behind.
     */
    public static function deleteLonelyEpobjects(Messages $messages, Options $options): void {
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
