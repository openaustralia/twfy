<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTimestampsToModels extends AbstractMigration
{
    public function up(): void
    {
        // Add timestamps to bills table
        $this->table('bills')
            ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->update();

        // Add timestamps to constituency table
        $this->table('constituency')
            ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->update();

        // Add updated_at to comments (posted already exists for creation)
        $this->table('comments')
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->update();

        // Add timestamps to uservotes table
        $this->table('uservotes')
            ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->update();
    }

    public function down(): void
    {
        // Remove timestamps from bills
        $this->table('bills')
            ->removeColumn('created_at')
            ->removeColumn('updated_at')
            ->update();

        // Remove timestamps from constituency
        $this->table('constituency')
            ->removeColumn('created_at')
            ->removeColumn('updated_at')
            ->update();

        // Remove updated_at from comments
        $this->table('comments')
            ->removeColumn('updated_at')
            ->update();

        // Remove timestamps from uservotes
        $this->table('uservotes')
            ->removeColumn('created_at')
            ->removeColumn('updated_at')
            ->update();
    }
}
