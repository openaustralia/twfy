<?php

namespace OpenAustralia\TWFY\Pages;

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\Glossary;

/**
 * The A to Z strip the glossary pages share.
 */
final class GlossaryLetters {

    /**
     * The letters A to Z, each with its address when some term starts with it.
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
        return array_map(fn (string $letter): array => [
            'letter' => $letter,
            'url' => in_array($letter, $used, true) ? WEBPATH . 'glossary/?az=' . $letter : null,
            'current' => $letter === $current,
        ], range('A', 'Z'));
    }

    /**
     * The first letter of a term's title, in capitals.
     */
    public static function initial(Glossary $term): string {
        return strtoupper(substr((string) $term->title, 0, 1));
    }

}
