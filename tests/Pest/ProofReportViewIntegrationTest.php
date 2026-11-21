<?php

/**
 * @file
 * The admin proof report's pages as HTML, built from rows in the database. Uses fictional gids and dates.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\ImportErrors;
use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\ProofReportView;

uses(TransactionalTestCase::class);

/**
 * Creates one hansard row for a day, house and proof value.
 */
function proof_view_row(int $id, string $date, int $major, ?bool $proof): void {
    Hansard::create([
        'epobject_id' => $id,
        'gid' => "uk.org.publicwhip/debate/$date.$id.1",
        'major' => $major,
        'hdate' => $date,
        'proof' => $proof,
        'modified' => '2026-03-02 10:00:00',
    ]);
}

describe('ProofReportView::overview()', function () {
    beforeEach(function () {
        proof_view_row(970001, '2026-03-01', 1, true);
        $this->html = ProofReportView::overview();
    });

    it('links a year that has days', function () {
        expect($this->html)->toContain('href="/admin/proof_year.php?year=2026"');
    });

    it('greys out a year with no days', function () {
        expect($this->html)->toContain('<span class="w-14 text-sm text-slate-400">2010</span>');
    });

    it('lists years back to 2007', function () {
        expect($this->html)->toContain('>2007</span>');
    });

    it('shows a year\'s days with errors', function () {
        ImportErrors::record('2026-03-01', 1, 'loader', 'Bad time');

        expect(ProofReportView::overview())->toContain('1 day with errors');
    });

    it('shows where you are', function () {
        expect($this->html)->toContain('aria-current="page">Import status');
    });
});

describe('ProofReportView::year()', function () {
    beforeEach(function () {
        proof_view_row(980001, '2026-03-01', 1, true);
        proof_view_row(980002, '2026-03-02', 1, false);
        proof_view_row(980003, '2026-03-03', 101, null);
        proof_view_row(980004, '2026-04-01', 1, false);
        proof_view_row(980005, '2026-05-01', 1, true);
        proof_view_row(980006, '2026-05-01', 1, false);
    });

    it('lists every month', function () {
        expect(ProofReportView::year(2026, 0))->toContain('>December</span>');
    });

    it('links a month that has days', function () {
        expect(ProofReportView::year(2026, 0))->toContain('year=2026&amp;month=3#days');
    });

    it('greys out a month with no days', function () {
        expect(ProofReportView::year(2026, 0))->toContain('<span class="text-slate-400">July</span>');
    });

    it('shows no days until a month is chosen', function () {
        expect(ProofReportView::year(2026, 0))->not->toContain('id="days"');
    });

    it('ignores a month outside 1 to 12', function () {
        expect(ProofReportView::year(2026, 13))->not->toContain('id="days"');
    });

    it('falls back to the newest year', function () {
        expect(ProofReportView::year(1999, 0))->toContain('aria-current="page">' . max(2026, (int) date('Y')));
    });

    it('links the newer year on the left', function () {
        expect(ProofReportView::year(2020, 0))->toContain('&larr; 2021');
    });

    it('links the older year on the right', function () {
        expect(ProofReportView::year(2020, 0))->toContain('2019 &rarr;');
    });

    it('has no newer link for the newest year', function () {
        expect(ProofReportView::year(max(2026, (int) date('Y')), 0))->toContain('<span></span>');
    });

    it('marks the chosen month', function () {
        expect(ProofReportView::year(2026, 3))->toContain('aria-current="true"');
    });

    it('lists the days not yet known to be final', function () {
        expect(ProofReportView::year(2026, 3))->toContain('?d=2026-03-01');
    });

    it('lists the final days too', function () {
        expect(ProofReportView::year(2026, 3))->toContain('?d=2026-03-02');
    });

    it('has no filter toggle', function () {
        expect(ProofReportView::year(2026, 3))->not->toContain('Every day');
    });

    it('links a Senate day to the Senate', function () {
        expect(ProofReportView::year(2026, 3))->toContain('/senate/?d=2026-03-03');
    });

    it('shows a day of mixed rows as mixed', function () {
        expect(ProofReportView::year(2026, 5))->toContain('bg-fuchsia-100');
    });

    it('has a column for days with errors', function () {
        expect(ProofReportView::year(2026, 0))->toContain('>Days with errors</th>');
    });

    it('shows a month\'s days with errors in red', function () {
        ImportErrors::record('2026-03-01', 1, 'loader', 'Bad time');

        expect(ProofReportView::year(2026, 0))->toContain('font-semibold text-rose-700">1</td>');
    });

    it('says when a month has no days', function () {
        expect(ProofReportView::year(2026, 7))->toContain('We hold no sitting days in this month.');
    });
});

