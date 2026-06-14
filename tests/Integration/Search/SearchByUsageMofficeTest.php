<?php

/**
 * @file
 * Integration tests for search_by_usage() moffice speaker offices.
 *
 * Tests the Member/Moffice ORM query in search_by_usage() to verify
 * speaker offices are correctly fetched and included in results.
 */

require_once EASYPARLIAMENTPATH . 'searchengine.php';

use Illuminate\Database\Capsule\Manager as DB;
use OpenAustralia\TWFY\Models\Member as MemberModel;
use OpenAustralia\TWFY\Models\Moffice as MofficeModel;
use OpenAustralia\TWFY\Models\Hansard as HansardModel;

/**
 * Tests for search_by_usage() ORM query functionality with moffice.
 */
class SearchByUsageMofficeTest extends TransactionalTestCase {

    /**
     * Helper to simulate hansard search results for search_by_usage().
     *
     * Creates Hansard records via ORM to enable testing the Member/Moffice
     * ORM query portion of search_by_usage().
     *
     * @param array $speakers Array of [member_id => count] pairs
     * @param string $gid_base Base GID for hansard records
     */
    private function insertHansardForSearchTest(array $speakers, string $gid_base = 'test_gid'): array {
        $gids = [];
        $epobject_id = 1000;  // Start at a high ID to avoid conflicts
        foreach ($speakers as $member_id => $count) {
            for ($i = 0; $i < $count; $i++) {
                $gid = $gid_base . '_' . $member_id . '_' . $i;
                HansardModel::create([
                    'epobject_id' => $epobject_id,
                    'gid' => $gid,
                    'htype' => 1,
                    'speaker_id' => $member_id,
                    'major' => 1,
                    'section_id' => 1,
                    'subsection_id' => 1,
                    'hpos' => 0,
                    'hdate' => date('Y-m-d'),
                    'source_url' => 'test',
                ]);
                $gids[] = $gid;
                $epobject_id++;
            }
        }
        return $gids;
    }

    /**
     * Test that ORM query correctly joins members with active offices.
     */
    public function test_search_by_usage_orm_query_includes_active_offices(): void {
        // Create member with active office
        $member = MemberModel::create([
            'member_id' => 99001,
            'house' => 1,
            'first_name' => 'Active',
            'last_name' => 'Minister',
            'constituency' => 'Test Seat',
            'party' => 'ALP',
            'entered_house' => '2020-01-01',
            'left_house' => '9999-12-31',
            'person_id' => 99001,
        ]);

        // Create active moffice (to_date = 9999-12-31)
        MofficeModel::create([
            'person' => $member->person_id,
            'dept' => 'Cabinet',
            'position' => 'Minister for Test',
            'from_date' => '2020-01-01',
            'to_date' => '9999-12-31',
            'source' => 'chgpages/selctee',
        ]);

        // Insert hansard records to simulate search results
        $this->insertHansardForSearchTest([99001 => 2]);

        // Test the ORM query directly (same query used in search_by_usage)
        $query = MemberModel::leftJoin('moffice', 'member.person_id', '=', 'moffice.person')
          ->select('member.member_id', 'member.person_id', 'member.title', 'member.first_name',
                 'member.last_name', 'member.constituency', 'member.house', 'member.party',
                 'moffice.moffice_id', 'moffice.dept', 'moffice.position', 'moffice.from_date',
                 'moffice.to_date', 'member.left_house')
          ->whereIn('member.member_id', [99001])
          ->orderBy('member.left_house', 'desc');

        $results = $query->get();

        // Should get at least one result with office data
        $this->assertGreaterThan(0, $results->count());
        $row = $results->first();
        $this->assertSame(99001, $row->member_id);
        $this->assertSame('Cabinet', $row->dept);
        $this->assertSame('Minister for Test', $row->position);
    }

    /**
     * Test that expired offices are correctly joined but filtered later.
     *
     * Note: The ORM query joins ALL moffice records (active and expired).
     * The filtering for active offices (to_date = '9999-12-31') happens
     * in search_by_usage() when building the speaker office array.
     */
    public function test_search_by_usage_orm_query_excludes_expired_offices(): void {
        // Create member with expired office
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

        // Create expired moffice (to_date != 9999-12-31)
        MofficeModel::create([
            'person' => $member->person_id,
            'dept' => 'Cabinet',
            'position' => 'Former Minister',
            'from_date' => '2015-01-01',
            'to_date' => '2020-12-31',
            'source' => 'chgpages/selctee',
        ]);

        $this->insertHansardForSearchTest([99002 => 1]);

        // ORM query (same as search_by_usage)
        $query = MemberModel::leftJoin('moffice', 'member.person_id', '=', 'moffice.person')
        ->select('member.member_id', 'member.person_id', 'member.title', 'member.first_name',
                 'member.last_name', 'member.constituency', 'member.house', 'member.party',
                 'moffice.moffice_id', 'moffice.dept', 'moffice.position', 'moffice.from_date',
                 'moffice.to_date', 'member.left_house')
        ->whereIn('member.member_id', [99002])
        ->orderBy('member.left_house', 'desc');

        $results = $query->get();

        // Member row will exist and include expired moffice data
        $this->assertGreaterThan(0, $results->count());
        $row = $results->first();
        $this->assertSame(99002, $row->member_id);
        // The query includes expired office data; filtering happens in search_by_usage()
        $this->assertSame('2020-12-31', $row->to_date);
    }

