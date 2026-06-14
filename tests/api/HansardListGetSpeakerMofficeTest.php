<?php

/**
 * @file
 * Integration tests for HANSARDLIST::_get_speaker() after migration to Moffice model.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/easyparliament/hansardlist.php';

use OpenAustralia\TWFY\Models\Member as MemberModel;
use OpenAustralia\TWFY\Models\Moffice as MofficeModel;

if (!function_exists('prettify_office')) {
    function prettify_office($pos, $dept) {
        if ($pos && $dept) {
            return "$pos, $dept";
        }
        return $pos ?: "Member, $dept";
    }
}

/**
 *
 */
class HansardListGetSpeakerMofficeTest extends TransactionalTestCase {

    private int $memberId;
    private int $personId;
    private int $mofficeId;
    private string $hdate = '2023-06-01';

    protected function setUp(): void {
        parent::setUp();

        $suffix = random_int(100000, 999999);
        $this->memberId = 973000 + $suffix;
        $this->personId = 983000 + $suffix;
        $this->mofficeId = 993000 + $suffix;

        // _get_speaker() still uses parlDBQuery for the member lookup, which
        // runs on the mysqli connection. To keep fixture data visible to it we
        // insert the member row through parlDBQuery (same connection). Moffice
        // is queried via MofficeModel (Eloquent), so that insert goes through
        // the ORM as expected.
        parlDBQuery(
            'INSERT INTO member
             (member_id, person_id, house, title, first_name, last_name, constituency, party,
              entered_house, left_house, entered_reason, left_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $this->memberId,
            $this->personId,
            HOUSE::REPRESENTATIVES,
            '',
            'Speaker',
            'TestPerson',
            'TxSpeakerSeat' . $suffix,
            'Test Party',
            '2010-01-01',
            '9999-12-31',
            'general_election',
            'still_in_office'
        );

        // Office active during $hdate.
        MofficeModel::query()->insert([
            'moffice_id' => $this->mofficeId,
            'dept'       => 'Tx Speaker Dept',
            'position'   => 'Tx Speaker Position',
            'from_date'  => '2020-01-01',
            'to_date'    => '9999-12-31',
            'person'     => $this->personId,
            'source'     => '',
        ]);
    }

    private function makeHansardList(): HANSARDLIST {
        if (!defined('WEBPATH')) {
            define('WEBPATH', '/');
        }
        if (!class_exists('URL')) {
            eval('class URL {
                private $page;
                public function __construct($page) { $this->page = $page; }
                public function insert(array $params): void {}
                public function generate($type = "") { return "/" . $this->page . "/"; }
            }');
        }
        if (!isset($GLOBALS['parties']) || !is_array($GLOBALS['parties'])) {
            $GLOBALS['parties'] = [];
        }
        return new HANSARDLIST();
    }

    public function test_get_speaker_includes_active_office(): void {
        $list = $this->makeHansardList();

        $speaker = $list->_get_speaker($this->memberId, $this->hdate);

        $this->assertIsArray($speaker);
        $this->assertSame((string) $this->memberId, (string) $speaker['member_id']);
        $this->assertArrayHasKey('office', $speaker);

        $found = false;
        foreach ($speaker['office'] as $office) {
            if ($office['position'] === 'Tx Speaker Position') {
                $found = true;
                $this->assertSame('Tx Speaker Dept', $office['dept']);
                $this->assertStringContainsString('Tx Speaker Position', $office['pretty']);
            }
        }
        $this->assertTrue($found, 'Expected office entry not found in speaker data');
    }

    public function test_get_speaker_excludes_future_office(): void {
        $suffix = random_int(100000, 999999);

        MofficeModel::query()->insert([
            'moffice_id' => 994000 + $suffix,
            'dept'       => 'Future Dept',
            'position'   => 'Future Position',
            'from_date'  => '2099-01-01',
            'to_date'    => '9999-12-31',
            'person'     => $this->personId,
            'source'     => '',
        ]);

        $list = $this->makeHansardList();
        $speaker = $list->_get_speaker($this->memberId, $this->hdate);

        foreach ($speaker['office'] ?? [] as $office) {
            $this->assertNotSame('Future Position', $office['position']);
        }
    }

    public function test_get_speaker_returns_empty_for_unknown_member(): void {
        $list = $this->makeHansardList();
        $result = $list->_get_speaker(999999999, $this->hdate);
        $this->assertSame([], $result);
    }

    public function test_get_speaker_caches_result(): void {
        $list = $this->makeHansardList();

        $first = $list->_get_speaker($this->memberId, $this->hdate);
        $second = $list->_get_speaker($this->memberId, $this->hdate);

        $this->assertSame($first, $second);
    }

}
