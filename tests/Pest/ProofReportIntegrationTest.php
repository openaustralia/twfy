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

    it('omits all-final days by default', function () {
        $keys = array_map(fn (array $day): string => $day['date'] . '/' . $day['major'], ProofReport::days(false));

        expect($keys)->toBe(['2026-03-01/1', '2026-02-01/1']);
    });

    it('omits other houses', function () {
        expect(array_unique(array_column(ProofReport::days(true), 'major')))->toBe([1, 101]);
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

    it('counts days by state for a year', function () {
        expect(array_column(ProofReport::years(), null, 'year')[2026])->toBe(['year' => 2026, 'proof' => 1, 'final' => 1, 'unknown' => 1, 'mixed' => 1, 'total' => 4]);
    });

    it('counts each house on a date as a day', function () {
        expect(array_column(ProofReport::years(), null, 'year')[2025])->toMatchArray(['year' => 2025, 'final' => 2, 'total' => 2]);
    });

    it('lists years back to 2007', function () {
        expect(array_column(ProofReport::years(), 'year'))->toBe(range(max(2026, (int) date('Y')), 2007));
    });

    it('gives a year with no days zeros', function () {
        expect(array_column(ProofReport::years(), null, 'year')[2010])->toBe(['year' => 2010, 'proof' => 0, 'final' => 0, 'unknown' => 0, 'mixed' => 0, 'total' => 0]);
    });

    it('lists a pre - 2007 year if it has days', function () {
        proof_report_row(930099, '2006-02-07', 1, false);
        expect(array_column(ProofReport::years(), 'year'))->toContain(2006);
    });
});

describe('ProofReport::months()', function () {
    beforeEach(function () {
        proof_report_row(950001, '2026-03-01', 1, true, '2026-03-05 09:00:00');
        proof_report_row(950002, '2026-03-02', 1, false, '2026-03-09 09:00:00');
        proof_report_row(950003, '2026-02-10', 1, null);
        proof_report_row(950004, '2025-03-01', 1, false);
    });

    it('counts days by state for a month', function () {
        expect(array_column(ProofReport::months(2026), null, 'month')[3])->toBe(['month' => 3, 'proof' => 1, 'final' => 1, 'unknown' => 0, 'mixed' => 0, 'total' => 2, 'last_changed' => '2026-03-09 09:00:00']);
    });

    it('lists every month, December first', function () {
        expect(array_column(ProofReport::months(2026), 'month'))->toBe([12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1]);
    });

    it('gives a month with no days zeros', function () {
        expect(array_column(ProofReport::months(2026), null, 'month')[7])->toBe(['month' => 7, 'proof' => 0, 'final' => 0, 'unknown' => 0, 'mixed' => 0, 'total' => 0, 'last_changed' => null]);
    });

    it('leaves out other years', function () {
        expect(array_sum(array_column(ProofReport::months(2026), 'total')))->toBe(3);
    });
});

describe('ProofReport::days() for a month', function () {
    beforeEach(function () {
        proof_report_row(960001, '2026-02-28', 1, true);
        proof_report_row(960002, '2026-03-01', 1, true);
        proof_report_row(960003, '2026-03-31', 1, true);
        proof_report_row(960004, '2026-04-01', 1, true);
    });

    it('lists only that month', function () {
        expect(array_column(ProofReport::days(true, 2026, 3), 'date'))->toBe(['2026-03-31', '2026-03-01']);
    });
});

describe('ProofReport::days() for a year', function () {
    beforeEach(function () {
        proof_report_row(940001, '2026-03-01', 1, true);
        proof_report_row(940002, '2025-12-31', 1, true);
        proof_report_row(940003, '2025-01-01', 1, true);
    });

    it('lists only that year', function () {
        expect(array_column(ProofReport::days(true, 2025), 'date'))->toBe(['2025-12-31', '2025-01-01']);
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
