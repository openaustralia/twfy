<?php

/**
 * @file
 * The shape of the small line chart of monthly counts.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Account\MonthlyChart;

/**
 * Twelve months ending in November 2026, with these counts.
 *
 * @param list<int> $counts
 *   One for each month, the earliest first.
 *
 * @return array<string, int>
 *   The counts keyed by month.
 */
function twelve_months(array $counts): array {
    $months = [];
    foreach ($counts as $index => $count) {
        $months[date('Y-m', strtotime('2025-12-01 +' . $index . ' months'))] = $count;
    }

    return $months;
}

describe('MonthlyChart::for()', function () {
    beforeEach(function () {
        $this->chart = MonthlyChart::for(twelve_months([3, 0, 4, 6, 2, 5, 7, 0, 4, 6, 3, 12]));
    });

    it('has a point for each month', function () {
        expect($this->chart['points'])->toHaveCount(12);
    });

    it('starts six across', function () {
        expect($this->chart['points'][0]['x'])->toBe(6.0);
    });

    it('ends at the far edge of the line', function () {
        expect($this->chart['lastX'])->toBe(126.0);
    });

    it('puts the busiest month at the top', function () {
        expect($this->chart['lastY'])->toBe(5.0);
    });

    it('puts a month with none on the baseline', function () {
        expect($this->chart['points'][1]['y'])->toBe(33.0);
    });

    it('draws a line through every point', function () {
        expect(substr_count($this->chart['path'], 'L'))->toBe(11);
    });

    it('adds up the months', function () {
        expect($this->chart['total'])->toBe(52);
    });

    it('names the first and last month', function () {
        expect([$this->chart['from'], $this->chart['to']])->toBe(['December 2025', 'November 2026']);
    });

    it('says what each month holds', function () {
        expect($this->chart['points'][0]['label'])->toBe('December 2025: 3 speeches');
    });

    it('says one speech in the singular', function () {
        $chart = MonthlyChart::for(twelve_months([1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]));

        expect($chart['points'][0]['label'])->toBe('December 2025: 1 speech');
    });

    it('describes the chart to screen readers', function () {
        expect($this->chart['label'])->toBe('52 speeches from December 2025 to November 2026. Most in November 2026 (12), fewest in January 2026 (0).');
    });

    it('gives each month a hover area', function () {
        expect($this->chart['points'][0]['left'])->toBe(0.5);
    });

    it('draws a flat line when nobody spoke', function () {
        $chart = MonthlyChart::for(twelve_months(array_fill(0, 12, 0)));

        expect([$chart['total'], $chart['lastY'], $chart['unit']])->toBe([0, 33.0, 'speeches']);
    });
});
