<?php

namespace OpenAustralia\TWFY;

use Illuminate\Database\UniqueConstraintViolationException;
use OpenAustralia\TWFY\Models\EmailSuppression;

/**
 * Addresses we have stopped sending alert emails to because mail to them
 * failed permanently. Every read and write goes through normalise(), so
 * Alice@Example.org and alice@example.org are one address.
 */
class EmailSuppressions {

    /**
     * Lower-cased, trimmed, and reduced to the bare address if a display name
     * is present.
     */
    public static function normalise(string $address): string {
        if (preg_match('/<([^>]*)>/', $address, $matches)) {
            $address = $matches[1];
        }
        return mb_strtolower(trim($address));
    }

    /**
     *
     */
    public static function isSuppressed(string $address): bool {
        return self::current($address) !== null;
    }

    /**
     * The active suppression for an address, if any.
     */
    public static function current(string $address): ?EmailSuppression {
        return EmailSuppression::where('email', self::normalise($address))
          ->whereNull('lifted_at')
          ->first();
    }

    /**
     * Suppress an address.
     *
     * @param string $address
     *   The address to stop sending alert emails to.
     * @param string $reason
     *   Why, for example hard_bounce.
     * @param string|null $postalEvent
     *   The Postal event that told us, if any.
     * @param string|null $replyExcerpt
     *   The receiving server's reply, cut to fit.
     * @param string|null $suppressedAt
     *   When mail started failing, as Y-m-d H:i:s. Defaults to now.
     *
     * @return bool
     *   False, changing nothing, if it already has an active suppression.
     */
    public static function suppress(string $address, string $reason, ?string $postalEvent = null, ?string $replyExcerpt = null, ?string $suppressedAt = null): bool {
        $email = self::normalise($address);
        if ($email === '' || self::current($email) !== null) {
            return false;
        }

        try {
            EmailSuppression::create([
                'email' => $email,
                'reason' => $reason,
                'postal_event' => $postalEvent,
                'reply_excerpt' => $replyExcerpt === null ? null : mb_substr($replyExcerpt, 0, 255),
                'suppressed_at' => $suppressedAt ?? date('Y-m-d H:i:s'),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent request suppressed it first.
            return false;
        }
        return true;
    }

    /**
     * Whether a suppression for the address was lifted at or after a time, or
     * ever, if no time is given.
     *
     * @param string $address
     *   The address to check.
     * @param string|null $time
     *   Y-m-d H:i:s, for example when a failure happened.
     *
     * @return bool
     *   True if someone restored the address since then.
     */
    public static function liftedSince(string $address, ?string $time = null): bool {
        $query = EmailSuppression::where('email', self::normalise($address))->whereNotNull('lifted_at');
        if ($time !== null) {
            $query->where('lifted_at', '>=', $time);
        }
        return $query->exists();
    }

    /**
     * Lift the active suppression, keeping the row. Returns how many were
     * lifted.
     */
    public static function lift(string $address): int {
        return EmailSuppression::where('email', self::normalise($address))
          ->whereNull('lifted_at')
          ->update(['lifted_at' => date('Y-m-d H:i:s')]);
    }

}
