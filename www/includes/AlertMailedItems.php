<?php

namespace OpenAustralia\TWFY;

use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\Models\Indexbatch;

/**
 * Tracks which Hansard rows the alert mailer has already treated as sent, in
 * hansard.alert_mailed_at. A re-imported day keeps its stamps, so it does not
 * email people a second time.
 */
class AlertMailedItems {

    /**
     * Seconds before a batch's creation time that a row must have been created
     * to count as sent. The batch time marks the end of an indexing run, so the
     * margin keeps a row loaded during that run, and not yet indexed, unstamped.
     */
    public const BATCH_MARGIN = 3600;

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

    /**
     * The gid prefix hansard.gid stores for each house. The Senate's is "lords",
     * not "senate", because the parser inherited the Lords naming.
     */
    public const GID_PREFIXES = [
        1 => 'uk.org.publicwhip/debate/',
        101 => 'uk.org.publicwhip/lords/',
    ];

    /**
     * True when the mailer has not sent the item yet. $gid is the short form the
     * site uses in URLs, such as 2026-03-01.3.1. A house we do not store gids for
     * is always mailed, as it was before this check existed, and a missing row is not.
     */
    public static function shouldMail(int $major, string $gid): bool {
        if (!isset(self::GID_PREFIXES[$major])) {
            return true;
        }

        return Hansard::where('gid', self::GID_PREFIXES[$major] . $gid)
          ->where('major', $major)
          ->whereNull('alert_mailed_at')
          ->exists();
    }

    /**
     * Stamps every unsent row created before the batch was indexed, and returns
     * how many it stamped.
     */
    public static function markMailedThroughBatch(int $batchId, ?string $now = null): int {
        $batch = Indexbatch::find($batchId);
        if (!$batch || !$batch->created) {
            return 0;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime($batch->created) - self::BATCH_MARGIN);

        return Hansard::whereNull('alert_mailed_at')
          ->where('created', '<=', $cutoff)
          ->update(['alert_mailed_at' => $now ?? date('Y-m-d H:i:s')]);
    }

}
