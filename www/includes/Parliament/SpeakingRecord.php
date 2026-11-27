<?php

namespace OpenAustralia\TWFY\Parliament;

use Illuminate\Support\Carbon;
use OpenAustralia\TWFY\Models\Hansard;

/**
 * How often a person has spoken in Hansard, year by year and month by month.
 */
final class SpeakingRecord {

    /**
     * The hansard htype of a speech.
     */
    private const SPEECH = 12;

    private const MOST_YEARS = 9;

    /**
     * Speeches in each of the last twelve months, this month included.
     *
     * @return array<string, int>
     *   Counts keyed by month as "2026-07", the earliest first. A month with none is 0.
     */
    public static function lastTwelveMonths(Person $person): array {
        return self::lastTwelveMonthsFor([$person])[$person->id];
    }

    /**
     * Speeches in each of the last twelve months for each of several people, counted in one query.
     *
     * @param iterable<Person> $people
     *   The people.
     *
     * @return array<int, array<string, int>>
     *   Each person's counts keyed by person id, then by month as "2026-07", the earliest first.
     */
    public static function lastTwelveMonthsFor(iterable $people): array {
        $start = Carbon::today()->startOfMonth()->subMonths(11);

        $personOf = [];
        foreach ($people as $person) {
            foreach ($person->terms as $term) {
                $personOf[$term->member_id] = $person->id;
            }
        }

        $counts = [];
        $rows = Hansard::query()
          ->whereIn('speaker_id', array_keys($personOf))
          ->where('htype', self::SPEECH)
          ->where('hdate', '>=', $start->toDateString())
          ->selectRaw("speaker_id, DATE_FORMAT(hdate, '%Y-%m') AS month, COUNT(*) AS speeches")
          ->groupBy('speaker_id')
          ->groupByRaw("DATE_FORMAT(hdate, '%Y-%m')")
          ->get();
        foreach ($rows as $row) {
            $id = $personOf[$row->speaker_id];
            $counts[$id][$row->month] = ($counts[$id][$row->month] ?? 0) + (int) $row->speeches;
        }

        $result = [];
        foreach (array_unique($personOf) as $id) {
            foreach (range(0, 11) as $offset) {
                $month = $start->copy()->addMonths($offset)->format('Y-m');
                $result[$id][$month] = $counts[$id][$month] ?? 0;
            }
        }

        return $result;
    }

    /**
     * Contributions in each of the last nine years, from the first with any. It ends this year for someone sitting, and
     * in the year they last spoke for anyone else.
     *
     * @return array<int, int>
     *   Counts keyed by year, the earliest first. Empty with fewer than two years, which make no line.
     */
    public static function for(Person $person): array {
        $speeches = Hansard::query()
          ->whereIn('speaker_id', $person->terms->pluck('member_id'))
          ->where('htype', self::SPEECH);
        $latest = (clone $speeches)->max('hdate');
        if (!$latest) {
            return [];
        }

        $last = $person->latest()->isSitting() ? Carbon::today()->year : Carbon::parse($latest)->year;
        $counts = $speeches
          ->where('hdate', '>=', ($last - self::MOST_YEARS + 1) . '-01-01')
          ->selectRaw('YEAR(hdate) AS year, COUNT(*) AS contributions')
          ->groupByRaw('YEAR(hdate)')
          ->pluck('contributions', 'year');
        if ($counts->isEmpty() || $counts->keys()->min() >= $last) {
            return [];
        }

        $years = [];
        foreach (range((int) $counts->keys()->min(), $last) as $year) {
            $years[$year] = (int) $counts->get($year, 0);
        }

        return $years;
    }

}
