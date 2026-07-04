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
 * Test access wrapper for protected getHandsardData().
 */
class DEBATELISTHansardDataAccessStub extends DEBATELIST {

    public function callGetHandsardData(array $input): array {
        return $this->getHandsardData($input);
    }

}

/**
 * Test access wrapper for protected getItem().
 */
class DEBATELISTGetItemAccessStub extends DEBATELIST {

    public function callGetItem(array $args) {
        return $this->getItem($args);
    }

}

/**
 * Test access wrapper for protected next/prev methods.
 */
class DEBATELISTNextPrevAccessStub extends DEBATELIST {

    public function callGetNextPrevItems(array $itemdata): array {
        return $this->getNextPrevItems($itemdata);
    }

    public function callGetNextPrevDates(string $date): array {
        return $this->getNextPrevDates($date);
    }

}

/**
 * Test access wrapper for protected getDataByRecent().
 */
class DEBATELISTRecentAccessStub extends DEBATELIST {

    public function callGetDataByRecent(array $args): array {
        return $this->getDataByRecent($args);
    }

}

/**
 * Test access wrapper for protected getDataByDate().
 */
class DEBATELISTDateAccessStub extends DEBATELIST {

    public function callGetDataByDate(array $args): array {
        return $this->getDataByDate($args);
    }

    public function callFetchDateSections(string $date): array {
        return $this->fetchDateSections($date)->all();
    }

    public function callFetchDateSubsections(int $sectionId): array {
        return $this->fetchDateSubsections($sectionId)->all();
    }

    public function callBuildDateRows(string $date): array {
        return $this->buildDateRows($date);
    }

    public function callGetDateHeadingContentCount(array $item): ?int {
        return $this->getDateHeadingContentCount($item);
    }

    public function callGetDateHeadingExcerpt(array $item): ?string {
        return $this->getDateHeadingExcerpt($item);
    }

}

/**
 * Test access wrapper for protected getDataByDate() on wrans.
 */
class WRANSLISTDateAccessStub extends WRANSLIST {

