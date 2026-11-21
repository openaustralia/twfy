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
     * @return list<array{date: string, major: int, proof: int, final: int, unknown: int, errors: int, last_changed: ?string}>
     *   The date, house, the number of rows that are proof, final and unknown, how many import errors the day has, and
     *   when a row last changed.
     */
    public static function days(?int $year = null, ?int $month = null): array {
        $range = $year === null ? null : self::dateRange($year, $month);

        // Eloquent can't count rows by condition within a group, so the counts are raw SQL.
        $rows = [];
        $query = Hansard::query()
          ->whereIn('major', array_keys(self::HOUSES))
          ->when($range !== null, fn ($query) => $query->whereBetween('hdate', $range))
          ->selectRaw('hdate, major, SUM(proof = 1) AS proof_rows, SUM(proof = 0) AS final_rows, SUM(proof IS NULL) AS unknown_rows, MAX(modified) AS last_changed')
          ->groupBy('hdate', 'major');
        foreach ($query->toBase()->get() as $row) {
            $rows[$row->hdate . '|' . $row->major] = [
                'date' => (string) $row->hdate,
                'major' => (int) $row->major,
                'proof' => (int) $row->proof_rows,
                'final' => (int) $row->final_rows,
                'unknown' => (int) $row->unknown_rows,
                'errors' => 0,
                'last_changed' => $row->last_changed === null ? null : (string) $row->last_changed,
            ];
        }

        // A day that failed before any of it loaded has errors and no rows, and still gets a row of its own.
        $errors = ImportError::query()
          ->when($range !== null, fn ($query) => $query->whereBetween('hdate', $range))
          ->selectRaw('hdate, major, COUNT(*) AS error_count')
          ->groupBy('hdate', 'major');
        foreach ($errors->toBase()->get() as $row) {
            $key = $row->hdate . '|' . $row->major;
            $rows[$key] ??= ['date' => (string) $row->hdate, 'major' => (int) $row->major, 'proof' => 0, 'final' => 0, 'unknown' => 0, 'errors' => 0, 'last_changed' => null];
            $rows[$key]['errors'] = (int) $row->error_count;
        }

        usort($rows, fn (array $a, array $b): int => [$b['date'], $a['major']] <=> [$a['date'], $b['major']]);
        return $rows;
    }

    /**
     * The import errors recorded for a day and house, parser errors first.
     *
     * @return list<array{source: string, message: string, created_at: ?string}>
     *   Where each came from, what it said, and when it was recorded.
     */
    public static function errorsForDay(string $date, int $major): array {
        $errors = [];
        foreach (ImportError::where('hdate', $date)->where('major', $major)->orderByDesc('source')->orderBy('id')->get() as $error) {
            $errors[] = ['source' => $error->source, 'message' => $error->message, 'created_at' => $error->created_at];
        }
        return $errors;
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
     *   The period, the number of days that are proof, final, unknown and mixed, how many days had import errors, and
     *   the total of all of them. A day with errors counts as an error day, whatever its rows say.
     */
    private static function summaries(string $period, ?int $year = null): array {
        // Eloquent can't count rows by condition within a group, so the counts are raw SQL. The null-safe <=>
        // keeps a day of only unknown rows at 0 proof and 0 final, not NULL.
        $days = Hansard::query()
          ->whereIn('major', array_keys(self::HOUSES))
          ->when($year !== null, fn ($query) => $query->whereBetween('hdate', self::dateRange($year, null)))
          ->selectRaw('hdate, major, SUM(proof <=> 1) AS p, SUM(proof <=> 0) AS f, SUM(proof IS NULL) AS u, MAX(modified) AS changed')
          ->groupBy('hdate', 'major');
        $failed = ImportError::query()->selectRaw('DISTINCT hdate AS error_date, major AS error_major');
        $rows = $days->getConnection()->query()
          ->fromSub($days->toBase(), 'days')
          ->leftJoinSub($failed->toBase(), 'failed', fn ($join) => $join->on('failed.error_date', '=', 'days.hdate')->on('failed.error_major', '=', 'days.major'))
          ->selectRaw("$period AS period, COUNT(*) AS total, "
            . 'SUM(failed.error_date IS NOT NULL) AS error_days, '
            . 'SUM(failed.error_date IS NULL AND p > 0 AND f = 0 AND u = 0) AS proof_days, '
            . 'SUM(failed.error_date IS NULL AND f > 0 AND p = 0 AND u = 0) AS final_days, '
            . 'SUM(failed.error_date IS NULL AND u > 0 AND p = 0 AND f = 0) AS unknown_days, '
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
            $with_rows = (int) $row->error_days;
            $errored = (int) ($errors[$row->period] ?? 0);
            // Days with errors and no rows of their own are in the error count but not in the rows counted above.
            $total = (int) $row->total + $errored - $with_rows;
            $summaries[] = [
                'period' => (int) $row->period,
                'proof' => $proof,
                'final' => $final,
                'unknown' => $unknown,
                'mixed' => (int) $row->total - $with_rows - $proof - $final - $unknown,
                'total' => $total,
                'errors' => $errored,
                'last_changed' => $row->last_changed === null ? null : (string) $row->last_changed,
            ];
        }

        // A day that failed before any of it loaded has errors but no hansard rows, and still counts.
        $listed = array_column($summaries, 'period');
        foreach ($errors as $error_period => $error_days) {
            if (!in_array((int) $error_period, $listed, true)) {
                $summaries[] = ['period' => (int) $error_period, 'proof' => 0, 'final' => 0, 'unknown' => 0, 'mixed' => 0, 'total' => (int) $error_days, 'errors' => (int) $error_days, 'last_changed' => null];
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
     * Whether a day has errors, is proof, final, unknown, a mix or not loaded at all, from the number of rows in each
     * state. A day with import errors is an error day, whatever its rows say.
     */
    public static function status(int $proof, int $final, int $unknown, int $errors = 0): string {
        if ($errors > 0) {
            return 'Errors';
        }
        if ($proof + $final + $unknown === 0) {
            return 'Not loaded';
        }
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
