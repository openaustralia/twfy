<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropTrackbacksTable extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('trackbacks')) {
            $this->table('trackbacks')->drop()->save();
        }
    }

    public function down(): void
    {
        // Re-creates the table structure as it was in the legacy schema.
        // Note: data dropped by up() is not recoverable.
        $table = $this->table('trackbacks', ['id' => 'trackback_id']);
        $table->addColumn('epobject_id', 'integer', ['null' => true])
              ->addColumn('blog_name', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('title', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('excerpt', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('url', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('posted', 'datetime', ['null' => true])
              ->addColumn('visible', 'boolean', ['default' => false])
              ->addColumn('source_ip', 'string', ['limit' => 20, 'null' => true])
              ->addIndex(['visible'])
              ->create();
    }
}
