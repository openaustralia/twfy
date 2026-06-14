<?php

/**
 * @file
 * Integration tests for MEMBER::member_id_to_person_id().
 */

use OpenAustralia\TWFY\Models\Member as MemberModel;

/**
 *
 */
class MemberIdToPersonIdTest extends TransactionalTestCase {

    private function insertTestMember(int $memberId, int $personId): void {
        MemberModel::query()->insert([
            'member_id' => $memberId,
            'person_id' => $personId,
            'house' => 1,
            'title' => '',
            'first_name' => 'Test',
            'last_name' => 'Member',
            'constituency' => 'TestVille',
            'party' => 'Test Party',
            'entered_house' => '2010-01-01',
            'left_house' => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
        ]);
    }

    public function test_member_id_to_person_id_returns_person_id_for_existing_member(): void {
        $this->insertTestMember(99100, 88100);

        $member = new MEMBER(['person_id' => 88100]);
        $this->assertTrue($member->valid);

        $this->assertSame(88100, $member->member_id_to_person_id(99100));
    }

    public function test_member_id_to_person_id_returns_false_for_missing_member(): void {
        $this->insertTestMember(99101, 88101);

        $member = new MEMBER(['person_id' => 88101]);
        $this->assertTrue($member->valid);

        $this->assertFalse($member->member_id_to_person_id(999999));
    }

}
