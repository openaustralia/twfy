<?php

namespace OpenAustralia\TWFY\Enums;

/**
 * How a list of members is sorted. The values are the ?o= parameter.
 */
enum MemberOrder: string {

    case FirstName = 'f';
    case LastName = 'l';
    case Constituency = 'c';
    case Party = 'p';

    /**
     * The order a query parameter asks for, or last name.
     */
    public static function fromQuery(mixed $value): self {
        return (is_string($value) ? self::tryFrom($value) : null) ?? self::LastName;
    }

    /**
     * The member columns to sort by.
     *
     * @return list<string>
     *   Column names, most significant first.
     */
    public function columns(): array {
        return match ($this) {
            self::FirstName => ['first_name', 'last_name'],
            self::LastName => ['last_name', 'first_name'],
            self::Constituency => ['constituency', 'last_name', 'first_name'],
            self::Party => ['party', 'last_name', 'first_name', 'constituency'],
        };
    }

}
