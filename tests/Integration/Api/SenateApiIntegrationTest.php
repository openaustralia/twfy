<?php

/**
 * @file
 * Integration tests for Senate API endpoints.
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
require_once BASEDIR . '/docs/api/api_getSenate.php';
require_once BASEDIR . '/docs/api/api_getSenator.php';
require_once BASEDIR . '/docs/api/api_getSenators.php';

/**
 *
 */
class SenateApiIntegrationTest extends TransactionalTestCase {

    private array $originalGet = [];
    private int $fixtureSenatorMemberId;
    private int $fixtureSenatorPersonId;
    private string $fixtureState;
    private string $fixtureParty;
    private int $fixtureRepresentativePersonId;

    protected function setUp(): void {
        parent::setUp();
        $this->originalGet = $_GET;
        $_GET['output'] = 'php';
        if (!isset($GLOBALS['parties']) || !is_array($GLOBALS['parties'])) {
            $GLOBALS['parties'] = [];
        }

        $suffix = random_int(100000, 999999);
        $this->fixtureSenatorMemberId = 973000 + $suffix;
        $this->fixtureSenatorPersonId = 983000 + $suffix;
        $this->fixtureState = 'Tasmania' . $suffix;
        $this->fixtureParty = 'GRN';
        $this->fixtureRepresentativePersonId = 984000 + $suffix;

        MemberModel::create([
            'member_id' => $this->fixtureSenatorMemberId,
            'person_id' => $this->fixtureSenatorPersonId,
            'house' => HOUSE::SENATE,
            'title' => 'Senator',
            'first_name' => 'Casey',
            'last_name' => 'Tasmanian',
            'constituency' => $this->fixtureState,
            'party' => $this->fixtureParty,
            'entered_house' => '2012-07-01',
            'left_house' => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
        ]);

        MemberModel::create([
            'member_id' => 974000 + $suffix,
            'person_id' => $this->fixtureRepresentativePersonId,
            'house' => HOUSE::REPRESENTATIVES,
            'title' => '',
            'first_name' => 'Robin',
            'last_name' => 'Canberra',
            'constituency' => 'Canberra' . $suffix,
            'party' => $this->fixtureParty,
            'entered_house' => '2010-01-01',
            'left_house' => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
        ]);
    }

    protected function tearDown(): void {
        $_GET = $this->originalGet;
        parent::tearDown();
    }

    public function test_getSenate_front_renders_expected_help_copy(): void {
        ob_start();
        api_getSenate_front();
        $raw = ob_get_clean();

        $this->assertIsString($raw);
        $this->assertStringContainsString('Fetch a particular Senator.', $raw);
        $this->assertStringContainsString('id (required)', $raw);
    }

    public function test_getSenator_front_renders_expected_help_copy(): void {
        ob_start();
        api_getSenator_front();
        $raw = ob_get_clean();

        $this->assertIsString($raw);
        $this->assertStringContainsString('Fetch a particular Senator.', $raw);
    }

    public function test_getSenators_front_renders_expected_help_copy(): void {
        ob_start();
        api_getSenators_front();
        $raw = ob_get_clean();

        $this->assertIsString($raw);
        $this->assertStringContainsString('Fetch a list of Senators.', $raw);
        $this->assertStringContainsString('state (optional)', $raw);
    }

    public function test__api_getSenate_row_sets_name_and_maps_party(): void {
        $GLOBALS['parties'][$this->fixtureParty] = 'Australian Greens';

        $row = MemberModel::where('person_id', $this->fixtureSenatorPersonId)->first();
        $this->assertNotNull($row);

        $out = _api_getSenate_row($row->toArray());

        $this->assertSame('Senator Casey Tasmanian', $out['full_name']);
        $this->assertSame('Australian Greens', $out['party']);
    }

    public function test__api_getSenate_output_returns_serialized_rows(): void {
        ob_start();
        _api_getSenate_output(MemberModel::query()->where('person_id', $this->fixtureSenatorPersonId));
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame($this->fixtureSenatorPersonId, (int) $decoded[0]['person_id']);
    }

