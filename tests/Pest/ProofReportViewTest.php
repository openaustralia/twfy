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
function proof_view_counts(int $final, int $proof, int $mixed, int $unknown, int $errors = 0): array {
    return ['final' => $final, 'proof' => $proof, 'mixed' => $mixed, 'unknown' => $unknown, 'errors' => $errors, 'total' => $final + $proof + $mixed + $unknown + $errors];
}

describe('ProofReportView::segments()', function () {
    it('leaves out Mixed and Errors when no day has them', function () {
        expect(array_keys(ProofReportView::segments([['mixed' => 0, 'errors' => 0]])))->toBe(['final', 'proof', 'unknown']);
    });

    it('keeps Mixed when a day is mixed', function () {
        expect(array_keys(ProofReportView::segments([['mixed' => 0, 'errors' => 0], ['mixed' => 2, 'errors' => 0]])))->toBe(['final', 'proof', 'mixed', 'unknown']);
    });

    it('keeps Errors when a day has errors', function () {
        expect(array_keys(ProofReportView::segments([['mixed' => 0, 'errors' => 1]])))->toBe(['final', 'proof', 'unknown', 'errors']);
    });
});

describe('ProofReportView::icon()', function () {
    it('draws an icon for every segment', function (string $key) {
        expect(ProofReportView::icon($key, 12))->toContain('<svg class="inline-block shrink-0" width="12" height="12"');
    })->with(['final', 'proof', 'mixed', 'unknown', 'errors']);

    it('hides the icon from screen readers', function () {
        expect(ProofReportView::icon('final', 12))->toContain('aria-hidden="true"');
    });
});

describe('ProofReportView::legend()', function () {
    it('names each segment', function () {
        expect(ProofReportView::legend(ProofReportView::segments([['mixed' => 0, 'errors' => 0]])))->toContain('Unknown');
    });

    it('gives each segment an icon', function () {
        expect(ProofReportView::legend(ProofReportView::segments([['mixed' => 0, 'errors' => 0]])))->toContain('<svg');
    });

    it('colours errors red', function () {
        expect(ProofReportView::legend(ProofReportView::segments([['mixed' => 0, 'errors' => 1]])))->toContain('bg-[#e11d48]');
    });
});

describe('ProofReportView::bar()', function () {
    beforeEach(function () {
        $this->segments = ProofReportView::segments([['mixed' => 0, 'errors' => 1]]);
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

    it('draws errors as a red segment', function () {
        expect(ProofReportView::bar(proof_view_counts(1, 0, 0, 0, 1), $this->segments, '2025'))->toContain('<div class="bg-[#e11d48]" style="width:50%');
    });

    it('draws an empty track for no days', function () {
        expect(ProofReportView::bar(proof_view_counts(0, 0, 0, 0), $this->segments, '2010'))->toContain('2010: no sitting days');
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

describe('ProofReportView::errorDays()', function () {
    it('says how many days had errors', function (int $count, string $text) {
        expect(ProofReportView::errorDays($count))->toBe($text);
    })->with([
        'none' => [0, ''],
        'one' => [1, '1 day with errors'],
        'many' => [1234, '1,234 days with errors'],
    ]);
});
