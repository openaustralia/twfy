<?php

/**
 * @file
 * The figures and labels the admin proof report is drawn from.
 */

require_once __DIR__ . '/../../../bootstrap.php';

use OpenAustralia\TWFY\Admin\ProofController;

/**
 * A row of day counts, as ProofReport gives them.
 *
 * @param array<string, int> $counts
 *   Counts to change.
 *
 * @return array<string, int>
 *   The row.
 */
function proof_counts(array $counts = []): array {
    return $counts + ['final' => 0, 'proof' => 0, 'mixed' => 0, 'unknown' => 0, 'errors' => 0, 'total' => 0];
}

describe('ProofController::segments()', function () {
    it('leaves out Mixed and Errors when no day has them', function () {
        expect(array_keys(ProofController::segments([proof_counts(['final' => 2, 'total' => 2])])))->toBe(['final', 'proof', 'unknown']);
    });

    it('keeps Mixed when a day is mixed', function () {
        expect(array_keys(ProofController::segments([proof_counts(['mixed' => 1, 'total' => 1])])))->toContain('mixed');
    });

    it('keeps Errors when a day has errors', function () {
        expect(array_keys(ProofController::segments([proof_counts(['errors' => 1, 'total' => 1])])))->toContain('errors');
    });
});

describe('ProofController::bar()', function () {
    beforeEach(function () {
        $row = proof_counts(['final' => 3, 'proof' => 1, 'total' => 4]);
        $this->bar = ProofController::bar($row, ProofController::segments([$row]), '2026');
    });

    it('sizes a segment by its share', function () {
        expect($this->bar['parts'][0]['width'])->toBe(75.0);
    });

    it('skips a state with no days', function () {
        expect(array_column($this->bar['parts'], 'class'))->toBe(['bg-[#0d9488]', 'bg-[#d97706]']);
    });

    it('describes the bar for screen readers', function () {
        expect($this->bar['label'])->toBe('2026: Final: 3 sitting days (75%), Proof: 1 sitting day (25%)');
    });
});

describe('ProofController::bar() with no days', function () {
    it('has no parts', function () {
        expect(ProofController::bar(proof_counts(), ProofController::SEGMENTS, '2010'))->toBe(['label' => '2010: no sitting days', 'parts' => []]);
    });
});

describe('ProofController::days()', function () {
    it('counts sitting days, plural right', function (int $count, string $text) {
        expect(ProofController::days($count))->toBe($text);
    })->with([[0, '0 sitting days'], [1, '1 sitting day'], [1234, '1,234 sitting days']]);
});

describe('ProofController::errorDays()', function () {
    it('says how many days had errors', function (int $count, string $text) {
        expect(ProofController::errorDays($count))->toBe($text);
    })->with([[0, ''], [1, '1 day with errors'], [2, '2 days with errors']]);
});

describe('ProofController::monthName()', function () {
    it('names a month', function (int $month, string $name) {
        expect(ProofController::monthName($month))->toBe($name);
    })->with([[1, 'January'], [12, 'December']]);
});
