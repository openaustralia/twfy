<?php

/**
 * @file
 * The admin report on the proof status of each day. Uses fictional gids and dates.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\ImportErrors;
use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\Models\ImportError;
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
        $days = ProofReport::days();

        expect($days[0])->toMatchArray(['date' => '2026-03-01', 'major' => 1, 'proof' => 1, 'final' => 1, 'unknown' => 1]);
    });

    it('reports when a row last changed', function () {
        expect(ProofReport::days()[0]['last_changed'])->toBe('2026-03-05 09:00:00');
    });

    it('lists newest days first', function () {
        $dates = array_column(ProofReport::days(), 'date');

        expect($dates)->toBe(['2026-03-01', '2026-03-01', '2026-02-01']);
    });

    it('omits other houses', function () {
        expect(array_unique(array_column(ProofReport::days(), 'major')))->toBe([1, 101]);
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
        expect(array_column(ProofReport::years(), null, 'year')[2026])->toBe(['year' => 2026, 'proof' => 1, 'final' => 1, 'unknown' => 1, 'mixed' => 1, 'total' => 4, 'errors' => 0]);
    });

    it('counts each house on a date as a day', function () {
        expect(array_column(ProofReport::years(), null, 'year')[2025])->toMatchArray(['year' => 2025, 'final' => 2, 'total' => 2]);
    });

    it('lists years back to 2007', function () {
        expect(array_column(ProofReport::years(), 'year'))->toBe(range(max(2026, (int) date('Y')), 2007));
    });

    it('gives a year with no days zeros', function () {
        expect(array_column(ProofReport::years(), null, 'year')[2010])->toBe(['year' => 2010, 'proof' => 0, 'final' => 0, 'unknown' => 0, 'mixed' => 0, 'total' => 0, 'errors' => 0]);
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
        expect(array_column(ProofReport::months(2026), null, 'month')[3])->toBe(['month' => 3, 'proof' => 1, 'final' => 1, 'unknown' => 0, 'mixed' => 0, 'total' => 2, 'errors' => 0, 'last_changed' => '2026-03-09 09:00:00']);
    });

    it('lists every month, December first', function () {
        expect(array_column(ProofReport::months(2026), 'month'))->toBe([12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1]);
    });

    it('gives a month with no days zeros', function () {
        expect(array_column(ProofReport::months(2026), null, 'month')[7])->toBe(['month' => 7, 'proof' => 0, 'final' => 0, 'unknown' => 0, 'mixed' => 0, 'total' => 0, 'errors' => 0, 'last_changed' => null]);
    });

    it('leaves out other years', function () {
        expect(array_sum(array_column(ProofReport::months(2026), 'total')))->toBe(3);
    });
});

describe('Import errors in the counts', function () {
    beforeEach(function () {
        proof_report_row(990001, '2026-03-01', 1, true);
        proof_report_row(990002, '2026-03-02', 1, true);
        ImportErrors::record('2026-03-01', 1, 'parser', 'First');
        ImportErrors::record('2026-03-01', 1, 'parser', 'Second');
        ImportErrors::record('2026-03-01', 1, 'loader', 'Third');
        ImportErrors::record('2026-03-02', 101, 'loader', 'Fourth');
    });

    it('counts a day once whatever its errors', function () {
        expect(array_column(ProofReport::months(2026), null, 'month')[3]['errors'])->toBe(2);
    });

    it('counts the days with errors for a year', function () {
        expect(array_column(ProofReport::years(), null, 'year')[2026]['errors'])->toBe(2);
    });

    it('counts none for a year without errors', function () {
        expect(array_column(ProofReport::years(), null, 'year')[2025]['errors'])->toBe(0);
    });

    it('counts a day that has errors and no rows', function () {
        ImportErrors::record('2026-06-10', 1, 'parser', 'Failed before loading');

        expect(array_column(ProofReport::months(2026), null, 'month')[6])->toMatchArray(['total' => 1, 'errors' => 1]);
    });

    it('counts an errors-only year', function () {
        ImportErrors::record('2012-05-08', 1, 'loader', 'Failed before loading');

        expect(array_column(ProofReport::years(), null, 'year')[2012])->toMatchArray(['total' => 1, 'errors' => 1]);
    });

    it('counts a day with errors as an error day only', function () {
        expect(array_column(ProofReport::months(2026), null, 'month')[3])->toMatchArray(['proof' => 1, 'final' => 0, 'errors' => 2, 'total' => 3]);
    });

    it('leaves the days without errors in their states', function () {
        proof_report_row(990003, '2026-03-05', 1, false);

        expect(array_column(ProofReport::months(2026), null, 'month')[3])->toMatchArray(['proof' => 1, 'final' => 1, 'errors' => 2, 'total' => 4]);
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
        expect(array_column(ProofReport::days(2026, 3), 'date'))->toBe(['2026-03-31', '2026-03-01']);
    });
});

describe('ProofReport::days() for a year', function () {
    beforeEach(function () {
        proof_report_row(940001, '2026-03-01', 1, true);
        proof_report_row(940002, '2025-12-31', 1, true);
        proof_report_row(940003, '2025-01-01', 1, true);
    });

    it('lists only that year', function () {
        expect(array_column(ProofReport::days(2025), 'date'))->toBe(['2025-12-31', '2025-01-01']);
    });
});

describe('ProofReport::days() with errors', function () {
    beforeEach(function () {
        proof_report_row(991001, '2026-03-01', 1, true);
        proof_report_row(991002, '2026-03-02', 1, false);
        ImportErrors::record('2026-03-01', 1, 'parser', 'First');
        ImportErrors::record('2026-03-01', 1, 'loader', 'Second');
        ImportErrors::record('2026-03-10', 101, 'parser', 'Failed before loading');
        $this->days = array_column(ProofReport::days(2026, 3), null, 'date');
    });

    it('counts the errors on a day', function () {
        expect($this->days['2026-03-01']['errors'])->toBe(2);
    });

    it('gives a day without errors none', function () {
        expect($this->days['2026-03-02']['errors'])->toBe(0);
    });

    it('lists a day that has errors and no rows', function () {
        expect($this->days['2026-03-10'])->toMatchArray(['major' => 101, 'proof' => 0, 'final' => 0, 'unknown' => 0, 'errors' => 1]);
    });

    it('leaves the errors of other months out', function () {
        ImportErrors::record('2026-04-10', 1, 'parser', 'Another month');

        expect(array_column(ProofReport::days(2026, 3), 'date'))->not->toContain('2026-04-10');
    });
});

describe('ProofReport::errorsForDay()', function () {
    beforeEach(function () {
        ImportErrors::record('2026-03-01', 1, 'loader', 'From the loader');
        ImportErrors::record('2026-03-01', 1, 'parser', 'From the parser');
        ImportErrors::record('2026-03-01', 101, 'parser', 'Other house');
        ImportErrors::record('2026-03-02', 1, 'parser', 'Other day');
        $this->errors = ProofReport::errorsForDay('2026-03-01', 1);
    });

    it('gives the messages for that day and house', function () {
        expect(array_column($this->errors, 'message'))->toBe(['From the parser', 'From the loader']);
    });

    it('says where each came from', function () {
        expect(array_column($this->errors, 'source'))->toBe(['parser', 'loader']);
    });

    it('gives none for a day without errors', function () {
        expect(ProofReport::errorsForDay('2026-03-05', 1))->toBe([]);
    });
});

describe('ProofReport::errorsForDay() times', function () {
    beforeEach(function () {
        $this->zone = date_default_timezone_get();
        date_default_timezone_set('Australia/Sydney');
        ImportErrors::record('2026-03-01', 1, 'loader', 'Bad time');
        ImportError::query()->update(['created_at' => '2026-03-01 00:30:00']);
    });

    afterEach(function () {
        date_default_timezone_set($this->zone);
    });

    it('shows when it was recorded in the site\'s time zone', function () {
        expect(ProofReport::errorsForDay('2026-03-01', 1)[0]['created_at'])->toBe('2026-03-01 11:30:00 AEDT');
    });

    it('follows daylight saving', function () {
        ImportError::query()->update(['created_at' => '2026-07-01 00:30:00']);

        expect(ProofReport::errorsForDay('2026-03-01', 1)[0]['created_at'])->toBe('2026-07-01 10:30:00 AEST');
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
    it('calls a day with errors Errors', function () {
        expect(ProofReport::status(3, 0, 0, 1))->toBe('Errors');
    });

    it('gives each state a label', function (int $proof, int $final, int $unknown, string $label) {
        expect(ProofReport::status($proof, $final, $unknown))->toBe($label);
    })->with([
        'proof' => [3, 0, 0, 'Proof'],
        'final' => [0, 3, 0, 'Final'],
        'unknown' => [0, 0, 3, 'Unknown'],
        'mixed' => [1, 2, 0, 'Mixed'],
        'not loaded' => [0, 0, 0, 'Not loaded'],

    ]);
});
