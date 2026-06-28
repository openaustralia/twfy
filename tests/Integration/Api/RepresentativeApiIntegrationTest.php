<?php

/**
 * @file
 * Integration tests for representative API endpoints.
 */

use OpenAustralia\TWFY\Models\Member as MemberModel;

if (!function_exists('member_full_name')) {

    function member_full_name($house, $title, $first_name, $last_name, $constituency) {
        $s = $first_name . ' ' . $last_name;
        if ($title) {
            $s = $title . ' ' . $s;
        }
        return $s;
    }

}

if (!function_exists('get_http_var')) {

    function get_http_var(string $varname): string {
        return $_GET[$varname] ?? '';
    }

}

require_once BASEDIR . '/docs/api/api_functions.php';
require_once BASEDIR . '/docs/api/api_getRepresentative.php';
require_once BASEDIR . '/docs/api/api_getRepresentatives.php';

/**
 *
 */
class RepresentativeApiIntegrationTest extends TransactionalTestCase {

    /**
     * Keep the current output mode and restore it in tearDown.
     */
    private array $originalGet = [];
    private int $fixtureMemberId;
    private int $fixturePersonId;
    private string $fixtureConstituency;
    private string $fixtureParty;
    private int $fixtureSenatorMemberId;
    private int $fixtureSenatorPersonId;
    private string $fixtureState;

    protected function setUp(): void {
        parent::setUp();
        $this->originalGet = $_GET;
        $_GET['output'] = 'php';
        if (!isset($GLOBALS['parties']) || !is_array($GLOBALS['parties'])) {
            $GLOBALS['parties'] = [];
        }

        $suffix = random_int(100000, 999999);
        $this->fixtureMemberId = 970000 + $suffix;
        $this->fixturePersonId = 980000 + $suffix;
        $this->fixtureConstituency = 'Canberra' . $suffix;
        $this->fixtureParty = 'ALP';
        $this->fixtureSenatorMemberId = 972000 + $suffix;
        $this->fixtureSenatorPersonId = 982000 + $suffix;
        $this->fixtureState = 'Victoria';

        MemberModel::create([
            'member_id' => $this->fixtureMemberId,
            'person_id' => $this->fixturePersonId,
            'house' => HOUSE::REPRESENTATIVES,
            'title' => '',
            'first_name' => 'Pat',
            'last_name' => 'Canberra',
            'constituency' => $this->fixtureConstituency,
            'party' => $this->fixtureParty,
            'entered_house' => '2010-01-01',
            'left_house' => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
        ]);

        MemberModel::create([
            'member_id' => $this->fixtureSenatorMemberId,
            'person_id' => $this->fixtureSenatorPersonId,
            'house' => HOUSE::SENATE,
            'title' => 'Senator',
            'first_name' => 'Jordan',
            'last_name' => 'Victorian',
            'constituency' => $this->fixtureState,
            'party' => 'GRN',
            'entered_house' => '2012-07-01',
            'left_house' => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
        ]);
    }

    protected function tearDown(): void {
        $_GET = $this->originalGet;
        parent::tearDown();
    }

    public function test_getRepresentative_division_returns_current_representative(): void {
        ob_start();
        api_getRepresentative_division($this->fixtureConstituency);
        $raw = ob_get_clean();

        $this->assertIsString($raw);
        $this->assertNotSame('', trim($raw));
        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertSame($this->fixturePersonId, (int) $decoded['person_id']);
        $this->assertSame((string) HOUSE::REPRESENTATIVES, (string) $decoded['house']);
    }

    public function test_getRepresentative_front_renders_expected_help_copy(): void {
        ob_start();
        api_getRepresentative_front();
        $raw = ob_get_clean();

        $this->assertIsString($raw);
        $this->assertStringContainsString('Fetch a particular member of the House of Representatives.', $raw);
        $this->assertStringContainsString('always_return', $raw);
    }

    public function test_getRepresentative_id_returns_error_for_unknown_person_id(): void {
        ob_start();
        api_getRepresentative_id(-99999999);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertSame('Unknown person ID', $decoded['error']);
    }

    public function test_getRepresentative_division_returns_error_for_unknown_constituency(): void {
        ob_start();
        api_getRepresentative_division('No Such Seat 999999');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertSame(
            'Unknown constituency, or no Representative for that constituency',
            $decoded['error']
        );
    }

