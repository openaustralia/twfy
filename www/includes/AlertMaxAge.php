<?php

namespace OpenAustralia\TWFY;

/**
 * The age limit on items the alert mailer emails about.
 */
class AlertMaxAge {

    /**
     * How long after a debate the mailer still emails it. Older items are not
     * news, even when they are new to the database, such as a day that was
     * re-fetched weeks later.
     */
    public const MAX_AGE = '8 weeks';

    /**
     * True when the debate date is no more than MAX_AGE before today.
     */
    public static function isWithinMaxAge(string $hdate, ?string $today = null): bool {
        $cutoff = date('Y-m-d', strtotime('-' . self::MAX_AGE, strtotime($today ?? 'today')));
        return $hdate >= $cutoff;
    }

}
