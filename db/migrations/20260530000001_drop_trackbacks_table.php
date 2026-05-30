<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

final class DropTrackbacksTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('DROP TABLE IF EXISTS `trackbacks`');
    }

    public function down(): void
    {
        throw new IrreversibleMigrationException(
            'The trackbacks table is deprecated and cannot be restored: '
            . 'dropping it loses all rows permanently.'
        );
    }
}