    public function callBuildDateRows(string $date): array {
        return $this->buildDateRows($date);
    }

}

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

    public function test_get_data_by_person_accepts_multiple_comma_separated_member_ids(): void {
        $sectionId = 91010;
        $subsectionId = 91011;
        $firstSpeechId = 91012;
        $secondSpeechId = 91013;

        $this->insertEpobject($sectionId, 'Cost of living');
        $this->insertEpobject($subsectionId, 'Questions without notice');
        $this->insertEpobject($firstSpeechId, 'First requested speech');
        $this->insertEpobject($secondSpeechId, 'Second requested speech');

        $this->insertHansard([
            'epobject_id' => $subsectionId,
            'gid' => 'uk.org.publicwhip/debate/2024-01-16.1.0',
            'htype' => 11,
            'speaker_id' => 0,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hpos' => 1,
        ]);
        $this->insertHansard([
            'epobject_id' => $firstSpeechId,
            'gid' => 'uk.org.publicwhip/debate/2024-01-16.1.1',
            'htype' => 12,
            'speaker_id' => 12345,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hpos' => 2,
        ]);
        $this->insertHansard([
            'epobject_id' => $secondSpeechId,
            'gid' => 'uk.org.publicwhip/debate/2024-01-16.1.2',
            'htype' => 12,
            'speaker_id' => 54321,
            'major' => 1,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'hpos' => 3,
        ]);

        $list = new DEBATELIST();
        $data = $list->_get_data_by_person(['member_ids' => '12345, nope, 54321', 'max' => 10]);

        $this->assertCount(2, $data['rows']);
        $speakerIds = array_map(static fn ($row) => (int) $row['speaker_id'], $data['rows']);
        $this->assertContains(12345, $speakerIds);
        $this->assertContains(54321, $speakerIds);
    }

    public function test_get_data_by_person_returns_empty_rows_for_invalid_member_id_string(): void {
        $list = new DEBATELIST();

        $data = $list->_get_data_by_person(['member_ids' => 'not-a-member-id', 'max' => 10]);

        $this->assertSame(['rows' => []], $data);
    }

    public function test_get_handsard_data_returns_row_with_body_and_urls(): void {
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

        $list = new DEBATELISTHansardDataAccessStub();
        $rows = $list->callGetHandsardData([
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

        $list = new DEBATELISTNextPrevAccessStub();
        $nextprev = $list->callGetNextPrevItems([
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

    public function test_get_nextprev_dates_returns_prev_next_and_up_links(): void {
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-02-27',
            'gid' => 'uk.org.publicwhip/debate/2024-02-27.1.0',
            'epobject_id' => 95000,
            'section_id' => 95000,
            'subsection_id' => 95000,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-03-01',
            'gid' => 'uk.org.publicwhip/debate/2024-03-01.1.0',
            'epobject_id' => 95001,
            'section_id' => 95001,
            'subsection_id' => 95001,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-03-05',
            'gid' => 'uk.org.publicwhip/debate/2024-03-05.1.0',
            'epobject_id' => 95002,
            'section_id' => 95002,
            'subsection_id' => 95002,
            'htype' => 10,
            'hpos' => 1,
        ]);

        $list = new DEBATELISTNextPrevAccessStub();
        $nextprev = $list->callGetNextPrevDates('2024-03-01');

        $this->assertArrayHasKey('prev', $nextprev);
        $this->assertArrayHasKey('next', $nextprev);
        $this->assertArrayHasKey('up', $nextprev);
        $this->assertSame('2024-02-27', $nextprev['prev']['hdate']);
        $this->assertSame('2024-03-05', $nextprev['next']['hdate']);
        $this->assertSame('Previous day', $nextprev['prev']['body']);
        $this->assertSame('Next day', $nextprev['next']['body']);
        $this->assertStringContainsString('d=2024-02-27', $nextprev['prev']['url']);
        $this->assertStringContainsString('d=2024-03-05', $nextprev['next']['url']);
        $this->assertSame("All of 2024's debates", $nextprev['up']['body']);
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

        $list = new DEBATELISTGetItemAccessStub();
        $item = $list->callGetItem(['gid' => '2024-04-01.2.0']);

        $this->assertIsArray($item);
        $this->assertSame($epobjectId, (int) $item['epobject_id']);
        $this->assertSame('Item body text', $item['body']);
        $this->assertSame('2024-04-01.2.0', $item['gid']);
    }

    public function test_get_data_by_recent_returns_distinct_dates_descending(): void {
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-06-02',
            'gid' => 'uk.org.publicwhip/debate/2024-06-02.1.0',
            'epobject_id' => 96000,
            'section_id' => 96000,
            'subsection_id' => 96000,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-06-02',
            'gid' => 'uk.org.publicwhip/debate/2024-06-02.1.1',
            'epobject_id' => 96001,
            'section_id' => 96000,
            'subsection_id' => 96000,
            'htype' => 12,
            'hpos' => 2,
        ]);
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-06-03',
            'gid' => 'uk.org.publicwhip/debate/2024-06-03.1.0',
            'epobject_id' => 96002,
            'section_id' => 96002,
            'subsection_id' => 96002,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansardRaw([
            'major' => 3,
            'hdate' => '2024-06-04',
            'gid' => 'uk.org.publicwhip/wrans/2024-06-04.1.0',
            'epobject_id' => 96003,
            'section_id' => 96003,
            'subsection_id' => 96003,
            'htype' => 10,
            'hpos' => 1,
        ]);

        $list = new DEBATELISTRecentAccessStub();
        $data = $list->callGetDataByRecent([]);

        $this->assertSame('Recent dates', $data['info']['text']);
        $this->assertCount(2, $data['rows']);
        $this->assertSame(format_date('2024-06-03', SHORTDATEFORMAT), $data['rows'][0]['body']);
        $this->assertSame(format_date('2024-06-02', SHORTDATEFORMAT), $data['rows'][1]['body']);
        $this->assertStringContainsString('d=2024-06-03', $data['rows'][0]['listurl']);
        $this->assertStringContainsString('d=2024-06-02', $data['rows'][1]['listurl']);
    }

    public function test_get_data_by_recent_respects_days_limit(): void {
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-06-05',
            'gid' => 'uk.org.publicwhip/debate/2024-06-05.1.0',
            'epobject_id' => 96010,
            'section_id' => 96010,
            'subsection_id' => 96010,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-06-06',
            'gid' => 'uk.org.publicwhip/debate/2024-06-06.1.0',
            'epobject_id' => 96011,
            'section_id' => 96011,
            'subsection_id' => 96011,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-06-07',
            'gid' => 'uk.org.publicwhip/debate/2024-06-07.1.0',
            'epobject_id' => 96012,
            'section_id' => 96012,
            'subsection_id' => 96012,
            'htype' => 10,
            'hpos' => 1,
        ]);

        $list = new DEBATELISTRecentAccessStub();
        $data = $list->callGetDataByRecent(['days' => 2]);

        $this->assertCount(2, $data['rows']);
        $this->assertStringContainsString('d=2024-06-07', $data['rows'][0]['listurl']);
        $this->assertStringContainsString('d=2024-06-06', $data['rows'][1]['listurl']);
    }

    public function test_get_data_by_date_returns_section_and_subsection_rows(): void {
        global $this_page;
        $this_page = 'debates';

        $sectionId = 96100;
        $subsectionId = 96101;
        $speechId = 96102;

        $this->insertEpobject($sectionId, 'Main section heading');
        $this->insertEpobject($subsectionId, 'Main subsection heading');
        $this->insertEpobject($speechId, 'First speech in subsection');

        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-01',
            'gid' => 'uk.org.publicwhip/debate/2024-07-01.1.0',
            'epobject_id' => $sectionId,
            'section_id' => $sectionId,
            'subsection_id' => $sectionId,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-01',
            'gid' => 'uk.org.publicwhip/debate/2024-07-01.1.0a',
            'epobject_id' => $subsectionId,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'htype' => 11,
            'hpos' => 2,
        ]);
        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-01',
            'gid' => 'uk.org.publicwhip/debate/2024-07-01.1.1',
            'epobject_id' => $speechId,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'htype' => 12,
            'hpos' => 3,
        ]);

        $list = new DEBATELISTDateAccessStub();
        $data = $list->callGetDataByDate(['date' => '2024-07-01']);

        $this->assertSame('2024-07-01', $data['info']['date']);
        $this->assertSame(1, $data['info']['major']);
        $this->assertCount(2, $data['rows']);
        $this->assertSame('Main section heading', $data['rows'][0]['body']);
        $this->assertSame('Main subsection heading', $data['rows'][1]['body']);
        $this->assertSame(1, $data['rows'][1]['contentcount']);
        $this->assertStringContainsString('2024-07-01.1.0', $data['rows'][0]['listurl']);
        $this->assertStringContainsString('2024-07-01.1.0a', $data['rows'][1]['listurl']);
    }

    public function test_fetch_date_sections_returns_only_sections_for_date_and_major(): void {
        $sectionId = 96110;
        $subsectionId = 96111;
        $otherDateSectionId = 96112;
        $otherMajorSectionId = 96113;

        $this->insertEpobject($sectionId, 'Section on requested date');
        $this->insertEpobject($subsectionId, 'Subsection on requested date');
        $this->insertEpobject($otherDateSectionId, 'Section on different date');
        $this->insertEpobject($otherMajorSectionId, 'Section on different major');

        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-02',
            'gid' => 'uk.org.publicwhip/debate/2024-07-02.1.0',
            'epobject_id' => $sectionId,
            'section_id' => $sectionId,
            'subsection_id' => $sectionId,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-02',
            'gid' => 'uk.org.publicwhip/debate/2024-07-02.1.0a',
            'epobject_id' => $subsectionId,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'htype' => 11,
            'hpos' => 2,
        ]);
        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-03',
            'gid' => 'uk.org.publicwhip/debate/2024-07-03.1.0',
            'epobject_id' => $otherDateSectionId,
            'section_id' => $otherDateSectionId,
            'subsection_id' => $otherDateSectionId,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansard([
            'major' => 3,
            'hdate' => '2024-07-02',
            'gid' => 'uk.org.publicwhip/wrans/2024-07-02.1.0',
            'epobject_id' => $otherMajorSectionId,
            'section_id' => $otherMajorSectionId,
            'subsection_id' => $otherMajorSectionId,
            'htype' => 10,
            'hpos' => 1,
        ]);

        $list = new DEBATELISTDateAccessStub();
        $rows = $list->callFetchDateSections('2024-07-02');

        $this->assertCount(1, $rows);
        $this->assertSame($sectionId, (int) $rows[0]->epobject_id);
        $this->assertSame('Section on requested date', $rows[0]->body);
    }

    public function test_fetch_date_subsections_returns_only_subsections_for_section(): void {
        $sectionId = 96120;
        $subsectionId = 96121;
        $otherSectionSubsectionId = 96122;

        $this->insertEpobject($sectionId, 'Section parent');
        $this->insertEpobject($subsectionId, 'Matching subsection');
        $this->insertEpobject($otherSectionSubsectionId, 'Other subsection');

        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-04',
            'gid' => 'uk.org.publicwhip/debate/2024-07-04.1.0',
            'epobject_id' => $sectionId,
            'section_id' => $sectionId,
            'subsection_id' => $sectionId,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-04',
            'gid' => 'uk.org.publicwhip/debate/2024-07-04.1.0a',
            'epobject_id' => $subsectionId,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'htype' => 11,
            'hpos' => 2,
        ]);
        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-04',
            'gid' => 'uk.org.publicwhip/debate/2024-07-04.2.0a',
            'epobject_id' => $otherSectionSubsectionId,
            'section_id' => 96123,
            'subsection_id' => $otherSectionSubsectionId,
            'htype' => 11,
            'hpos' => 3,
        ]);

        $list = new DEBATELISTDateAccessStub();
        $rows = $list->callFetchDateSubsections($sectionId);

        $this->assertCount(1, $rows);
        $this->assertSame($subsectionId, (int) $rows[0]->epobject_id);
        $this->assertSame('Matching subsection', $rows[0]->body);
    }

    public function test_build_date_rows_adds_counts_and_excerpts_for_section_and_subsection(): void {
        $sectionId = 96130;
        $subsectionId = 96131;
        $sectionSpeechId = 96132;
        $subsectionSpeechId = 96133;

        $this->insertEpobject($sectionId, 'Section heading');
        $this->insertEpobject($subsectionId, 'Subsection heading');
        $this->insertEpobject($sectionSpeechId, 'Section excerpt body');
        $this->insertEpobject($subsectionSpeechId, 'Subsection excerpt body');

        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-05',
            'gid' => 'uk.org.publicwhip/debate/2024-07-05.1.0',
            'epobject_id' => $sectionId,
            'section_id' => $sectionId,
            'subsection_id' => $sectionId,
            'htype' => 10,
            'hpos' => 1,
        ]);
        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-05',
            'gid' => 'uk.org.publicwhip/debate/2024-07-05.1.1',
            'epobject_id' => $sectionSpeechId,
            'section_id' => $sectionId,
            'subsection_id' => $sectionId,
            'htype' => 12,
            'hpos' => 2,
        ]);
        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-05',
            'gid' => 'uk.org.publicwhip/debate/2024-07-05.1.2',
            'epobject_id' => $subsectionId,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'htype' => 11,
            'hpos' => 3,
        ]);
        $this->insertHansard([
            'major' => 1,
            'hdate' => '2024-07-05',
            'gid' => 'uk.org.publicwhip/debate/2024-07-05.1.3',
            'epobject_id' => $subsectionSpeechId,
            'section_id' => $sectionId,
            'subsection_id' => $subsectionId,
            'htype' => 12,
            'hpos' => 4,
        ]);

        $list = new DEBATELISTDateAccessStub();
        $rows = $list->callBuildDateRows('2024-07-05');

        $this->assertCount(2, $rows);
        $this->assertSame('Section heading', $rows[0]['body']);
        $this->assertSame(1, $rows[0]['contentcount']);
        $this->assertSame('Section heading', $rows[0]['excerpt']);
        $this->assertSame('Subsection heading', $rows[1]['body']);
        $this->assertSame(1, $rows[1]['contentcount']);
        $this->assertSame('Subsection heading', $rows[1]['excerpt']);
    }

    public function test_date_heading_helpers_return_null_for_non_debate_rows(): void {
        $sectionId = 96140;

        $this->insertEpobject($sectionId, 'Wrans section');
        $this->insertHansard([
            'major' => 3,
            'hdate' => '2024-07-06',
            'gid' => 'uk.org.publicwhip/wrans/2024-07-06.1.0',
            'epobject_id' => $sectionId,
            'section_id' => $sectionId,
            'subsection_id' => $sectionId,
            'htype' => 10,
            'hpos' => 1,
        ]);

        $list = new WRANSLISTDateAccessStub();
        $rows = $list->callBuildDateRows('2024-07-06');

        $this->assertCount(1, $rows);
        $this->assertArrayNotHasKey('contentcount', $rows[0]);
        $this->assertSame('Wrans section', $rows[0]['body']);
    }

    public function test_most_recent_day_returns_latest_hdate_for_major_and_caches_result(): void {
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-05-01',
            'gid' => 'uk.org.publicwhip/debate/2024-05-01.1.0',
        ]);
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-05-03',
            'gid' => 'uk.org.publicwhip/debate/2024-05-03.1.0',
        ]);
        $this->insertHansardRaw([
            'major' => 3,
            'hdate' => '2024-05-09',
            'gid' => 'uk.org.publicwhip/wrans/2024-05-09.1.0',
        ]);

        $list = new DEBATELIST();

        $first = $list->most_recent_day();

        $expectedUrl = new URL('debates');
        $expectedUrl->insert(['d' => '2024-05-03']);

        $this->assertSame('2024-05-03', $first['hdate']);
        $this->assertSame(gmmktime(0, 0, 0, 5, 3, 2024), $first['timestamp']);
        $this->assertSame($expectedUrl->generate(), $first['listurl']);

        // Cached result should be returned on subsequent calls.
        $this->insertHansardRaw([
            'major' => 1,
            'hdate' => '2024-05-10',
            'gid' => 'uk.org.publicwhip/debate/2024-05-10.1.0',
        ]);

        $second = $list->most_recent_day();
        $this->assertSame($first, $second);
    }

}
