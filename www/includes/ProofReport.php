<?php

namespace OpenAustralia\TWFY;

use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\Models\ImportError;

/**
 * The figures behind the admin report on the proof status of each day.
 */
class ProofReport {

    /**
     * The first year the report lists, even when we hold no days in it.
     */
    public const FIRST_YEAR = 2007;

    /**
     * The houses we hold, by the hansard.major value of their debates.
     */
    public const HOUSES = [
        1 => 'Representatives',
        101 => 'Senate',
    ];

    /**
     * The counts for a period with no days.
     */
    private const NO_DAYS = ['proof' => 0, 'final' => 0, 'unknown' => 0, 'mixed' => 0, 'total' => 0, 'errors' => 0, 'last_changed' => null];

    /**
     * One row for each day and house we hold, newest first.
     *
     * @param int|null $year
     *   The year to list, or null for every year.
     * @param int|null $month
     *   The month of that year to list, from 1 to 12, or null for the whole year.
     *
     * @return list<array{date: string, major: int, proof: int, final: int, unknown: int, last_changed: ?string}>
     *   The date, house, the number of rows that are proof, final and unknown, and when a row last changed.
     */
    public static function days(?int $year = null, ?int $month = null): array {
        // Eloquent can't count rows by condition within a group, so the counts are raw SQL.
        $query = Hansard::query()
          ->whereIn('major', array_keys(self::HOUSES))
          ->when($year !== null, fn ($query) => $query->whereBetween('hdate', self::dateRange($year, $month)))
          ->selectRaw('hdate, major, SUM(proof = 1) AS proof_rows, SUM(proof = 0) AS final_rows, SUM(proof IS NULL) AS unknown_rows, MAX(modified) AS last_changed')
          ->groupBy('hdate', 'major')
          ->orderByDesc('hdate')
          ->orderBy('major');

        $rows = [];
        foreach ($query->toBase()->get() as $row) {
            $rows[] = [
                'date' => (string) $row->hdate,
                'major' => (int) $row->major,
                'proof' => (int) $row->proof_rows,
                'final' => (int) $row->final_rows,
                'unknown' => (int) $row->unknown_rows,
                'last_changed' => $row->last_changed === null ? null : (string) $row->last_changed,
            ];
        }
        return $rows;
    }

    /**
     * How many days we hold in each state, for each year from the current year back to FIRST_YEAR, newest first.
     *
     * A year with no days has zeros, and a year before FIRST_YEAR is listed only when we hold days in it.
     * A day is one house's sitting, so a date with both houses counts twice.
     *
     * @return list<array{year: int, proof: int, final: int, unknown: int, mixed: int, total: int, errors: int}>
     *   The year, the number of days that are proof, final, unknown and mixed, and how many days had import errors.
     */
    public static function years(): array {
        $held = array_column(self::summaries('YEAR(hdate)'), null, 'period');
        $listed = array_unique(array_merge(range(max((int) date('Y'), self::FIRST_YEAR), self::FIRST_YEAR), array_keys($held)));
        rsort($listed);

        return array_map(function (int $year) use ($held): array {
            $row = $held[$year] ?? self::NO_DAYS;
            return [
                'year' => $year,
                'proof' => $row['proof'],
                'final' => $row['final'],
                'unknown' => $row['unknown'],
                'mixed' => $row['mixed'],
                'total' => $row['total'],
                'errors' => $row['errors'],
            ];
        }, $listed);
    }

    /**
     * How many days we hold in each state, for each month of a year, December first. A month with no days has zeros.
     *
     * @return list<array{month: int, proof: int, final: int, unknown: int, mixed: int, total: int, errors: int, last_changed: ?string}>
     *   The month, the number of days that are proof, final, unknown and mixed, how many days had import errors, and
     *   when a row last changed.
     */
    public static function months(int $year): array {
        $held = array_column(self::summaries('MONTH(hdate)', $year), null, 'period');
        $months = [];
        for ($month = 12; $month >= 1; $month--) {
            $row = $held[$month] ?? self::NO_DAYS;
            $months[] = [
                'month' => $month,
                'proof' => $row['proof'],
                'final' => $row['final'],
                'unknown' => $row['unknown'],
                'mixed' => $row['mixed'],
                'total' => $row['total'],
                'errors' => $row['errors'],
                'last_changed' => $row['last_changed'],
            ];
        }
        return $months;
    }

