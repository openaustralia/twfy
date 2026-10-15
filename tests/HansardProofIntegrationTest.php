<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/utility.php';
require_once __DIR__ . '/../www/includes/easyparliament/hansardlist.php';

use OpenAustralia\TWFY\Models\Hansard;

/**
 * Hansard.proof = true means the day is a proof (draft), not the final version.
 * Null means unknown (not yet backfilled), and must not be treated as final.
 */
class HansardProofIntegrationTest extends TransactionalTestCase {

    /**
     *
     */
    private function makeRow(int $id, string $date, int $major, bool $proof): void {
        Hansard::create([
            'epobject_id' => $id,
            'gid' => "uk.org.publicwhip/debate/$date.$id.1",
            'major' => $major,
            'hdate' => $date,
            'proof' => $proof,
        ]);
    }

    /**
     *
     */
    public function test_rows_default_to_unknown() {
        Hansard::create(['epobject_id' => 900001, 'gid' => 'uk.org.publicwhip/debate/2026-03-01.1.1', 'major' => 1, 'hdate' => '2026-03-01']);

        $this->assertNull(Hansard::find(900001)->proof);
    }

    /**
     *
     */
    public function test_day_is_proof_when_any_row_is_proof() {
        $this->makeRow(900001, '2026-03-01', 1, false);
        $this->makeRow(900002, '2026-03-01', 1, true);

        $this->assertTrue((new DEBATELIST())->day_is_proof('2026-03-01'));
    }

    /**
     *
     */
    public function test_day_is_not_proof_when_every_row_is_final() {
        $this->makeRow(900001, '2026-03-02', 1, false);

        $this->assertFalse((new DEBATELIST())->day_is_proof('2026-03-02'));
    }

    /**
     *
     */
    public function test_day_is_not_proof_when_proof_is_unknown() {
        Hansard::create(['epobject_id' => 900001, 'gid' => 'uk.org.publicwhip/debate/2026-03-04.1.1', 'major' => 1, 'hdate' => '2026-03-04']);

        $this->assertFalse((new DEBATELIST())->day_is_proof('2026-03-04'));
    }

    /**
     *
     */
    public function test_proof_in_the_other_house_does_not_count() {
        $this->makeRow(900001, '2026-03-03', 101, true);

        $this->assertFalse((new DEBATELIST())->day_is_proof('2026-03-03'));
    }

}
