<?php

namespace OpenAustralia\TWFY\Pages;

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\Glossary;

/**
 * The A to Z strip the glossary pages share.
 */
final class GlossaryLetters {

    /**
     * The letter that groups titles starting with anything other than A to Z.
     */
    public const OTHER = '#';

    /**
     * The letters A to Z, each with its address when some term starts with it, and a # when some term starts with another character.
     *
     * @param \Illuminate\Support\Collection<int, \OpenAustralia\TWFY\Models\Glossary> $terms
     *   The approved terms.
     * @param string|null $current
     *   The letter being shown, if any.
     *
     * @return list<array{letter: string, url: string|null, current: bool}>
     *   One entry for each letter.
     */
    public static function for(Collection $terms, ?string $current = null): array {
        $used = $terms->map(fn (Glossary $term): string => self::initial($term))->unique()->all();
        $letters = in_array(self::OTHER, $used, true) ? [...range('A', 'Z'), self::OTHER] : range('A', 'Z');
        return array_map(fn (string $letter): array => [
            'letter' => $letter,
            'url' => in_array($letter, $used, true) ? WEBPATH . 'glossary/?az=' . rawurlencode($letter) : null,
            'current' => $letter === $current,
        ], $letters);
    }

    /**
     * The first letter of a term's title in capitals, or # when it is not one of A to Z.
     */
    public static function initial(Glossary $term): string {
        $first = strtoupper(mb_substr((string) $term->title, 0, 1));
        return preg_match('/^[A-Z]$/', $first) === 1 ? $first : self::OTHER;
    }

}
