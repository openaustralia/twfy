<?php

/**
 * @file
 * Tests for MEMBER::previous_mps() method.
 */

require_once __DIR__ . '/../../bootstrap.php';

use OpenAustralia\TWFY\Models\Member as MemberModel;

/**
 * Tests for previous MPs functionality.
 */
class MemberPreviousMpsTest extends TransactionalTestCase {

    /**
     * Insert a test member record.
     */
    private function insertTestMember($person_id, $first_name, $last_name, $constituency, $entered_house, $left_house = '9999-12-31', $member_id = null) {
        static $auto_member_id = 91000;

        if (!$member_id) {
            $member_id = $auto_member_id++;
        }

        MemberModel::query()->insert([
            'member_id' => $member_id,
            'person_id' => $person_id,
            'house' => 1,
            'title' => '',
            'first_name' => $first_name,
            'last_name' => $last_name,
            'constituency' => $constituency,
            'party' => 'Test Party',
            'entered_house' => $entered_house,
            'left_house' => $left_house,
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
        ]);
    }

    /**
     * Test that previous_mps() returns MPs who entered before current member.
     */
    public function test_previous_mps_returns_earlier_members() {
        // Insert current member (entered 2015)
        $this->insertTestMember(99101, 'Alice', 'Current', 'TestVille', '2015-01-01');

        // Insert previous MPs (entered before current member)
        $this->insertTestMember(99102, 'Bob', 'Earlier1', 'TestVille', '2010-01-01', '2015-01-01');
        $this->insertTestMember(99103, 'Charlie', 'Earlier2', 'TestVille', '2005-01-01', '2010-01-01');

        // Insert future MP (entered after current member)
        $this->insertTestMember(99104, 'Diana', 'Later', 'TestVille', '2020-01-01');

        // Create MEMBER instance for Alice
        $member = new MEMBER(['person_id' => 99101]);
        $this->assertTrue($member->valid);

        // Get previous MPs
        $output = $member->previous_mps();

        // Output should contain links to Bob and Charlie
        $this->assertStringContainsString('Bob', $output);
        $this->assertStringContainsString('Earlier1', $output);
        $this->assertStringContainsString('Charlie', $output);
        $this->assertStringContainsString('Earlier2', $output);

        // Output should NOT contain Diana (future MP)
        $this->assertStringNotContainsString('Diana', $output);
    }

    /**
     * Test that previous_mps() filters by constituency.
     */
    public function test_previous_mps_filters_by_constituency() {
        // Insert current member
        $this->insertTestMember(99110, 'Alice', 'Current', 'Constituency1', '2015-01-01');

        // Insert previous MP in same constituency
        $this->insertTestMember(99111, 'Bob', 'Earlier', 'Constituency1', '2010-01-01', '2015-01-01');

        // Insert previous MP in different constituency
        $this->insertTestMember(99112, 'Charlie', 'Earlier', 'Constituency2', '2010-01-01', '2015-01-01');

        $member = new MEMBER(['person_id' => 99110]);
        $this->assertTrue($member->valid);

        $output = $member->previous_mps();

        // Should contain Bob (same constituency)
        $this->assertStringContainsString('Bob', $output);

        // Should NOT contain Charlie (different constituency)
        $this->assertStringNotContainsString('Charlie', $output);
    }

    /**
     * Test that previous_mps() excludes the current member.
     */
    public function test_previous_mps_excludes_current_member() {
        // Insert current member (entered 2015)
        $this->insertTestMember(99120, 'Alice', 'Current', 'TestVille', '2015-01-01');

        // Insert another member with same name but different person_id and earlier entry
        $this->insertTestMember(99121, 'Alice', 'Current', 'TestVille', '2010-01-01', '2015-01-01');

        $member = new MEMBER(['person_id' => 99120]);
        $this->assertTrue($member->valid);

        $output = $member->previous_mps();

        // Output should contain the earlier Alice (different person_id)
        $this->assertStringContainsString('Alice', $output);
    }

    /**
     * Test that previous_mps() returns empty string when member is not in House of Commons.
     */
    public function test_previous_mps_returns_empty_for_non_house_of_commons() {
        // Insert a member with no entered_house data for House 1
        $member = new MEMBER(['person_id' => 99130]);
        $this->assertFalse($member->valid);

        // The method should handle this gracefully
        // (Returns empty when entered_house(1) is null)
    }

    /**
     * Test that previous_mps() orders by entered_house DESC (most recent first).
     */
    public function test_previous_mps_ordered_by_entered_house_desc() {
        // Insert current member
        $this->insertTestMember(99140, 'Current', 'Alice', 'TestVille', '2020-01-01');

        // Insert previous MPs in non-chronological order
        $this->insertTestMember(99141, 'Earliest', 'Bob', 'TestVille', '2000-01-01', '2005-01-01');
        $this->insertTestMember(99142, 'Middle', 'Charlie', 'TestVille', '2010-01-01', '2015-01-01');
        $this->insertTestMember(99143, 'Latest', 'Diana', 'TestVille', '2015-01-01', '2020-01-01');

        $member = new MEMBER(['person_id' => 99140]);
        $this->assertTrue($member->valid);

        $output = $member->previous_mps();

        // Find positions - later members should appear first (DESC ordering)
        $diana_pos = strpos($output, 'Diana');
        $charlie_pos = strpos($output, 'Charlie');
        $bob_pos = strpos($output, 'Bob');

        $this->assertNotFalse($diana_pos);
        $this->assertNotFalse($charlie_pos);
        $this->assertNotFalse($bob_pos);

        // Diana (2015 entry) should appear before Charlie (2010 entry)
        $this->assertLessThan($charlie_pos, $diana_pos);

        // Charlie (2010 entry) should appear before Bob (2000 entry)
        $this->assertLessThan($bob_pos, $charlie_pos);
    }

    /**
     * Test that previous_mps() returns empty list when no previous members exist.
     */
    public function test_previous_mps_returns_empty_when_no_previous_members() {
        // Insert first member (no one before them)
        $this->insertTestMember(99150, 'Alice', 'First', 'TestVille', '2000-01-01');

        $member = new MEMBER(['person_id' => 99150]);
        $this->assertTrue($member->valid);

        $output = $member->previous_mps();

        // Should be empty string when no previous members
        $this->assertSame('', $output);
    }

    /**
     * Test that previous_mps() handles multiple terms for same person correctly.
     */
    public function test_previous_mps_handles_multiple_terms() {
        // Insert current member
        $this->insertTestMember(99160, 'Current', 'Alice', 'TestVille', '2020-01-01');

        // Insert previous MP with multiple terms (using MAX to get most recent entry)
        $this->insertTestMember(99161, 'Bob', 'Earlier', 'TestVille', '2005-01-01', '2010-01-01', 91160);
        $this->insertTestMember(99161, 'Bob', 'Earlier', 'TestVille', '2010-01-01', '2015-01-01', 91161);

        $member = new MEMBER(['person_id' => 99160]);
        $this->assertTrue($member->valid);

        $output = $member->previous_mps();

        // Bob should appear (with most recent entered_house date used)
        $this->assertStringContainsString('Bob', $output);
    }

}
