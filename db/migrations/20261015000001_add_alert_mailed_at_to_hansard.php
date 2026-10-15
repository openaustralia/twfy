<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds hansard.alert_mailed_at, the time the email alert mailer last treated a row as sent.
 * The mailer skips rows that already have it, so re-importing a day does not email people twice.
 * NULL means the mailer has not sent the row yet.
 *
 * Every existing row counts as already sent, so the backfill copies its created time.
 * The backfill needs raw SQL because it copies one column into another, and it keeps
 * updated_at unchanged so the bulk update does not look like an edit to every row.
 */
final class AddAlertMailedAtToHansard extends AbstractMigration
{
    public function up(): void
    {
        $this->table('hansard')
            ->addColumn('alert_mailed_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When the alert mailer treated this row as sent; NULL = not sent yet',
            ])
            ->update();

        $this->execute('UPDATE hansard SET alert_mailed_at = COALESCE(created, NOW()), updated_at = updated_at');
    }

    public function down(): void
    {
        $this->table('hansard')->removeColumn('alert_mailed_at')->update();
    }
}
