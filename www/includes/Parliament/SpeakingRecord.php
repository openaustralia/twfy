<?php

namespace OpenAustralia\TWFY\Parliament;

use Illuminate\Support\Carbon;
use OpenAustralia\TWFY\Models\Hansard;

/**
 * How often a person has spoken in Hansard, year by year.
 */
final class SpeakingRecord {

    /**
     * The hansard htype of a speech.
     */
    private const SPEECH = 12;

    private const MOST_YEARS = 9;

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
