<?php

/**
 * @file
 * alertmailer_sanitize.php
 *
 * Methods to sanitize input for alertmailer.
 */

/**
 * Remove unknown prefix patterns that make the search layer emit HTML warnings.
 */
function sanitize_alert_criteria($criteria) {
    static $known_prefixes = ['speaker', 'major', 'groupby', 'bias', 'date', 'batch', 'section'];

    return preg_replace_callback(
        '/\b([A-Za-z][A-Za-z0-9_]*)\:( ?)(?!\/\/)/',
        function ($matches) use ($known_prefixes) {
            $prefix = strtolower($matches[1]);
            if (in_array($prefix, $known_prefixes, TRUE)) {
                return $matches[0];
            }
            return $matches[1] . ' ';
        },
        $criteria
    );
}
