<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seeds the `memberinfo` table from db/seeds/data/memberinfo.csv.
 *
 * Restricted to members that appear as speakers in the seeded hansard
 * sample, so the row count stays bounded.
 */
final class MemberinfoSeeder extends AbstractSeed {

    use CsvSeederTrait;

    public function getDependencies(): array {
        return ['MemberSeeder'];
    }

    public function run(): void {
        $this->loadCsv($this, 'memberinfo', 'memberinfo.csv');
    }

}
