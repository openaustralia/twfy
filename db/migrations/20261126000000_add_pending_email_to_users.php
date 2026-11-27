<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * The new address someone asked to change to, until they follow the link emailed to it.
 */
final class AddPendingEmailToUsers extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE users ADD COLUMN pending_email varchar(255) DEFAULT NULL AFTER email');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE users DROP COLUMN pending_email');
    }
}
