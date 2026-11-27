<?php

namespace OpenAustralia\TWFY\Account;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The shape of a small line chart of twelve monthly counts, drawn on a 132 by 36 grid.
 */
final class MonthlyChart {

    /**
     * Works out the line, the dots and the words for a chart.
     *
     * @param array<string, int> $months
     *   Counts keyed by month as "2026-07", the earliest first.
     *
     * @return array{path: string, lastX: float, lastY: float, total: int, unit: string, from: string, to: string, label: string, points: list<array{x: float, y: float, left: float, label: string}>}
     *   The path of the line, where its last dot goes, the total and what it counts, the first and last month in
     *   words, a description for screen readers, and a hover area for each month.
     */
    public static function for(array $months): array {
        $counts = array_values($months);
        $names = array_map(fn (string $month): string => Carbon::parse("$month-01")->format('F Y'), array_keys($months));
        $max = max(1, ...$counts);
        $step = 120 / (count($counts) - 1);

        $points = [];
        foreach ($counts as $index => $count) {
            $x = 6 + $index * $step;
            $points[] = [
                'x' => round($x, 1),
                'y' => round(33 - $count / $max * 28, 1),
                'left' => round($x - 5.5, 1),
                'label' => $names[$index] . ': ' . $count . ' ' . Str::plural('speech', $count),
            ];
        }

        $total = array_sum($counts);
        $most = array_search(max($counts), $counts, true);
        $fewest = array_search(min($counts), $counts, true);
        $last = end($points);

        return [
            'path' => 'M' . implode(' L', array_map(fn (array $point): string => $point['x'] . ' ' . $point['y'], $points)),
            'lastX' => $last['x'],
            'lastY' => $last['y'],
            'total' => $total,
            'unit' => Str::plural('speech', $total),
            'from' => $names[0],
            'to' => end($names),
            'label' => "$total " . Str::plural('speech', $total) . " from $names[0] to " . end($names) . ". Most in $names[$most] ($counts[$most]), fewest in $names[$fewest] ($counts[$fewest]).",
            'points' => $points,
        ];
    }

}
