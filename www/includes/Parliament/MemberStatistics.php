<?php

namespace OpenAustralia\TWFY\Parliament;

use Illuminate\Support\Str;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Alert;

/**
 * The numbers on a profile. scripts/mpinfoin.pl works most of them out; Senate keys start with "L".
 */
final class MemberStatistics {

    private const QUINTILES = ['well above average', 'above average', 'average', 'below average', 'well below average'];

    /**
     * Reads one person's numbers.
     */
    public function __construct(private readonly Person $person, private readonly MemberFacts $info) {
    }

    /**
     * How many debates they spoke in over the last year, for each house with a count.
     *
     * @return list<array{count: int, figure: string, standing: string|null}>
     *   One entry a house.
     */
    public function debates(): array {
        $entries = [];
        foreach (House::cases() as $house) {
            $count = $this->info->get($house->debatesKey());
            if ($count !== null) {
                $entries[] = [
                    'count' => (int) $count,
                    'figure' => $count . ' ' . Str::plural('debate', (int) $count),
                    'standing' => $this->standing($house->debatesKey(), $house),
                ];
            }
        }
        return $entries;
    }

    /**
     * The ages that can follow their speeches, by the Flesch-Kincaid grade level.
     */
    public function readingAge(): ?string {
        $year = $this->info->get('reading_year');
        if ($this->info->get('reading_ease') === null || $year === null) {
            return null;
        }
        $year = (int) round((float) $year);
        return ($year + 4) . '–' . ($year + 5);
    }

    /**
     * How often they have used three-word alliterative phrases, for each house with a count.
     *
     * @return list<array{figure: string, standing: string|null}>
     *   One entry a house.
     */
    public function alliterations(): array {
        $entries = [];
        foreach (['three_word_alliterations' => House::Representatives, 'Lthree_word_alliterations' => House::Senate] as $key => $house) {
            $count = $this->info->get($key);
            if ($count !== null) {
                $entries[] = [
                    'figure' => $count . ' ' . Str::plural('time', (int) $count),
                    'standing' => $this->standing($key, $house),
                ];
            }
        }
        return $entries;
    }

    /**
     * How many confirmed alerts follow what they say.
     */
    public function followers(): int {
        return Alert::query()->active()->where('criteria', 'like', '%speaker:' . $this->person->id . '%')->count();
    }

    /**
     * How a number compares with the rest of the house, from its quintile or else its rank.
     */
    private function standing(string $key, House $house): ?string {
        $others = $house->memberTitle() . 's';

        $quintile = $this->info->get($key . '_quintile');
        if ($quintile !== null && isset(self::QUINTILES[(int) $quintile])) {
            return self::QUINTILES[(int) $quintile] . " amongst $others";
        }

        $rank = $this->info->get($key . '_rank');
        if ($rank === null) {
            return null;
        }
        return make_ranking((int) $rank) . ' out of ' . $this->info->get($key . '_rank_outof') . " $others";
    }

}
