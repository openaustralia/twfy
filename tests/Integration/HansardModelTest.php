<?php

/**
 * @file
 * Tests for HANSARDLIST methods that use the Hansard model.
 */

use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;

include_once EASYPARLIAMENTPATH . 'hansardlist.php';

/**
 * Tests for HANSARDLIST/DEBATELIST/WRANSLIST ORM-converted methods.
 */
class HansardModelTest extends TransactionalTestCase {

    private static int $nextId = 80000;

    /**
     * Insert a minimal hansard record.
     */
    private function insertHansard(array $overrides = []): void {
        $id = self::$nextId++;
        $defaults = [
            'epobject_id' => $id,
            'gid' => 'uk.org.publicwhip/test.' . $id,
            'htype' => 12,
            'speaker_id' => 0,
            'major' => 1,
            'section_id' => 0,
            'subsection_id' => 0,
            'hpos' => 0,
            'hdate' => '2024-01-15',
            'source_url' => '',
        ];
        Hansard::query()->insert(array_merge($defaults, $overrides));
    }

    public function test_debatelist_total_items(): void {
        $this->insertHansard(['major' => 1]);
        $this->insertHansard(['major' => 1]);
        $this->insertHansard(['major' => 3]);

        $list = new DEBATELIST();
        $this->assertEquals(2, $list->total_items());
    }

    public function test_wranslist_total_items(): void {
        $this->insertHansard(['major' => 3]);
        $this->insertHansard(['major' => 3]);
        $this->insertHansard(['major' => 1]);

        $list = new WRANSLIST();
        $this->assertEquals(2, $list->total_items());
    }

    public function test_debatelist_total_speeches(): void {
        $this->insertHansard(['major' => 1, 'htype' => 12]);
        $this->insertHansard(['major' => 1, 'htype' => 10]);
        $this->insertHansard(['major' => 1, 'htype' => 12]);

        $list = new DEBATELIST();
        $this->assertEquals(2, $list->total_speeches());
    }

    public function test_wranslist_total_questions(): void {
        $this->insertHansard(['major' => 3, 'minor' => 1]);
        $this->insertHansard(['major' => 3, 'minor' => 2]);
        $this->insertHansard(['major' => 3, 'minor' => 1]);

        $list = new WRANSLIST();
        $this->assertEquals(2, $list->total_questions());
    }

    public function test_total_items_returns_zero_when_empty(): void {
        $list = new DEBATELIST();
        $this->assertEquals(0, $list->total_items());
    }

    public function test_total_speeches_returns_zero_when_empty(): void {
        $list = new DEBATELIST();
        $this->assertEquals(0, $list->total_speeches());
    }

}
