<?php

/**
 * @file
 * Unit tests for HANSARDLIST methods that don't require a database connection.
 */

use PHPUnit\Framework\TestCase;

require_once INCLUDESPATH . 'utility.php';
require_once INCLUDESPATH . 'data.php';
require_once INCLUDESPATH . 'url.php';
require_once INCLUDESPATH . 'easyparliament/hansardlist.php';

if (!isset($GLOBALS['DATA'])) {
    $GLOBALS['DATA'] = new DATA();
}

/**
 * Minimal PAGE stub that captures error messages.
 */
class FakePageForHansardTest {
    public array $errors = [];

    public function error_message(string $msg): void {
        $this->errors[] = $msg;
    }

    public function set_hansard_headings($info): void {
    }
}

class HANSARDLISTRecentStub extends HANSARDLIST {

    public function _get_data_by_recent($args) {
        return [
            'info' => ['text' => 'Recent dates'],
            'rows' => [],
        ];
    }

}

class HANSARDLISTMostRecentDayStub extends HANSARDLIST {

    public ?string $fakeMostRecentDate = null;
    public int $fetchMostRecentDateCalls = 0;
    public $listpage = 'debates';

    protected function fetchMostRecentHansardDate(): ?string {
        $this->fetchMostRecentDateCalls++;
        return $this->fakeMostRecentDate;
    }

}

class HANSARDLISTGetSectionStub extends HANSARDLIST {

    public array $fakeHansardData = [];
    public ?array $lastInput = null;

    public function callGetSection(array $itemdata): array {
        return $this->getSection($itemdata);
    }

    public function getHandsardData($input) {
        $this->lastInput = $input;
        return $this->fakeHansardData;
    }

}

class HansardListTest extends TestCase {

    private $origPage;
    private $origSearchengine;
    private $origSearchlog;

    protected function setUp(): void {
        global $PAGE, $SEARCHENGINE, $SEARCHLOG;
        $this->origPage = $PAGE ?? null;
        $this->origSearchengine = $SEARCHENGINE ?? null;
        $this->origSearchlog = $SEARCHLOG ?? null;
        $PAGE = new FakePageForHansardTest();
    }

    protected function tearDown(): void {
        global $PAGE, $SEARCHENGINE, $SEARCHLOG;
        $this->clearPageGlobal();
        $PAGE = $this->origPage;
        $SEARCHENGINE = $this->origSearchengine;
        $SEARCHLOG = $this->origSearchlog;
    }

    private function clearPageGlobal(): void {
        global $PAGE;
        $PAGE = null;
    }

    // --- _validate_date ---

    public function test_validate_date_accepts_valid_date(): void {
        $list = new HANSARDLIST();
        $result = $list->_validate_date(['date' => '2023-06-15']);
        $this->assertSame('2023-06-15', $result);
    }

    public function test_validate_date_pads_single_digit_month_and_day(): void {
        $list = new HANSARDLIST();
        $result = $list->_validate_date(['date' => '2023-1-5']);
        $this->assertSame('2023-01-05', $result);
    }

    public function test_validate_date_returns_false_when_no_date_key(): void {
        $list = new HANSARDLIST();
        $result = $list->_validate_date([]);
        $this->assertFalse($result);
    }

    public function test_validate_date_returns_false_for_invalid_format(): void {
        $list = new HANSARDLIST();
        $result = $list->_validate_date(['date' => 'not-a-date']);
        $this->assertFalse($result);
    }

    public function test_validate_date_returns_false_for_invalid_calendar_date(): void {
        $list = new HANSARDLIST();
        // Feb 30 doesn't exist.
        $result = $list->_validate_date(['date' => '2023-02-30']);
        $this->assertFalse($result);
    }

    public function test_validate_date_returns_false_for_partial_date(): void {
        $list = new HANSARDLIST();
        $result = $list->_validate_date(['date' => '2023-12']);
        $this->assertFalse($result);
    }

    public function test_validate_date_sets_error_message_on_failure(): void {
        global $PAGE;
        /** @var FakePageForHansardTest $PAGE */
        $list = new HANSARDLIST();
        $list->_validate_date(['date' => 'bad']);
        $this->assertNotEmpty($PAGE->errors);
    }

