<?php

/**
 * @file
 * The pieces of the admin proof report's HTML that need no database.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenAustralia\TWFY\ProofReportView;

/**
 * A row of counts like the ones the report builds, for a bar.
 *
 * @return array<string, int>
 */
function proof_view_counts(int $final, int $proof, int $mixed, int $unknown): array {
    return ['final' => $final, 'proof' => $proof, 'mixed' => $mixed, 'unknown' => $unknown, 'total' => $final + $proof + $mixed + $unknown];
}

describe('ProofReportView::segments()', function () {
    it('leaves out Mixed when no day is mixed', function () {
        expect(array_keys(ProofReportView::segments([['mixed' => 0]])))->toBe(['final', 'proof', 'unknown']);
    });

    it('keeps Mixed when a day is mixed', function () {
        expect(array_keys(ProofReportView::segments([['mixed' => 0], ['mixed' => 2]])))->toBe(['final', 'proof', 'mixed', 'unknown']);
    });
});

describe('ProofReportView::legend()', function () {
    it('names each segment', function () {
        expect(ProofReportView::legend(ProofReportView::segments([['mixed' => 0]])))->toContain('Unknown');
    });
});

describe('ProofReportView::bar()', function () {
    beforeEach(function () {
        $this->segments = ProofReportView::segments([['mixed' => 0]]);
    });

    it('sizes a segment by its share', function () {
        expect(ProofReportView::bar(proof_view_counts(1, 1, 0, 2), $this->segments, '2025'))->toContain('width:25%');
    });

    it('skips a state with no days', function () {
        expect(ProofReportView::bar(proof_view_counts(2, 0, 0, 2), $this->segments, '2025'))->not->toContain('Proof:');
    });

    it('describes the bar for screen readers', function () {
        expect(ProofReportView::bar(proof_view_counts(1, 1, 0, 2), $this->segments, '2025'))->toContain('aria-label="2025: Final: 1 sitting day (25%)');
    });

    it('draws an empty track for no days', function () {
        expect(ProofReportView::bar(proof_view_counts(0, 0, 0, 0), $this->segments, '2010'))->toContain('2010: no sitting days');
    });
});

describe('ProofReportView::link()', function () {
    it('leaves out parameters with no value', function () {
        expect(ProofReportView::link('proof_year.php', ['year' => 2026, 'month' => null, 'all' => '']))->toBe('/admin/proof_year.php?year=2026');
    });

    it('keeps several parameters', function () {
        expect(ProofReportView::link('proof_year.php', ['year' => 2026, 'month' => 3]))->toBe('/admin/proof_year.php?year=2026&month=3');
    });

    it('adds no query when there are none', function () {
        expect(ProofReportView::link('proof.php'))->toBe('/admin/proof.php');
    });
});

describe('ProofReportView::breadcrumbs()', function () {
    beforeEach(function () {
        $this->html = ProofReportView::breadcrumbs([
            ['Admin', '/admin/'],
            ['Year', ProofReportView::link('proof_year.php', ['year' => 2025, 'month' => 3])],
            ['March', null],
        ]);
    });

    it('links every step but the last', function () {
        expect($this->html)->toContain('href="/admin/"');
    });

    it('marks the last step as the current page', function () {
        expect($this->html)->toContain('aria-current="page">March');
    });

    it('escapes an address once', function () {
        expect($this->html)->toContain('year=2025&amp;month=3"');
    });

    it('does not escape an address twice', function () {
        expect($this->html)->not->toContain('&amp;amp;');
    });
});

describe('ProofReportView::days()', function () {
    it('counts sitting days, plural right', function (int $count, string $text) {
        expect(ProofReportView::days($count))->toBe($text);
    })->with([
        'none' => [0, '0 sitting days'],
        'one' => [1, '1 sitting day'],
        'thousands' => [1234, '1,234 sitting days'],
    ]);
});

describe('ProofReportView::monthName()', function () {
    it('names a month', function (int $month, string $name) {
        expect(ProofReportView::monthName($month))->toBe($name);
    })->with([
        'January' => [1, 'January'],
        'December' => [12, 'December'],
    ]);
});