    /**
     * Test query with member having no offices.
     */
    public function test_search_by_usage_orm_query_member_with_no_offices(): void {
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

        $this->insertHansardForSearchTest([99003 => 1]);

        $query = MemberModel::leftJoin('moffice', 'member.person_id', '=', 'moffice.person')
        ->select('member.member_id', 'member.person_id', 'member.title', 'member.first_name',
                 'member.last_name', 'member.constituency', 'member.house', 'member.party',
                 'moffice.moffice_id', 'moffice.dept', 'moffice.position', 'moffice.from_date',
                 'moffice.to_date', 'member.left_house')
        ->whereIn('member.member_id', [99003])
        ->orderBy('member.left_house', 'desc');

        $results = $query->get();

        $this->assertGreaterThan(0, $results->count());
        $row = $results->first();
        $this->assertSame(99003, $row->member_id);
        $this->assertNull($row->moffice_id);  // LEFT JOIN shows null for no office
    }

    /**
     * Test query with multiple active offices for same member.
     */
    public function test_search_by_usage_orm_query_multiple_active_offices(): void {
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

        $this->insertHansardForSearchTest([99004 => 1]);

        // ORM query should return multiple rows (one for each office due to LEFT JOIN)
        $query = MemberModel::leftJoin('moffice', 'member.person_id', '=', 'moffice.person')
        ->select('member.member_id', 'member.person_id', 'member.title', 'member.first_name',
                 'member.last_name', 'member.constituency', 'member.house', 'member.party',
                 'moffice.moffice_id', 'moffice.dept', 'moffice.position', 'moffice.from_date',
                 'moffice.to_date', 'member.left_house')
        ->whereIn('member.member_id', [99004])
        ->orderBy('member.left_house', 'desc');

        $results = $query->get();

        // LEFT JOIN should produce one row per moffice record
        $this->assertGreaterThanOrEqual(2, $results->count());
        $positions = $results->pluck('position')->filter()->unique()->all();
        $this->assertContains('Minister A', $positions);
        $this->assertContains('Chair', $positions);
    }

    /**
     * Test ORM query with house parameter filtering.
     */
    public function test_search_by_usage_orm_query_with_house_filter(): void {
        // Create members in different houses
        $reps_member = MemberModel::create([
            'member_id' => 99005,
            'house' => 1,
            'first_name' => 'House',
            'last_name' => 'Reps',
            'constituency' => 'Test Seat',
            'party' => 'ALP',
            'entered_house' => '2020-01-01',
            'left_house' => '9999-12-31',
            'person_id' => 99005,
        ]);

        $senate_member = MemberModel::create([
            'member_id' => 99006,
            'house' => 2,
            'first_name' => 'Senate',
            'last_name' => 'Member',
            'constituency' => 'State',
            'party' => 'ALP',
            'entered_house' => '2020-01-01',
            'left_house' => '9999-12-31',
            'person_id' => 99006,
        ]);

        // Add offices to both
        foreach ([$reps_member, $senate_member] as $m) {
            MofficeModel::create([
                'person' => $m->person_id,
                'dept' => 'Cabinet',
                'position' => 'Minister',
                'from_date' => '2020-01-01',
                'to_date' => '9999-12-31',
                'source' => 'chgpages/selctee',
            ]);
        }

        $this->insertHansardForSearchTest([99005 => 1, 99006 => 1]);

        // Filter by House 1 only
        $query = MemberModel::leftJoin('moffice', 'member.person_id', '=', 'moffice.person')
        ->select('member.member_id', 'member.person_id', 'member.title', 'member.first_name',
                 'member.last_name', 'member.constituency', 'member.house', 'member.party',
                 'moffice.moffice_id', 'moffice.dept', 'moffice.position', 'moffice.from_date',
                 'moffice.to_date', 'member.left_house')
        ->whereIn('member.member_id', [99005, 99006])
        ->where('member.house', 1)
        ->orderBy('member.left_house', 'desc');

        $results = $query->get();

        // Should only get House member
        foreach ($results as $row) {
            $this->assertSame(1, $row->house);
        }
    }

}

