<?php

/**
 * @file
 * The queries and accessors behind the lists of Representatives and Senators. Uses fictional members.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/utility.php';

use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Enums\MemberOrder;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\Moffice;
use OpenAustralia\TWFY\Models\Personinfo;

uses(TransactionalTestCase::class);

/**
 * Adds a term for a fictional person, sitting unless told otherwise.
 *
 * @param int $person
 *   The person id, also used for the member id.
 * @param array<string, int|string> $overrides
 *   Columns to change.
 */
function member_list_term(int $person, array $overrides = []): Member {
    return Member::create($overrides + [
        'member_id' => $person,
        'person_id' => $person,
        'house' => 1,
        'title' => '',
        'first_name' => 'Alex',
        'last_name' => 'Example',
        'constituency' => 'Testville',
        'party' => 'Example Party',
        'entered_house' => '2022-05-21',
        'left_house' => '9999-12-31',
        'entered_reason' => 'general_election',
        'left_reason' => 'still_in_office',
    ]);
}

/**
 * The ids of the fixture people in a list, in order.
 *
 * @return list<int>
 *   Person ids.
 */
function member_list_ids(House $house, MemberOrder $order): array {
    return Member::sittingList($house, $order)
      ->pluck('person_id')
      ->intersect([97001, 97002, 97003])
      ->values()
      ->all();
}

describe('Member::sittingList()', function () {
    beforeEach(function () {
        member_list_term(97001, ['first_name' => 'Cam', 'last_name' => 'Adams', 'constituency' => 'Zed', 'party' => 'B Party']);
        member_list_term(97002, ['first_name' => 'Ada', 'last_name' => 'Brown', 'constituency' => 'Yarra', 'party' => 'C Party']);
        member_list_term(97003, ['first_name' => 'Bo', 'last_name' => 'Clark', 'constituency' => 'Xavier', 'party' => 'A Party']);
    });

    dataset('orders', [
        'first name' => [MemberOrder::FirstName, [97002, 97003, 97001]],
        'last name' => [MemberOrder::LastName, [97001, 97002, 97003]],
        'constituency' => [MemberOrder::Constituency, [97003, 97002, 97001]],
        'party' => [MemberOrder::Party, [97003, 97001, 97002]],
    ]);

    it('sorts as asked', function (MemberOrder $order, array $expected) {
        expect(member_list_ids(House::Representatives, $order))->toBe($expected);
    })->with('orders');

    describe('with a member who has left', function () {
        it('leaves them out', function () {
            Member::find(97001)->update(['left_house' => '2025-05-03']);

            expect(member_list_ids(House::Representatives, MemberOrder::LastName))->toBe([97002, 97003]);
        });
    });

    describe('with a member of the other house', function () {
        it('leaves them out', function () {
            Member::find(97001)->update(['house' => 2]);

            expect(member_list_ids(House::Representatives, MemberOrder::LastName))->toBe([97002, 97003]);
        });
    });

    describe('when sorted by debates', function () {
        beforeEach(function () {
            Member::whereIn('member_id', [97001, 97002, 97003])->update(['house' => 2]);
            Personinfo::create(['person_id' => 97001, 'data_key' => 'Ldebate_sectionsspoken_inlastyear', 'data_value' => '9']);
            Personinfo::create(['person_id' => 97002, 'data_key' => 'Ldebate_sectionsspoken_inlastyear', 'data_value' => '10']);
        });

        it('puts the highest count first', function () {
            expect(member_list_ids(House::Senate, MemberOrder::Debates))->toBe([97002, 97001]);
        });
    });
});

describe('MemberOrder::fromQuery()', function () {
    dataset('unknown orders', [null, '', 'x', [['f']]]);

    it('falls back to last name', function (mixed $value) {
        expect(MemberOrder::fromQuery($value))->toBe(MemberOrder::LastName);
    })->with('unknown orders');
});

describe('Member full_name', function () {
    it('includes a title', function () {
        expect(member_list_term(97001, ['title' => 'Ms'])->full_name)->toBe('Ms Alex Example');
    });

    describe('without a title', function () {
        it('has no leading space', function () {
            expect(member_list_term(97001)->full_name)->toBe('Alex Example');
        });
    });
});

describe('Member stored entities', function () {
    beforeEach(function () {
        $this->member = member_list_term(97001, [
            'first_name' => "D'Arcy O&#39;Neil",
            'last_name' => 'Nu&ntilde;ez',
            'constituency' => 'Bar &amp; Baz',
        ]);
    });

    dataset('shown', [
        'full_name' => ['full_name', "D'Arcy O'Neil Nuñez"],
        'short_name' => ['short_name', "D'Arcy O'Neil Nuñez"],
        'display_first_name' => ['display_first_name', "D'Arcy O'Neil"],
        'display_last_name' => ['display_last_name', 'Nuñez'],
        'display_constituency' => ['display_constituency', 'Bar & Baz'],
    ]);

    it('shows the characters', function (string $attribute, string $expected) {
        expect($this->member->{$attribute})->toBe($expected);
    })->with('shown');

    it('keeps the stored form', function () {
        expect($this->member->last_name)->toBe('Nu&ntilde;ez');
    });
});

describe('Member profile_url', function () {
    dataset('houses', [
        'Representative' => [1, '/mp/alex_example/testville'],
        'Senator' => [2, '/senator/alex_example/testville'],
    ]);

    it('names the member and their seat', function (int $house, string $url) {
        expect(member_list_term(97001, ['house' => $house, 'title' => 'Ms'])->profile_url)->toBe($url);
    })->with('houses');
});

describe('Member positions', function () {
    beforeEach(function () {
        Moffice::create(['person' => 97001, 'position' => 'Minister for Examples', 'dept' => '', 'from_date' => '2025-01-01', 'to_date' => '9999-12-31', 'source' => '']);
        Moffice::create(['person' => 97001, 'position' => 'Old Office', 'dept' => '', 'from_date' => '2020-01-01', 'to_date' => '2021-01-01', 'source' => '']);
    });

    it('lists the offices held now', function () {
        expect(member_list_term(97001)->positions)->toBe(['Minister for Examples']);
    });

    describe('with a presiding officer', function () {
        it('puts that office first', function () {
            expect(member_list_term(97001, ['party' => 'SPK'])->positions)
              ->toBe(['Speaker, House of Representatives', 'Minister for Examples']);
        });

        it('shows no party', function () {
            expect(member_list_term(97001, ['party' => 'SPK'])->party_label)->toBe('-');
        });
    });
});

describe('Moffice description', function () {
    dataset('offices', [
        'both' => ['Chair', 'Example Committee', 'Chair, Example Committee'],
        'a position only' => ['Chair', '', 'Chair'],
        'a department only' => ['', 'Example Committee', 'Member, Example Committee'],
        'neither' => ['', '', ''],
    ]);

    it('puts the office in words', function (string $position, string $dept, string $expected) {
        expect((new Moffice(['position' => $position, 'dept' => $dept]))->description)->toBe($expected);
    })->with('offices');

    describe('with a stored HTML entity', function () {
        it('gives the character', function () {
            expect((new Moffice(['position' => 'Veterans&rsquo; Affairs', 'dept' => '']))->description)->toBe('Veterans’ Affairs');
        });
    });
});