    /**
     * The first and last date of a year, or of one month in it.
     *
     * @return array{string, string}
     *   The two dates, as Y-m-d.
     */
    private static function dateRange(int $year, ?int $month): array {
        if ($month === null) {
            return ["$year-01-01", "$year-12-31"];
        }
        $first = sprintf('%04d-%02d-01', $year, $month);
        return [$first, date('Y-m-t', strtotime($first))];
    }

    /**
     * Days in each state for each period, newest period first.
     *
     * @param string $period
     *   SQL for the period of a day, from the code, never from a request.
     * @param int|null $year
     *   Only the days of this year, or null for every year.
     *
     * @return list<array{period: int, proof: int, final: int, unknown: int, mixed: int, total: int, errors: int, last_changed: ?string}>
     *   The period, the number of days that are proof, final, unknown and mixed, and how many days had import errors.
     */
    private static function summaries(string $period, ?int $year = null): array {
        // Eloquent can't count rows by condition within a group, so the counts are raw SQL. The null-safe <=>
        // keeps a day of only unknown rows at 0 proof and 0 final, not NULL.
        $days = Hansard::query()
          ->whereIn('major', array_keys(self::HOUSES))
          ->when($year !== null, fn ($query) => $query->whereBetween('hdate', self::dateRange($year, null)))
          ->selectRaw('hdate, SUM(proof <=> 1) AS p, SUM(proof <=> 0) AS f, SUM(proof IS NULL) AS u, MAX(modified) AS changed')
          ->groupBy('hdate', 'major');
        $rows = $days->getConnection()->query()
          ->fromSub($days->toBase(), 'days')
          ->selectRaw("$period AS period, COUNT(*) AS total, "
            . 'SUM(p > 0 AND f = 0 AND u = 0) AS proof_days, '
            . 'SUM(f > 0 AND p = 0 AND u = 0) AS final_days, '
            . 'SUM(u > 0 AND p = 0 AND f = 0) AS unknown_days, '
            . 'MAX(changed) AS last_changed')
          ->groupBy('period')
          ->orderByDesc('period')
          ->get();

        // A day with several errors, or errors from both stages, counts once.
        $errors = ImportError::query()
          ->when($year !== null, fn ($query) => $query->whereBetween('hdate', self::dateRange($year, null)))
          ->selectRaw("$period AS period, COUNT(DISTINCT hdate, major) AS error_days")
          ->groupBy('period')
          ->pluck('error_days', 'period');

        $summaries = [];
        foreach ($rows as $row) {
            $proof = (int) $row->proof_days;
            $final = (int) $row->final_days;
            $unknown = (int) $row->unknown_days;
            $total = (int) $row->total;
            $summaries[] = [
                'period' => (int) $row->period,
                'proof' => $proof,
                'final' => $final,
                'unknown' => $unknown,
                'mixed' => $total - $proof - $final - $unknown,
                'total' => $total,
                'errors' => (int) ($errors[$row->period] ?? 0),
                'last_changed' => $row->last_changed === null ? null : (string) $row->last_changed,
            ];
        }

        // A day that failed before any of it loaded has errors but no hansard rows, and still counts.
        $listed = array_column($summaries, 'period');
        foreach ($errors as $error_period => $error_days) {
            if (!in_array((int) $error_period, $listed, true)) {
                $summaries[] = ['period' => (int) $error_period, 'proof' => 0, 'final' => 0, 'unknown' => 0, 'mixed' => 0, 'total' => 0, 'errors' => (int) $error_days, 'last_changed' => null];
            }
        }
        usort($summaries, fn (array $a, array $b): int => $b['period'] <=> $a['period']);
        return $summaries;
    }

    /**
     * A share as a percentage, or 0 when there is no whole.
     */
    public static function percent(int $part, int $total): float {
        return $total === 0 ? 0.0 : round($part * 100 / $total, 2);
    }

    /**
     * Whether a day is proof, final, unknown or a mix, from the number of rows in each state.
     */
    public static function status(int $proof, int $final, int $unknown): string {
        if ($proof > 0 && $final === 0 && $unknown === 0) {
            return 'Proof';
        }
        if ($final > 0 && $proof === 0 && $unknown === 0) {
            return 'Final';
        }
        if ($unknown > 0 && $proof === 0 && $final === 0) {
            return 'Unknown';
        }
        return 'Mixed';
    }

}
