<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seeds the `epobject` table from db/seeds/data/epobject.csv.
 *
 * `epobject` is the parent record table for hansard entries; it must be
 * seeded before HansardSeeder.
 */
final class EpobjectSeeder extends AbstractSeed {

    use CsvSeederTrait;

    public function run(): void {
        $this->loadCsv($this, 'epobject', 'epobject.csv', ['title', 'body', 'type', 'created', 'modified']);
    }

}
