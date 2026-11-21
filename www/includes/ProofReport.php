<?php

namespace OpenAustralia\TWFY;

use OpenAustralia\TWFY\Models\Hansard;

/**
 * The figures behind the admin report on the proof status of each day.
 */
class ProofReport {

    public const PER_PAGE = 100;

    /**
     * The houses we hold, by the hansard.major value of their debates.
     */
    public const HOUSES = [
        1 => 'Representatives',
        101 => 'Senate',
    ];

    /**
     * One row for each day and house we hold, newest first.
     *
     * Asks for one row more than PER_PAGE, so the caller can tell whether there is another page.
     *
     * @param bool $all
     *   True to list every day, false for only the days not yet known to be final.
     * @param int $page
     *   The page to list, starting at 1.
     * @param int|null $year
     *   The year to list, or null for every year.
     *
     * @return list<array{date: string, major: int, proof: int, final: int, unknown: int, last_changed: ?string}>
     *   The date, house, the number of rows that are proof, final and unknown, and when a row last changed.
     */
    public static function days(bool $all, int $page = 1, ?int $year = null): array {
        // Eloquent can't count rows by condition within a group, so the counts are raw SQL.
        $query = Hansard::query()
          ->whereIn('major', array_keys(self::HOUSES))
          ->when($year !== null, fn ($query) => $query->whereBetween('hdate', ["$year-01-01", "$year-12-31"]))
          ->selectRaw('hdate, major, SUM(proof = 1) AS proof_rows, SUM(proof = 0) AS final_rows, SUM(proof IS NULL) AS unknown_rows, MAX(modified) AS last_changed')
          ->groupBy('hdate', 'major')
          ->orderByDesc('hdate')
          ->orderBy('major')
          ->offset((max($page, 1) - 1) * self::PER_PAGE)
          ->limit(self::PER_PAGE + 1);
        if (!$all) {
            $query->havingRaw('SUM(proof = 1 OR proof IS NULL) > 0');
        }

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
     * How many days we hold in each state, for each year, newest year first.
     *
     * A day is one house's sitting, so a date with both houses counts twice.
     *
     * @return list<array{year: int, proof: int, final: int, unknown: int, mixed: int, total: int}>
     *   The year and the number of days that are proof, final, unknown and mixed.
     */
    public static function years(): array {
        // Eloquent can't count rows by condition within a group, so the counts are raw SQL. The null-safe <=>
        // keeps a day of only unknown rows at 0 proof and 0 final, not NULL.
        $days = Hansard::query()
          ->whereIn('major', array_keys(self::HOUSES))
          ->selectRaw('hdate, SUM(proof <=> 1) AS p, SUM(proof <=> 0) AS f, SUM(proof IS NULL) AS u')
          ->groupBy('hdate', 'major');
        $rows = $days->getConnection()->query()
          ->fromSub($days->toBase(), 'days')
          ->selectRaw('YEAR(hdate) AS year, COUNT(*) AS total, '
            . 'SUM(p > 0 AND f = 0 AND u = 0) AS proof_days, '
            . 'SUM(f > 0 AND p = 0 AND u = 0) AS final_days, '
            . 'SUM(u > 0 AND p = 0 AND f = 0) AS unknown_days')
          ->groupBy('year')
          ->orderByDesc('year')
          ->get();

        $years = [];
        foreach ($rows as $row) {
            $proof = (int) $row->proof_days;
            $final = (int) $row->final_days;
            $unknown = (int) $row->unknown_days;
            $total = (int) $row->total;
            $years[] = [
                'year' => (int) $row->year,
                'proof' => $proof,
                'final' => $final,
                'unknown' => $unknown,
                'mixed' => $total - $proof - $final - $unknown,
                'total' => $total,
            ];
        }
        return $years;
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
