<?php

namespace OpenAustralia\TWFY\UsageStats;

/**
 * Shortens an API key's stated reason to fit one table row.
 */
class ReasonText {

    /**
     * Keeps rows to one line on a full HD screen.
     */
    public const MAX_LENGTH = 65;

    /**
     * Collapses whitespace, then cuts at a word boundary with "..." if too long.
     *
     * @return string
     *   At most MAX_LENGTH characters, "..." included.
     */
    public static function shorten(string $reason, int $max = self::MAX_LENGTH): string {
        $text = trim(preg_replace('/\s+/u', ' ', $reason));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 3);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > 0) {
            $cut = mb_substr($cut, 0, $space);
        }
        return rtrim($cut, " ,.;:-") . '...';
    }

}
