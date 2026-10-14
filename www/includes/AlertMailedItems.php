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
     * True when the row exists and the mailer has not sent it yet.
     */
    public static function shouldMail(string $gid): bool {
        return Hansard::where('gid', $gid)->whereNull('alert_mailed_at')->exists();
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
