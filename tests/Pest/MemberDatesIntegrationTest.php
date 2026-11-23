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
