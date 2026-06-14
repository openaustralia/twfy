<?php

/**
 * @file
 * Integration tests for representative API endpoints.
 */

require_once __DIR__ . '/../../bootstrap.php';

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

require_once __DIR__ . '/../../../www/docs/api/api_functions.php';
require_once __DIR__ . '/../../../www/docs/api/api_getRepresentative.php';
require_once __DIR__ . '/../../../www/docs/api/api_getRepresentatives.php';

class RepresentativeApiIntegrationTest extends TransactionalTestCase {

    /**
     * Keep the current output mode and restore it in tearDown.
     */
    private array $originalGet = [];
    private int $fixtureMemberId;
    private int $fixturePersonId;
    private string $fixtureConstituency;
    private string $fixtureParty;

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
        $this->fixtureConstituency = 'TxRepSeat' . $suffix;
        $this->fixtureParty = 'Tx Rep Party ' . $suffix;

        parlDBQuery(
            'INSERT INTO member (member_id, person_id, house, title, first_name, last_name, constituency, party, entered_house, left_house, entered_reason, left_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $this->fixtureMemberId,
            $this->fixturePersonId,
            HOUSE::REPRESENTATIVES,
            '',
            'Tx',
            'Representative',
            $this->fixtureConstituency,
            $this->fixtureParty,
            '2010-01-01',
            '9999-12-31',
            'general_election',
            'still_in_office'
        );
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
        $this->assertFileDoesNotExist(__DIR__ . '/../../../www/docs/api/api_getMP.php');
        $this->assertFileDoesNotExist(__DIR__ . '/../../../www/docs/api/api_getMPs.php');
    }
}
