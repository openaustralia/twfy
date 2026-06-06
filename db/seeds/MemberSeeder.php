<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seeds the `member` table from db/seeds/data/member.csv.
 *
 * The CSV is pruned to only those members that appear as speakers in the
 * hansard sample seeded by HansardSeeder, so dev DBs stay small.
 *
 * To regenerate, see db/seeds/README.md.
 */
final class MemberSeeder extends AbstractSeed {

    use CsvSeederTrait;

    public function run(): void {
        $this->loadCsv($this, 'member', 'member.csv', ['house', 'first_name']);
    }

}
