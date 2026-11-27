<?php

/**
 * @file
 * Contributions to Hansard per year. Uses a fictional member.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/MemberFixtures.php';

use Illuminate\Support\Carbon;
use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Parliament\Person;
use OpenAustralia\TWFY\Parliament\SpeakingRecord;

uses(TransactionalTestCase::class);

/**
 * Adds a contribution by the fictional person's term, or by another speaker.
 */
function spoke(string $date, int $htype = 12, int $speaker = 97701): void {
    static $id = 97700000;
    Hansard::create(['epobject_id' => ++$id, 'gid' => "spec/$id", 'htype' => $htype, 'speaker_id' => $speaker, 'major' => 1, 'section_id' => 0, 'subsection_id' => 0, 'hpos' => 1, 'hdate' => $date, 'source_url' => '']);
}

/**
 * The fictional person's counts.
 *
 * @return array<int, int>
 *   Counts keyed by year.
 */
function speaking(): array {
    return SpeakingRecord::for(Person::find(97701));
}

beforeEach(function () {
    Carbon::setTestNow('2026-07-01');
    MemberFixtures::term(97701);
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('SpeakingRecord::for()', function () {
    describe('with a sitting member', function () {
        it('counts each year up to this one', function () {
            spoke('2024-02-01');
            spoke('2024-03-01');
            spoke('2026-02-01');

            expect(speaking())->toBe([2024 => 2, 2025 => 0, 2026 => 1]);
        });
    });

    describe('with someone who has left', function () {
        it('ends when they last spoke', function () {
            Member::find(97701)->update(['left_house' => '2025-06-30']);
            spoke('2023-02-01');
            spoke('2024-02-01');

            expect(speaking())->toBe([2023 => 1, 2024 => 1]);
        });
    });

    describe('with many years', function () {
        it('keeps the last nine', function () {
            spoke('2010-02-01');
            spoke('2018-02-01');

            expect(array_keys(speaking()))->toBe(range(2018, 2026));
        });
    });

    describe('with entries that are not speeches', function () {
        it('leaves them out', function () {
            spoke('2025-02-01', 10);

            expect(speaking())->toBe([]);
        });
    });

    describe('with only one year', function () {
        it('is empty', function () {
            spoke('2026-02-01');

            expect(speaking())->toBe([]);
        });
    });

    describe('with nothing said', function () {
        it('is empty', function () {
            expect(speaking())->toBe([]);
        });
    });
});

describe('SpeakingRecord::lastTwelveMonths()', function () {
    /**
     * The fictional person's counts for the last twelve months.
     *
     * @return array<string, int>
     *   Counts keyed by month.
     */
    function by_month(): array {
        return SpeakingRecord::lastTwelveMonths(Person::find(97701));
    }

    it('gives twelve months, this one last', function () {
        expect(array_keys(by_month()))->toBe(['2025-08', '2025-09', '2025-10', '2025-11', '2025-12', '2026-01', '2026-02', '2026-03', '2026-04', '2026-05', '2026-06', '2026-07']);
    });

    it('counts the speeches in each month', function () {
        spoke('2026-06-02');
        spoke('2026-06-20');
        spoke('2026-07-01');

        expect(by_month())->toMatchArray(['2026-05' => 0, '2026-06' => 2, '2026-07' => 1]);
    });

    it('counts the first month of the twelve', function () {
        spoke('2025-08-01');

        expect(by_month()['2025-08'])->toBe(1);
    });

    it('leaves out earlier months', function () {
        spoke('2025-07-31');

        expect(array_sum(by_month()))->toBe(0);
    });

    it('counts speeches and nothing else', function () {
        spoke('2026-06-02');
        spoke('2026-06-02', 10);

        expect(by_month()['2026-06'])->toBe(1);
    });

    it('counts only that person', function () {
        MemberFixtures::term(97702, ['person_id' => 97702, 'first_name' => 'Casey']);
        spoke('2026-06-02', 12, 97702);

        expect(array_sum(by_month()))->toBe(0);
    });
});

