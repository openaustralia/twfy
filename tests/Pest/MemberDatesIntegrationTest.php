<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\Member as MemberModel;

uses(TransactionalTestCase::class);

/**
 * Inserts a House of Representatives stint and loads it through MEMBER.
 *
 * @param string $leftHouse
 *   The stint's end date, '9999-12-31' for a sitting member.
 */
function member_with_stint(string $leftHouse): MEMBER {
    MemberModel::query()->insert([
        'member_id' => 99700,
        'person_id' => 88700,
        'house' => 1,
        'title' => '',
        'first_name' => 'Test',
        'last_name' => 'Member',
        'constituency' => 'TestVille',
        'party' => 'Test Party',
        'entered_house' => '2022-05-21',
        'left_house' => $leftHouse,
        'entered_reason' => 'general_election',
        'left_reason' => 'still_in_office',
    ]);

    return new MEMBER(['person_id' => 88700]);
}

describe('MEMBER::current_member()', function () {
    describe('with a sitting member', function () {
        it('is true for the House', function () {
            expect(member_with_stint('9999-12-31')->current_member(1))->toBeTrue();
        });
    });

    describe('with a member who has left', function () {
        it('is false for the House', function () {
            expect(member_with_stint('2025-05-03')->current_member(1))->toBeFalse();
        });
    });
});

describe('MEMBER::left_house()', function () {
    it('gives the date as a Y-m-d string', function () {
        expect(member_with_stint('9999-12-31')->left_house(1)['date'])->toBe('9999-12-31');
    });

    it('gives a readable date once they have left', function () {
        expect(member_with_stint('2025-05-03')->left_house(1)['date_pretty'])->toBe('3 May 2025');
    });
});

describe('MEMBER::entered_house()', function () {
    it('gives the date as a Y-m-d string', function () {
        expect(member_with_stint('9999-12-31')->entered_house(1)['date'])->toBe('2022-05-21');
    });
});

/**
 * Loads a person who changed party, through the MEMBER constructor.
 *
 * Their earlier stint ended with changed_party and the later one is current.
 */
function member_who_changed_party(): MEMBER {
    $stint = [
        'person_id' => 88701,
        'house' => 1,
        'title' => '',
        'first_name' => 'Test',
        'last_name' => 'Switcher',
        'entered_reason' => 'general_election',
    ];
    MemberModel::query()->insert([
        ...$stint,
        'member_id' => 99701,
        'constituency' => 'OldVille',
        'party' => 'Old Party',
        'entered_house' => '2019-05-18',
        'left_house' => '2022-07-26',
        'left_reason' => 'changed_party',
    ]);
    MemberModel::query()->insert([
        ...$stint,
        'member_id' => 99702,
        'constituency' => 'NewVille',
        'party' => 'New Party',
        'entered_house' => '2022-07-26',
        'left_house' => '9999-12-31',
        'left_reason' => 'still_in_office',
    ]);

    return new MEMBER(['person_id' => 88701]);
}

describe('MEMBER with a change of party', function () {
    it('records the old party', function () {
        expect(member_who_changed_party()->other_parties[0]['from'])->toBe('Old Party');
    });

    it('dates the change as a Y-m-d string', function () {
        expect(member_who_changed_party()->other_parties[0]['date'])->toBe('2022-07-26');
    });

    it('shows the latest constituency', function () {
        expect(member_who_changed_party()->constituency())->toBe('NewVille');
    });

    it('shows the latest party', function () {
        expect(member_who_changed_party()->party())->toBe('New Party');
    });
});
