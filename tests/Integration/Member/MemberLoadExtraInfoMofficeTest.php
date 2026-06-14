<?php

/**
 * @file
 * Integration tests for MEMBER::load_extra_info() after migration to Moffice model.
 */


use OpenAustralia\TWFY\Models\Member as MemberModel;
use OpenAustralia\TWFY\Models\Moffice as MofficeModel;

/**
 *
 */
class MemberLoadExtraInfoMofficeTest extends TransactionalTestCase {

    private int $memberId;
    private int $personId;
    private int $mofficeId;

    protected function setUp(): void {
        parent::setUp();

        $suffix = random_int(100000, 999999);
        $this->memberId = 971000 + $suffix;
        $this->personId = 981000 + $suffix;
        $this->mofficeId = 991000 + $suffix;

        MemberModel::query()->insert([
            'member_id'      => $this->memberId,
            'person_id'      => $this->personId,
            'house'          => HOUSE::REPRESENTATIVES,
            'title'          => '',
            'first_name'     => 'Test',
            'last_name'      => 'MofficeUser',
            'constituency'   => 'TxMofficeSeat' . $suffix,
            'party'          => 'Test Party',
            'entered_house'  => '2010-01-01',
            'left_house'     => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason'    => 'still_in_office',
        ]);

        MofficeModel::query()->insert([
            'moffice_id' => $this->mofficeId,
            'dept'       => 'Tx Department',
            'position'   => 'Tx Minister',
            'from_date'  => '2020-01-01',
            'to_date'    => '9999-12-31',
            'person'     => $this->personId,
            'source'     => '',
        ]);
    }

    public function test_load_extra_info_populates_office_from_moffice(): void {
        $GLOBALS['this_page'] = 'mp';
        $GLOBALS['PAGE'] = new class {
            public function error_message($msg): void {
            }
        };

        $member = new MEMBER(['person_id' => $this->personId]);
        $this->assertTrue($member->valid);

        $member->load_extra_info();

        $this->assertArrayHasKey('office', $member->extra_info);
        $offices = $member->extra_info['office'];
        $this->assertNotEmpty($offices);

        $found = false;
        foreach ($offices as $office) {
            if ((int) $office['moffice_id'] === $this->mofficeId) {
                $found = true;
                $this->assertSame('Tx Department', $office['dept']);
                $this->assertSame('Tx Minister', $office['position']);
            }
        }
        $this->assertTrue($found, 'Expected moffice row not found in extra_info[office]');
    }

    public function test_load_extra_info_returns_no_offices_when_none_exist(): void {
        $GLOBALS['this_page'] = 'mp';
        $GLOBALS['PAGE'] = new class {
            public function error_message($msg): void {
            }
        };

        $suffix2 = random_int(100000, 999999);
        $memberId2 = 972000 + $suffix2;
        $personId2 = 982000 + $suffix2;

        MemberModel::query()->insert([
            'member_id'      => $memberId2,
            'person_id'      => $personId2,
            'house'          => HOUSE::REPRESENTATIVES,
            'title'          => '',
            'first_name'     => 'Empty',
            'last_name'      => 'Offices',
            'constituency'   => 'TxEmptySeat' . $suffix2,
            'party'          => 'Test Party',
            'entered_house'  => '2010-01-01',
            'left_house'     => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason'    => 'still_in_office',
        ]);

        $member = new MEMBER(['person_id' => $personId2]);
        $this->assertTrue($member->valid);

        $member->load_extra_info();

        $this->assertArrayNotHasKey('office', $member->extra_info);
    }

}
