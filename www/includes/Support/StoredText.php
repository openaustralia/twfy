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
     * Plain text written the way the loaders store it, for matching against a stored column.
     */
    public static function encode(string $text): string {
        return XmlText::entitiesNoApos($text);
    }

}
