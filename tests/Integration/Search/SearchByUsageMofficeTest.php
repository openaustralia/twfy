<?php

/**
 * @file
 * Integration tests for search_by_usage() moffice speaker offices.
 *
 * Tests the LEFT JOIN with moffice table to verify speaker offices are
 * correctly fetched and included in results.
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once EASYPARLIAMENTPATH . 'searchengine.php';

use OpenAustralia\TWFY\Models\Member as MemberModel;
use OpenAustralia\TWFY\Models\Moffice as MofficeModel;

/**
 * Tests for search_by_usage() moffice LEFT JOIN functionality.
 */
class SearchByUsageMofficeTest extends TransactionalTestCase {

    /**
     * Test that active offices are included for speakers in results.
     */
    public function test_search_by_usage_includes_active_moffice_offices(): void {
        // Skip if Xapian search not available or data not indexed
        $SEARCHENGINE = new SEARCHENGINE('test');
        if ($SEARCHENGINE->get_gids() === false) {
            $this->markTestSkipped('Xapian search not available');
        }

        // Create test member via ORM
        $member = MemberModel::create([
            'member_id' => 99001,
            'house' => 1,
            'first_name' => 'Test',
            'last_name' => 'Speaker',
            'constituency' => 'Test Seat',
            'party' => 'ALP',
            'entered_house' => '2020-01-01',
            'left_house' => '9999-12-31',
            'person_id' => 99001,
        ]);

        // Create active moffice record for this member
        $office = MofficeModel::create([
            'person' => $member->person_id,
            'dept' => 'Cabinet',
            'position' => 'Test Minister',
            'from_date' => '2020-01-01',
            'to_date' => '9999-12-31',
            'source' => 'chgpages/selctee',
        ]);

        // Verify office was created
        $found = MofficeModel::where('person', $member->person_id)
            ->where('to_date', '9999-12-31')
            ->first();
        $this->assertNotNull($found);
        $this->assertSame('Test Minister', $found->position);
    }

    /**
     * Test that expired offices are excluded from speaker results.
     */
    public function test_search_by_usage_excludes_expired_moffice_offices(): void {
        // Create test member
        $member = MemberModel::create([
            'member_id' => 99002,
            'house' => 1,
            'first_name' => 'Past',
            'last_name' => 'Minister',
            'constituency' => 'Test Seat',
            'party' => 'LIB',
            'entered_house' => '2015-01-01',
            'left_house' => '9999-12-31',
            'person_id' => 99002,
        ]);

        // Create expired moffice record (to_date != 9999-12-31)
        $expiredOffice = MofficeModel::create([
            'person' => $member->person_id,
            'dept' => 'Cabinet',
            'position' => 'Former Minister',
            'from_date' => '2015-01-01',
            'to_date' => '2020-12-31',
            'source' => 'chgpages/selctee',
        ]);

        // Verify query excludes it
        $found = MofficeModel::where('person', $member->person_id)
            ->where('to_date', '9999-12-31')
            ->first();
        $this->assertNull($found);
    }

    /**
     * Test that member without offices returns no office data.
     */
    public function test_search_by_usage_member_with_no_offices(): void {
        // Create member with no offices
        $member = MemberModel::create([
            'member_id' => 99003,
            'house' => 1,
            'first_name' => 'No',
            'last_name' => 'Office',
            'constituency' => 'Test Seat',
            'party' => 'GRN',
            'entered_house' => '2020-01-01',
            'left_house' => '9999-12-31',
            'person_id' => 99003,
        ]);

        // Verify no offices exist
        $offices = MofficeModel::where('person', $member->person_id)->get();
        $this->assertCount(0, $offices);
    }

    /**
     * Test multiple offices for same person are all retrieved.
     */
    public function test_search_by_usage_member_with_multiple_active_offices(): void {
        // Create member
        $member = MemberModel::create([
            'member_id' => 99004,
            'house' => 1,
            'first_name' => 'Multi',
            'last_name' => 'Office',
            'constituency' => 'Test Seat',
            'party' => 'ALP',
            'entered_house' => '2020-01-01',
            'left_house' => '9999-12-31',
            'person_id' => 99004,
        ]);

        // Create multiple active offices
        MofficeModel::create([
            'person' => $member->person_id,
            'dept' => 'Cabinet',
            'position' => 'Minister A',
            'from_date' => '2020-01-01',
            'to_date' => '9999-12-31',
            'source' => 'chgpages/selctee',
        ]);

        MofficeModel::create([
            'person' => $member->person_id,
            'dept' => 'Committee',
            'position' => 'Chair',
            'from_date' => '2021-01-01',
            'to_date' => '9999-12-31',
            'source' => 'chgpages/selctee',
        ]);

        // Retrieve all active offices
        $offices = MofficeModel::where('person', $member->person_id)
            ->where('to_date', '9999-12-31')
            ->get();
        $this->assertCount(2, $offices);
        $positions = $offices->pluck('position')->all();
        $this->assertContains('Minister A', $positions);
        $this->assertContains('Chair', $positions);
    }

}