    // --- display() view validation ---

    public function test_display_returns_false_for_invalid_view(): void {
        global $PAGE;
        /** @var FakePageForHansardTest $PAGE */
        $list = new HANSARDLIST();
        $result = $list->display('nonexistent_view');
        $this->assertFalse($result);
        $this->assertNotEmpty($PAGE->errors);
    }

    public function test_display_does_not_fatal_when_page_is_null_and_info_exists(): void {
        $this->clearPageGlobal();
        $list = new HANSARDLISTRecentStub();

        $result = $list->display('recent', [], 'none');

        $this->assertIsArray($result);
        $this->assertSame('Recent dates', $result['info']['text']);
    }

    // --- Child class properties ---

    public function test_debatelist_major_is_1(): void {
        $list = new DEBATELIST();
        $this->assertSame(1, $list->major);
    }

    public function test_debatelist_gidprefix(): void {
        $list = new DEBATELIST();
        $this->assertSame('uk.org.publicwhip/debate/', $list->gidprefix);
    }

    public function test_debatelist_listpage(): void {
        $list = new DEBATELIST();
        $this->assertSame('debates', $list->listpage);
    }

    public function test_wranslist_major_is_3(): void {
        $list = new WRANSLIST();
        $this->assertSame(3, $list->major);
    }

    public function test_wranslist_gidprefix(): void {
        $list = new WRANSLIST();
        $this->assertSame('uk.org.publicwhip/wrans/', $list->gidprefix);
    }

    public function test_wmslist_gidprefix(): void {
        $list = new WMSLIST();
        $this->assertSame('uk.org.publicwhip/wms/', $list->gidprefix);
    }

    public function test_whalllist_major_is_2(): void {
        $list = new WHALLLIST();
        $this->assertSame(2, $list->major);
    }

    public function test_whalllist_gidprefix(): void {
        $list = new WHALLLIST();
        $this->assertSame('uk.org.publicwhip/debate/westminhall/', $list->gidprefix);
    }

    public function test_nilist_major_is_5(): void {
        $list = new NILIST();
        $this->assertSame(5, $list->major);
    }

    public function test_nilist_gidprefix(): void {
        $list = new NILIST();
        $this->assertSame('uk.org.publicwhip/debate/ni/', $list->gidprefix);
    }

    public function test_splist_major_is_7(): void {
        $list = new SPLIST();
        $this->assertSame(7, $list->major);
    }

    public function test_splist_gidprefix(): void {
        $list = new SPLIST();
        $this->assertSame('uk.org.publicwhip/debate/spor/', $list->gidprefix);
    }

    public function test_spwranslist_major_is_8(): void {
        $list = new SPWRANSLIST();
        $this->assertSame(8, $list->major);
    }

    public function test_spwranslist_gidprefix(): void {
        $list = new SPWRANSLIST();
        $this->assertSame('uk.org.publicwhip/wrans/spwa/', $list->gidprefix);
    }

    public function test_lordsdebatelist_major_is_101(): void {
        $list = new LORDSDEBATELIST();
        $this->assertSame(101, $list->major);
    }

    public function test_lordsdebatelist_gidprefix(): void {
        $list = new LORDSDEBATELIST();
        $this->assertSame('uk.org.publicwhip/lords/', $list->gidprefix);
    }

    // --- HANSARDLIST accessor defaults ---

    public function test_htype_returns_null_initially(): void {
        $list = new HANSARDLIST();
        $this->assertNull($list->htype());
    }

    public function test_epobject_id_returns_null_initially(): void {
        $list = new HANSARDLIST();
        $this->assertNull($list->epobject_id());
    }

    public function test_gid_returns_null_initially(): void {
        $list = new HANSARDLIST();
        $this->assertNull($list->gid());
    }

    // --- _get_listurl with cached gid ---

