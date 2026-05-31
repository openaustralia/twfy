<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seeds the `moffice` table (ministerial offices) from db/seeds/data/moffice.csv.
 *
 * The full table is included since it's small (~2.4k rows).
 */
final class MofficeSeeder extends AbstractSeed {

    use CsvSeederTrait;

    public function run(): void {
        $this->loadCsv($this, 'moffice', 'moffice.csv', ['person']);
    }

}
