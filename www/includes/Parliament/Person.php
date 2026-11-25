<?php

namespace OpenAustralia\TWFY\Parliament;

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Member;

/**
 * Someone who has sat in parliament, made of their rows in the member table, one for each term.
 */
final class Person {

    /**
     * Holds a person's terms. Use find().
     *
     * @param int $id
     *   The person id.
     * @param \Illuminate\Support\Collection<int, Member> $terms
     *   Latest ending first, and the Senate first when two end together.
     */
    private function __construct(public readonly int $id, public readonly Collection $terms) {
    }

    /**
     * The person with this id, if they have sat in either house.
     */
    public static function find(int $id): ?self {
        $terms = Member::query()
          ->where('person_id', $id)
          ->whereIn('house', array_column(House::cases(), 'value'))
          ->orderByDesc('left_house')
          ->orderByDesc('house')
          ->get();

        return $terms->isEmpty() ? null : new self($id, $terms->toBase());
    }

    /**
     * The ids of the people of a name who have sat in a house, the latest to leave first.
     *
     * @param \OpenAustralia\TWFY\Enums\House $house
     *   The house.
     * @param string $name
     *   The first and last names, separated by a space.
     * @param string|null $constituency
     *   Only those who sat for this electorate or state.
     *
     * @return \Illuminate\Support\Collection<int, int>
     *   Person ids, each once.
     */
    public static function idsNamed(House $house, string $name, ?string $constituency = null): Collection {
        return Member::query()
          ->inHouse($house)
          ->named($name)
          ->when($constituency, fn ($query) => $query->where('constituency', $constituency))
          ->orderByDesc('left_house')
          ->pluck('person_id')
          ->unique()
          ->values();
    }

    /**
     * Their latest term, which heads their profile.
     */
    public function latest(): Member {
        return $this->terms->first();
    }

    /**
     * The houses they have sat in, the one they left last first.
     *
     * @return list<\OpenAustralia\TWFY\Enums\House>
     *   Each house once.
     */
    public function houses(): array {
        return $this->terms->pluck('house')->unique()->map(fn (int $house): House => House::from($house))->values()->all();
    }

    /**
     * Their latest term in a house.
     */
    public function latestTerm(House $house): ?Member {
        return $this->terms->firstWhere('house', $house->value);
    }

    /**
     * Their earliest term in a house.
     */
    public function firstTerm(House $house): ?Member {
        return $this->terms->where('house', $house->value)->sortBy('entered_house')->first();
    }

    /**
     * Whether they sit in a house now.
     */
    public function sitsIn(House $house): bool {
        return $this->latestTerm($house)?->isSitting() ?? false;
    }

    /**
     * The terms that ended with a change of party, latest first.
     *
     * @return \Illuminate\Support\Collection<int, Member>
     *   Those terms.
     */
    public function partyChanges(): Collection {
        return $this->terms->where('left_reason', 'changed_party')->values();
    }

}
