<?php

namespace OpenAustralia\TWFY\Parliament;

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\Consinfo;
use OpenAustralia\TWFY\Models\Memberinfo;
use OpenAustralia\TWFY\Models\Personinfo;

/**
 * The extra facts the loaders keep about a person, their latest term and their constituency, as named values.
 */
final class MemberFacts {

    /**
     * Holds the values. Use for().
     *
     * @param \Illuminate\Support\Collection<string, string> $values
     *   Values by key.
     */
    private function __construct(private readonly Collection $values) {
    }

    /**
     * What is known about a person. Where keys clash, the constituency's value wins, then the person's.
     */
    public static function for(Person $person): self {
        $term = $person->latest();

        return new self(
            Memberinfo::query()->where('member_id', $term->member_id)->toBase()->pluck('data_value', 'data_key')
              ->merge(Personinfo::query()->where('person_id', $person->id)->toBase()->pluck('data_value', 'data_key'))
              ->merge(Consinfo::query()->where('constituency', $term->constituency)->toBase()->pluck('data_value', 'data_key'))
        );
    }

    /**
     * A value as plain text, or null if there is none. The loaders may store HTML entities.
     */
    public function get(string $key): ?string {
        $value = $this->values->get($key);
        return $value === null ? null : html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

}