    public function test__api_getRepresentative_row_sets_name_and_maps_party(): void {
        $GLOBALS['parties'][$this->fixtureParty] = 'Australian Labor Party';

        $row = MemberModel::where('person_id', $this->fixturePersonId)->first();
        $this->assertNotNull($row);

        $out = _api_getRepresentative_row($row->toArray());

        $this->assertSame('Pat Canberra', $out['full_name']);
        $this->assertSame($out['full_name'], $out['name']);
        $this->assertSame('Australian Labor Party', $out['party']);
    }

    public function test__api_getRepresentative_row_adds_current_office_rows(): void {
        \OpenAustralia\TWFY\Models\Moffice::create([
            'dept' => 'Cabinet',
            'position' => 'Minister for Testing',
            'from_date' => '2020-01-01',
            'to_date' => '9999-12-31',
            'person' => $this->fixturePersonId,
            'source' => 'fixture',
        ]);

        $row = MemberModel::where('person_id', $this->fixturePersonId)->first();
        $this->assertNotNull($row);

        $out = _api_getRepresentative_row($row->toArray());

        $this->assertArrayHasKey('office', $out);
        $this->assertNotEmpty($out['office']);
        $this->assertSame('Minister for Testing', $out['office'][0]['position']);
    }

    public function test__api_getRepresentative_constituency_returns_false_for_empty_input(): void {
        $this->assertFalse(_api_getRepresentative_constituency(''));
    }

    public function test__api_getRepresentative_constituency_finds_current_member(): void {
        $out = _api_getRepresentative_constituency($this->fixtureConstituency);

        $this->assertIsArray($out);
        $this->assertSame($this->fixturePersonId, (int) $out['person_id']);
    }

    public function test__api_getRepresentative_constituency_honours_always_return(): void {
        $suffix = random_int(100000, 999999);
        $personId = 990000 + $suffix;
        $constituency = 'VacantSeat' . $suffix;

        MemberModel::create([
            'member_id' => 995000 + $suffix,
            'person_id' => $personId,
            'house' => HOUSE::REPRESENTATIVES,
            'title' => '',
            'first_name' => 'Former',
            'last_name' => 'Member',
            'constituency' => $constituency,
            'party' => 'Past Party',
            'entered_house' => '2000-01-01',
            'left_house' => '2010-01-01',
            'entered_reason' => 'general_election',
            'left_reason' => 'general_election',
        ]);

        $_GET['always_return'] = '1';
        $out = _api_getRepresentative_constituency($constituency);

        $this->assertIsArray($out);
        $this->assertSame($personId, (int) $out['person_id']);
    }

    public function test__api_getMembers_output_returns_serialized_member_rows(): void {
        ob_start();
        _api_getMembers_output(MemberModel::query()->where('person_id', $this->fixturePersonId));
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame($this->fixturePersonId, (int) $decoded[0]['person_id']);
    }

    public function test__api_currentMembers_returns_only_current_for_house(): void {
        $suffix = random_int(100000, 999999);
        $pastPersonId = 993000 + $suffix;

        MemberModel::create([
            'member_id' => 997000 + $suffix,
            'person_id' => $pastPersonId,
            'house' => HOUSE::REPRESENTATIVES,
            'title' => '',
            'first_name' => 'Retired',
            'last_name' => 'Member',
            'constituency' => 'Wannon',
            'party' => 'LNP',
            'entered_house' => '1996-03-02',
            'left_house' => '1998-10-03',
            'entered_reason' => 'general_election',
            'left_reason' => 'general_election',
        ]);

        $current = _api_currentMembers(HOUSE::REPRESENTATIVES)->get();
        $personIds = array_map(static fn ($row) => (int) $row['person_id'], $current->toArray());

        $this->assertContains($this->fixturePersonId, $personIds);
        $this->assertNotContains($pastPersonId, $personIds);
    }

    public function test_getMembers_party_maps_canonical_name_to_short_code(): void {
        $GLOBALS['parties'] = [
            'ALP' => 'Australian Labor Party',
            'LNP' => 'Liberal National Party',
        ];

        ob_start();
        api_getMembers_party(HOUSE::REPRESENTATIVES, 'australian labor party');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);

