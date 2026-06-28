<?php

/**
 * @file
 * Tests for HANSARDLIST methods that use the Hansard model.
 */

use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;

require_once INCLUDESPATH . 'url.php';
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

    private function insertEpobject(int $id, string $body): void {
        Epobject::query()->insert([
            'epobject_id' => $id,
            'body' => $body,
            'type' => 1,
        ]);
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

    public function test_get_data_by_person_returns_only_requested_member_ids_for_current_major(): void {
        $sectionId = 91000;
        $subsectionId = 91001;
        $speechId = 91002;
        $otherSpeechId = 91003;
        $otherMajorSpeechId = 91004;

        $this->insertEpobject($sectionId, 'Cost of living');
        $this->insertEpobject($subsectionId, 'Questions without notice');
        $this->insertEpobject($speechId, 'A speech by the requested member');
        $this->insertEpobject($otherSpeechId, 'A speech by a different member');
        $this->insertEpobject($otherMajorSpeechId, 'A speech in another major');

        // Subsection heading row used by the inner join on hansard_subsection.
        $this->insertHansard([
            'epobject_id' => $subsectionId,
            'gid' => 'uk.org.publicwhip/debate/2024-01-15.1.0',
            'htype' => 11,
            'speaker_id' => 0,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hpos' => 1,
        ]);

        // Matching speech (expected in results).
        $this->insertHansard([
            'epobject_id' => $speechId,
            'gid' => 'uk.org.publicwhip/debate/2024-01-15.1.1',
            'htype' => 12,
            'speaker_id' => 12345,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hpos' => 2,
        ]);

        // Non-matching speaker (must be filtered out).
        $this->insertHansard([
            'epobject_id' => $otherSpeechId,
            'gid' => 'uk.org.publicwhip/debate/2024-01-15.1.2',
            'htype' => 12,
            'speaker_id' => 54321,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hpos' => 3,
        ]);

        // Matching speaker but wrong major (must be filtered out by $this->major).
        $this->insertHansard([
            'epobject_id' => $otherMajorSpeechId,
            'gid' => 'uk.org.publicwhip/wrans/2024-01-15.1.3',
            'htype' => 12,
            'speaker_id' => 12345,
            'major' => 3,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hpos' => 4,
        ]);

        $list = new DEBATELIST();
        $data = $list->_get_data_by_person(['member_ids' => '12345', 'max' => 10]);

        $this->assertArrayHasKey('rows', $data);
        $this->assertCount(1, $data['rows']);
        $this->assertSame(12345, $data['rows'][0]['speaker_id']);
        $this->assertSame('A speech by the requested member', $data['rows'][0]['body']);
        $this->assertStringContainsString('Questions without notice | House of Representatives debates', $data['rows'][0]['parent']['body']);
    }

}
