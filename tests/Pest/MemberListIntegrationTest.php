<?php

/**
 * @file
 * The queries and accessors behind the lists of Representatives and Senators. Uses fictional members.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/MemberFixtures.php';
require_once __DIR__ . '/../../www/includes/utility.php';

use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Enums\MemberOrder;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\Moffice;

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
    return MemberFixtures::term($person, $overrides + ['first_name' => 'Alex', 'constituency' => 'Testville']);
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
});

describe('Member::sittingList() filters', function () {
    beforeEach(function () {
        member_list_term(97001, ['first_name' => 'Cam', 'last_name' => 'Adams', 'constituency' => 'Zed', 'party' => 'B Party']);
        member_list_term(97002, ['first_name' => 'Ada', 'last_name' => 'Brown', 'constituency' => 'Yarra', 'party' => 'C Party']);
        member_list_term(97003, ['first_name' => 'Bo', 'last_name' => 'Clark', 'constituency' => 'Xavier', 'party' => 'B Party']);
    });

    /**
     * The ids of the fixture people a search and party leave.
     *
     * @return list<int>
     *   Person ids.
     */
    function member_list_found(string $search = '', ?string $party = null): array {
        return Member::sittingList(House::Representatives, MemberOrder::LastName, $search, $party)
          ->pluck('person_id')
          ->intersect([97001, 97002, 97003])
          ->values()
          ->all();
    }

    dataset('searches', [
        'a first name' => ['bo', [97003]],
        'a last name' => ['CLARK', [97003]],
        'an electorate' => ['zed', [97001]],
        'a part of a name' => ['ar', [97002, 97003]],
        'words in any order' => ['brown ada', [97002]],
        'words from different fields' => ['bo xavier', [97003]],
        'a first name inside another' => ['ada', [97001, 97002]],
        'a word nobody has' => ['nobody', []],
        'a blank search' => ['   ', [97001, 97002, 97003]],
    ]);

    it('finds what a search names', function (string $search, array $expected) {
        expect(member_list_found($search))->toBe($expected);
    })->with('searches');

    dataset('wildcards', ['%', '_', '\\', "' OR '1'='1"]);

    it('matches wildcards as text', function (string $search) {
        expect(member_list_found($search))->toBe([]);
    })->with('wildcards');

    describe('with names stored as entities', function () {
        beforeEach(function () {
            member_list_term(97004, ['last_name' => 'Gon&ccedil;alves', 'constituency' => 'Zo&euml;ville']);
            member_list_term(97005, ['last_name' => "O'Brien", 'constituency' => 'Elsewhere']);
            member_list_term(97006, ['first_name' => "D'Arcy", 'last_name' => 'O&#39;Neil', 'constituency' => 'Nowhere']);
        });

        dataset('accented searches', [
            'an accent in a last name' => ['gonçalves', 97004],
            'an accent in uppercase' => ['GONÇALVES', 97004],
            'an accent in an electorate' => ['zoëville', 97004],
            'an apostrophe' => ["o'brien", 97005],
            'a first apostrophe' => ["d'arcy", 97006],
            'a second apostrophe' => ["o'neil", 97006],
        ]);

        it('finds them by the plain text', function (string $search, int $person) {
            expect(Member::sittingList(House::Representatives, MemberOrder::LastName, $search)->pluck('person_id')->all())->toBe([$person]);
        })->with('accented searches');
    });

    it('limits the list to a party', function () {
        expect(member_list_found('', 'B Party'))->toBe([97001, 97003]);
    });

    it('combines a search with a party', function () {
        expect(member_list_found('bo', 'B Party'))->toBe([97003]);
    });

    describe('with a party nobody sits for', function () {
        it('finds nobody', function () {
            expect(member_list_found('', 'Z Party'))->toBe([]);
        });
    });
});

describe('Member::sittingParties()', function () {
    beforeEach(function () {
        member_list_term(97001, ['last_name' => 'Adams', 'party' => 'B Party']);
        member_list_term(97002, ['last_name' => 'Brown', 'party' => 'A Party']);
        member_list_term(97003, ['last_name' => 'Clark', 'party' => 'B Party']);
    });

    it('lists each party once, A to Z', function () {
        expect(Member::sittingParties(House::Representatives)->intersect(['A Party', 'B Party'])->values()->all())->toBe(['A Party', 'B Party']);
    });

    describe('with a presiding officer', function () {
        it('leaves out their office code', function () {
            Member::find(97001)->update(['party' => 'SPK']);

            expect(Member::sittingParties(House::Representatives)->all())->not->toContain('SPK');
        });
    });

    describe('with a member who has left', function () {
        it('leaves out their party', function () {
            Member::find(97002)->update(['left_house' => '2025-05-03']);

            expect(Member::sittingParties(House::Representatives)->all())->not->toContain('A Party');
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
