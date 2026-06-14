<?php

/**
 * @file
 * Tests for changed-party handling in MEMBER construction.
 */

require_once __DIR__ . '/bootstrap.php';

use OpenAustralia\TWFY\Models\Member as MemberModel;

/**
 * Integration tests for MEMBER changed-party history.
 */
class MemberChangedPartyTest extends TransactionalTestCase {

    /**
     * Insert a member row for tests.
     */
    private function insertTestMember(
      int $memberId,
      int $personId,
      string $party,
      string $leftReason,
      string $leftHouse,
    ): void {
        MemberModel::query()->insert([
            'member_id' => $memberId,
            'person_id' => $personId,
            'house' => 1,
            'title' => '',
            'first_name' => 'Test',
            'last_name' => 'Member',
            'constituency' => 'TestVille',
            'party' => $party,
            'entered_house' => '2010-01-01',
            'left_house' => $leftHouse,
            'entered_reason' => 'general_election',
            'left_reason' => $leftReason,
        ]);
    }

    /**
     * MEMBER should record previous party when left_reason is changed_party.
     */
    public function test_constructor_records_other_parties_for_changed_party_left_reason(): void {
        $this->insertTestMember(99510, 88510, 'Unmapped Test Party', 'changed_party', '2015-05-01');

        $member = new MEMBER(['person_id' => 88510]);

        $this->assertTrue($member->valid);
        $this->assertIsArray($member->other_parties);
        $this->assertSame('Unmapped Test Party', $member->other_parties[0]['from']);

        $date = $member->other_parties[0]['date'];
        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('Y-m-d');
        }
        $this->assertSame('2015-05-01', (string) $date);
    }

}
