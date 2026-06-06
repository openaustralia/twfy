<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seeds the `hansard` table from db/seeds/data/hansard.csv.
 *
 * Sample selection (see db/seeds/README for regeneration command):
 * - 20 most recent rows from 2026
 * - One month per pre-2026 year present in the source DB:
 *   2018-10, 2019-07, 2020-02, 2022-11, 2023-06
 *
 * Depends on EpobjectSeeder for the parent epobject rows referenced by
 * speaker_id / section_id / subsection_id.
 */
final class HansardSeeder extends AbstractSeed {

    use CsvSeederTrait;

    public function getDependencies(): array {
        return ['EpobjectSeeder'];
    }

    public function run(): void {
        $this->loadCsv($this, 'hansard', 'hansard.csv', ['htime', 'minor', 'created', 'modified']);
    }

}
