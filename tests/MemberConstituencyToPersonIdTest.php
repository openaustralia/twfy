<?php

/**
 * @file
 * Integration tests for MEMBER::constituency_to_person_id().
 */

require_once __DIR__ . '/bootstrap.php';

use OpenAustralia\TWFY\Models\Member as MemberModel;

class MemberConstituencyToPersonIdTest extends TransactionalTestCase {

    private function insertTestMember(
        int $memberId,
        int $personId,
        string $constituency,
        string $leftReason,
        string $leftHouse
    ): void {
        MemberModel::query()->insert([
            'member_id' => $memberId,
            'person_id' => $personId,
            'house' => 1,
            'title' => '',
            'first_name' => 'Test',
            'last_name' => 'Member',
            'constituency' => $constituency,
            'party' => 'Test Party',
            'entered_house' => '2010-01-01',
            'left_house' => $leftHouse,
            'entered_reason' => 'general_election',
            'left_reason' => $leftReason,
        ]);
    }

    public function test_constituency_to_person_id_prefers_current_member(): void {
        $constituency = 'TxCurrentConstituency';

        $this->insertTestMember(99200, 88200, $constituency, 'general_election', '2020-01-01');
        $this->insertTestMember(99201, 88201, $constituency, 'still_in_office', '9999-12-31');

        $member = new MEMBER(['person_id' => 88201]);
        $this->assertTrue($member->valid);

        $this->assertSame(88201, $member->constituency_to_person_id($constituency));
    }

    public function test_constituency_to_person_id_falls_back_to_latest_left_house(): void {
        $constituency = 'TxHistoricalConstituency';

        $this->insertTestMember(99210, 88210, $constituency, 'general_election', '2015-01-01');
        $this->insertTestMember(99211, 88211, $constituency, 'resigned', '2020-01-01');

        $member = new MEMBER(['person_id' => 88211]);
        $this->assertTrue($member->valid);

        $this->assertSame(88211, $member->constituency_to_person_id($constituency));
    }

    public function test_constituency_to_person_id_returns_false_when_missing(): void {
        $member = new MEMBER(['person_id' => 88220]);
        $this->assertFalse($member->valid);

        // Use an impossible constituency name to avoid accidental fixture matches.
        $this->assertFalse($member->constituency_to_person_id('TxNoMatchConstituency_9f26f90a'));
    }

    public function test_constituency_to_person_id_returns_false_for_empty_constituency(): void {
        // Create a stub for $PAGE to avoid error_message() errors.
        $PAGE = new class {
            public function error_message($msg) {
                // Stub - do nothing.
            }
        };
        $GLOBALS['PAGE'] = $PAGE;

        $member = new MEMBER(['person_id' => 88230]);

        // Empty string should return false early.
        $this->assertFalse($member->constituency_to_person_id(''));
    }
}
