<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Indexes hansard.alert_mailed_at, leading a composite with created.
 *
 * AlertMailedItems::markMailedThroughBatch() scans every row with alert_mailed_at IS NULL and created <= a cutoff
 * each time the mailer runs. Without this, that is a full table scan over 100,000+ rows.
 */
final class AddAlertMailedAtIndexToHansard extends AbstractMigration
{
    public function change(): void
    {
        $this->table('hansard')
            ->addIndex(['alert_mailed_at', 'created'], ['name' => 'alert_mailed_at_created'])
            ->update();
    }
}
