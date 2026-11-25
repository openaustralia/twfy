<?php

/**
 * @file
 * Finding a person and reading their terms. Uses fictional members.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Parliament\Person;

uses(TransactionalTestCase::class);

/**
 * Adds a term for a fictional person.
 *
 * @param int $member
 *   The member id.
 * @param array<string, int|string> $overrides
 *   Columns to change.
 */
function person_term(int $member, array $overrides = []): Member {
    return Member::create($overrides + [
        'member_id' => $member,
        'person_id' => 97201,
        'house' => 2,
        'title' => '',
        'first_name' => 'Kim',
        'last_name' => 'Specimen',
        'constituency' => 'Tasmania',
        'party' => 'Example Party',
        'entered_house' => '2022-07-01',
        'left_house' => '9999-12-31',
        'entered_reason' => 'general_election',
        'left_reason' => 'still_in_office',
    ]);
}

/**
 * The ids of the people found by name.
 *
 * @return list<int>
 *   Person ids, in order.
 */
function person_ids_named(House $house, string $name, ?string $constituency = null): array {
    return Person::termsNamed($house, $name, $constituency)->pluck('person_id')->all();
}

beforeEach(function () {
    // A Representative who changed party, then moved to the Senate.
    person_term(97201, ['house' => 1, 'constituency' => 'Testville', 'party' => 'First Party', 'entered_house' => '2013-09-07', 'left_house' => '2016-05-09', 'left_reason' => 'changed_party']);
    person_term(97202, ['house' => 1, 'constituency' => 'Testville', 'entered_house' => '2016-05-09', 'left_house' => '2019-04-11', 'left_reason' => 'resigned']);
    person_term(97203);
});

describe('Person::find()', function () {
    describe('with an unknown id', function () {
        it('finds nobody', function () {
            expect(Person::find(97299))->toBeNull();
        });
    });
});

describe('Person::latest()', function () {
    it('is the term that ends last', function () {
        expect(Person::find(97201)->latest()->member_id)->toBe(97203);
    });
});

describe('Person::houses()', function () {
    it('lists each house, latest first', function () {
        expect(Person::find(97201)->houses())->toBe([House::Senate, House::Representatives]);
    });
});

describe('Person::latestTerm()', function () {
    it('is the last term in that house', function () {
        expect(Person::find(97201)->latestTerm(House::Representatives)->member_id)->toBe(97202);
    });
});

describe('Person::firstTerm()', function () {
    it('is the first term in that house', function () {
        expect(Person::find(97201)->firstTerm(House::Representatives)->member_id)->toBe(97201);
    });
});

describe('Person::sitsIn()', function () {
    dataset('seats', [
        'their current house' => [House::Senate, true],
        'a house they left' => [House::Representatives, false],
    ]);

    it('says whether they sit there now', function (House $house, bool $expected) {
        expect(Person::find(97201)->sitsIn($house))->toBe($expected);
    })->with('seats');
});

describe('Person::partyChanges()', function () {
    it('lists the terms left for a party', function () {
        expect(Person::find(97201)->partyChanges()->pluck('party')->all())->toBe(['First Party']);
    });
});

describe('Person::termsNamed()', function () {
    it('ignores the case of the name', function () {
        expect(person_ids_named(House::Senate, 'kim specimen', 'tasmania'))->toBe([97201]);
    });

    describe('with another state', function () {
        it('finds nobody', function () {
            expect(person_ids_named(House::Senate, 'kim specimen', 'victoria'))->toBe([]);
        });
    });

    describe('with one word', function () {
        it('finds nobody', function () {
            expect(person_ids_named(House::Senate, 'specimen'))->toBe([]);
        });
    });

    dataset('three word names', [
        'a two word first name' => ['Mary Jo', 'Specimen'],
        'a two word last name' => ['Mary', 'Jo Specimen'],
    ]);

    describe('with three words', function () {
        it('tries both ways to split them', function (string $first, string $last) {
            person_term(97210, ['person_id' => 97210, 'first_name' => $first, 'last_name' => $last]);

            expect(person_ids_named(House::Senate, 'mary jo specimen'))->toBe([97210]);
        })->with('three word names');
    });

    describe('with two people of one name', function () {
        it('puts the latest to leave first', function () {
            person_term(97220, ['person_id' => 97220, 'left_house' => '2001-06-30', 'entered_house' => '1996-07-01']);

            expect(person_ids_named(House::Senate, 'kim specimen'))->toBe([97201, 97220]);
        });
    });
});

describe('Member party_name', function () {
    dataset('parties', [
        'a party' => ['Example Party', 'Example Party'],
        'a presiding officer' => ['DPRES', 'Deputy President'],
    ]);

    it('puts the party in words', function (string $code, string $name) {
        expect((new Member(['party' => $code]))->party_name)->toBe($name);
    })->with('parties');
});