        $personIds = array_map(fn ($r) => (int) $r['person_id'], $decoded);
        $this->assertContains($this->fixturePersonId, $personIds);
    }

    public function test_getMembers_search_matches_representative_name(): void {
        ob_start();
        api_getMembers_search(HOUSE::REPRESENTATIVES, 'Pat Canberra');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);

        $personIds = array_map(fn ($r) => (int) $r['person_id'], $decoded);
        $this->assertContains($this->fixturePersonId, $personIds);
    }

    public function test_getMembers_search_matches_state_for_senate(): void {
        ob_start();
        api_getMembers_search(HOUSE::SENATE, $this->fixtureState);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);

        $personIds = array_map(fn ($r) => (int) $r['person_id'], $decoded);
        $this->assertContains($this->fixtureSenatorPersonId, $personIds);
    }

    public function test_getMembers_date_returns_error_for_invalid_date(): void {
        ob_start();
        api_getMembers_date(HOUSE::REPRESENTATIVES, 'not-a-date');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertSame('Invalid date format', $decoded['error']);
    }

    public function test_getMembers_date_returns_members_for_valid_date(): void {
        ob_start();
        api_getMembers_date(HOUSE::REPRESENTATIVES, '1/1/2011');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertArrayNotHasKey('error', $decoded);
    }

    public function test_getMembers_without_date_returns_current_house_members(): void {
        ob_start();
        api_getMembers(HOUSE::REPRESENTATIVES);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);

        $personIds = array_map(fn ($r) => (int) $r['person_id'], $decoded);
        $this->assertContains($this->fixturePersonId, $personIds);
    }

    public function test_getMembers_with_date_filters_out_future_member(): void {
        $suffix = random_int(100000, 999999);
        $futurePersonId = 994000 + $suffix;

        MemberModel::create([
            'member_id' => 998000 + $suffix,
            'person_id' => $futurePersonId,
            'house' => HOUSE::REPRESENTATIVES,
            'title' => '',
            'first_name' => 'Future',
            'last_name' => 'Member',
            'constituency' => 'Bennelong',
            'party' => 'LIB',
            'entered_house' => '2025-05-01',
            'left_house' => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
        ]);

        ob_start();
        api_getMembers(HOUSE::REPRESENTATIVES, '2011-01-01');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);

        $personIds = array_map(fn ($r) => (int) $r['person_id'], $decoded);
        $this->assertContains($this->fixturePersonId, $personIds);
        $this->assertNotContains($futurePersonId, $personIds);
    }

    public function test_getRepresentatives_party_returns_representatives_only(): void {
        ob_start();
        api_getRepresentatives_party($this->fixtureParty);
        $raw = ob_get_clean();

        $this->assertIsString($raw);
        $this->assertNotSame('', trim($raw));
        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);

        $seenFixture = false;
        foreach ($decoded as $row) {
            $this->assertIsArray($row);
            $this->assertArrayHasKey('house', $row);
            $this->assertSame((string) HOUSE::REPRESENTATIVES, (string) $row['house']);
            if ((int) $row['person_id'] === $this->fixturePersonId) {
                $seenFixture = true;
            }
        }
        $this->assertTrue($seenFixture);
    }

    public function test_mp_named_api_files_are_removed(): void {
        $this->assertFileDoesNotExist(BASEDIR . '/docs/api/api_getMP.php');
        $this->assertFileDoesNotExist(BASEDIR . '/docs/api/api_getMPs.php');
    }

    public function test_getRepresentative_id_returns_member_by_person_id(): void {
        ob_start();
        api_getRepresentative_id($this->fixturePersonId);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);
        $this->assertSame($this->fixturePersonId, (int) $decoded[0]['person_id']);
    }

    public function test_getRepresentatives_search_finds_member_by_name(): void {
        ob_start();
        api_getRepresentatives_search('Pat Canberra');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);

        $personIds = array_map(fn($r) => (int) $r['person_id'], $decoded);
        $this->assertContains($this->fixturePersonId, $personIds);
    }

    public function test_getRepresentatives_returns_current_members(): void {
        ob_start();
        api_getRepresentatives();
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);

        $personIds = array_map(fn($r) => (int) $r['person_id'], $decoded);
        $this->assertContains($this->fixturePersonId, $personIds);
    }

    public function test_getMembers_state_finds_member_by_constituency(): void {
        ob_start();
        api_getMembers_state(HOUSE::REPRESENTATIVES, $this->fixtureConstituency);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);

        $personIds = array_map(fn($r) => (int) $r['person_id'], $decoded);
        $this->assertContains($this->fixturePersonId, $personIds);
    }

}
