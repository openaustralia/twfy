<?php

/**
 * @file
 * The admin report on the proof status of each day. Uses fictional gids and dates.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\ProofReport;

uses(TransactionalTestCase::class);

/**
 * Creates one hansard row for a day, house and proof value.
 */
function proof_report_row(int $id, string $date, int $major, ?bool $proof, string $modified = '2026-03-02 10:00:00'): void {
    Hansard::create([
        'epobject_id' => $id,
        'gid' => "uk.org.publicwhip/debate/$date.$id.1",
        'major' => $major,
        'hdate' => $date,
        'proof' => $proof,
        'modified' => $modified,
    ]);
}

describe('ProofReport::days()', function () {
    beforeEach(function () {
        proof_report_row(910001, '2026-03-01', 1, true);
        proof_report_row(910002, '2026-03-01', 1, false);
        proof_report_row(910003, '2026-03-01', 1, null, '2026-03-05 09:00:00');
        proof_report_row(910004, '2026-03-01', 101, false);
        proof_report_row(910005, '2026-02-01', 1, null);
        proof_report_row(910006, '2026-02-01', 3, true);
    });

    it('counts the rows in each state', function () {
        $days = ProofReport::days(true);

        expect($days[0])->toMatchArray(['date' => '2026-03-01', 'major' => 1, 'proof' => 1, 'final' => 1, 'unknown' => 1]);
    });

    it('reports when a row last changed', function () {
        expect(ProofReport::days(true)[0]['last_changed'])->toBe('2026-03-05 09:00:00');
    });

    it('lists newest days first', function () {
        $dates = array_column(ProofReport::days(true), 'date');

        expect($dates)->toBe(['2026-03-01', '2026-03-01', '2026-02-01']);
    });

    it('leaves out days that are all final by default', function () {
        $keys = array_map(fn (array $day): string => $day['date'] . '/' . $day['major'], ProofReport::days(false));

        expect($keys)->toBe(['2026-03-01/1', '2026-02-01/1']);
    });

    it('leaves out houses other than the two we hold', function () {
        expect(array_unique(array_column(ProofReport::days(true), 'major')))->toBe([1, 101]);
    });
});

describe('ProofReport::days() with many days', function () {
    beforeEach(function () {
        for ($i = 0; $i < ProofReport::PER_PAGE + 1; $i++) {
            proof_report_row(920000 + $i, date('Y-m-d', strtotime('2025-01-01 +' . $i . ' days')), 1, true);
        }
    });

    it('returns one row more than a page', function () {
        expect(ProofReport::days(false))->toHaveCount(ProofReport::PER_PAGE + 1);
    });

    it('returns the older days on the next page', function () {
        expect(ProofReport::days(false, 2))->toHaveCount(1);
    });
});

describe('ProofReport::years()', function () {
    beforeEach(function () {
        proof_report_row(930001, '2026-03-01', 1, true);
        proof_report_row(930002, '2026-03-02', 1, false);
        proof_report_row(930003, '2026-03-03', 1, null);
        proof_report_row(930004, '2026-03-04', 1, true);
        proof_report_row(930005, '2026-03-04', 1, false);
        proof_report_row(930006, '2025-01-01', 101, false);
        proof_report_row(930007, '2025-01-01', 1, false);
    });

    it('counts the days in each state for a year', function () {
        expect(ProofReport::years()[0])->toBe(['year' => 2026, 'proof' => 1, 'final' => 1, 'unknown' => 1, 'mixed' => 1, 'total' => 4]);
    });

    it('counts each house on a date as a day', function () {
        expect(ProofReport::years()[1])->toMatchArray(['year' => 2025, 'final' => 2, 'total' => 2]);
    });

    it('lists the newest year first', function () {
        expect(array_column(ProofReport::years(), 'year'))->toBe([2026, 2025]);
    });
});

describe('ProofReport::days() for a year', function () {
    beforeEach(function () {
        proof_report_row(940001, '2026-03-01', 1, true);
        proof_report_row(940002, '2025-12-31', 1, true);
        proof_report_row(940003, '2025-01-01', 1, true);
    });

    it('lists only that year', function () {
        expect(array_column(ProofReport::days(true, 1, 2025), 'date'))->toBe(['2025-12-31', '2025-01-01']);
    });
});

describe('ProofReport::percent()', function () {
    it('gives a share of the whole', function (int $part, int $total, float $percent) {
        expect(ProofReport::percent($part, $total))->toBe($percent);
    })->with([
        'a quarter' => [1, 4, 25.0],
        'a third' => [1, 3, 33.33],
        'nothing held' => [0, 0, 0.0],
    ]);
});

describe('ProofReport::status()', function () {
    it('gives each state a label', function (int $proof, int $final, int $unknown, string $label) {
        expect(ProofReport::status($proof, $final, $unknown))->toBe($label);
    })->with([
        'proof' => [3, 0, 0, 'Proof'],
        'final' => [0, 3, 0, 'Final'],
        'unknown' => [0, 0, 3, 'Unknown'],
        'mixed' => [1, 2, 0, 'Mixed'],
    ]);
});