    public function test_get_listurl_for_section_htype_10(): void {
        $list = new DEBATELIST();
        $id_data = [
            'major' => 1,
            'htype' => '10',
            'gid' => '2023-06-15.1.0',
            'section_id' => 100,
            'subsection_id' => 101,
        ];
        $url = $list->_get_listurl($id_data);
        $this->assertIsString($url);
        // Should contain the gid in the URL.
        $this->assertStringContainsString('2023-06-15.1.0', $url);
    }

    public function test_get_listurl_for_speech_uses_cached_parent_gid(): void {
        $list = new DEBATELIST();
        // Pre-cache the parent gid.
        $list->epobjectid_to_gid[200] = '2023-06-15.5.0';
        $id_data = [
            'major' => 1,
            'htype' => '12',
            'gid' => '2023-06-15.5.3',
            'section_id' => 100,
            'subsection_id' => 200,
        ];
        $url = $list->_get_listurl($id_data);
        $this->assertIsString($url);
        // Should use the cached parent gid in the URL.
        $this->assertStringContainsString('2023-06-15.5.0', $url);
        // Should have the item's gid as an anchor.
        $this->assertStringContainsString('#g5.3', $url);
    }

    public function test_get_data_by_search_returns_empty_rows_when_count_is_zero(): void {
        global $SEARCHENGINE, $SEARCHLOG;

        $SEARCHENGINE = new class {
            public function query_description_long(): string {
                return 'stub description';
            }

            public function run_count(): int {
                return 0;
            }
        };

        $SEARCHLOG = new class {
            public array $calls = [];

            public function add(array $data): void {
                $this->calls[] = $data;
            }
        };

        $list = new DEBATELIST();
        $data = $list->_get_data_by_search(['s' => 'housing']);

        $this->assertSame('housing', $data['info']['s']);
        $this->assertSame(0, $data['info']['total_results']);
        $this->assertSame([], $data['rows']);
        $this->assertCount(1, $SEARCHLOG->calls);
        $this->assertSame('housing', $SEARCHLOG->calls[0]['query']);
        $this->assertSame(1, $SEARCHLOG->calls[0]['page']);
        $this->assertSame(0, $SEARCHLOG->calls[0]['hits']);
    }

    public function test_most_recent_day_returns_empty_array_when_no_date_found(): void {
        $list = new HANSARDLISTMostRecentDayStub();

        $result = $list->most_recent_day();

        $this->assertSame([], $result);
        $this->assertSame(1, $list->fetchMostRecentDateCalls);
    }

    public function test_most_recent_day_returns_computed_data_and_caches_result(): void {
        $list = new HANSARDLISTMostRecentDayStub();
        $list->fakeMostRecentDate = '2024-02-29';

        $first = $list->most_recent_day();
        $second = $list->most_recent_day();

        $expectedTimestamp = gmmktime(0, 0, 0, 2, 29, 2024);
        $expectedUrl = new URL('debates');
        $expectedUrl->insert(['d' => '2024-02-29']);

        $this->assertSame('2024-02-29', $first['hdate']);
        $this->assertSame($expectedTimestamp, $first['timestamp']);
        $this->assertSame($expectedUrl->generate(), $first['listurl']);
        $this->assertSame($first, $second);
        $this->assertSame(1, $list->fetchMostRecentDateCalls);
    }

    public function test_get_section_returns_itemdata_when_item_is_section(): void {
        $list = new HANSARDLISTGetSectionStub();
        $itemdata = [
            'htype' => '10',
            'epobject_id' => 123,
            'body' => 'Section heading',
            'section_id' => 123,
        ];

        $result = $list->callGetSection($itemdata);

        $this->assertSame($itemdata, $result);
        $this->assertNull($list->lastInput);
    }

    public function test_get_section_fetches_parent_section_for_non_section_item(): void {
        $list = new HANSARDLISTGetSectionStub();
        $list->fakeHansardData = [[
            'epobject_id' => 50,
            'body' => 'Parent section',
        ]];

        $itemdata = [
            'htype' => '12',
            'section_id' => 50,
        ];

        $result = $list->callGetSection($itemdata);

        $this->assertSame([
            'amount' => ['body' => true],
            'where' => ['hansard.epobject_id=' => 50],
        ], $list->lastInput);
        $this->assertSame($list->fakeHansardData[0], $result);
    }

}
