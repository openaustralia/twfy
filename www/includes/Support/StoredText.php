<?php

namespace OpenAustralia\TWFY\Support;

use OpenAustralia\TWFY\XmlImport\XmlText;

/**
 * Text as the loaders store it, with accented characters and some punctuation written as HTML entities.
 */
final class StoredText {

    /**
     * The plain text, ready for Blade to escape.
     */
    public static function decode(?string $text): string {
        return html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * The ways the loaders may store some plain text, for matching against a stored column.
     *
     * The loaders leave a field's first apostrophe as it is and write any other as an entity. A word taken from the
     * middle of a field may be stored either way, so both are given.
     *
     * @return list<string>
     *   The distinct stored forms.
     */
    public static function encodings(string $text): array {
        $encoded = XmlText::entitiesNoApos($text);

        return array_values(array_unique([$encoded, str_replace("'", '&#39;', $encoded)]));
    }

}