    public function test_getSenate_id_returns_senator_by_person_id(): void {
        ob_start();
        api_getSenate_id($this->fixtureSenatorPersonId);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame($this->fixtureSenatorPersonId, (int) $decoded[0]['person_id']);
    }

    public function test_getSenator_id_delegates_to_senate_lookup(): void {
        ob_start();
        api_getSenator_id($this->fixtureSenatorPersonId);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame($this->fixtureSenatorPersonId, (int) $decoded[0]['person_id']);
    }

    public function test_getSenate_id_returns_history_rows_in_descending_left_house_order(): void {
        $suffix = random_int(100000, 999999);

        MemberModel::create([
            'member_id' => 975000 + $suffix,
            'person_id' => $this->fixtureSenatorPersonId,
            'house' => HOUSE::SENATE,
            'title' => 'Senator',
            'first_name' => 'Casey',
            'last_name' => 'Tasmanian',
            'constituency' => 'Old State' . $suffix,
            'party' => $this->fixtureParty,
            'entered_house' => '2005-07-01',
            'left_house' => '2011-06-30',
            'entered_reason' => 'general_election',
            'left_reason' => 'general_election',
        ]);

        ob_start();
        api_getSenate_id($this->fixtureSenatorPersonId);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertStringStartsWith('9999-12-31', $decoded[0]['left_house']);
        $this->assertStringStartsWith('2011-06-30', $decoded[1]['left_house']);
    }

    public function test_getSenate_id_returns_error_for_unknown_person_id(): void {
        ob_start();
        api_getSenate_id(-99999999);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertSame('Unknown person ID', $decoded['error']);
    }

    public function test_getSenate_id_rejects_representative_person_id(): void {
        ob_start();
        api_getSenate_id($this->fixtureRepresentativePersonId);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertSame('Unknown person ID', $decoded['error']);
    }

    public function test_getSenators_party_returns_senators_only(): void {
        $GLOBALS['parties'][$this->fixtureParty] = 'Australian Greens';

        ob_start();
        api_getSenators_party('australian greens');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);

        $personIds = array_map(static fn ($row) => (int) $row['person_id'], $decoded);
        $this->assertContains($this->fixtureSenatorPersonId, $personIds);
        $this->assertNotContains($this->fixtureRepresentativePersonId, $personIds);
    }

    public function test_getSenators_state_returns_matching_senator(): void {
        ob_start();
        api_getSenators_state($this->fixtureState);
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);

        $personIds = array_map(static fn ($row) => (int) $row['person_id'], $decoded);
        $this->assertContains($this->fixtureSenatorPersonId, $personIds);
    }

    public function test_getSenators_search_finds_senator_by_name(): void {
        ob_start();
        api_getSenators_search('Casey Tasmanian');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);

        $personIds = array_map(static fn ($row) => (int) $row['person_id'], $decoded);
        $this->assertContains($this->fixtureSenatorPersonId, $personIds);
    }

    public function test_getSenators_date_returns_error_for_invalid_date(): void {
        ob_start();
        api_getSenators_date('not-a-date');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $this->assertSame('Invalid date format', $decoded['error']);
    }

    public function test_getSenators_date_returns_members_for_valid_date(): void {
        ob_start();
        api_getSenators_date('1/1/2020');
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);
        $personIds = array_map(static fn ($row) => (int) $row['person_id'], $decoded);
        $this->assertContains($this->fixtureSenatorPersonId, $personIds);
    }

    public function test_getSenators_returns_current_senators(): void {
        ob_start();
        api_getSenators();
        $raw = ob_get_clean();

        $decoded = unserialize($raw, ['allowed_classes' => false]);
        $this->assertIsArray($decoded);

        $personIds = array_map(static fn ($row) => (int) $row['person_id'], $decoded);
        $this->assertContains($this->fixtureSenatorPersonId, $personIds);
        $this->assertNotContains($this->fixtureRepresentativePersonId, $personIds);
    }

}
