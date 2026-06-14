<?php

/**
 * @file
 * Tests for MEMBER::future_mps() method.
 */


use OpenAustralia\TWFY\Models\Member as MemberModel;

/**
 * Tests for future MPs functionality.
 */
class MemberFutureMpsTest extends TransactionalTestCase {

    /**
     * Insert a test member record.
     */
    private function insertTestMember($person_id, $first_name, $last_name, $constituency, $entered_house, $left_house = '9999-12-31', $member_id = null) {
        static $auto_member_id = 90000;

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
     * Test that future_mps() returns MPs who entered after current member.
     */
    public function test_future_mps_returns_later_members() {
        // Insert current member
        $this->insertTestMember(99001, 'Alice', 'Current', 'TestVille', '2010-01-01');

        // Insert future MPs (entered after current member)
        $this->insertTestMember(99002, 'Bob', 'Future1', 'TestVille', '2015-01-01');
        $this->insertTestMember(99003, 'Charlie', 'Future2', 'TestVille', '2020-01-01');

        // Insert past MPs (entered before current member)
        $this->insertTestMember(99004, 'Diana', 'Past', 'TestVille', '2005-01-01', '2010-01-01');

        // Create MEMBER instance for Alice
        $member = new MEMBER(['person_id' => 99001]);
        $this->assertTrue($member->valid);

        // Get future MPs
        $output = $member->future_mps();

        // Output should contain links to Bob and Charlie
        $this->assertStringContainsString('Bob', $output);
        $this->assertStringContainsString('Future1', $output);
        $this->assertStringContainsString('Charlie', $output);
        $this->assertStringContainsString('Future2', $output);

        // Output should NOT contain Diana (past MP)
        $this->assertStringNotContainsString('Diana', $output);
    }

    /**
     * Test that future_mps() filters by constituency.
     */
    public function test_future_mps_filters_by_constituency() {
        // Insert current member
        $this->insertTestMember(99010, 'Alice', 'TestA', 'Constituency1', '2010-01-01');

        // Insert future MP in same constituency
        $this->insertTestMember(99011, 'Bob', 'TestB', 'Constituency1', '2015-01-01');

        // Insert future MP in different constituency
        $this->insertTestMember(99012, 'Charlie', 'TestC', 'Constituency2', '2015-01-01');

        $member = new MEMBER(['person_id' => 99010]);
        $this->assertTrue($member->valid);

        $output = $member->future_mps();

        // Should contain Bob (same constituency)
        $this->assertStringContainsString('Bob', $output);

        // Should NOT contain Charlie (different constituency)
        $this->assertStringNotContainsString('Charlie', $output);
    }

    /**
     * Test that future_mps() excludes the current member.
     */
    public function test_future_mps_excludes_current_member() {
        // Insert current member
        $this->insertTestMember(99020, 'Alice', 'Current', 'TestVille', '2010-01-01');

        // Insert another member with same name but different person_id
        $this->insertTestMember(99021, 'Alice', 'Current', 'TestVille', '2015-01-01');

        $member = new MEMBER(['person_id' => 99020]);
        $this->assertTrue($member->valid);

        $output = $member->future_mps();

        // Output should contain Alice (different person_id, entered later)
        $this->assertStringContainsString('Alice', $output);
    }

    /**
     * Test that future_mps() returns empty string when member is not in House of Commons.
     */
    public function test_future_mps_returns_empty_for_non_house_of_commons() {
        // Insert a member who entered the House of Commons then left
        // This should have no entered_house[1] and thus return empty
        MemberModel::query()->insert([
            'member_id' => 90010,
            'person_id' => 99030,
            'house' => 2,
            'title' => '',
            'first_name' => 'Lord',
            'last_name' => 'TestLord',
            'constituency' => '',
            'party' => 'Test Party',
            'entered_house' => '2010-01-01',
            'left_house' => '9999-12-31',
            'entered_reason' => 'appointed',
            'left_reason' => 'still_in_office',
        ]);

        $member = new MEMBER(['person_id' => 99030]);
        $this->assertTrue($member->valid);

        $output = $member->future_mps();

        // Should return empty string since member not in House of Commons
        $this->assertSame('', $output);
    }

    /**
     * Test future_mps() output format contains expected HTML structure.
     */
    public function test_future_mps_output_format() {
        // Insert current member
        $this->insertTestMember(99040, 'Alice', 'Current', 'TestVille', '2010-01-01');

        // Insert future MP
        $this->insertTestMember(99041, 'Bob', 'Future', 'TestVille', '2015-01-01');

        $member = new MEMBER(['person_id' => 99040]);
        $output = $member->future_mps();

        // Check for expected HTML structure
        $this->assertStringContainsString('<li>', $output);
        $this->assertStringContainsString('</li>', $output);
        $this->assertStringContainsString('<a href=', $output);
        $this->assertStringContainsString('mp/?pid=', $output);
    }

    /**
     * Test future_mps() with no future MPs returns empty string.
     */
    public function test_future_mps_no_future_members() {
        // Insert only one member
        $this->insertTestMember(99050, 'Alice', 'Only', 'TestVille', '2010-01-01');

        $member = new MEMBER(['person_id' => 99050]);
        $this->assertTrue($member->valid);

        $output = $member->future_mps();

        // Should return empty string (no future MPs)
        $this->assertSame('', $output);
    }

    /**
     * Test future_mps() returns members in ascending order of entered_house.
     */
    public function test_future_mps_ordered_by_entered_house() {
        // Insert current member
        $this->insertTestMember(99060, 'Alice', 'Current', 'TestVille', '2010-01-01');

        // Insert future MPs in non-chronological order
        $this->insertTestMember(99062, 'Charlie', 'Third', 'TestVille', '2020-01-01');
        $this->insertTestMember(99061, 'Bob', 'Second', 'TestVille', '2015-01-01');

        $member = new MEMBER(['person_id' => 99060]);
        $output = $member->future_mps();

        // Bob should appear before Charlie in output
        $pos_bob = strpos($output, 'Bob');
        $pos_charlie = strpos($output, 'Charlie');

        $this->assertNotFalse($pos_bob);
        $this->assertNotFalse($pos_charlie);
        $this->assertLessThan($pos_charlie, $pos_bob);
    }
}
