<?php

/**
 * @file
 * The admin proof report's pages as HTML, built from rows in the database. Uses fictional gids and dates.
 */

require_once __DIR__ . '/../bootstrap.php';

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

    it('shows where you are', function () {
        expect($this->html)->toContain('aria-current="page">Proof');
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
        expect(ProofReportView::year(2026, 5))->toContain('bg-rose-100');
    });

    it('says when a month has no days', function () {
        expect(ProofReportView::year(2026, 7))->toContain('We hold no sitting days in this month.');
    });
});
