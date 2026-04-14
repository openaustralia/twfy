<?php

require_once BASEDIR . '/docs/api/api_getMP.php';

use support\DatabaseIntegrationTestCase;

/**
 * Tests for api_getMP.php
 *
 * Fixture data: member_id 1 = Tony Abbott, person_id 10001, constituency Warringah.
 * member_id 567 = Darren Chester, person_id 10703, constituency Gippsland, still_in_office.
 */
class GetMPTest extends DatabaseIntegrationTestCase
{
    protected function createTemporaryTables(): void
    {
        $this->createTemporaryTablesFromSchema('member', 'moffice');
    }

    protected function dropTemporaryTables(): void
    {
        $this->dropTemporaryTablesIfExists('member', 'moffice');
    }

    protected function setUpData(): void
    {
        $this->loadFixtures('member', 'moffice');
    }

    private function captureOutput(callable $fn): array
    {
        ob_start();
        try {
            $fn();
        } finally {
            $output = ob_get_clean();
        }
        return json_decode($output, true);
    }

    // -------------------------------------------------------------------------
    // api_getMP_id
    // -------------------------------------------------------------------------

    public function test_api_getMP_id_returns_member_for_known_person(): void
    {
        $data = $this->captureOutput(fn() => api_getMP_id(10001));

        $this->assertIsArray($data);
        $this->assertSame('Abbott', $data[0]['last_name']);
        $this->assertSame('Tony', $data[0]['first_name']);
        $this->assertSame('Warringah', $data[0]['constituency']);
    }

    public function test_api_getMP_id_returns_full_name(): void
    {
        $data = $this->captureOutput(fn() => api_getMP_id(10001));

        $this->assertSame('Tony Abbott', $data[0]['full_name']);
    }

    public function test_api_getMP_id_returns_error_for_unknown_person(): void
    {
        $data = $this->captureOutput(fn() => api_getMP_id(999999));

        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Unknown person ID', $data['error']);
    }

    public function test_api_getMP_id_returns_multiple_rows_for_person_with_multiple_terms(): void
    {
        // Harry Jenkins (person_id 10335) has two member rows — ALP and SPK.
        $data = $this->captureOutput(fn() => api_getMP_id(10335));

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertSame('Jenkins', $data[0]['last_name']);
    }

    public function test_api_getMP_id_includes_office_key(): void
    {
        $data = $this->captureOutput(fn() => api_getMP_id(10001));

        // office key should exist (may be empty array if no current offices in fixture).
        $this->assertArrayHasKey('office', $data[0]);
    }

    // -------------------------------------------------------------------------
    // _api_getMP_constituency
    // -------------------------------------------------------------------------

    public function test_internal_constituency_returns_row_for_current_mp(): void
    {
        // Darren Chester is still_in_office for Gippsland.
        $row = _api_getMP_constituency('Gippsland');

        $this->assertIsArray($row);
        $this->assertSame('Chester', $row['last_name']);
    }

    public function test_internal_constituency_returns_false_for_empty_string(): void
    {
        $this->assertFalse(_api_getMP_constituency(''));
    }

    public function test_internal_constituency_returns_false_for_unknown(): void
    {
        $this->assertFalse(_api_getMP_constituency('Neverland'));
    }

    public function test_internal_constituency_returns_false_for_former_mp_constituency(): void
    {
        // Warringah — Abbott left_reason is 'unknown', not 'still_in_office'.
        $this->assertFalse(_api_getMP_constituency('Warringah'));
    }

    // -------------------------------------------------------------------------
    // api_getMP_constituency
    // -------------------------------------------------------------------------

    public function test_api_getMP_constituency_returns_current_mp(): void
    {
        $data = $this->captureOutput(fn() => api_getMP_constituency('Gippsland'));

        $this->assertIsArray($data);
        $this->assertSame('Chester', $data['last_name']);
    }

    public function test_api_getMP_constituency_returns_error_for_unknown(): void
    {
        $data = $this->captureOutput(fn() => api_getMP_constituency('Neverland'));

        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Unknown constituency, or no MP for that constituency', $data['error']);
    }
}