describe('ProofReportView::year() months table', function () {
    it('has no second errors column', function () {
        proof_view_row(996001, '2026-03-01', 1, true);
        ImportErrors::record('2026-03-01', 1, 'loader', 'Bad time');

        expect(ProofReportView::year(2026, 0))->not->toContain('>Errors</th>');
    });
});

describe('ProofReportView::year() days with errors', function () {
    beforeEach(function () {
        proof_view_row(995001, '2026-03-01', 1, true);
        proof_view_row(995002, '2026-03-02', 1, false);
        ImportErrors::record('2026-03-01', 1, 'loader', 'Bad time');
        ImportErrors::record('2026-03-01', 1, 'parser', 'Bad entity');
        ImportErrors::record('2026-03-10', 101, 'parser', 'Failed before loading');
        $this->html = ProofReportView::year(2026, 3);
    });

    it('links a day to its errors', function () {
        expect($this->html)->toContain('href="/admin/proof_errors.php?date=2026-03-01&amp;house=representatives"');
    });

    it('counts a day\'s errors in the link', function () {
        expect($this->html)->toContain('2 errors</a>');
    });

    it('says one error in the singular', function () {
        expect($this->html)->toContain('1 error</a>');
    });

    it('marks a day with errors with an icon', function () {
        expect($this->html)->toContain('!no-underline hover:!underline" href="/admin/proof_errors.php?date=2026-03-01&amp;house=representatives"><svg');
    });

    it('tints a row with errors pale red', function () {
        expect($this->html)->toContain('bg-rose-50 hover:bg-rose-100');
    });

    it('leaves a row without errors striped', function () {
        expect($this->html)->toContain('even:bg-slate-50 hover:bg-teal-50');
    });

    it('lists a day with errors and no rows', function () {
        expect($this->html)->toContain('house=senate');
    });

    it('labels a day with errors as Errors', function () {
        expect($this->html)->toContain('bg-rose-100 text-rose-800"><svg');
    });

    it('labels a day with no rows or errors as not loaded', function () {
        expect(ProofReportView::year(2026, 3))->not->toContain('>Not loaded</span>');
    });
});

describe('ProofReportView::errors()', function () {
    beforeEach(function () {
        ImportErrors::record('2022-03-29', 1, 'loader', 'Incorrect <b>time</b> value');
        $this->html = ProofReportView::errors('2022-03-29', 'representatives');
    });

    it('names the day and house', function () {
        expect($this->html)->toContain('Import errors for 2022-03-29, Representatives');
    });

    it('shows the message', function () {
        expect($this->html)->toContain('Incorrect &lt;b&gt;time&lt;/b&gt; value');
    });

    it('says who found it', function () {
        expect($this->html)->toContain('>Loader</td>');
    });

    it('links back to the month', function () {
        expect($this->html)->toContain('href="/admin/proof_year.php?year=2022&amp;month=3#days"');
    });

    it('links the day on Parliament House', function () {
        expect($this->html)->toContain('Dataset%3Ahansardr,hansardr80%20Date%3A29%2F3%2F2022;');
    });

    it('links the original XML', function () {
        expect($this->html)->toContain('href="https://data.openaustralia.org.au/origxml/representatives_debates/2022-03-29.xml"');
    });

    it('links the parsed XML', function () {
        expect($this->html)->toContain('href="https://data.openaustralia.org.au/scrapedxml/representatives_debates/2022-03-29.xml"');
    });

    it('links a Senate day to the Senate files', function () {
        expect(ProofReportView::errors('2022-03-29', 'senate'))->toContain('/origxml/senate_debates/2022-03-29.xml');
    });

    it('says when a day has no errors', function () {
        expect(ProofReportView::errors('2022-03-30', 'senate'))->toContain('No errors are recorded for this day.');
    });

    it('turns away a date that is not a day', function () {
        expect(ProofReportView::errors('2022-02-30', 'senate'))->toContain('That is not a day and house');
    });

    it('turns away an unknown house', function () {
        expect(ProofReportView::errors('2022-03-29', 'lords'))->toContain('That is not a day and house');
    });

    it('turns away text in place of a date', function () {
        expect(ProofReportView::errors('<script>', 'senate'))->not->toContain('<script>');
    });
});
