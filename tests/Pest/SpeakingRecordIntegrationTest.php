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
 * Adds a contribution by the fictional person's term.
 */
function spoke(string $date, int $htype = 12): void {
    static $id = 97700000;
    Hansard::create(['epobject_id' => ++$id, 'gid' => "spec/$id", 'htype' => $htype, 'speaker_id' => 97701, 'major' => 1, 'section_id' => 0, 'subsection_id' => 0, 'hpos' => 1, 'hdate' => $date, 'source_url' => '']);
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
