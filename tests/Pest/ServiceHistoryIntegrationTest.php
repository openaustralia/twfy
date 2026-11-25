<?php

/**
 * @file
 * The timelines and party change notice on a profile. Uses fictional members.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/MemberFixtures.php';
require_once __DIR__ . '/../../www/includes/utility.php';

use Illuminate\Support\Carbon;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Parliament\Person;
use OpenAustralia\TWFY\Parliament\ServiceHistory;

uses(TransactionalTestCase::class);

/**
 * Adds a term in the House for a fictional person.
 *
 * @param int $member
 *   The member id.
 * @param string $entered
 *   When it started.
 * @param array<string, int|string> $overrides
 *   Columns to change.
 */
function history_term(int $member, string $entered, array $overrides = []): Member {
    return MemberFixtures::term($member, $overrides + ['person_id' => 97601, 'entered_house' => $entered]);
}

/**
 * Adds a term that ended with a move to another party.
 */
function history_left_party(int $member, string $party, string $entered, string $left): Member {
    return history_term($member, $entered, ['party' => $party, 'left_house' => $left, 'left_reason' => 'changed_party']);
}

/**
 * The fictional person's history.
 */
function history(): ServiceHistory {
    return new ServiceHistory(Person::find(97601));
}

beforeEach(function () {
    Carbon::setTestNow('2026-07-01');
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('ServiceHistory::partyNotice()', function () {
    describe('with one party', function () {
        it('says nothing', function () {
            history_term(97601, '2022-05-21');

            expect(history()->partyNotice())->toBeNull();
        });
    });

    describe('with a change', function () {
        it('names the date and old party', function () {
            history_left_party(97601, 'Old Party', '2019-05-18', '2023-02-01');
            history_term(97602, '2023-02-01', ['party' => 'New Party']);

            expect(history()->partyNotice())->toBe('on 1 Feb 2023, from Old Party.');
        });
    });

    describe('with a break after the change', function () {
        it('dates it when the old party ended', function () {
            history_left_party(97601, 'Old Party', '2016-07-02', '2018-04-10');
            history_term(97602, '2018-07-28', ['party' => 'New Party']);

            expect(history()->partyNotice())->toBe('on 10 Apr 2018, from Old Party.');
        });
    });

    describe('with a move to Independent', function () {
        it('says they now sit as one', function () {
            history_left_party(97601, 'Old Party', '2019-05-18', '2023-02-01');
            history_term(97602, '2023-02-01', ['party' => 'Independent']);

            expect(history()->partyNotice())->toBe('on 1 Feb 2023: left the Old Party and now sits as an Independent.');
        });
    });

    describe('with two changes', function () {
        it('tells the latest first', function () {
            history_left_party(97601, 'Old Party', '2019-05-18', '2023-02-01');
            history_left_party(97602, 'Independent', '2023-02-01', '2023-03-01');
            history_term(97603, '2023-03-01', ['party' => 'New Party']);

            expect(history()->partyNotice())->toBe('on 1 Mar 2023, from Independent. Left the Old Party on 1 Feb 2023.');
        });
    });

    describe('with a presiding officer', function () {
        it('is not a change of party', function () {
            history_left_party(97601, 'Old Party', '2019-05-18', '2022-07-26');
            history_term(97602, '2022-07-26', ['party' => 'SPK']);

            expect(history()->partyNotice())->toBeNull();
        });
    });
});

describe('ServiceHistory::parties()', function () {
    describe('with terms in one party', function () {
        it('joins them', function () {
            history_term(97601, '2019-05-18', ['left_house' => '2022-05-21', 'left_reason' => 'general_election_standing']);
            history_term(97602, '2022-05-21');

            expect(array_column(history()->parties(), 'dates'))->toBe(['18 May 2019 to present (7 years)']);
        });
    });

    describe('with a break between parties', function () {
        it('shows time outside parliament', function () {
            history_term(97601, '2016-07-02', ['party' => 'Old Party', 'left_house' => '2019-04-11', 'left_reason' => 'resigned']);
            history_term(97602, '2022-05-21');

            expect(array_column(history()->parties(), 'name'))->toBe(['Old Party', 'Not in Parliament', 'Example Party']);
        });
    });

    describe('with a presiding officer', function () {
        it('keeps their party', function () {
            history_left_party(97601, 'Old Party', '2019-05-18', '2022-07-26');
            history_term(97602, '2022-07-26', ['party' => 'SPK']);

            expect(array_column(history()->parties(), 'name'))->toBe(['Old Party']);
        });
    });
});

describe('ServiceHistory::seats()', function () {
    describe('with a sitting member', function () {
        beforeEach(function () {
            history_term(97601, '2022-05-21');
        });

        it('marks the seat current', function () {
            expect(history()->seats()[0]['current'])->toBeTrue();
        });

        it('says how they entered', function () {
            expect(history()->seats()[0]['detail'])->toBe('House of Representatives. Entered at a federal election.');
        });
    });

    describe('with a seat they left', function () {
        it('says how it started and ended', function () {
            history_term(97601, '2019-05-18', ['entered_reason' => 'by_election', 'left_house' => '2021-06-01', 'left_reason' => 'resigned']);

            expect(history()->seats()[0]['detail'])->toBe('House of Representatives. Entered at a by-election; resigned.');
        });
    });

    describe('with a few days between terms', function () {
        it('reads them as one', function () {
            history_term(97601, '2019-05-18', ['left_house' => '2022-05-14']);
            history_term(97602, '2022-05-21');

            expect(history()->seats())->toHaveCount(1);
        });
    });

    describe('with longer between terms', function () {
        it('shows the break', function () {
            history_term(97601, '2019-05-18', ['left_house' => '2021-05-21', 'left_reason' => 'resigned']);
            history_term(97602, '2022-05-21');

            expect(history()->seats()[1])
              ->toMatchArray(['name' => 'Not in Parliament', 'dates' => '21 May 2021 to 21 May 2022 (1 year)', 'gap' => true]);
        });
    });

    describe('with a move to the other house', function () {
        it('lists both seats', function () {
            history_term(97601, '2019-07-01', ['house' => 2, 'constituency' => 'Tasmania', 'left_house' => '2022-05-14', 'left_reason' => 'resigned']);
            history_term(97602, '2022-05-21');

            expect(array_column(history()->seats(), 'house'))->toBe([House::Senate, House::Representatives]);
        });
    });

    describe('with a start known to the year', function () {
        it('gives only the year', function () {
            history_term(97601, '1990-01-01', ['house' => 2, 'left_house' => '1990-03-01']);

            expect(history()->seats()[0]['dates'])->toBe('1990 to 1 Mar 1990');
        });
    });
});
