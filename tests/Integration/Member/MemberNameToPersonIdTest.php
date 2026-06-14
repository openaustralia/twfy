<?php

/**
 * @file
 * Integration tests for MEMBER::name_to_person_id().
 */

require_once __DIR__ . '/../../bootstrap.php';

use OpenAustralia\TWFY\Models\Member as MemberModel;

class MemberNameToPersonIdTest extends TransactionalTestCase {

    private function insertTestMember(
        int $memberId,
        int $personId,
        string $firstName,
        string $lastName,
        string $constituency,
        int $house = HOUSE::REPRESENTATIVES
    ): void {
        MemberModel::query()->insert([
            'member_id' => $memberId,
            'person_id' => $personId,
            'house' => $house,
            'title' => '',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'constituency' => $constituency,
            'party' => 'Test Party',
            'entered_house' => '2010-01-01',
            'left_house' => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
        ]);
    }

    public function test_name_to_person_id_returns_match_for_mp_name_without_middle_name(): void {
        $GLOBALS['this_page'] = 'mp';
        $GLOBALS['PAGE'] = new class {
            public function error_message($msg) {
            }
        };

        $this->insertTestMember(99400, 88400, 'Jane', 'Citizen', 'TxNameSeatA');
        $this->insertTestMember(99401, 88401, 'Helper', 'Member', 'TxNameSeatB');

        $member = new MEMBER(['person_id' => 88401]);
        $this->assertTrue($member->valid);

        $result = $member->name_to_person_id('Jane Citizen');
        $this->assertSame(88400, $result);
    }

    public function test_name_to_person_id_returns_all_person_ids_when_name_is_ambiguous(): void {
        $GLOBALS['this_page'] = 'mp';
        $GLOBALS['PAGE'] = new class {
            public function error_message($msg) {
            }
        };

        $this->insertTestMember(99410, 88410, 'Alex', 'Taylor', 'TxNameSeatC');
        $this->insertTestMember(99411, 88411, 'Alex', 'Taylor', 'TxNameSeatD');
        $this->insertTestMember(99412, 88412, 'Helper', 'Member', 'TxNameSeatE');

        $member = new MEMBER(['person_id' => 88412]);
        $this->assertTrue($member->valid);

        $result = $member->name_to_person_id('Alex Taylor');
        $this->assertIsArray($result);
        $this->assertContains(88410, $result);
        $this->assertContains(88411, $result);
        $this->assertSame(['TxNameSeatC', 'TxNameSeatD'], $member->constituency);
    }

    public function test_name_to_person_id_matches_name_with_middle_name(): void {
        $GLOBALS['this_page'] = 'mp';
        $GLOBALS['PAGE'] = new class {
            public function error_message($msg) {
            }
        };

        // Exercise the middle-name branch where first_name contains
        // first+middle and last_name contains the surname.
        $this->insertTestMember(99420, 88420, 'Mary Jane', 'Watson', 'TxNameSeatF');
        $this->insertTestMember(99421, 88421, 'Helper', 'Member', 'TxNameSeatG');

        $member = new MEMBER(['person_id' => 88421]);
        $this->assertTrue($member->valid);

        $result = $member->name_to_person_id('Mary Jane Watson');
        $this->assertSame(88420, $result);
    }

    public function test_name_to_person_id_filters_by_passed_constituency(): void {
        $GLOBALS['this_page'] = 'mp';
        $GLOBALS['PAGE'] = new class {
            public function error_message($msg) {
            }
        };

        $this->insertTestMember(99430, 88430, 'Chris', 'Jordan', 'TxNameSeatH');
        $this->insertTestMember(99431, 88431, 'Chris', 'Jordan', 'TxNameSeatI');
        $this->insertTestMember(99432, 88432, 'Helper', 'Member', 'TxNameSeatJ');

        $member = new MEMBER(['person_id' => 88432]);
        $this->assertTrue($member->valid);

        $result = $member->name_to_person_id('Chris Jordan', 'TxNameSeatI');
        $this->assertSame(88431, $result);
    }
}
