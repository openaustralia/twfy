<?php

/**
 * @file
 * Tests for HANSARDLIST methods that use the Hansard model.
 */

use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;

require_once INCLUDESPATH . 'data.php';
require_once INCLUDESPATH . 'url.php';
include_once EASYPARLIAMENTPATH . 'hansardlist.php';

/**
 * Tests for HANSARDLIST/DEBATELIST/WRANSLIST ORM-converted methods.
 */
class HansardModelTest extends TransactionalTestCase {

    private static int $nextId = 80000;

    protected function setUp(): void {
        parent::setUp();

        if (!defined('SHORTDATEFORMAT')) {
            define('SHORTDATEFORMAT', 'j M Y');
        }

        if (!isset($GLOBALS['DATA']) || !$GLOBALS['DATA']) {
            $GLOBALS['DATA'] = new DATA();
        }
        if (!isset($GLOBALS['hansardmajors']) || !$GLOBALS['hansardmajors']) {
            $GLOBALS['hansardmajors'] = [
                1 => [
                    'title' => 'House of Representatives debates',
                    'page_all' => 'debates',
                    'type' => 'debate',
                    'singular' => 'debate',
                    'plural' => 'debates',
                    'gidvar' => 'id',
                    'page_year' => 'debates'
                ],
                3 => [
                    'title' => 'Written Answers',
                    'page_all' => 'wrans',
                    'type' => 'other',
                    'singular' => 'written answer',
                    'plural' => 'written answers',
                    'gidvar' => 'id',
                    'page_year' => 'wrans'
                ],
            ];
        }
    }

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

    private function insertEpobjectRaw(int $id, string $body): void {
        parlDBQuery(
            'INSERT INTO epobject (epobject_id, body, type) VALUES (?, ?, ?)',
            $id,
            $body,
            1
        );
    }

    private function insertHansardRaw(array $overrides = []): void {
        $id = self::$nextId++;
        $row = array_merge([
            'epobject_id' => $id,
            'gid' => 'uk.org.publicwhip/test.' . $id,
            'htype' => 12,
            'speaker_id' => 0,
            'major' => 1,
            'section_id' => 0,
            'subsection_id' => 0,
            'hpos' => 0,
            'hdate' => '2024-01-15',
            'htime' => null,
            'source_url' => '',
            'minor' => null,
            'created' => null,
            'modified' => null,
        ], $overrides);

        parlDBQuery(
            'INSERT INTO hansard (epobject_id, gid, htype, speaker_id, major, section_id, subsection_id, hpos, hdate, htime, source_url, minor, created, modified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $row['epobject_id'],
            $row['gid'],
            $row['htype'],
            $row['speaker_id'],
            $row['major'],
            $row['section_id'],
            $row['subsection_id'],
            $row['hpos'],
            $row['hdate'],
            $row['htime'],
            $row['source_url'],
            $row['minor'],
            $row['created'],
            $row['modified']
        );
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
        $this->assertSame('Questions without notice | Cost of living | House of Representatives debates', $data['rows'][0]['parent']['body']);
    }

    public function test_get_hansard_data_returns_row_with_body_and_urls(): void {
        $epobjectId = 92000;
        $this->insertEpobjectRaw($epobjectId, 'Body for hansard data');
        $this->insertHansardRaw([
            'epobject_id' => $epobjectId,
            'gid' => 'uk.org.publicwhip/debate/2024-02-01.1.0',
            'htype' => 10,
            'major' => 1,
            'section_id' => $epobjectId,
            'subsection_id' => $epobjectId,
            'hdate' => '2024-02-01',
            'hpos' => 1,
        ]);

        $list = new DEBATELIST();
        $rows = $list->_get_hansard_data([
            'amount' => ['body' => true],
            'where' => ['hansard.epobject_id=' => $epobjectId],
            'order' => 'hansard.hpos ASC',
            'limit' => '1',
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame($epobjectId, (int) $rows[0]['epobject_id']);
        $this->assertSame('Body for hansard data', $rows[0]['body']);
        $this->assertArrayHasKey('listurl', $rows[0]);
        $this->assertArrayHasKey('commentsurl', $rows[0]);
        $this->assertStringContainsString('2024-02-01.1.0', $rows[0]['gid']);
    }

    public function test_get_nextprev_items_returns_previous_and_next_speaker_links(): void {
        $sectionId = 93000;
        $subsectionId = 93001;
        $prevId = 93002;
        $nextId = 93003;

        $this->insertEpobjectRaw($sectionId, 'Main section');
        $this->insertEpobjectRaw($subsectionId, 'Main subsection');
        $this->insertEpobjectRaw($prevId, 'Previous speech');
        $this->insertEpobjectRaw($nextId, 'Next speech');

        $this->insertHansardRaw([
            'epobject_id' => $sectionId,
            'gid' => 'uk.org.publicwhip/debate/2024-03-01.1.0',
            'htype' => 10,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $sectionId,
            'hdate' => '2024-03-01',
            'hpos' => 1,
        ]);
        $this->insertHansardRaw([
            'epobject_id' => $subsectionId,
            'gid' => 'uk.org.publicwhip/debate/2024-03-01.1.0a',
            'htype' => 11,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hdate' => '2024-03-01',
            'hpos' => 2,
        ]);
        $this->insertHansardRaw([
            'epobject_id' => $prevId,
            'gid' => 'uk.org.publicwhip/debate/2024-03-01.1.1',
            'htype' => 12,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hdate' => '2024-03-01',
            'hpos' => 3,
        ]);
        $this->insertHansardRaw([
            'epobject_id' => $nextId,
            'gid' => 'uk.org.publicwhip/debate/2024-03-01.1.3',
            'htype' => 12,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hdate' => '2024-03-01',
            'hpos' => 5,
        ]);

        $list = new DEBATELIST();
        $nextprev = $list->_get_nextprev_items([
            'htype' => '12',
            'major' => 1,
            'hdate' => '2024-03-01',
            'hpos' => 4,
            'subsection_id' => $subsectionId,
            'section_id' => $sectionId,
        ]);

        $this->assertArrayHasKey('prev', $nextprev);
        $this->assertArrayHasKey('next', $nextprev);
        $this->assertSame('Previous speaker', $nextprev['prev']['body']);
        $this->assertSame('Next speaker', $nextprev['next']['body']);
        $this->assertStringContainsString('2024-03-01.1.1', $nextprev['prev']['url']);
        $this->assertStringContainsString('2024-03-01.1.3', $nextprev['next']['url']);
    }

    public function test_get_item_returns_item_for_existing_gid(): void {
        $epobjectId = 94000;
        $this->insertEpobjectRaw($epobjectId, 'Item body text');
        $this->insertHansardRaw([
            'epobject_id' => $epobjectId,
            'gid' => 'uk.org.publicwhip/debate/2024-04-01.2.0',
            'htype' => 12,
            'major' => 1,
            'section_id' => $epobjectId,
            'subsection_id' => $epobjectId,
            'hdate' => '2024-04-01',
            'hpos' => 10,
            'speaker_id' => 0,
        ]);

        $list = new DEBATELIST();
        $item = $list->_get_item(['gid' => '2024-04-01.2.0']);

        $this->assertIsArray($item);
        $this->assertSame($epobjectId, (int) $item['epobject_id']);
        $this->assertSame('Item body text', $item['body']);
        $this->assertSame('2024-04-01.2.0', $item['gid']);
    }

}
